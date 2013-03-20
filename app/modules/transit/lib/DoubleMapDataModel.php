<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * DoubleMapDataModel
  * @package Transit
  */

class DoubleMapDataModel extends TransitDataModel
{
    protected $DEFAULT_PARSER_CLASS = 'JSONDataParser';
    protected $DEFAULT_RETRIEVER_CLASS = 'DoubleMapDataRetriever';
    
    protected $agencyID = "";
    protected $routeColors = array();
    protected $doubleMapMarkersURL = '';
    protected $doubleMapServiceURL = 'http://www.doublemap.com/';
    protected $predictionDataLoaded = array();
    
    const DEFAULT_EXTERNAL_SERVICE_URL = "http://www.doublemap.com/";
    // http://iub.doublemap.com/map/img/colorize?img=bus_icon&color=FF350D&annotate=A
    
    static private function argVal($array, $key, $default='') {
        return is_array($array) && isset($array[$key]) ? $array[$key] : $default;
    }
    
    protected function init($args) {
        if (isset($args['DOUBLEMAP_MARKERS_URL'])) {
            $this->doubleMapMarkersURL = $args['DOUBLEMAP_MARKERS_URL'];
        } else if (isset($args['URL_HOST'])) {
            $this->doubleMapMarkersURL = "http://{$args['URL_HOST']}.doublemap.com/map/img/";
        }
        
        if (isset($args['EXTERNAL_SERVICE_URL'])) {
            $this->doubleMapServiceURL = $args['EXTERNAL_SERVICE_URL'];
        }
        
        parent::init($args);
    }

    protected function isLive() {
        return true;
    }
    
    /* 
    // If we get permission from DoubleMap to use their icon API, we can uncomment this
    protected function getMapIconUrlForRouteVehicle($routeID, $vehicle=null) {
        if ($this->doubleMapMarkersURL) {
            $args = array(
                'img'      => 'bus_icon',
                'color'    => $this->getRouteColor($routeID),
                'annotate' => $this->getRoute($routeID)->getShortName(),
            );
            return rtrim($this->doubleMapMarkersURL, '/')."/colorize?".http_build_query($args);
        } else {
            return parent::getMapIconUrlForRouteVehicle($routeID, $vehicle);
        }
    }
    */

    protected function getRouteColor($routeId) {
        if (isset($this->routeColors[$routeId])) {
            return $this->routeColors[$routeId];
        } else {
            return parent::getRouteColor($routeId);
        }
    }
    
    protected function getServiceName() {
        return 'DoubleMap';
    }
    
    protected function getServiceId() {
        return 'doublemap';
    }
    
    protected function getServiceLink() {
      return isset($this->initArgs['EXTERNAL_SERVICE_URL']) ? 
          $this->initArgs['EXTERNAL_SERVICE_URL'] : self::DEFAULT_EXTERNAL_SERVICE_URL;
    }
    
    public function getRouteVehicles($routeId) {
        $vehicles = array();
        
        if ($this->doubleMapRouteIsRunning($routeId)) {
            $vehiclesInfo = $this->getDoubleMapData('buses');
            foreach ($vehiclesInfo as $vehicleInfo) {
                if ($routeId != self::argVal($vehicleInfo, 'route')) { continue; }
                
                // Is this vehicle being tracked and have a valid location?
                $lat = self::argVal($vehicleInfo, 'lat', false);
                $lon = self::argVal($vehicleInfo, 'lon', false);
                if (!$lat || !$lon) { continue; }
                
                $vehicles[$vehicleInfo['id']] = array(
                    'secsSinceReport' => 0,
                    'lat'             => $lat,
                    'lon'             => $lon,
                    'heading'         => self::argVal($vehicleInfo, 'heading', 0),
                    'speed'           => self::argVal($vehicleInfo, 'speed', 0),
                    'nextStop'        => null,
                    'agency'          => $this->agencyID,
                    'routeID'         => $routeId,
                    'directionID'     => self::LOOP_DIRECTION, // DoubleMap doesn't have the direction concept
                );
                
                $vehicles[$vehicleInfo['id']]['iconURL'] = 
                    $this->getMapIconUrlForRouteVehicle($routeId, $vehicles[$vehicleInfo['id']]);
            }
        }
        return $vehicles;
    }
    
    protected function getStopPredictionsForRoute($routeId) {
        $result = array();
        
        $stopsInfo = $this->getRoute($routeId)->getStops();
        foreach ($stopsInfo as $stopInfo) {
            $stopId = $stopInfo['stopID'];
            
            $now = time();
            $predictions = array();
            $stopETAsInfo = $this->getDoubleMapData('eta', array('stop' => $stopId));
            if (isset($stopETAsInfo['etas'], 
                      $stopETAsInfo['etas'][$stopId], 
                      $stopETAsInfo['etas'][$stopId]['etas'])) {
                foreach ($stopETAsInfo['etas'][$stopId]['etas'] as $etaInfo) {
                    if (self::argVal($etaInfo, 'route', '') == $routeId && isset($etaInfo['avg'])) {
                        $predictions[] = $now + ($etaInfo['avg'] * 60);
                    }
                }
            }
            sort($predictions);
            $result[$stopId] = array_unique($predictions);
        }
        
        return $result;
    }
    
    protected function updatePredictionData($routeId) {
        if (isset($this->predictionDataLoaded[$routeId])) {
            return; // already loaded
        }
        
        $route = $this->getRoute($routeId);
        if (!$route) { return; }
        
        $stopsPredictions = $this->getStopPredictionsForRoute($routeId);
        foreach ($stopsPredictions as $stopId => $stopPredictions) {
            $route->setStopPredictions(self::LOOP_DIRECTION, $stopId, $stopPredictions);
        }
        $this->predictionDataLoaded[$routeId] = true;
    }
    
