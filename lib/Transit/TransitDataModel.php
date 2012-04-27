<?php

/**
  * TransitDataModel Abstract Class
  * @package Transit
  */

includePackage('Maps'); // for Polyline class

abstract class TransitDataModel extends DataModel implements TransitDataModelInterface
{
    protected $args = array();
    protected $agencyIDs = array();
    protected $routeWhitelist = array();
    protected $routeBlacklist = array();
    protected $viewAsLoopRoutes = array();
    protected $scheduleViewRoutes = array();
    protected $splitByHeadsignRoutes = array();
    protected $daemonMode = false;
    
    protected $routes    = array();
    protected $stops     = array();
    protected $overrides = array();

    // For routes where not all vehicles stop at all stops
    protected $stopOrders = array();

    // Set this to true for stop order debugging
    // Do not leave this set to true because it modifies the REST API output
    protected $debugStopOrder = false;
    
    protected $platform = 'unknown';
    protected $pagetype = 'compliant';
    
    static private $arrows = array(
        '1' => 'n',
        '2' => 'ne',
        '3' => 'e',
        '4' => 'se',
        '5' => 's',
        '6' => 'sw',
        '7' => 'w',
        '8' => 'nw',
    );
    
    static private $sortHelper = array();
    
    const GOOGLE_STATIC_MAPS_URL = 'http://maps.google.com/maps/api/staticmap?';
    const GOOGLE_CHART_API_URL = 'http://chart.apis.google.com/chart?';
    
    const LOOP_DIRECTION = 'loop';
    
    protected function init($args) {
        $args['CACHE_FOLDER'] = isset($args['CACHE_FOLDER']) ? 
            $args['CACHE_FOLDER'] : Kurogo::getOptionalSiteVar('TRANSIT_CACHE_DIR', 'Transit');
        
        parent::init($args);
        
        if (isset($args['FIELD_OVERRIDES'])) {
            $this->overrides = $args['FIELD_OVERRIDES'];
        }
        
        if (isset($args['AGENCIES'])) {
            $this->agencyIDs = array_filter(array_map('trim', explode(',', $args['AGENCIES'])));
        }
        if (!$this->agencyIDs) {
            Kurogo::log(LOG_ERR, "Agencies not specified in feeds.ini", 'transit');
        }
        
        if (isset($args['ROUTE_WHITELIST'])) {
            $this->routeWhitelist = array_filter(array_map('trim', explode(',', $args['ROUTE_WHITELIST'])));
        }
        
        if (isset($args['ROUTE_BLACKLIST'])) {
            $this->routeBlacklist = array_filter(array_map('trim', explode(',', $args['ROUTE_BLACKLIST'])));
        }
        
        if (isset($args['VIEW_ROUTES_AS_LOOP'])) {
            if ($args['VIEW_ROUTES_AS_LOOP'] === "*") {
                $this->viewAsLoopRoutes = true; // all routes
            } else {
                $this->viewAsLoopRoutes = array_filter(array_map('trim', explode(',', $args['VIEW_ROUTES_AS_LOOP'])));
            }
        }
        
        if (isset($args['SCHEDULE_VIEW'])) {
            if ($args['SCHEDULE_VIEW'] === "*") {
                $this->scheduleViewRoutes = true; // all routes
            } else {
                $this->scheduleViewRoutes = array_filter(array_map('trim', explode(',', $args['SCHEDULE_VIEW'])));
            }
        }

        if (isset($args['SPLIT_BY_HEADSIGN'])) {
            if ($args['SPLIT_BY_HEADSIGN'] === "*") {
                $this->splitByHeadsignRoutes = true; // all routes
            } else {
                $this->splitByHeadsignRoutes = array_filter(array_map('trim', explode(',', $args['SPLIT_BY_HEADSIGN'])));
            }
        }
        
        if (isset($args['DAEMON_MODE'])) {
            $this->daemonMode = $args['DAEMON_MODE'];
        }
        
        $this->pagetype = Kurogo::deviceClassifier()->getPagetype();
        $this->platform = Kurogo::deviceClassifier()->getPlatform();
        
        $this->loadData();
    }
    
    protected function viewRoute($routeID) {
        $passedWhitelist = count($this->routeWhitelist) === 0 || in_array($routeID, $this->routeWhitelist);
        $passedBlacklist = count($this->routeBlacklist) === 0 || !in_array($routeID, $this->routeBlacklist);
        return $passedWhitelist && $passedBlacklist;
    }
    
    protected function viewRouteAsLoop($routeID) {
        return $this->viewAsLoopRoutes === true || in_array($routeID, $this->viewAsLoopRoutes);
    }
    
    protected function viewRouteInScheduleView($routeID) {
        return $this->scheduleViewRoutes === true || in_array($routeID, $this->scheduleViewRoutes);
    }
    
    protected function viewRouteSplitByHeadsign($routeID) {
        return $this->splitByHeadsignRoutes === true || in_array($routeID, $this->splitByHeadsignRoutes);
    }
    
    protected function updatePredictionData($routeID) {
        // override if you want to break out loading of prediction data
    }
      
    public function getRouteVehicles($routeID) {
        // override if the parser has vehicle locations
        return array();
    }
    
    protected function setUpcomingRouteStops($routeID, &$directions) {
        // override if you have vehicle locations that say what stop is upcoming
        
        $now = time();
        
        foreach ($directions as $directionID => $directionInfo) {
            // Walk the stops to figure out which is upcoming
            $stopIndexes = array_keys($directionInfo['stops']);
            $firstStopIndex = reset($stopIndexes);
            
            $firstStopPrevIndex  = null;
            if (count($directions) == 1) {
                // Loop case
                $firstStopPrevIndex = end($stopIndexes);
                if (self::isSameStop($directionInfo['stops'][$firstStopIndex]['id'], 
                                     $directionInfo['stops'][$firstStopPrevIndex]['id'])) {
                    $firstStopPrevIndex = prev($stopIndexes);
                }
            }
            
            foreach ($stopIndexes as $i => $stopIndex) {
                $arrives = $directionInfo['stops'][$stopIndex]['arrives'];
                
                $prevArrives = PHP_INT_MAX; // Non-loop case
                if ($stopIndex == $firstStopIndex) {
                    if ($firstStopPrevIndex) {
                        // Loop case
                        $prevArrives = $directionInfo['stops'][$firstStopPrevIndex]['arrives'];
                    }
                } else {
                    $prevArrives = $directionInfo['stops'][$stopIndexes[$i-1]]['arrives'];
                }
                
                // Suppress any soonest stops which are more than 2 hours from now
                $directions[$directionID]['stops'][$stopIndex]['upcoming'] = 
                    (abs($arrives - $now) < Kurogo::getSiteVar('TRANSIT_MAX_ARRIVAL_DELAY')) && 
                    $arrives <= $prevArrives;
            }
        }
    }

    
    protected function getServiceId() {
        return '';
    }
  
    protected function getServiceName() {
        return '';
    }
    
    protected function getServiceLink() {
        return '';
    }
    
    public function getServiceInfoForRoute($routeID) {
        return array(
            'id'    => $this->getServiceId(),
            'title' => $this->getServiceName(),
            'url'   => $this->getServiceLink(),
        );
    }
    
    abstract protected function loadData();
    
    abstract protected function isLive();
    
    //
    // Routes
    //
    
