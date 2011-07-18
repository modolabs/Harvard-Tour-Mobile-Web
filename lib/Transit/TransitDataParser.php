<?php
/**
  * Transit Data Parser Abstract Class
  * @package Transit
  */

includePackage('Maps'); // for Polyline class

abstract class TransitDataParser {
  protected $args = array();
  protected $whitelist = false;
  protected $daemonMode = false;
  
  protected $routes    = array();
  protected $stops     = array();
  protected $overrides = array();
  
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
  
  const GOOGLE_STATIC_MAPS_URL = 'http://maps.google.com/maps/api/staticmap?';
  const GOOGLE_CHART_API_URL = 'http://chart.apis.google.com/chart?';
  
  public static function factory($class, $args, $overrides, $whitelist, $daemonMode=false) {
    return new $class($args, $overrides, $whitelist, $daemonMode);
  }
  
  function __construct($args, $overrides, $whitelist, $daemonMode=false) {
    $this->pagetype = Kurogo::deviceClassifier()->getPagetype();
    $this->platform = Kurogo::deviceClassifier()->getPlatform();
  
    $this->daemonMode = $daemonMode;
    $this->args = $args;
    $this->overrides = $overrides;
    $this->whitelist = $whitelist ? $whitelist : false;
    
    $this->loadData();
  }
  
  protected function updatePredictionData($routeID) {
    // override if you want to break out loading of prediction data
  }
    
  public function getRouteVehicles($routeID) {
    // override if the parser has vehicle locations
    return array();
  }
  
  public function getNewsForRoutes() {
    // override if the parser can get news items
    return array();
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
  
  public function getServiceInfo() {
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
      error_log(__FUNCTION__."(): Warning duplicate route '$id'");
      return;
    }
    $this->routes[$id] = $route;
  }
    
  protected function getRoute($id) {
    if (!isset($this->routes[$id])) {
      error_log(__FUNCTION__."(): Warning no such route '$id'");
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
      //error_log(__FUNCTION__."(): Warning duplicate stop '$id'");
      return;
    }
    $this->stops[$id] = $stop;
  }
    
