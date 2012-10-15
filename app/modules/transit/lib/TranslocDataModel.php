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
  * TranslocDataModel
  * @package Transit
  */

class TranslocDataModel extends TransitDataModel
{
    protected $DEFAULT_PARSER_CLASS = 'JSONDataParser';
    protected $DEFAULT_RETRIEVER_CLASS = 'TranslocDataRetriever';
    
    protected $routeColors = array();
    protected $translocMarkersURL = 'http://feeds.transloc.com/markers/';
    protected $translocServiceURL = 'http://www.transloc.com/';
    
    static private function argVal($array, $key, $default='') {
        return is_array($array) && isset($array[$key]) ? $array[$key] : $default;
    }
    
    protected function init($args) {
        if (isset($args['TRANSLOC_MARKERS_URL'])) {
            $this->translocMarkersURL = $args['TRANSLOC_MARKERS_URL'];
        }
        if (isset($args['EXTERNAL_SERVICE_URL'])) {
            $this->translocServiceURL = $args['EXTERNAL_SERVICE_URL'];
        }
        
        parent::init($args);
    }

    protected function isLive() {
        return true;
    }
    
    protected function getMapIconUrlForRouteStop($routeID) {
        if ($this->translocMarkersURL) {
            return rtrim($this->translocMarkersURL, '/')."/stop.png?".http_build_query(array(
                's' => 5,  // radius of 5 px
                'c' => $this->getRouteColor($routeID),
            ));
        } else {
            return parent::getMapIconUrlForRouteVehicle($routeID, $vehicle);
        }
    }
    
    protected function getMapIconUrlForRouteVehicle($routeID, $vehicle=null) {
        if ($this->translocMarkersURL) {
            $args = array(
                'c' => $this->getRouteColor($routeID),
            );
            if ($vehicle && ($heading = self::argVal($vehicle, 'heading'))) {
                $args['h'] = $heading;
            }
            return rtrim($this->translocMarkersURL, '/')."/vehicle.png?".http_build_query($args);
        } else {
            return parent::getMapIconUrlForRouteVehicle($routeID, $vehicle);
        }
    }
    
    protected function getMapMarkersForVehicles($vehicles) {
        $query = '';
        
        foreach ($vehicles as $vehicle) {
            if ($vehicle['lat'] && $vehicle['lon']) {
                $query .= '&'.http_build_query(array(
                    'markers' => "icon:{$vehicle['iconURL']}|{$vehicle['lat']},{$vehicle['lon']}",
                ));
            }
        }
        
        return $query;
    }
    
    protected function getRouteColor($routeID) {
        if (isset($this->routeColors[$routeID])) {
            return $this->routeColors[$routeID];
        } else {
            return parent::getRouteColor($routeID);
        }
    }
    
    protected function getServiceName() {
        return 'TransLōc';
    }
    
    protected function getServiceId() {
        return 'transloc';
    }
    
    protected function getServiceLink() {
      return $this->translocServiceURL;
    }
    