    protected function addRoute($route) {
        $id = $route->getID();
        
        if (isset($this->routes[$id])) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): Duplicate route '$id'", 'transit');
            return;
        }
        $this->routes[$id] = $route;
    }
    
    protected function getRoute($id) {
        if (!isset($this->routes[$id])) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No such route '$id'", 'transit');
            return false;
        }
    
        return $this->routes[$id];
    }
    
    // used to avoid warnings when looking for the right parser for a route
    public function hasRoute($routeID) {
        return isset($this->routes[$routeID]);
    }
  
    //
    // Stops
    //
  
    protected function addStop($stop) {
        $id = $stop->getID();
    
        if (isset($this->stops[$id])) {
            // This case seems to happen fairly often
            // Kurogo::log(LOG_WARNING, __FUNCTION__."(): Duplicate stop '$id'", 'transit');
            return;
        }
        $this->stops[$id] = $stop;
    }
      
    protected function getStop($id) {
        if (!isset($this->stops[$id])) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No such stop '$id'", 'transit');
            return false;
        }
    
        return $this->stops[$id];
    }
    
    // used to avoid warnings when looking at the wrong agency
    public function hasStop($id) {
        return isset($this->stops[$id]);
    }
    
    protected function getMapIconUrlForRouteStop($routeID) {
        if ($_SERVER['SERVER_NAME'] != 'localhost') {
            return FULL_URL_PREFIX.'modules/transit/images/shuttle_stop_dot.png';
        } else {
            return self::GOOGLE_CHART_API_URL.http_build_query(array(
                'chst' => 'd_simple_text_icon_left',
                'chld' => '|9|000|glyphish_target|12|'.$this->getRouteColor($routeID).'|FFF',
            ));
        }
    }
   
    protected function getMapIconUrlForRouteStopPin($routeID=null) {
        if ($_SERVER['SERVER_NAME'] != 'localhost') {
            return FULL_URL_PREFIX.'modules/transit/images/shuttle_stop_pin.png';
        } else {
            $routeColor = Kurogo::getSiteVar('TRANSIT_DEFAULT_ROUTE_COLOR');
            if ($routeID) {
                $routeColor = $this->getRouteColor($routeID);
            }
            
            return self::GOOGLE_CHART_API_URL.http_build_query(array(
                'chst' => 'd_map_pin_icon',
                'chld' => "bus|$routeColor",
            ));
        }
    }
   
    protected function getMapIconUrlForRouteVehicle($routeID, $vehicle=null) {
        // same icon for every vehicle by default
        return self::GOOGLE_CHART_API_URL.http_build_query(array(
            'chst' => 'd_map_pin_icon',
            'chld' => 'bus|'.$this->getRouteColor($routeID),
        ));
    }
   
    protected function getMapMarkersForVehicles($vehicles) {
        $query = '';
        
        if (count($vehicles)) {
            $firstVehicle = reset($vehicles);
          
            $markers = "icon:".$this->getMapIconUrlForRouteVehicle($firstVehicle['routeID']);
            foreach ($vehicles as $vehicle) {
                if ($vehicle['lat'] && $vehicle['lon']) {
                    $markers .= "|{$vehicle['lat']},{$vehicle['lon']}";
                }
            }
            $query .= '&'.http_build_query(array(
                'markers' => $markers,
            ));
        }
        
        return $query;
    }  
    
    protected function getDirectionForHeading($heading) {
        $arrowIndex = ($heading / 45) + 1.5;
        if ($arrowIndex > 8) { $arrowIndex = 8; }
        if ($arrowIndex < 0) { $arrowIndex = 0; }
        $arrowIndex = floor($arrowIndex);
        
        return self::$arrows[$arrowIndex];
    }
    
    protected function getRouteColor($routeID) {
        return Kurogo::getSiteVar('TRANSIT_DEFAULT_ROUTE_COLOR');
    }
    
    protected function getRouteDirectionPredictionsForStop($routeID, $stopID, $time) {
        $route = $this->getRoute($routeID);
        if (!$route) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No such route '$routeID'", 'transit');
            return false;
        }

        $directionPredictions = array();
        foreach ($route->getDirections() as $directionID) {
            $directionHasStop = false;
            $directionName = '';
            $predictions = array();
            
            if (!$this->lookupStopOrder($route->getAgencyID(), $routeID, $directionID, &$directionName)) {
                $directionName = '';
            }
            
            foreach ($route->getSegmentsForDirection($directionID) as $segment) {
                $segmentHasStop = false;
                
                foreach ($segment->getStops() as $stopInfo) {
                    if ($stopInfo['stopID'] == $stopID) {
                        $segmentHasStop = true;
                        $directionHasStop = true;
                        break;
                    }
                }
                
                if ($segmentHasStop) {
                    $predictions = array_merge($predictions, $segment->getArrivalTimesForStop($stopID, $time));
                }
                
                if (!$directionName && $segment->getService()->isRunning($time)) {
                    $directionName = strval($segment->getName());
                }
            }
            
            if ($directionHasStop) {
                if (count($predictions)) {
                    sort($predictions);
                    $predictions = array_values(array_unique($predictions, SORT_NUMERIC));
                }
    
                $directionPredictions[$directionID] = array(
                    'name'        => $directionName,
                    'predictions' => $predictions,
                );
            }
        }

        if ($this->viewRouteAsLoop($routeID)) {
            $names = array();
            $predictions = array();
            
            foreach ($directionPredictions as $directionID => $directionInfo) {
                $names[] = $directionInfo['name'];
                $predictions = array_merge($predictions, $directionInfo['predictions']);
            }
            
            $directionPredictions = array(
                self::LOOP_DIRECTION => array(
                    'name'        => implode(' / ', array_filter($names)),
                    'predictions' => $predictions,
                ),
            );
        }
        
        return $directionPredictions;
    }

    //
    // Query functions
    // 
    
    public function getStopInfo($stopID) {
        if (!isset($this->stops[$stopID])) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No such stop '$stopID'", 'transit');
            return array();
        }
      
        $now = TransitTime::getCurrentTime();
    
        $routePredictions = array();
        foreach ($this->routes as $routeID => $route) {
            if (!$route->hasStop($stopID)) { continue; }

            $this->updatePredictionData($route->getID());
            
            $directionPredictions = $this->getRouteDirectionPredictionsForStop($routeID, $stopID, $now);
            
            $routePredictions[$routeID] = array(
                'name'       => $route->getName(),
                'agency'     => $route->getAgencyID(),
                'directions' => $directionPredictions,
                'running'    => $route->isRunning($now),
                'live'       => $this->isLive(),
            );
        }
        
        $stopInfo = array(
            'name'        => $this->stops[$stopID]->getName(),
            'description' => $this->stops[$stopID]->getDescription(),
            'coordinates' => $this->stops[$stopID]->getCoordinates(),
            'stopIconURL' => $this->getMapIconUrlForRouteStopPin(),
            'routes'      => $routePredictions,
        );
        
        $this->applyStopInfoOverrides($stopID, $stopInfo);
    
        return $stopInfo;
    }
    
    public function getMapImageForStop($id, $width=270, $height=270) {
        $stop = $this->getStop($id);
        if (!$stop) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No such stop '$id'", 'transit');
            return false;
        }
        
        $coords = $stop->getCoordinates();
        $iconURL = $this->getMapIconUrlForRouteStopPin();
        
        $query = http_build_query(array(
            'sensor'  => 'false',
            'size'    => "{$width}x{$height}",
            'markers' => "icon:$iconURL|{$coords['lat']},{$coords['lon']}",
        ));
        
        return self::GOOGLE_STATIC_MAPS_URL.$query;
    }
  
    public function getMapImageForRoute($id, $width=270, $height=270) {
        $route = $this->getRoute($id);
        if (!$route) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No such route '$id'", 'transit');
            return false;
        }
        
        $paths = $route->getPaths();
        $color = $this->getRouteColor($id);
        
        if (!count($paths)) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No path for route '$id'", 'transit');
            return false;
        }
        
        $query = http_build_query(array(
            'sensor' => 'false',
            'size'   => "{$width}x{$height}",
        ));
      
        $now = TransitTime::getCurrentTime();
        if ($route->isRunning($now)) {
            $vehicles = $this->getRouteVehicles($id);
            $query .= $this->getMapMarkersForVehicles($vehicles);
        }
        
        foreach ($paths as $points) {
            foreach ($points as &$point) {
                $point = array_values($point);
            }
            $query .= '&'.http_build_query(array(
                'path' => 'weight:3|color:0x'.$color.'C0|enc:'.Polyline::encodeFromArray($points)
            ), 0, '&amp;');
        }
        
        return self::GOOGLE_STATIC_MAPS_URL.$query;
    }
  
    public function routeIsRunning($routeID, $time=null) {
        $route = $this->getRoute($routeID);
        if (!$route) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No such route '$routeID'", 'transit');
            return false;
        }
        
        $this->updatePredictionData($routeID);
    
        if (!isset($time)) {
            $time = TransitTime::getCurrentTime();
        }
        return $route->isRunning($time);
    }
    
    public function getRoutePaths($routeID) {
        $route = $this->getRoute($routeID);
        if (!$route) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No such route '$routeID'", 'transit');
            return array();
        }
    
        return $route->getPaths();
    }
    
    public function getRouteInfo($routeID, $time=null) {
        $route = $this->getRoute($routeID);
        if (!$route) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No such route '$routeID'", 'transit');
            return array();
        }
        $this->updatePredictionData($routeID);
    
        if (!isset($time)) {
            $time = TransitTime::getCurrentTime();
        }
    
        $inService = false;
        $isRunning = $route->isRunning($time, $inService);
        
        $routeInfo = array(
            'agency'          => $route->getAgencyID(),
            'name'            => $route->getName(),
            'description'     => $route->getDescription(),
            'color'           => $this->getRouteColor($routeID),
            'live'            => $isRunning ? $this->isLive() : false,
            'frequency'       => round($route->getServiceFrequency($time) / 60, 0),
            'running'         => $isRunning,
            'inService'       => $inService,
            'stopIconURL'     => $this->getMapIconUrlForRouteStop($routeID),
            'vehicleIconURL'  => $this->getMapIconUrlForRouteVehicle($routeID),
            'view'            => $this->viewRouteInScheduleView($routeID) ? 'schedule' : 'list',
            'splitByHeadsign' => $this->viewRouteSplitByHeadsign($routeID),
            'directions'      => array(),
        );
    
        // Check if there are a valid services and segments
        // Add a minute to the time checking so we don't tell people about buses 
        // that are leaving
        
        foreach ($route->getDirections() as $direction) {
            $directionID = is_numeric($direction) ? "direction_{$direction}" : $direction;
            
            $segments = $route->getSegmentsForDirection($direction);

            $routeInfo['directions'][$directionID] = 
                $this->getDirectionInfo($route->getAgencyID(), $routeID, $direction, $segments, $time);
        }
        
        if ($routeInfo['splitByHeadsign']) {
            $headsignDirections = array();
            
            foreach ($routeInfo['directions'] as $directionID => $directionInfo) {
                foreach ($directionInfo['segments'] as $segment) {
                    $headsign = $segment['name'] ? $segment['name'] : $directionInfo['name'];
                    if (!isset($headsignDirections[$headsign])) {
                        $headsignDirections[$headsign] = array(
                            'name'     => $headsign,
                            'segments' => array(),
                            'stops'    => array(),
                        );
                    }
                    $headsignDirections[$headsign]['segments'][] = $segment;
                    $headsignDirections[$headsign]['stops'][$directionID] = $directionInfo['stops'];
                }
            }
            
            foreach ($headsignDirections as $directionID => $directionInfo) {
                usort($headsignDirections[$directionID]['segments'], array(get_class(), 'sortDirectionSegments'));
            
                // flatten stop lists -- sometimes the same headsign exists in both directions
                $allStops = array();
                foreach ($headsignDirections[$directionID]['stops'] as $id => $stops) {
                    $allStops = array_merge($allStops, $stops);
                }
                $headsignDirections[$directionID]['stops'] = $allStops;
            }
            
            $routeInfo['directions'] = $headsignDirections;
            //error_log(print_r($routeInfo['directions'], true));
        }
        
        $this->setUpcomingRouteStops($routeID, $routeInfo['directions']);
        
        $this->applyRouteInfoOverrides($routeID, $routeInfo);
        
        // Do this last because it will confuse everything else!
        if ($this->viewRouteAsLoop($routeID)) {
            $routeInfo['directions'] = self::mergeDirections($routeInfo['directions']);
        }
        
        //error_log(print_r($routeInfo, true));
        
        return $routeInfo;
    }
    
    public static function mergeDirections($directions) {
        $names = array();
        $segments = array();
        $stops = array();
        
        foreach ($directions as $directionID => $directionInfo) {
            $names[] = $directionInfo['name'];
            $segments = array_merge($segments, $directionInfo['segments']);
            $stops = array_merge($stops, $directionInfo['stops']);
        }
        
        return array(
            self::LOOP_DIRECTION => array(
                'name'     => implode(' / ', array_filter($names)),
                'segments' => $segments,
                'stops'    => $stops,
            ),
        );
    }
  
    public function getRoutes($time=null) {
        if (!isset($time)) {
            $time = TransitTime::getCurrentTime();
        }
        
        $timeRange = array($time, $time + Kurogo::getOptionalSiteVar('TRANSIT_SCHEDULE_ROUTE_RUNNING_PADDING', 900));
        
        $routes = array();
        foreach ($this->routes as $routeID => $route) {
            $this->updatePredictionData($routeID);
            
            $inService = false; // Safety in case isRunning doesn't set this
            $runningTime = $this->viewRouteInScheduleView($routeID) ? $timeRange : $time;
            $isRunning = $route->isRunning($runningTime, $inService);
            
            $routes[$routeID] = array(
                'name'         => $route->getName(),
                'description'  => $route->getDescription(),
                'color'        => $this->getRouteColor($routeID),
                'frequency'    => round($route->getServiceFrequency($time) / 60),
                'agency'       => $route->getAgencyID(),
                'live'         => $isRunning ? $this->isLive() : false,
                'inService'    => $inService,
                'running'      => $isRunning,
                'view'         => $this->viewRouteInScheduleView($routeID) ? 'schedule' : 'list',
            );
            
            $this->applyRouteInfoOverrides($routeID, $routes[$routeID]);
        }
    
        return $routes;
    }
    
    protected function applyRouteInfoOverrides($routeID, &$routeInfo) {
        if (isset($this->overrides['route'])) {
            foreach ($this->overrides['route'] as $field => $overrides) {
                if (isset($overrides[$routeID])) {
                    $routeInfo[$field] = $overrides[$routeID];
                }
            }
        }
        if (isset($routeInfo['stops'], $this->overrides['stop'])) {
            foreach ($routeInfo['stops'] as $stopID => $stopInfo) {
                foreach ($this->overrides['stop'] as $field => $overrides) {
                    if (isset($overrides[$stopID], $stopInfo[$field])) {
                        $routeInfo['stops'][$stopID][$field] = $overrides[$stopID];
                    }
                }
            }
        }
    }
    
    protected function applyStopInfoOverrides($stopID, &$stopInfo) {
        if (isset($this->overrides['stop'])) {
            foreach ($this->overrides['stop'] as $field => $overrides) {
                if (isset($overrides[$stopID])) {
                    $stopInfo[$field] = $overrides[$stopID];
                }
            }
        }
        if (isset($stopInfo['routes'], $this->overrides['route'])) {
            foreach ($stopInfo['routes'] as $routeID => $routeInfo) {
                foreach ($this->overrides['route'] as $field => $overrides) {
                    if (isset($overrides[$routeID], $routeInfo[$field])) {
                        $stopInfo['routes'][$routeID][$field] = $overrides[$routeID];
                    }
                }
            }
        }
    }
    
    public static function isSameStop($stopID, $compareStopID) {
        if ($stopID == $compareStopID) {
            return true;
        }
        if ($stopID == $compareStopID.'_ar') {
            return true;
        }
        if ($stopID.'_ar' == $compareStopID) {
            return true;
        }
        return false;
    }
    
    public static function removeLastStop(&$stops) {
        end($stops);
        unset($stops[key($stops)]);
    }
    
    public static function removeFirstStop(&$stops) {
        reset($stops);
        unset($stops[key($stops)]);
    }
    
    public static function sortStops($a, $b) {
        if ($a["i"] == $b["i"]) { 
            return 0; 
        }
        return ($a["i"] < $b["i"]) ? -1 : 1;
    }
    
    public static function sortStopIs($type, $a, $b, $seenStopList=array()) {
        $stopInfo = self::$sortHelper[$a];
        foreach (self::$sortHelper[$a][$type] as $stopID) {
            if ($stopID == $b) {
                return true;
            } else if (!in_array($stopID, $seenStopList)) {
                $seenStopList[] = $stopID;
                return self::sortStopIs($type, $a, $stopID, $seenStopList);
            }
        }
        return false;
    }
    
    public static function sortByStopOrder($a, $b) {
        if ($a == $b) {
            return 0;
        }
        
        if (self::sortStopIs('before', $a, $b) || self::sortStopIs('after', $b, $a)) {
            return -1;
        } else if (self::sortStopIs('after', $a, $b) || self::sortStopIs('before', $b, $a)) {
            return 1;
        }
        Kurogo::log(LOG_WARNING, "Not enough information in trip stop orders to determine the relative order of stops $a and $b", 'transit');
        
        return 0;
    }

    protected function lookupStopOrder($agencyID, $routeID, $directionID, &$directionName) {
        if (!$this->stopOrders) {
            $config = ConfigFile::factory('transit-stoporder', 'site');
            $stopOrderConfigs = $config->getSectionVars(Config::EXPAND_VALUE);
            
            foreach ($stopOrderConfigs as $stopOrderConfig) {
                if (!isset($stopOrderConfig['route_id'])) { continue; }
                
                $stops = array();
                if (isset($stopOrderConfig['stop_ids'])) {
                    foreach ($stopOrderConfig['stop_ids'] as $stopID) {
                        $stops[] = array(
                            'id' => $stopID,
                        );
                    }
                }
                
                $this->stopOrders[] = array(
                    'agencyID'      => $stopOrderConfig['agency_id'],
                    'routeID'       => $stopOrderConfig['route_id'],
                    'directionID'   => $stopOrderConfig['direction_id'],
                    'directionName' => $stopOrderConfig['direction_name'],
                    'stops'         => $stops,
                );
            }
        }
        
        foreach ($this->stopOrders as $stopOrder) {
            if ($stopOrder['agencyID'] == $agencyID && 
                    $stopOrder['routeID'] == $routeID && 
                    $stopOrder['directionID'] == $directionID) {
                $directionName = $stopOrder['directionName'];
                return $stopOrder['stops'];
            }
        }
        return array();
    }
    
    protected static function sortDirectionSegments($a, $b) {
        for ($i = 0; $i < count($a['stops']); $i++) {
            if (isset($a['stops'][$i], $a['stops'][$i]['arrives'],
                                $b['stops'][$i], $b['stops'][$i]['arrives']) &&
                    $a['stops'][$i]['arrives'] && $b['stops'][$i]['arrives']) {
                // Found a stop where both $a and $b have stop times
                if ($a['stops'][$i]['arrives'] < $b['stops'][$i]['arrives']) {
                    return -1;
                } else if ($a['stops'][$i]['arrives'] > $b['stops'][$i]['arrives']) {
                    return 1;
                } else {
                    return 0;
                }
            }
        }
        //error_log("Found two trips with no stop overlap");
        
        // There are no stop overlaps between $a and $b, just order them by first stop time
        $aFirstStopTime = PHP_INT_MAX;
        $bFirstStopTime = PHP_INT_MAX;
        
        foreach ($a['stops'] as $stop) {
            if (isset($stop['arrives']) && $stop['arrives']) {
                $aFirstStopTime = $stop['arrives'];
                break;
            }
        }
        foreach ($b['stops'] as $stop) {
            if (isset($stop['arrives']) && $stop['arrives']) {
                $bFirstStopTime = $stop['arrives'];
                break;
            }
        }
        
        if ($aFirstStopTime < $bFirstStopTime) {
            return -1;
        } else if ($aFirstStopTime > $bFirstStopTime) {
            return 1;
        }
        
        return 0;
    }
    
    protected function getDirectionInfo($agencyID, $routeID, $directionID, $directionSegments, $time) {
        $directionName = '';
        
        $stopArray = $this->lookupStopOrder($agencyID, $routeID, $directionID, &$directionName);
        if (!$stopArray) {
            // No stop order in config, build with graph
            $segmentStopOrders = array(); // reset this
            $stopCounts = array();  // Keep track of stops that appear more than once in a segment
            
            foreach ($directionSegments as $segment) {
                if (!$segment->getService()->isRunning($time)) {
                    continue;
                }
                
                // If we don't have a direction name, try the first segment name (headsign)
                if (!$directionName && ($segmentName = $segment->getName())) {
                    $directionName = $segmentName;
                }
                
                $segmentStopOrder = array();
                $segmentStopCounts = array();
                foreach ($segment->getStops() as $stopIndex => $stopInfo) {
                    // skip stops with non-integer ids (things like 'skipped')
                    if (!ctype_digit(strval($stopInfo['i']))) { continue; }
                    
                    if (!isset($segmentStopCounts[$stopInfo['stopID']])) {
                        $segmentStopCounts[$stopInfo['stopID']] = 1;
                    } else {
                        $segmentStopCounts[$stopInfo['stopID']]++;
                    }
                    $segmentStopOrder[] = $stopInfo['stopID'];
                }
                $segmentStopOrders[] = $segmentStopOrder;
                
                foreach ($segmentStopCounts as $stopID => $count) {
                    if (!isset($stopCounts[$stopID]) || $count > $stopCounts[$stopID]) {
                        $stopCounts[$stopID] = $count;  // remember max count in any segment
                    }
                }
            }
            //error_log("HEADSIGN: $directionName");
            //error_log(print_r($segmentStopOrders, true));
            
            // The following attempts to fix the problem of cycles in the graph
            // It assumes that there is at least one trip with all the visits to a single
            // stop in it.  It numbers these stops uniquely and then removes all other 
            // instances of the stop from the other trips
            foreach ($stopCounts as $stopID => $count) {
                if ($count > 1) {
                    foreach ($segmentStopOrders as $i => $segmentStopOrder) {
                        $matching = array_intersect($segmentStopOrder, array($stopID));
                        if (count($matching) < $count) {
                            // remove all elements
                            $segmentStopOrders[$i] = array_diff($segmentStopOrder, array($stopID));
                        } else {
                            // order all elements
                            $index = 0;
                            foreach ($segmentStopOrder as $j => $segmentStopID) {
                                if ($segmentStopID == $stopID) {
                                    $segmentStopOrders[$i][$j] = $segmentStopID.'___'.$index++;
                                    $stopCounts[$segmentStopOrders[$i][$j]] = 1;
                                }
                            }
                        }
                    }
                }
            }
            
            //error_log(print_r($segmentStopOrders, true));
            //error_log(print_r($stopCounts, true));
            $directionStops = array();
            $tempStopCounts = $stopCounts;
            $stopSortGraph = $this->buildSortStopGraph($segmentStopOrders);
            $this->topologicalSortStops($stopSortGraph, $tempStopCounts, $directionStops);
            //error_log(print_r($directionStops, true));
            
            $stopArray = array();
            foreach ($directionStops as $stopID) {
                // strip any index which might have been added
                $parts = explode('___', $stopID);
                if ($parts) { $stopID = $parts[0]; }
                
                $stopArray[] = array(
                    'id'      => $stopID,
                );
            }
        }
        
        // initialize arrives field
        foreach ($stopArray as $i => $stop) {
            $stopArray[$i]['arrives'] = null;
        }
        
        $fullStopList = $stopArray;
        foreach ($fullStopList as $i => $stopInfo) {
            $stop = $this->getStop($stopInfo['id']);
            if (!$stop) {
                Kurogo::log(LOG_WARNING, "Attempt to look up invalid stop {$stopInfo['id']}", 'transit');
                continue;
            }
            
            $fullStopList[$i]['i']           = $i;
            $fullStopList[$i]['name']        = $stop->getName();
            $fullStopList[$i]['coordinates'] = $stop->getCoordinates();
            $fullStopList[$i]['hasTiming']   = false;
            $fullStopList[$i]['upcoming']    = false;
            
            $this->applyStopInfoOverrides($stopInfo['id'], $fullStopList[$i]);
        }
        
        $segments = array();
        
        $timeRange = array($time, $time + Kurogo::getOptionalSiteVar('TRANSIT_SCHEDULE_ROUTE_RUNNING_PADDING', 900));
        $runningTime = $this->viewRouteInScheduleView($routeID) ? $timeRange : $time;
        
        foreach ($directionSegments as $segment) {
            if (!$segment->isRunning($runningTime)) { continue; }
            
            $segmentInfo = array(
                'id'   => $segment->getID(),
                'name' => $segment->getName(),
                'stops' => $stopArray,
            );

            //error_log(print_r($segment->getStops(), true));
            $remainingStopsIndex = 0;
            foreach ($segment->getStops() as $i => $stopInfo) {
                $arrivalTime = null;
                
                if (isset($stopInfo['arrives']) && $stopInfo['arrives']) {
                    $arrivalTime = TransitTime::getTimestampOnDate($stopInfo['arrives'], $time);
                } else if (isset($stopInfo['predictions']) && $stopInfo['predictions']) {
                    $arrivalTime = reset($stopInfo['predictions']);
                }
                
                for ($j = $remainingStopsIndex; $j < count($segmentInfo['stops']); $j++) {
                    if ($segmentInfo['stops'][$j]['id'] == $stopInfo['stopID']) {
                        $remainingStopsIndex = $j+1;
                        if ($this->debugStopOrder) {
                            $segmentInfo['stops'][$j]['i'] = $stopInfo['i']; // useful for debugging stop sorting issues
                        }
                        $segmentInfo['stops'][$j]['arrives'] = $arrivalTime;
                        
                        // Augment full stop list with this arrival time
                        if ($arrivalTime) {
                            $oldArrivalTime = $fullStopList[$j]['arrives'];
                            if (!$oldArrivalTime || ($arrivalTime > $time && 
                                                     ($arrivalTime < $oldArrivalTime || $oldArrivalTime < $time))) {
                                $fullStopList[$j]['arrives'] = $arrivalTime;
                                $fullStopList[$j]['hasTiming'] = true;
                                if (isset($stopInfo['predictions'])) {
                                    $fullStopList[$j]['predictions'] = $stopInfo['predictions'];
                                }
                                
                                /*
                                // Debugging arrival time calculations
                                $offset = $arrivalTime - $time;
                                $mins = floor($offset / 60);
                                $secs = $offset - ($mins * 60);
                                $prev = $oldArrivalTime ? strftime("%H:%M:%S", $oldArrivalTime) : 'not set ';
                                error_log('Arrival time for stop '.str_pad($stopInfo['stopID'], 6).' is in '.str_pad($mins, 3, ' ', STR_PAD_LEFT).'m '.str_pad($secs, 2, ' ', STR_PAD_LEFT).'s (now '.strftime("%H:%M:%S", $arrivalTime).' / was '.$prev.') '.$this->stops[$stopInfo['stopID']]->getName());
                                */
                            }
                        }
                        break;
                    }
                }
                if ($j == count($segmentInfo['stops'])) {
                    Kurogo::log(LOG_WARNING, "Unable to place stop {$stopInfo['stopID']} for direction '$directionName' at index {$stopInfo['i']}", 'transit');
                    error_log("Unable to place stop {$stopInfo['stopID']} for direction '$directionName' at index {$stopInfo['i']}");
                }
            }
            $segments[] = $segmentInfo;
        }
        
        // Useful for debugging stop sorting issues
        // very noisy output so we really don't want this most of the time
        if ($this->debugStopOrder) {
            foreach ($segments as $i => $segmentInfo) {
                error_log("Trip {$segmentInfo['id']} ($directionName)");
                foreach ($segmentInfo['stops'] as $stop) {
                    error_log("\t\t".str_pad($stop['id'], 8).' => '.(isset($stop['i']) ? $stop['i'] : 'skipped'));
                }
            }
        }
        
        return array(
            'name'     => $directionName,
            'segments' => $segments,
            'stops'    => $fullStopList,
        );
    }

    protected function buildSortStopGraph($segmentStopOrders) {
        // Warning: stops within a trip are in order, but not all trips contain
        // all stops.  The following sort function attempts to build a graph of the 
        // stop orders in $sortHelper which is then used below in "topologicalSortStops"
        $stopSortGraph = array();
        foreach ($segmentStopOrders as $segmentStopOrder) {
            foreach ($segmentStopOrder as $i => $stopID) {
                $before = array_slice($segmentStopOrder, 0, $i);
                $after = array_slice($segmentStopOrder, $i+1);
                
                if (!isset($stopSortGraph[$stopID])) {
                    $stopSortGraph[$stopID] = array(
                        'before' => $before,
                        'after'  => $after,
                    );
                } else {
                    $stopSortGraph[$stopID]['before'] = array_merge($stopSortGraph[$stopID]['before'], $before);
                    $stopSortGraph[$stopID]['after']  = array_merge($stopSortGraph[$stopID]['after'],  $after);
                }
            }
        }
        
        // collapse graph to reduce sort time
        foreach ($stopSortGraph as $stopID => $stopInfo) {
            $stopSortGraph[$stopID]['before'] = array_unique($stopSortGraph[$stopID]['before']);
            $stopSortGraph[$stopID]['after']  = array_unique($stopSortGraph[$stopID]['after']);
        }
        
        return $stopSortGraph;
    }
    
    protected function topologicalSortStops($stopSortGraph, &$stopCounts, &$sortedStops, $current=null) {
        if ($current === null) {
            foreach ($stopSortGraph as $stopID => $stopInfo) {
                if (!count($stopInfo['after'])) {
                    $current = $stopID;
                    break;
                }
            }
            if ($current === null) {
                Kurogo::log(LOG_WARNING, "Could not find last stop.", 'transit');
                return;
            }
        }
        
        $stopCounts[$current]--; // remember we will be placing this stop
        //error_log("Looking at $current (".implode(', ', $stopSortGraph[$current]['before']).')');
        
        foreach ($stopSortGraph[$current]['before'] as $stopID) {
            // Each leg of the tree is permitted to have $seenStopCounts[$stopID] of each stop
            // Keep track of how many we have allowed into this branch
            if (isset($stopCounts[$stopID]) && $stopCounts[$stopID] > 0) {
                $this->topologicalSortStops($stopSortGraph, $stopCounts, $sortedStops, $stopID);
            }
        }
        
        $sortedStops[] = $current;
        //error_log(print_r($sortedStops, true));
    }
}