    protected function loadData() {
        // DoubleMap doesn't have a concept of agencies so use the host:
        $this->agencyID = $this->retriever->getURLHost();
        
        $stopsInfo = $this->getDoubleMapData('stops');
        foreach ($stopsInfo as $stopInfo) {
            $stopId = self::argVal($stopInfo, 'id');
            
            // TODO: do something with buddy field
            $this->addStop(new TransitStop(
                $stopId, 
                self::argVal($stopInfo, 'name'), 
                self::argVal($stopInfo, 'description'), 
                self::argVal($stopInfo, 'lat', 0), 
                self::argVal($stopInfo, 'lon', 0)
            ));
        }

        $routesInfo = $this->getDoubleMapData('routes');
        foreach ($routesInfo as $routeInfo) {
            $routeId = self::argVal($routeInfo, 'id');
            
            // TODO: do something with short_name field
            $this->addRoute(new DoubleMapTransitRoute(
                $routeId, 
                $this->agencyID, 
                self::argVal($routeInfo, 'short_name'), 
                self::argVal($routeInfo, 'name'), 
                self::argVal($routeInfo, 'description')
            ));  
            
            $this->routeColors[$routeId] = self::argVal($routeInfo, 'color', parent::getRouteColor($routeId));
            
            // Route always in service
            $routeService = new TransitService("{$routeId}_service", true);
            
            // Single segment containing all stops:
            $routeSegment = new DoubleMapTransitSegment(
                self::LOOP_DIRECTION,
                self::LOOP_DIRECTION_NAME,
                $routeService,
                self::LOOP_DIRECTION,
                $routeId,
                $this
            );
            $this->getRoute($routeId)->addSegment($routeSegment);

            $doubleMapStops = self::argVal($routeInfo, 'stops', array());
            foreach ($doubleMapStops as $stopIndex => $stopId) {
                $routeSegment->addStop($stopId, $stopIndex);
            }
            
            // DoubleMap returns the path as a flattened array:
            // array(lat0, lon0, lat1, lon1, lat2, lon2, ...)
            $doubleMapPath = self::argVal($routeInfo, 'path', array());
            $path = array();
            for ($i = 0; $i < count($doubleMapPath)-2; $i += 2) {
                $path[] = array($doubleMapPath[$i], $doubleMapPath[$i+1]);
            }
            
            // Repeat first stop at end to close loop
            // Note: Once DoubleMap gets their first customer who has a 
            // non-loop route they will probably rev the API such that this 
            // code will need to be removed!
            $path[] = reset($path);
            
            $this->getRoute($routeId)->addPath(new TransitPath("path_{$routeId}", $path));
        }
    }
    
    protected function getDoubleMapData($command, $params=array()) {
        $this->retriever->setParameters($params);
        $this->retriever->setCommand($command);
        
        $results = $this->retriever->getData();
        
        return $results;
    }
    
    /*
    // TODO: if "lastStop" becomes reliable, start using it
    protected function setUpcomingRouteStops($routeId, &$directions, $segmentTimeRange) {
        $upcomingVehicleStops = array();
        if ($this->doubleMapRouteIsRunning($routeId)) {
            $stopsInfo = $this->getRoute($routeId)->getStops();
            $firstStop = reset($stopsInfo);
            
            $stopToNextStopInfo = array();
            $nextStop = $firstStop;
            foreach (array_reverse($stopsInfo) as $stopInfo) {
                $stopToNextStopInfo[$stopInfo['stopID']] = $nextStop;
                $nextStop = $stopInfo;
            }
            $vehiclesInfo = $this->getDoubleMapData('buses');
            foreach ($vehiclesInfo as $vehicleInfo) {
                if ($routeId != self::argVal($vehicleInfo, 'route')) { continue; }
                
                if (isset($vehicleInfo['lastStop'], $stopToNextStop[$vehicleInfo['lastStop']])) {
                    // Seen at previous stop, set 
                    $nextStopId = $stopToNextStop[$vehicleInfo['lastStop']]['stopId'];
                    $nextStopIndex = $stopToNextStop[$vehicleInfo['lastStop']]['i'];
                    $directions[$directionID]['stops'][$nextStopIndex]['upcoming'] = true;
                }
            }
        }
    }
    */
    
    public function doubleMapRouteIsRunning($routeId) {
        // Are there any buses?
        $vehiclesInfo = $this->getDoubleMapData('buses');
        foreach ($vehiclesInfo as $vehicleInfo) {
            if ($routeId != self::argVal($vehicleInfo, 'route')) { continue; }
            
            if (self::argVal($vehicleInfo, 'lat') && self::argVal($vehicleInfo, 'lon')) {
                return true;
            }
        }
        
        // no vehicles, check etas
        //$stopsPredictions = $this->getStopPredictionsForRoute($routeId);
        //if (count($stopsPredictions)) {
        //    return true;
        //}
        
        return false;
    }
}

class DoubleMapTransitRoute extends TransitRoute
{
    protected $shortName = '';
    
    function __construct($id, $agencyID, $shortName, $name, $description) {
        parent::__construct($id, $agencyID, $name, $description);
        $this->shortName = $shortName;
    }

    public function getShortName() {
        return $this->shortName;
    }
}

// Special version of the TransitSegment class
class DoubleMapTransitSegment extends TransitSegment
{
    protected $routeID = null;
    protected $model = null;
    
    function __construct($id, $name, $service, $direction, $routeID, $model) {
        parent::__construct($id, $name, $service, $direction);
        $this->routeID = $routeID;
        $this->model = $model;
    }
  
    public function isRunning($time) {
        return $this->model->doubleMapRouteIsRunning($this->routeID);
    }
}