    public function getRouteVehicles($routeID) {
        $vehicles = array();
        
        if ($this->translocRouteIsRunning($routeID)) {
            $translocAgencyVehiclesInfo = $this->getTranslocData('vehicles');
            foreach ($translocAgencyVehiclesInfo as $agencyID => $vehiclesInfo) {
                foreach ($vehiclesInfo as $vehicleInfo) {
                    if ($routeID != self::argVal($vehicleInfo, 'route_id')) { continue; }
                    
                    // Is this vehicle being tracked and have a valid location?
                    $coords = self::argVal($vehicleInfo, 'location', false);
                    $trackingStatus = self::argVal($vehicleInfo, 'tracking_status', 'down');
                    if (!$coords || $trackingStatus != 'up') { continue; }
                    
                    $nextStop = null;
                    if (isset($vehicleInfo['arrival_estimates']) && $vehicleInfo['arrival_estimates']) {
                        $firstEstimate = reset($vehicleInfo['arrival_estimates']);
                        $nextStop = self::argVal($firstEstimate, 'stop_id', null);
                    }
                    
                    $lastReport = new DateTime(self::argVal($vehicleInfo, 'last_updated_on'));
                    $secsSinceReport = time() - intval($lastReport->format('U'));
                    
                    $vehicles[$vehicleInfo['vehicle_id']] = array(
                        'secsSinceReport' => $secsSinceReport,
                        'lat'             => self::argVal($coords, 'lat'),
                        'lon'             => self::argVal($coords, 'lng'),
                        'heading'         => self::argVal($vehicleInfo, 'heading', 0),
                        'speed'           => self::argVal($vehicleInfo, 'speed', 0),
                        'nextStop'        => $nextStop,
                        'agency'          => $agencyID,
                        'routeID'         => $routeID,
                        'directionID'     => self::LOOP_DIRECTION, // Transloc doesn't have the direction concept
                    );
                    
                    $vehicles[$vehicleInfo['vehicle_id']]['iconURL'] = 
                        $this->getMapIconUrlForRouteVehicle($routeID, $vehicles[$vehicleInfo['vehicle_id']]);
                }
            }
        }
        return $vehicles;
    }
    
    protected function filterPredictions($prediction) {
        return (intval($prediction) - time()) > 9;
    }
    
    protected function getAgencyName($agencyID) {
        $agencyIDToName = array_flip($this->agencyIDs);
        return isset($agencyIDToName[$agencyID]) ? $agencyIDToName[$agencyID] : $agencyID;
    }
    