/**
  * Transit compacted time to reduce memory footprint
  * @package Transit
  */

class TransitTime
{   
    private static $localTimezone = null;
    private static $gmtTimezone = null;
    
    const HOUR_MULTIPLIER = 10000;
    const MINUTE_MULTIPLIER = 100;
    
    private static function getLocalTimezone() {
        if (!isset(self::$localTimezone)) {
            self::$localTimezone = Kurogo::siteTimezone();
        }
        return self::$localTimezone;
    }
  
    private static function getGMTTimezone() {
        if (!isset(self::$gmtTimezone)) {
            self::$gmtTimezone = new DateTimeZone('GMT');
        }
        return self::$gmtTimezone;
    }
  
    private static function getComponents($tt) {
        $hours = floor($tt/self::HOUR_MULTIPLIER);
        $minutes = floor(($tt - $hours*self::HOUR_MULTIPLIER)/self::MINUTE_MULTIPLIER); 
        $seconds = $tt - $minutes*self::MINUTE_MULTIPLIER - $hours*self::HOUR_MULTIPLIER;
        
        return array($hours, $minutes, $seconds);
    }
    
    private static function createFromComponents($hours, $minutes, $seconds) {
        if ($seconds > 59) {
            $addMinutes = floor($seconds/60);
            $minutes += $addMinutes;
            $seconds -= $addMinutes*60;
        }
        if ($minutes > 59) {
            $addHours = floor($minutes/60);
            $hours += $addHours;
            $minutes -= $addHours*60;
        }
        
        return $hours*self::HOUR_MULTIPLIER + $minutes*self::MINUTE_MULTIPLIER + $seconds;
    }
    