  protected function getStop($id) {
    if (!isset($this->stops[$id])) {
      error_log(__FUNCTION__."(): Warning no such stop '$id'");
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

  //
  // Query functions
  // 
  
  public function getStopInfoForRoute($routeID, $stopID) {
    if (!isset($this->routes[$routeID])) {
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return array();
    }
  
    $this->updatePredictionData($routeID);
    
    $stopInfo = array();

    $now = TransitTime::getCurrentTime(); 
    $stopInfo = array(
      'name'        => $this->stops[$stopID]->getName(),
      'description' => $this->stops[$stopID]->getDescription(),
      'coordinates' => $this->stops[$stopID]->getCoordinates(),
      'predictions' => $this->routes[$routeID]->getPredictionsForStop($stopID, $now),
      'live'        => $this->isLive(),
    );
    
    $this->applyStopInfoOverrides($stopID, $stopInfo);
    
    return $stopInfo;
  }
  
  public function getStopInfo($stopID) {
    if (!isset($this->stops[$stopID])) {
      error_log(__FUNCTION__."(): Warning no such stop '$stopID'");
      return array();
    }
  
    $now = TransitTime::getCurrentTime();

    $routePredictions = array();
    foreach ($this->routes as $routeID => $route) {
      if ($route->routeContainsStop($stopID)) {
        $this->updatePredictionData($route->getID());
        
        $routePredictions[$routeID]['predictions'] = $route->getPredictionsForStop($stopID, $now);
        $routePredictions[$routeID]['running'] = $route->isRunning($now);
        $routePredictions[$routeID]['name'] = $route->getName();
        $routePredictions[$routeID]['agency'] = $route->getAgencyID();
        $routePredictions[$routeID]['live'] = $this->isLive();
      }
    }
    
    $stopInfo = array(
      'name'        => $this->stops[$stopID]->getName(),
      'description' => $this->stops[$stopID]->getDescription(),
      'coordinates' => $this->stops[$stopID]->getCoordinates(),
      'routes'      => $routePredictions,
    );
    
    $this->applyStopInfoOverrides($stopID, $stopInfo);

    return $stopInfo;
  }
  
  public function getMapImageForStop($id, $width=270, $height=270) {
    $stop = $this->getStop($id);
    if (!$stop) {
      error_log(__FUNCTION__."(): Warning no such stop '$id'");
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
      error_log(__FUNCTION__."(): Warning no such route '$id'");
      return false;
    }
    
    $paths = $route->getPaths();
    $color = $this->getRouteColor($id);
    
    if (!count($paths)) {
      error_log(__FUNCTION__."(): Warning no path for route '$id'");
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
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
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
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return array();
    }

    return $route->getPaths();
  }
  
  public function getRouteInfo($routeID, $time=null) {
    $route = $this->getRoute($routeID);
    if (!$route) {
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return array();
    }
    $this->updatePredictionData($routeID);

    if (!isset($time)) {
      $time = TransitTime::getCurrentTime();
    }

    $inService = false;
    $isRunning = $route->isRunning($time, $inService);
    
    $routeInfo = array(
      'agency'         => $route->getAgencyID(),
      'name'           => $route->getName(),
      'description'    => $route->getDescription(),
      'color'          => $this->getRouteColor($routeID),
      'live'           => $isRunning ? $this->isLive() : false,
      'frequency'      => $route->getServiceFrequency($time),
      'running'        => $isRunning,
      'inService'      => $inService,
      'stopIconURL'    => $this->getMapIconUrlForRouteStop($routeID),
      'vehicleIconURL' => $this->getMapIconUrlForRouteVehicle($routeID),
      'stops'          => array(),
    );

    // Check if there are a valid services and segments
    // Add a minute to the time checking so we don't tell people about buses 
    // that are leaving
    
    $seenDirections = array();
    $directions = array();
    foreach ($route->getDirections() as $direction) {
      $directionNames = array();
      $directionStops = array();

      foreach ($route->getSegmentsForDirection($direction) as $segment) {
        if (!$segment->getService()->isRunning($time)) {
          continue;
        }
        
        $segmentName = $segment->getName();
        if (isset($segmentName)) {
          $directionNames[$segment->getID()] = $segmentName;
        }

        foreach ($segment->getStops() as $stopIndex => $stopInfo) {
          $stopID = $stopInfo['stopID'];
          
          $arrivalTime = null;
          if ($stopInfo['hasTiming']) {
            $arrivalTime = $segment->getNextArrivalTime($time, $stopIndex);
          }
          
          if (!isset($directionStops[$stopID])) {
            $stop = $this->getStop($stopID);
            if ($stop) {
              $directionStops[$stopID] = array(
                'name'      => $stop->getName(),
                'arrives'   => $arrivalTime,
                'hasTiming' => $stopInfo['hasTiming'],
                'i'         => $stopInfo['i'],
              );
              $directionStops[$stopID]['coordinates'] = $stop->getCoordinates();
            }
            if (isset($stopInfo['predictions'])) {
              $directionStops[$stopID]['predictions'] = $stopInfo['predictions'];
            }
            //error_log('Setting stop time to '.strftime("%H:%M:%S %Y/%m/%d", $arrivalTime).' for '.$this->stops[$stopID]->getName());
          } else {
            $oldArrivalTime = $directionStops[$stopID]['arrives'];
            if ($arrivalTime > $time && ($arrivalTime < $oldArrivalTime || $oldArrivalTime < $time)) {
              $directionStops[$stopID]['arrives'] = $arrivalTime;
              //error_log('Replacing stop time '.strftime("%H:%M:%S %Y/%m/%d", $oldArrivalTime).' with '.strftime("%H:%M:%S %Y/%m/%d", $arrivalTime)." (".strftime("%H:%M:%S %Y/%m/%d", $time).') for stop '.$this->stops[$stopID]['name']);
            }
          }
        }
        
        $directions[$direction] = array(
          'names' => array_unique($directionNames),
          'stops' => $directionStops,
        );
      }
    }

    // Check if we can merge the directions together into one big loop
    if (count($directions) > 1) {
      $newDirections = array();
      $handled = array();
      foreach ($directions as $direction => &$info) {
        $directionStops = array_keys($info['stops']);
        $first = reset($directionStops);
        $last = end($directionStops);
        foreach ($directions as $testDirection => &$testInfo) {
          if ($direction != $testDirection && 
              !in_array($direction, $handled) && !in_array($testDirection, $handled)) {
            //error_log("Looking at directions '$direction' and '$testDirection'");
            $testDirectionStops = array_keys($testInfo['stops']);
            $testFirst = reset($testDirectionStops);
            $testLast = end($testDirectionStops);
            $stops = $info['stops'];
            $testStops = $testInfo['stops'];
            
            if (TransitDataParser::isSameStop($last, $testFirst)) {
              if ($last['arrives'] > $testFirst['arrives']) {
                TransitDataParser::removeLastStop($stops);
              } else {
                TransitDataParser::removeFirstStop($testStops);
              }
              //error_log("Collapsing '$direction' and '$testDirection'");
              $newDirections["$direction-$testDirection"] = array(
                'names' => array_unique($info['names'] + $testInfo['names']),
                'stops' => $stops + $testStops,
              );
              $handled[] = $testDirection;
              $handled[] = $direction;
              break;
              
            } else if (TransitDataParser::isSameStop($testLast, $first)) {
              if ($testLast['arrives'] > $first['arrives']) {
                TransitDataParser::removeLastStop($testStops);
              } else {
                TransitDataParser::removeFirstStop($stops);
              }
              //error_log("Collapsing '$testDirection' and '$direction'");
              $newDirections["$testDirection-$direction"] = array(
                'names' => array_unique($testInfo['names'] + $info['names']),
                'stops' => $testStops + $stops,
              );
              $handled[] = $testDirection;
              $handled[] = $direction;
              break;
              
            }
          }
        }
        if (!in_array($direction, $handled) && count($directions[$direction]['stops'])) {
          $newDirections[$direction] = $info;
        }
      }
      //error_log('NEW DIRECTIONS: '.print_r($newDirections, true));
      $directions = $newDirections;
    }

    $names = array();
    foreach ($directions as $direction => $info) {
      $routeInfo['stops'] += $info['stops'];
      $names = array_merge($names, $info['names']);
    }
    
    $routeInfo['frequency'] = round($routeInfo['frequency'] / 60, 0);
    //error_log(print_r($routeInfo, true));
    
    $this->applyRouteInfoOverrides($routeID, $routeInfo);
    
    return $routeInfo;
  }

  public function getRoutes($time=null) {
    if (!isset($time)) {
      $time = TransitTime::getCurrentTime();
    }

    $routes = array();
    foreach ($this->routes as $routeID => $route) {
      $this->updatePredictionData($routeID);
          
      $inService = false; // Safety in case isRunning doesn't set this
      $isRunning = $route->isRunning($time, $inService);

      $routes[$routeID] = array(
        'name'        => $route->getName(),
        'description' => $route->getDescription(),
        'color'       => $this->getRouteColor($routeID),
        'frequency'   => round($route->getServiceFrequency($time) / 60),
        'agency'      => $route->getAgencyID(),
        'live'        => $isRunning ? $this->isLive() : false,
        'inService'   => $inService,
        'running'     => $isRunning,
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
}

/**
  * Transit compacted time to reduce memory footprint
  * @package Transit
  */

define('HOUR_MULTIPLIER', 10000);
define('MINUTE_MULTIPLIER', 100);

class TransitTime {   
  static $localTimezone = null;
  static $gmtTimezone = null;
  
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
  
  public static function getLocalDatetimeFromTimestamp($timestamp) {
    $datetime = new DateTime('@'.$timestamp, self::getGMTTimezone());
    $datetime->setTimeZone(self::getLocalTimezone()); 
    
    $hours = intval($datetime->format('G'));
    if ($hours < 5) {
      $datetime->modify('-1 day'); // before 5am is for the previous day
    }
    
    return $datetime;
  }

  static public function getCurrentTime() {
    return time();
  }

  private static function getComponents($tt) {
    $hours = floor($tt/HOUR_MULTIPLIER);
    $minutes = floor(($tt - $hours*HOUR_MULTIPLIER)/MINUTE_MULTIPLIER); 
    $seconds = $tt - $minutes*MINUTE_MULTIPLIER - $hours*HOUR_MULTIPLIER;
    
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
    
    return $hours*HOUR_MULTIPLIER + $minutes*MINUTE_MULTIPLIER + $seconds;
  }
  
  public static function createFromString($timeString) {
    list($hours, $minutes, $seconds) = explode(':', $timeString);
    
    $hours = intval($hours);
    $minutes = intval($minutes);
    $seconds = intval($seconds);
    
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
    $time = self::getLocalDatetimeFromTimestamp($timestamp);
    
    $tt = TransitTime::createFromString($time->format('G:i:s'));
    
    $afterStart = TransitTime::compare($fromTT, $tt) <= 0;
    $beforeEnd  = TransitTime::compare($toTT, $tt) >= 0;
    $inRange = $afterStart && $beforeEnd;
    
    //error_log(TransitTime::getString($tt)." is ".($inRange ? '' : 'not ')."in range ".TransitTime::getString($fromTT).' - '.TransitTime::getString($toTT));
    return $inRange;
  }
  
  public static function predictionIsValidForTime($prediction, $time) {
    return $prediction > $time && $prediction - $time < 60*60;
  }
}

/**
  * Transit Route Object
  * @package Transit
  */

class TransitRoute {
  private $id = null;
  private $name = null;
  private $description = null;
  private $agencyID = null;
  private $viewAsLoop = false;
  protected $directions = array();
  
  function __construct($id, $agencyID, $name, $description, $viewAsLoop=false) {
    $this->id = $id;
    $this->name = $name;
    $this->description = $description;
    $this->agencyID = $agencyID;
    $this->viewAsLoop = $viewAsLoop;
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
      error_log(__FUNCTION__."(): Warning duplicate segment '$segmentID' for route '{$this->name}'");
    }
    
    $this->directions[$direction]['segments'][$segmentID] = $segment;
  }
  
  public function getDirections() {
    if ($this->viewAsLoop) {
      return array('loop');
    } else {
      return array_keys($this->directions);
    }
  }
  
  public function getDirection($id) {
    if (!isset($this->directions[$id])) {
      error_log(__FUNCTION__."(): Warning no such direction '$id'");
      return false;
    }
    return $this->directions[$id];
  }
  
  public function getSegmentsForDirection($direction) {
    if ($this->viewAsLoop) {
      $segments = array();
      foreach ($this->directions as $directionID => $direction) {
        $segments += $direction['segments'];
      }
      return $segments;
    } else {
      $direction = $this->getDirection($direction);
      return $direction['segments'];
    }
  }
  
  public function setStopTimes($directionID, $stopID, $arrivesOffset, $departsOffset) {
    if (!isset($this->directions[$directionID])) {
      error_log("Warning no direction $directionID for route {$this->id}");
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
  
  public function routeContainsStop($stopID) {
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
  
  public function getPredictionsForStop($stopID, $time) {
    $predictions = array();
    $arrives = null;
    
    foreach ($this->directions as $directionID => $direction) {
      foreach ($direction['segments'] as $segment) {
        $segmentPredictions = $segment->getArrivalTimesForStop($stopID, $time);
        
        $predictions = array_merge($predictions, $segmentPredictions);
        /*foreach ($segment->getStops() as $stopIndex => $stopInfo) {
          if ($stopInfo['stopID'] == $stopID && $stopInfo['hasTiming']) {
            $arrivalTime = $segment->getNextArrivalTime($time, $stopIndex);
            
            // remember best arrival time in case we don't get any predictions
            if (!isset($arrives) || $arrivalTime < $arrives) {
              $arrives = $arrivalTime;
            }
            if (isset($stopInfo['predictions'])) {
              foreach ($stopInfo['predictions'] as $prediction) {
                if (TransitTime::predictionIsValidForTime($prediction, $time)) {
                  $predictions[] = $prediction;
                }
              }
            }
            break;
          }
        }*/
      }
    }
    
    if (count($predictions)) {
      sort($predictions);
      $predictions = array_values(array_unique($predictions, SORT_NUMERIC));
      
    } else if ($arrives) {
      $predictions[] = $arrives;
    }
    
    return $predictions;
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
  
  private function getFirstStopIDAndDirection() {
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

class TransitService {
  private $id = null;
  private $dateRanges = array();
  private $exceptions = array();
  private $additions = array();
  
  private $live = false;
  
  function __construct($id, $live=false) {
    $this->id = $id;
    $this->live = $live;
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
    if ($this->live) { return true; }
  
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

class TransitSegment {
  private $id = null;
  private $name = null;
  private $service = null;
  private $direction = null;
  private $stopsSorted = false;
  protected $stops = array();
  protected $frequencies = null;
  
  private $hasPredictions = false;
  
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
  
  private function getIndexForStop($stopID) { 
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
  
  private function sortStopsIfNeeded() {
    if (!$this->stopsSorted) {
      usort($this->stops, array('TransitDataParser', 'sortStops'));
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

class TransitStop {
  private $id = null;
  private $name = null;
  private $description = null;
  private $latitude = null;
  private $longitude = null;
  
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

class TransitPath {
  private $id = null;
  private $points = array();
  
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
