<?php

class TranslocTransitDataParser extends TransitDataParser {
    private static $daemonCacheMode = false;
    private static $caches = array();
    private $routeColors = array();
    private $agencyIDs = array();
    const TRANSLOC_API_VERSION = '1.2';
    
    static private function argVal($array, $key, $default='') {
        return is_array($array) && isset($array[$key]) ? $array[$key] : $default;
    }
    
    function __construct($args, $overrides, $whitelist, $daemonMode=false) {
        parent::__construct($args, $overrides, $whitelist, $daemonMode);
        self::$daemonCacheMode = $daemonMode;
    }
  
    protected function isLive() {
        return true;
    }
    
    protected function getMapIconUrlForRouteVehicle($routeID, $vehicle=null) {
        $markerURL = Kurogo::getOptionalSiteVar('TRANSLOC_MARKERS_URL', '');
        if ($markerURL) {
            return $markerURL.http_build_query(array(
                'm' => 'bus',
                'c' => $this->getRouteColor($routeID),
                'h' => $this->getDirectionForHeading(self::argVal($vehicle, 'heading', 4)),
            ));
        } else {
            return parent::getMapIconUrlForRouteVehicle($routeID, $vehicle=null);
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
      return isset($this->args['serviceURL']) ? $this->args['serviceURL'] : 'http://www.transloc.com/';
    }
  
    public function getRouteVehicles($routeID) {
        $vehicles = array();
        $translocAgencyVehiclesInfo = $this->getData('vehicles');
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
                    'nextStop'        => $nextStop,
                    'agency'          => $agencyID,
                    'routeID'         => $routeID,
                );
                if (isset($vehicleInfo['speed'])) {
                    $vehicles[$vehicleInfo['vehicle_id']]['speed'] = $vehicleInfo['speed'];
                }
                $vehicles[$vehicleInfo['vehicle_id']]['iconURL'] = 
                    $this->getMapIconUrlForRouteVehicle($routeID, $vehicles[$vehicleInfo['vehicle_id']]);
            }
        }
        return $vehicles;
    }
    
    private function filterPredictions($prediction) {
        return (intval($prediction) - time()) > 9;
    }
    
    private function getAgencyName($agencyID) {
        $agencyIDToName = array_flip($this->agencyIDs);
        return isset($agencyIDToName[$agencyID]) ? $agencyIDToName[$agencyID] : $agencyID;
    }
  
    protected function loadData() {
        $agencyNames = array(); // all agencies
        if (isset($this->args['agencies'])) {
            $agencyNames = array_filter(explode(',', $this->args['agencies']));
        }
        
        $translocAgenciesInfo = $this->getData('agencies');
        
        $this->agencyIDs = array();
        foreach ($translocAgenciesInfo as $agencyInfo) {
            $agencyName = self::argVal($agencyInfo, 'name');
            if (!$agencyNames || in_array($agencyName, $agencyNames)) {
                $this->agencyIDs[$agencyName] = self::argVal($agencyInfo, 'agency_id');
            }
        }
        
        // Now that we have $this->agencyIDs we can hit the other APIs:
        
        $translocSegmentsInfo = $this->getData('segments');

        $segments = array();
        foreach ($translocSegmentsInfo as $segmentID => $segmentPolyline) {
            $segments["segment-$segmentID"] = Polyline::decodeToArray($segmentPolyline);
        }

        $translocStopsInfo = $this->getData('stops');
        
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
        
        $translocAllRoutesInfo = $this->getData('routes');
        
        $routeSegments = array();
        foreach ($translocAllRoutesInfo as $agencyID => $routesInfo) {
            foreach ($routesInfo as $routeInfo) {
                $routeID = self::argVal($routeInfo, 'route_id');
                
                if ($this->whitelist && !in_array($routeID, $this->whitelist)) {
                    continue;  // skip entries not on whitelist
                }
                
                $this->addRoute(new TransitRoute(
                    $routeID, 
                    $this->getAgencyName($agencyID), 
                    self::argVal($routeInfo, 'long_name'), 
                    self::argVal($routeInfo, 'description')
                ));
                
                $this->routeColors[$routeID] = self::argVal($routeInfo, 'color', parent::getRouteColor($routeID));
                
                $routeService = new TranslocTransitService("{$routeID}_service", $routeID, $this);
                
                $routeSegments[$routeID] = new TranslocTransitSegment(
                    'loop',
                    '',
                    $routeService,
                    'loop',
                    $routeID,
                    $this
                );
                foreach ($routeInfo['stops'] as $stopIndex => $stopID) {
                    $routeSegments[$routeID]->addStop($stopID, $stopIndex);
                }
                
                $paths = array();
                foreach ($routeInfo['segments'] as $segmentInfo) {
                    $segmentID = 'loop';
                    
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
        
        $translocStopsArrivalsInfo = $this->getData('arrival-estimates');

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
        
        foreach ($routeSegments as $routeID => $segment) {
            $this->getRoute($routeID)->addSegment($segment);
        }
    }
    
    private static function getTimeoutForCommand($action) {
        switch ($action) {
            case 'agencies':
            case 'announcements':
            case 'routes':
            case 'segments':
            case 'stops':
                return Kurogo::getOptionalSiteVar('TRANSLOC_ROUTE_REQUEST_TIMEOUT', 5);
      
            case 'arrival-estimates':
            case 'vehicles':
                return Kurogo::getOptionalSiteVar('TRANSLOC_UPDATE_REQUEST_TIMEOUT', 2);
        }
        return 10; // unknown command
    }
  
    private static function getCacheForCommand($action) {
        $cacheKey = $action;
        
        if (!isset(self::$caches[$cacheKey])) {
            $cacheTimeout = 20;
            $suffix = 'json';
            
            switch ($action) {
                case 'agencies':
                case 'routes':
                case 'segments':
                case 'stops':
                    $cacheTimeout = Kurogo::getOptionalSiteVar('TRANSLOC_ROUTE_CACHE_TIMEOUT', 3600);
                    $cacheKey = 'config';
                    break;
         
                case 'arrival-estimates':
                case 'vehicles':
                    $cacheTimeout = Kurogo::getOptionalSiteVar('TRANSLOC_UPDATE_CACHE_TIMEOUT', 6);
                    $cacheKey = 'update';
                    break;
                  
                case 'announcements':
                    $cacheTimeout = Kurogo::getOptionalSiteVar('TRANSLOC_ANNOUNCEMENT_CACHE_TIMEOUT', 120);
                    $cacheKey = 'news';
                    break;
            }
        
            // daemons should load cached files aggressively to beat user page loads
            if (self::$daemonCacheMode) {
                $cacheTimeout -= 300;
                if ($cacheTimeout < 0) { $cacheTimeout = 0; }
            }
            
            self::$caches[$cacheKey] = new DiskCache(
                Kurogo::getSiteVar('TRANSLOC_CACHE_DIR'), $cacheTimeout, TRUE);
            self::$caches[$cacheKey]->preserveFormat();
            self::$caches[$cacheKey]->setSuffix(".$suffix");
        }
        
        return self::$caches[$cacheKey];
    }
    
    private function getData($action, $params=array()) {
        $cache = self::getCacheForCommand($action);
        
        $cacheName = $action;
        if ($action != 'agencies') {
            $cacheName = implode('+', array_flip($this->agencyIDs)).".$cacheName";
        }
        
        $results = false;
        if ($cache->isFresh($cacheName)) {
            //error_log("TranslocTransitDataParser has cache for $cacheName");
            $results = json_decode($cache->read($cacheName), true);
            
        } else {
            if ($action != 'agencies') {
                $params['agencies'] = implode(',', $this->agencyIDs);
            }
            
            $url = Kurogo::getSiteVar('TRANSLOC_SERVICE_URL').self::TRANSLOC_API_VERSION.
                "/{$action}.json".(count($params) ? '?'.http_build_query($params) : '');
            
            //error_log("TranslocTransitDataParser requesting $url", 0);
            $streamContext = stream_context_create(array(
                'http' => array(
                    'timeout' => floatval(self::getTimeoutForCommand($action)),
                ),
            ));
            $contents = file_get_contents($url, false, $streamContext);
            
            if ($contents === false) {
                Kurogo::log(LOG_ERR, "Error reading '$url', reading expired cache", 'transit');
                $results = json_decode($cache->read($cacheName), true);
              
            } else {
                $results = json_decode($contents, true);
                if ($results && isset($results['data'])) {
                    //error_log("TranslocTransitDataParser got data", 0);
                    $cache->write($contents, $cacheName);
                  
                } else {
                    Kurogo::log(LOG_WARNING, "Error parsing JSON from '$url', reading expired cache", 'transit');
                    $results = json_decode($cache->read($cacheName), true);
                }
            }
        }
        
        //error_log(print_r($results, true));
        return $results && isset($results['data']) ? $results['data'] : array();
    }
    
    public function getRouteInfo($routeID, $time=null) {
        $routeInfo = parent::getRouteInfo($routeID, $time);
        
        $upcomingVehicleStops = array();
        $agencyVehiclesInfo = $this->getData('vehicles');
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
        
        // Add upcoming stop information
        foreach ($routeInfo['stops'] as $stopID => $stopInfo) {
          $routeInfo['stops'][$stopID]['upcoming'] = isset($upcomingVehicleStops[$stopID]);
        }
        
        return $routeInfo;
    }
  
    public function translocRouteIsRunning($routeID) {
        // Is the route active?
        $translocAllRoutesInfo = $this->getData('routes');
        foreach ($translocAllRoutesInfo as $agencyID => $routesInfo) {
            foreach ($routesInfo as $routeInfo) {
                if (self::argVal($routeInfo, 'route_id') == $routeID && !self::argVal($routeInfo, 'is_active', false)) {
                    return false;
                }
            }
        }
        
        // Are there any vehicles?
        $translocAgencyVehiclesInfo = $this->getData('vehicles');
        foreach ($translocAgencyVehiclesInfo as $agencyID => $vehiclesInfo) {
            foreach ($vehiclesInfo as $vehicleInfo) {
                if ($routeID != self::argVal($vehicleInfo, 'route_id')) { continue; }
                
                if (self::argVal($vehicleInfo, 'location')) {
                    return true;
                }
            }
        }
        
        // no vehicles, check arrival estimates
        $translocStopsArrivalsInfo = $this->getData('arrival-estimates');
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

// Special version of the TransitService class
class TranslocTransitService extends TransitService {
    private $routeID = null;
    private $parser = null;
  
    function __construct($id, $routeID, $parser) {
        parent::__construct($id);
        $this->routeID = $routeID;
        $this->parser = $parser;
    }
  
    public function isRunning($time) {
        return true; // all routes in feed are in service
    }
}

// Special version of the TransitSegment class
class TranslocTransitSegment extends TransitSegment {
    private $routeID = null;
    private $parser = null;
    
    function __construct($id, $name, $service, $direction, $routeID, $parser) {
        parent::__construct($id, $name, $service, $direction);
        $this->routeID = $routeID;
        $this->parser = $parser;
    }
  
    public function isRunning($time) {
        return $this->parser->translocRouteIsRunning($this->routeID);
    }
}