    public static function getLocalDatetimeFromTimestamp($timestamp) {
        $datetime = new DateTime('@'.$timestamp, self::getGMTTimezone());
        $datetime->setTimeZone(self::getLocalTimezone()); 
        
        $hours = intval($datetime->format('G'));
        if ($hours < 5) {
            $datetime->modify('-1 day'); // before 5am is for the previous day
        }
        
        return $datetime;
    }
  
    public static function getCurrentTime() {
        return time();
    }
    
    public static function createFromString($timeString) {
        list($hours, $minutes, $seconds) = explode(':', $timeString);
        
        $hours = intval($hours);
        $minutes = intval($minutes);
        $seconds = intval($seconds);
        
        return self::createFromComponents($hours, $minutes, $seconds);
    }
    
    public static function createFromTimestamp($timestamp) {
        $datetime = new DateTime('@'.$timestamp, self::getGMTTimezone());
        $datetime->setTimeZone(self::getLocalTimezone());
        
        $hours = intval($datetime->format('G'));
        if ($hours < 5) {
            $hours += 24; // Before 5am is represented as hours+24 (eg 1am is 25:00:00)
        }
        $minutes = intval($datetime->format('i'));
        $seconds = intval($datetime->format('s'));
        
        return self::createFromComponents($hours, $minutes, $seconds);
    }
    