    protected function loadData() {
        $translocAgenciesInfo = $this->getTranslocData('agencies');
        
        // Transloc's "ids" have a pretty name and an id.  We need the real id for queries.
        $newAgencyIDs = array();
        foreach ($translocAgenciesInfo as $agencyInfo) {
            $agencyName = self::argVal($agencyInfo, 'name');
            $agencyID = self::argVal($agencyInfo, 'agency_id');
            if ($agencyName && $agencyID && in_array($agencyName, $this->agencyIDs)) {
                $newAgencyIDs[$agencyName] = $agencyID;
            }
        }
        $extraAgencies = array_diff($this->agencyIDs, array_keys($newAgencyIDs));
        foreach ($extraAgencies as $extraAgencyID) {
            Kurogo::log(LOG_WARNING, "Transloc server does not know about agency '$extraAgencyID'", 'transit');
            $newAgencyIDs[$extraAgencyID] = $extraAgencyID;
        }
        $this->agencyIDs = $newAgencyIDs;
        
        // Now that we have $this->agencyIDs we can hit the other APIs:
        
        $translocSegmentsInfo = $this->getTranslocData('segments');

        $segments = array();
        foreach ($translocSegmentsInfo as $segmentID => $segmentPolyline) {
            $segments["segment-$segmentID"] = Polyline::decodeToArray($segmentPolyline);
        }

        $translocStopsInfo = $this->getTranslocData('stops');
        
        foreach ($translocStopsInfo as $stopInfo) {
            $stopID = self::argVal($stopInfo, 'stop_id');
            $coords = self::argVal($stopInfo, 'location', array());
            
            $this->addStop(new TransitStop(
                $stopID, 
                self::argVal($stopInfo, 'name'), 
                self::argVal($stopInfo, 'description'), 
                self::argVal($coords, 'lat', 0), 
                self::argVal($coords, 'lng', 0)
            ));
        }
        
        $translocAllRoutesInfo = $this->getTranslocData('routes');
        
        $routeSegments = array();
        foreach ($translocAllRoutesInfo as $agencyID => $routesInfo) {
            foreach ($routesInfo as $routeInfo) {
                $routeID = self::argVal($routeInfo, 'route_id');
                
                if (!$this->viewRoute($routeID)) { continue; }
                
                $this->addRoute(new TransitRoute(
                    $routeID, 
                    $this->getAgencyName($agencyID), 
                    self::argVal($routeInfo, 'long_name'), 
                    self::argVal($routeInfo, 'description')
                ));
                
                $this->routeColors[$routeID] = self::argVal($routeInfo, 'color', parent::getRouteColor($routeID));
                
                $routeService = new TransitService("{$routeID}_service", true);
                
                $routeSegments[$routeID] = new TranslocTransitSegment(
                    self::LOOP_DIRECTION,
                    self::LOOP_DIRECTION_NAME,
                    $routeService,
                    self::LOOP_DIRECTION,
                    $routeID,
                    $this
                );
                foreach ($routeInfo['stops'] as $stopIndex => $stopID) {
                    $routeSegments[$routeID]->addStop($stopID, $stopIndex);
                }
                
                $paths = array();
                foreach ($routeInfo['segments'] as $segmentInfo) {
                    $segmentID = self::LOOP_DIRECTION;
                    
                    if (is_string($segmentInfo)) {
                        $segmentID = "segment-{$segmentInfo}";
                        $segmentPath = $segments[$segmentID];
                        
                    } else if (is_array($segmentInfo) && count($segmentInfo) == 2) {
                        $segmentID = "segment-".reset($segmentInfo);
                        $segmentPath = $segments[$segmentID];
                        if (end($segmentInfo) != 'forward') {
                            $segmentPath = array_reverse($segmentPath);
                        }
                    }
                    $paths[$segmentID] = $segmentPath;
                }
                
                // Reduce path count by matching up pairs.  Large path counts will
                // prevent Google static maps from displaying properly (URI too long).
                $foundPair = true;
                while (count($paths) > 1 && $foundPair) {
                    $foundPair = false;
                    $pathSegments = array_keys($paths);
                    for ($i = 0; $i < count($pathSegments); $i++) {
                        for ($j = $i + 1; $j < count($pathSegments); $j++) {
                            $p1 = $paths[$pathSegments[$i]];
                            $p2 = $paths[$pathSegments[$j]];
                            
                            $merged = array();
                            if (end($p1) == reset($p2)) {
                                $merged = array_merge($p1, array_slice($p2, 1));
                                
                            } else if (end($p2) == reset($p1)) {
                                $merged = array_merge($p2, array_slice($p1, 1));
                            }
                            
                            if ($merged) {
                                unset($paths[$pathSegments[$i]]);
                                unset($paths[$pathSegments[$j]]);
                                $paths[$pathSegments[$i].'+'.$pathSegments[$j]] = $merged;
                                $foundPair = true;
                                break;
                            }
                        }
                        if ($foundPair) { break; }
                    }
                }
                //error_log("Route $routeID path count reduced to ".count($paths));
                
                foreach ($paths as $segmentID => $segmentPath) {
                    $this->getRoute($routeID)->addPath(new TransitPath($segmentID, $segmentPath));
                }
            }
        }
        
        if ($this->translocRouteIsRunning($routeID)) {
            $translocStopsArrivalsInfo = $this->getTranslocData('arrival-estimates');
    
            $routeStopPredictions = array();
            foreach ($translocStopsArrivalsInfo as $stopArrivalsInfo) {
                $agencyID = self::argVal($stopArrivalsInfo, 'agency_id');
                $stopID = self::argVal($stopArrivalsInfo, 'stop_id');
                $arrivals = self::argVal($stopArrivalsInfo, 'arrivals', array());
                
                foreach ($arrivals as $arrival) {
                    $routeID = self::argVal($arrival, 'route_id');
                    
                    if (isset($arrival['arrival_at'])) {
                        if (!isset($routeStopPredictions[$routeID])) {
                            $routeStopPredictions[$routeID] = array();
                        }
                        if (!isset($routeStopPredictions[$routeID][$stopID])) {
                            $routeStopPredictions[$routeID][$stopID] = array();
                        }
                        
                        $datetime = new DateTime($arrival['arrival_at']);
                        $routeStopPredictions[$routeID][$stopID][] = $datetime->format('U');
                    }
                }
            }
            
            foreach ($routeStopPredictions as $routeID => $stopPredictions) {
                if (!isset($routeSegments[$routeID])) { continue; }
                
                foreach ($stopPredictions as $stopID => $predictions) {
                    sort($predictions);
                    $routeSegments[$routeID]->setStopPredictions($stopID, $predictions);
                }
            }
        }
        
        foreach ($routeSegments as $routeID => $segment) {
            $this->getRoute($routeID)->addSegment($segment);
        }
    }
    
    protected function getTranslocData($command, $params=array()) {
        if ($command != 'agencies') {
            $params['agencies'] = implode(',', $this->agencyIDs);
        }
        $this->retriever->setParameters($params);
        $this->retriever->setCommand($command);
        
        $results = $this->retriever->getData();
        
        return $results && isset($results['data']) ? $results['data'] : array();
    }
    
    protected function setUpcomingRouteStops($routeID, &$directions) {
        $upcomingVehicleStops = array();
        if ($this->translocRouteIsRunning($routeID)) {
            $agencyVehiclesInfo = $this->getTranslocData('vehicles');
            foreach ($agencyVehiclesInfo as $agencyID => $vehiclesInfo) {
                foreach ($vehiclesInfo as $vehicleInfo) {
                    if ($routeID != self::argVal($vehicleInfo, 'route_id')) { continue; }
                    
                    $vehicleStopTimes = array();
                    $arrivalEstimates = self::argVal($vehicleInfo, 'arrival_estimates', array());
                    foreach ($arrivalEstimates as $arrivalEstimate) {
                        if ($routeID != self::argVal($arrivalEstimate, 'route_id')) { continue; }
                        
                        $datetime = new DateTime($arrivalEstimate['arrival_at']);
                        $vehicleStopTimes[intval($datetime->format('U'))] = $arrivalEstimate['stop_id'];
                    }
                    if (count($vehicleStopTimes)) {
                        // remember the first stop vehicle is making
                        ksort($vehicleStopTimes);
                        $upcomingVehicleStops[array_shift($vehicleStopTimes)] = true; 
                    }
                }
            }
        }
        
        // Add upcoming stop information
        foreach ($directions as $directionID => $directionInfo) {
            foreach ($directionInfo['stops'] as $i => $stopInfo) {
                $directions[$directionID]['stops'][$i]['upcoming'] = isset($upcomingVehicleStops[$stopInfo['id']]);
            }
        }
    }
    
    public function translocRouteIsRunning($routeID) {
        // Is the route active?
        $translocAllRoutesInfo = $this->getTranslocData('routes');
        foreach ($translocAllRoutesInfo as $agencyID => $routesInfo) {
            foreach ($routesInfo as $routeInfo) {
                if (self::argVal($routeInfo, 'route_id') == $routeID && !self::argVal($routeInfo, 'is_active', false)) {
                    return false;
                }
            }
        }
        
        // Are there any vehicles?
        $translocAgencyVehiclesInfo = $this->getTranslocData('vehicles');
        foreach ($translocAgencyVehiclesInfo as $agencyID => $vehiclesInfo) {
            foreach ($vehiclesInfo as $vehicleInfo) {
                if ($routeID != self::argVal($vehicleInfo, 'route_id')) { continue; }
                
                if (self::argVal($vehicleInfo, 'location')) {
                    return true;
                }
            }
        }
        
        // no vehicles, check arrival estimates
        $translocStopsArrivalsInfo = $this->getTranslocData('arrival-estimates');
        foreach ($translocStopsArrivalsInfo as $stopArrivalsInfo) {
            foreach (self::argVal($stopArrivalsInfo, 'arrivals', array()) as $arrival) {
                if ($routeID == self::argVal($arrival, 'route_id')) {
                    return true;
                }
            }
        }
        
        return false;
    }
}

// Special version of the TransitSegment class
class TranslocTransitSegment extends TransitSegment
{
    protected $routeID = null;
    protected $model = null;
    
    function __construct($id, $name, $service, $direction, $routeID, $model) {
        parent::__construct($id, $name, $service, $direction);
        $this->routeID = $routeID;
        $this->model = $model;
    }
  
    public function isRunning($timestampRange) {
        return $this->model->translocRouteIsRunning($this->routeID);
    }
}