    public static function getString($tt) {
        list($hours, $minutes, $seconds) = self::getComponents($tt);
        
        return 
            str_pad($hours,   2, '0', STR_PAD_LEFT).':'.
            str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.
            str_pad($seconds, 2, '0', STR_PAD_LEFT);
    }
    
    public static function getTimestampOnDate($tt, $dateTimestamp) {
        $date = self::getLocalDatetimeFromTimestamp($dateTimestamp);
    
        list($hours, $minutes, $seconds) = explode(':', $date->format('G:i:s'));
        $dateTT = self::createFromComponents($hours, $minutes, $seconds);
      
        list($ttHours, $ttMinutes, $ttSeconds) = self::getComponents($tt);
    
        // Note: getLocalDatetimeFromTimestamp subtracts a day if it is before 5am
        // so it will end up being the same day if ttHours > 23
        if ($ttHours > 23) {
            $date->modify('+1 day');
        }
        
        $date->setTime($ttHours, $ttMinutes, $ttSeconds);
        
        return $date->format('U');
    }
    
    public static function compare($tt1, $tt2) {
        //error_log("Comparing ".self::getString($tt1)." to ".self::getString($tt2));
        if ($tt1 == $tt2) {
            return 0;
        } else {
            return $tt1 < $tt2 ? -1 : 1;
        }
    }
    
    public static function addSeconds(&$tt, $addSeconds) {
        list($hours, $minutes, $seconds) = self::getComponents($tt);
        $tt = self::createFromComponents($hours, $minutes, $seconds+$addSeconds);
    }
    
    public function addMinutes(&$tt, $addMinutes) {
        list($hours, $minutes, $seconds) = self::getComponents($tt);
        $tt = self::createFromComponents($hours, $minutes+$addMinutes, $seconds);
    }
    
    public function addHours(&$tt, $addHours) {
        list($hours, $minutes, $seconds) = self::getComponents($tt);
        $tt = self::createFromComponents($hours+$addHours, $minutes, $seconds);
    }
    
    public function addTime(&$tt, $addTT) {
        list($hours,    $minutes,    $seconds)    = self::getComponents($tt);
        list($addHours, $addMinutes, $addSeconds) = self::getComponents($addTT);
      
        $tt = self::createFromComponents($hours+$addHours, $minutes+$addMinutes, $seconds+$addSeconds);
    }
    
    public static function isTimeInRange($timestamp, $fromTT, $toTT) {
        if (is_array($timestamp)) {
            $startTT = TransitTime::createFromTimestamp($timestamp[0]);
            $endTT = TransitTime::createFromTimestamp($timestamp[1]);
            
            $endBeforeFrom = TransitTime::compare($endTT, $fromTT) < 0; // timestamp before range
            $toBeforeStart = TransitTime::compare($toTT, $startTT) < 0; // range before timestamp
            $inRange = !$endBeforeFrom && !$toBeforeStart;
            
            //error_log(TransitTime::getString($startTT).' - '.TransitTime::getString($endTT)." is ".($inRange ? '' : 'not ')."in range ".TransitTime::getString($fromTT).' - '.TransitTime::getString($toTT));
          
        } else {
            $tt = TransitTime::createFromTimestamp($timestamp);
            
            $afterStart = TransitTime::compare($fromTT, $tt) <= 0;
            $beforeEnd  = TransitTime::compare($toTT, $tt) >= 0;
            $inRange = $afterStart && $beforeEnd;
            
            //error_log(TransitTime::getString($tt)." is ".($inRange ? '' : 'not ')."in range ".TransitTime::getString($fromTT).' - '.TransitTime::getString($toTT));
        }
        return $inRange;
    }
    
    public static function predictionIsValidForTime($prediction, $time) {
        if (is_array($time)) {
            return $prediction > $time[0] && $prediction - $time[1] < 60*60;
        } else {
            return $prediction > $time && $prediction - $time < 60*60;
        }
    }
}

/**
  * Transit Route Object
  * @package Transit
  */

class TransitRoute
{
    protected $id = null;
    protected $name = null;
    protected $description = null;
    protected $agencyID = null;
    protected $directions = array();
    
    function __construct($id, $agencyID, $name, $description) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->agencyID = $agencyID;
        $this->paths = array();
    }
    
    public function getID() {
        return $this->id;
    }
      
    public function getName() {
        return $this->name;
    }
      
    public function getDescription() {
        return $this->description;
    }
      
    public function getAgencyID() {
        return $this->agencyID;
    }
      
    public function addSegment(&$segment) {
        $direction = $segment->getDirection();
      
        if (!isset($this->directions[$direction])) {
            $this->directions[$direction] = array(
                'segments' => array(),
            );
        }
        
        $segmentID = $segment->getID();
        if (isset($this->directions[$direction]['segments'][$segmentID])) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): Duplicate segment '$segmentID' for route '{$this->name}'", 'transit');
        }
        
        $this->directions[$direction]['segments'][$segmentID] = $segment;
    }
    
    public function getDirections() {
        return array_keys($this->directions);
    }
    
    public function getDirection($id) {
        if (!isset($this->directions[$id])) {
            Kurogo::log(LOG_WARNING, __FUNCTION__."(): No such direction '$id'", 'transit');
            return false;
        }
        return $this->directions[$id];
    }
    
    public function getSegmentsForDirection($direction) {
        $direction = $this->getDirection($direction);
        return $direction['segments'];
    }
    
    public function setStopTimes($directionID, $stopID, $arrivesOffset, $departsOffset) {
        if (!isset($this->directions[$directionID])) {
            Kurogo::log(LOG_WARNING, "No direction $directionID for route {$this->id}", 'transit');
        }
        foreach ($this->directions[$directionID]['segments'] as &$segment) {
            $segment->setStopTimes($stopID, $predictions, $arrivesOffset, $departsOffset);
        }
    }
    
    public function setStopPredictions($directionID, $stopID, $predictions) {
        $direction = $this->getDirection($directionID);
        if ($direction && isset($direction['segments'])) {
            foreach ($direction['segments'] as $segment) {
                $segment->setStopPredictions($stopID, $predictions);
            }
        }
    }
    
    public function getStops() {
        $stops = array();
        foreach ($this->directions as $directionID => $direction) {
            foreach ($direction['segments'] as $segment) {
                foreach ($segment->getStops() as $stopInfo) {
                    $stops[] = $stopInfo;
                }
            }
        }
        return $stops;
    }
    
    public function hasStop($stopID) {
        foreach ($this->directions as $directionID => $direction) {
            foreach ($direction['segments'] as $segment) {
                foreach ($segment->getStops() as $stopInfo) {
                    if ($stopInfo['stopID'] == $stopID) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    public function isRunning($time, &$inService=null, &$runningSegmentNames=null) {
        $isRunning = false;
        $inService = false;
        $runningSegmentNames = array();
        
        // Check if there is a valid segment
        $servicesForDate = null;
        
        //error_log(__FUNCTION__."(): Looking at route {$this->id} ({$this->name})");
        foreach ($this->directions as $direction) {
            foreach ($direction['segments'] as $segment) {
                //error_log("    Looking at segment $segment");
                if ($segment->getService()->isRunning($time)) {
                    $inService = true;
                    
                    if ($segment->isRunning($time)) {
                        $name = $segment->getName();
                        if (isset($name) && !isset($runningSegmentNames[$name])) {
                            //error_log("   Route {$this->name} has named running segment '$name' (direction '$direction')");
                            $runningSegmentNames[$name] = $name;
                        }
                        $isRunning = true;
                    }
                }
            }
        }
        
        $runningSegmentNames = array_values($runningSegmentNames);
        return $isRunning;
    }
    
    protected function segmentsUseFrequencies() {
        foreach ($this->directions as $direction) {
            foreach ($direction['segments'] as $segment) {
                return $segment->hasFrequencies();
            }
        }
        return false;
    }
    
    protected function getFirstStopIDAndDirection() {
        foreach ($this->directions as $directionID => $direction) {
            foreach ($direction['segments'] as $segment) {
                foreach ($segment->getStops() as $stopInfo) {
                    return array($stopInfo['stopID'], $directionID);
                }
            }
        }
        return array(false, false);
    }
    
    public function getServiceFrequency($time) {
        // Time between shuttles at the same stop
        $frequency = 0;
        
        if ($this->segmentsUseFrequencies()) {
            foreach ($this->directions as $direction) {
                foreach ($direction['segments'] as $segment) {
                    if ($segment->isRunning($time)) {
                        $frequency = $segment->getFrequency($time);
                        if ($frequency > 0) { break; }
                    }
                    if ($frequency > 0) { break; }
                }
                if ($frequency > 0) { break; }
            }
        } else {
            // grab the first stop and check how often vehicles arrive at it
            list($stopID, $directionID) = $this->getFirstStopIDAndDirection();
                  
            if ($stopID) {
                $arrivalTimes = array();
                
                foreach ($this->directions[$directionID]['segments'] as $segment) {
                    if ($segment->getService()->isRunning($time)) {
                        $segmentArrivalTimes = $segment->getArrivalTimesForStop($stopID, $time);
                        $arrivalTimes = array_merge($arrivalTimes, $segmentArrivalTimes);
                    }
                }
                $arrivalTimes = array_unique($arrivalTimes);
                sort($arrivalTimes);
              
                for ($i = 0; $i < count($arrivalTimes); $i++) {
                    if ($arrivalTimes[$i] > $time) {
                        if (isset($arrivalTimes[$i+1])) {
                            $frequency = $arrivalTimes[$i+1] - $arrivalTimes[$i];
                        } else if (isset($arrivalTimes[$i-1])) {
                            $frequency = $arrivalTimes[$i] - $arrivalTimes[$i-1];
                        }
                    }
                    if ($frequency > 0 && $frequency < Kurogo::getSiteVar('TRANSIT_MAX_ARRIVAL_DELAY')) { break; }
                }
            }
            if ($frequency == 0) { $frequency = 60*60; } // default to 1 hour
        }
        return $frequency;
    }
    
    public function addPath($path) {
        $this->paths[] = $path;
    }
    
    public function getPaths() {
        $paths = array();
        foreach ($this->paths as $path) {
            $paths[$path->getID()] = $path->getPoints();
        }
        return $paths;
    }
}

/**
  * Transit class to track dates when route runs
  * @package Transit
  */

class TransitService
{
    protected $id = null;
    protected $dateRanges = array();
    protected $exceptions = array();
    protected $additions = array();
    
    protected $alwaysRunning = false;
    
    function __construct($id, $alwaysRunning=false) {
        $this->id = $id;
        $this->alwaysRunning = $alwaysRunning;
    }
    
    public function getID() {
        return $this->id;
    }
    public function addDateRange($firstDate, $lastDate, $weekdays) {
        $this->dateRanges[] = array(
            'first'    => intval($firstDate),
            'last'     => intval($lastDate),
            'weekdays' => $weekdays,
        );
    }
    
    public function addExceptionDate($date) {
        $this->exceptions[] = intval($date);
    }
    
    public function addAdditionalDate($date) {
        $this->additions[] = intval($date);    
    }
    
    public function isRunning($time) {
        if ($this->alwaysRunning) { return true; }
      
        $datetime = TransitTime::getLocalDatetimeFromTimestamp($time);
        
        $date = intval($datetime->format('Ymd'));
        $dayOfWeek = $datetime->format('l');
        
        if (count($this->dateRanges)) {
            $insideValidDateRange = false;
            foreach ($this->dateRanges as $dateRange) {
                $week  = $dateRange['weekdays'];
              
                if ($date >= $dateRange['first'] && $date <= $dateRange['last'] && $week[strtolower($dayOfWeek)]) {
                    $insideValidDateRange = true;
                    break;
                }
            }
        } else {
            // no date ranges means always valid
            $insideValidDateRange = true;
        }
        
        $isException  = in_array($date, $this->exceptions);
        $isAddition   = in_array($date, $this->additions);
    
        //error_log("service $service is ".($isAddition || ($inValidDateRange && !$isException) ? '' : 'not ').'running');
        return $isAddition || ($insideValidDateRange && !$isException);
    }
}

/**
  * Transit class to described sets of stops which are part of a route
  * @package Transit
  */

class TransitSegment
{
    protected $id = null;
    protected $name = null;
    protected $service = null;
    protected $direction = null;
    protected $stopsSorted = false;
    protected $stops = array();
    protected $frequencies = null;
    
    protected $hasPredictions = false;
    
    function __construct($id, $name, $service, $direction) {
        $this->id = $id;
        $this->name = $name;
        $this->service = $service;
        $this->direction = $direction;
    }
    
    public function getID() {
        return $this->id;
    }
    
    public function getName() {
        return $this->name;
    }
  
    public function getDirection() {
        return $this->direction;
    }
  
    public function getService() {
        return $this->service;
    }
  
    public function addFrequency($firstTT, $lastTT, $frequency) {
        if (!isset($this->frequencies)) {
            $this->frequencies = array();
        }
            
        $this->frequencies[] = array(
            'start'     => $firstTT,
            'end'       => $lastTT,
            'frequency' => intval($frequency),
        );
    }
    
    public function hasFrequencies() {
        return isset($this->frequencies);
    }
    
    public function getFrequency($time) {
        $frequency = false;
        
        if (isset($this->frequencies)) {
            foreach ($this->frequencies as $index => $frequencyInfo) {
                if (TransitTime::isTimeInRange($time, $frequencyInfo['start'], $frequencyInfo['end'])) {
                    $frequency = $frequencyInfo['frequency'];
                    break;
                } else if (!$frequency) {
                    $frequency = $frequencyInfo['frequency'];
                }
            }
        }
        return $frequency;
    }
    
    public function addStop($stopID, $sequenceNumber) {
        $this->stops[] = array(
            'stopID'    => $stopID,
            'i'         => intval($sequenceNumber),
            'hasTiming' => false,
        );
        $this->stopsSorted = false;
    }
    
    protected function getIndexForStop($stopID) { 
        foreach ($this->stops as $index => $stop) {
            if ($stopID == $stop['stopID']) {
                return $index;
            }
        }
        return false;
    }
  
    public function setStopTimes($stopID, $arrivesTT, $departsTT) {
        $index = $this->getIndexForStop($stopID);
        if ($index !== false) {
            $this->stops[$index]['arrives'] = $arrivesTT;
            $this->stops[$index]['departs'] = $departsTT;
            $this->stops[$index]['hasTiming'] = true;
        }
    }
    
    public function setStopPredictions($stopID, $predictions) {
        $index = $this->getIndexForStop($stopID);
        if ($index !== false) {
            if (!$this->hasPredictions && count($predictions)) {
                $this->hasPredictions = true;
            }
            $this->stops[$index]['predictions'] = $predictions;
            $this->stops[$index]['hasTiming'] = count($predictions) > 0;
        }
    }
    
    protected function sortStopsIfNeeded() {
        if (!$this->stopsSorted) {
            usort($this->stops, array('TransitDataModel', 'sortStops'));
            $this->stopsSorted = true;
        }
    }
    
    public function getStops() {
        $this->sortStopsIfNeeded();
        return $this->stops;
    }
    
    public function hasPredictions() {
        return $this->hasPredictions;
    }
    
    public function isRunning($time) {
        $this->sortStopsIfNeeded();
    
        if ($this->hasPredictions) {
            foreach ($this->stops as $index => $stop) {
                if (isset($stop['predictions']) && is_array($stop['predictions'])) {
                    foreach ($stop['predictions'] as $prediction) {
                        if (TransitTime::predictionIsValidForTime($prediction, $time)) {
                            return true; // live service with valid prediction
                        }
                    }
                }
            }
        }
        
        if ($this->service->isRunning($time)) {
            if (isset($this->frequencies)) {
                foreach ($this->frequencies as $index => $frequencyInfo) {
                    if (TransitTime::isTimeInRange($time, $frequencyInfo['start'], $frequencyInfo['end'])) {
                        return true;
                    }
                }
            } else {
                $firstStop = reset($this->stops);
                $lastStop  = end($this->stops);
                
                if (isset($firstStop['arrives'], $lastStop['departs'])) {
                    if (TransitTime::isTimeInRange($time, $firstStop['arrives'], $lastStop['departs'])) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    public function getArrivalTimesForStop($stopID, $time) {
        $arrivalTimes = array(); 
        $index = 0;
        if (isset($stopID)) {
            $index = $this->getIndexForStop($stopID);
        }
        
        if ($index !== false && isset($this->stops[$index])) {
            $stop = $this->stops[$index];
            
            if (isset($stop['predictions'])) {
                foreach ($stop['predictions'] as $prediction) {
                    if (TransitTime::predictionIsValidForTime($prediction, $time)) {
                        $arrivalTimes[] = $prediction;
                    }
                }
            }
            
            if (!count($arrivalTimes) && isset($stop['arrives']) && $stop['arrives'] > $time) {
                $arrivalTimes[] = TransitTime::getTimestampOnDate($stop['arrives'], $now);
            }
        }
        return $arrivalTimes;
    }
    
    public function getNextArrivalTime($time, $stopIndex) {
        $this->sortStopsIfNeeded();
    
        $arrivalTime = 0; // noticeable error state
    
        $stop = $this->stops[$stopIndex];
        
        if ($this->hasFrequencies()) {
            $firstFrequency = reset($this->frequencies);
            
            $firstLoopStopTime = $firstFrequency['start'];
            TransitTime::addTime($firstLoopStopTime, $stop['arrives']);
            
            $arrivalTime = TransitTime::getTimestampOnDate($firstLoopStopTime, $time);
            //error_log("Stop {$stop['stopID']} default arrival time will be ".$firstLoopStopTime->getString()." start is ".$firstFrequency['range']->getStart()->getString()." offset is ".$stop['arrives']->getString());
      
            $foundArrivalTime = false;
            foreach ($this->frequencies as $frequencyInfo) {
                $currentTT = $frequencyInfo['start']; // loop start
                TransitTime::addTime($currentTT, $stop['arrives']); // stop offset from loop start
                
                while (TransitTime::compare($currentTT, $frequencyInfo['end']) <= 0) {
                    $testTime = TransitTime::getTimestampOnDate($currentTT, $time);
                    //error_log("Looking at ".$currentTT->getString()." is ".($testTime > $time ? 'after now' : 'before now'));
                    if ($testTime > $time && (!$foundArrivalTime || $testTime < $arrivalTime)) { 
                        $arrivalTime = $testTime; 
                        $foundArrivalTime = true;
                        break;
                    }
                    TransitTime::addSeconds($currentTT, $frequencyInfo['frequency']);
                }
            }
          
        } else if ($this->hasPredictions && count($stop['predictions'])) {
            $now = TransitTime::getCurrentTime();
            
            foreach ($stop['predictions'] as $prediction) {
                if (TransitTime::predictionIsValidForTime($prediction, $time)) {
                    $arrivalTime = $prediction;
                    break;
                }
            }
        
        } else if (isset($stop['arrives'])) { 
            $arrivalTime = TransitTime::getTimestampOnDate($stop['arrives'], $time);
        }
        
        return $arrivalTime;
    }
}

/**
  * Transit class to describe a stop
  * @package Transit
  */

class TransitStop
{
    protected $id = null;
    protected $name = null;
    protected $description = null;
    protected $latitude = null;
    protected $longitude = null;
    
    function __construct($id, $name, $description, $latitude, $longitude) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->latitude = floatVal($latitude);
        $this->longitude = floatVal($longitude);
    }
    
    public function getID() {
        return $this->id;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getCoordinates() {
        return array(
          'lat' => $this->latitude, 
          'lon' => $this->longitude,
        );
    }
}


/**
  * Transit class to describe a path
  * @package Transit
  */

class TransitPath
{
    protected $id = null;
    protected $points = array();
    
    function __construct($id, $points) {
      $this->id = $id;
      
      $pathPoints = array();
      foreach ($points as &$point) {
          $pathPoints[] = array(
              'lat' => floatVal(reset($point)),
              'lon' => floatVal(end($point)),
          );
      }
      $this->points = $pathPoints;
    }
    
    public function getID() {
        return $this->id;
    }
    
    public function getPoints() {
        return $this->points;
    }
}
