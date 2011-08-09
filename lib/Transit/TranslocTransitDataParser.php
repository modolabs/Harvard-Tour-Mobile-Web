<?php

class TranslocTransitDataParser extends TransitDataParser {
  private static $daemonCacheMode = false;
  private static $caches = array();
  private $routeColors = array();
  private $translocHostname = '';
  
  static private function argVal($array, $key, $default='') {
    return isset($array[$key]) ? $array[$key] : $default;
  }
  
  function __construct($args, $overrides, $whitelist, $daemonMode=false) {
    parent::__construct($args, $overrides, $whitelist, $daemonMode);
    self::$daemonCacheMode = $daemonMode;
  }

  protected function isLive() {
    return true;
  }
  
  protected function getMapIconUrlForRouteStop($routeID) {
    return Kurogo::getSiteVar('TRANSLOC_MARKERS_URL').http_build_query(array(
      'm' => 'stop',
      'c' => $this->getRouteColor($routeID),
    ));
  }
 
  protected function getMapIconUrlForRouteVehicle($routeID, $vehicle=null) {
    return Kurogo::getSiteVar('TRANSLOC_MARKERS_URL').http_build_query(array(
      'm' => 'bus',
      'c' => $this->getRouteColor($routeID),
      'h' => $this->getDirectionForHeading(self::argVal($vehicle, 'heading', 4)),
    ));
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

  public function getNewsForRoutes() {
    $news = array();
    
    $newsInfo = self::getData($this->translocHostname, 'announcements');
    
    foreach (self::argVal($newsInfo, 'agencies', array()) as $agencyNews) {
      foreach (self::argVal($agencyNews, 'announcements', array()) as $routeNews) {
        if (!isset($routeNews['id'])) { continue; }
        
        $news[$routeNews['id']] = array(
          'agency' => self::argVal($agencyNews, 'name'),
          'title'  => self::argVal($routeNews, 'title'),
          'date'   => strtotime(self::argVal($routeNews, 'date')),
          'urgent' => self::argVal($routeNews, 'urgent'),
          'html'   => self::argVal($routeNews, 'html'),
        );
      }
    }
    
    return $news;
  }
  
  protected function getServiceName() {
    return 'Translöc';
  }
  
  protected function getServiceId() {
    return 'transloc';
  }
  
  protected function getServiceLink() {
    return isset($this->args['serviceURL']) ? $this->args['serviceURL'] : 'http://www.transloc.com/';
  }

  public function getRouteVehicles($routeID) {
    $updateInfo = self::getData($this->translocHostname, 'update');
    
    $vehicles = array();
    foreach ($updateInfo['vehicles'] as $vehicleInfo) {
      if ($vehicleInfo['r'] != $routeID) { continue; }
      
      if ($this->routeIsRunning($routeID) && isset($vehicleInfo['id'])) {
        $latLon = self::argVal($vehicleInfo, 'll', false);
        if ($latLon) {
          $vehicles[$vehicleInfo['id']] = array(
            'secsSinceReport' => self::argVal($vehicleInfo, 't', PHP_INT_MAX),
            'lat'             => self::argVal($latLon, 0),
            'lon'             => self::argVal($latLon, 1),
            'heading'         => self::argVal($vehicleInfo, 'h', 0),
            'nextStop'        => self::argVal($vehicleInfo, 'next_stop'),
            'agencyID'        => $this->getRoute($routeID)->getAgencyID(),
            'routeID'         => $routeID,
          );
          if (isset($vehicleInfo['s'])) {
            $vehicles[$vehicleInfo['id']]['speed'] = $vehicleInfo['s'];
          }
          $vehicles[$vehicleInfo['id']]['iconURL'] = 
            $this->getMapIconUrlForRouteVehicle($routeID, $vehicles[$vehicleInfo['id']]);
        }
      } else {
        error_log('Warning: inactive route '.$routeID.' has active vehicle '.$vehicleInfo['id']);
      }
    }
    return $vehicles;
  }
  
  private function filterPredictions($prediction) {
    return ($prediction - time()) > 9;
  }

  protected function loadData() {
    $this->translocHostname = $this->args['hostname'];
  
    $setupInfo = self::getData($this->translocHostname, 'setup');
        
    $segments = array();
    foreach (self::argVal($setupInfo, 'segments', array()) as $segmentInfo) {
      if (isset($segmentInfo['id'], $segmentInfo['points'])) {
        $segments[$segmentInfo['id']] = Polyline::decodeToArray($segmentInfo['points']);
      }
    }
    
    $mergedSegments = array();
    foreach (self::argVal($setupInfo, 'agencies', array()) as $agency) {
      foreach (self::argVal($agency, 'routes', array()) as $i => $routeInfo) {
        if (!isset($routeInfo['id'])) { continue; }
      
        $routeID = $routeInfo['id'];
        
        if ($this->whitelist && !in_array($routeID, $this->whitelist)) {
          continue;  // skip entries not on whitelist
        }
      
        $this->addRoute(new TransitRoute(
          $routeID, 
          $agency['name'], 
          self::argVal($routeInfo, 'long_name'), 
          '' // will be overridden
        ));

        $this->routeColors[$routeID] = self::argVal($routeInfo, 'color', parent::getRouteColor($routeID));
        
        $path = array();
        foreach (self::argVal($routeInfo, 'segments', array()) as $segmentNum) {
          $segmentNum = intval($segmentNum);
          
          $segmentPath = $segments[abs($segmentNum)];
          if ($segmentNum < 0) {
            $segmentPath = array_reverse($segmentPath);
          }
          
          $path = array_merge($path, $segmentPath);
        }
        $this->getRoute($routeID)->addPath(new TransitPath('loop', $path));
        
        // special service type
        $routeService = new TransitService("{$routeID}_service", true);
        
        // segments will be filled in below by the stop config
        $mergedSegments[$routeID] = new TranslocTransitSegment(
          'loop',
          '',
          $routeService,
          'loop',
          $this->translocHostname, 
          $routeID
        );
      }
    }

    $updateInfo = self::getData($this->translocHostname, 'update');
    if (isset($updateInfo['time'])) {
      $baseTime = intval($updateInfo['time']);
      
      $arrivalTimes = self::getData($this->translocHostname, 'arrivals');
  
      $stopPredictions = array();
      foreach ($arrivalTimes as $arrivalInfo) {
        if (!isset($arrivalInfo['route_id']) || !isset($arrivalInfo['stop_id'])) { continue; }
        
        $routeID = $arrivalInfo['route_id'];
        $stopID = $arrivalInfo['stop_id'];
        
        if (!isset($stopPredictions[$routeID])) {
          $stopPredictions[$routeID] = array();
        }
        if (!isset($stopPredictions[$routeID][$stopID])) {
          $stopPredictions[$routeID][$stopID] = array();
        }
        
        if (isset($arrivalInfo['timestamp'])) {
          $stopPredictions[$routeID][$stopID][] = intval($arrivalInfo['timestamp']);
        }
      }  
    }
    
    $stopsInfo = self::getData($this->translocHostname, 'stops');
    foreach ($stopsInfo['stops'] as $stopInfo) {
      if (isset($stopInfo['id'])) {
        $latLon = self::argVal($stopInfo, 'll');
        
        $this->addStop(new TransitStop(
          $stopInfo['id'], 
          self::argVal($stopInfo, 'name'), 
          '', 
          self::argVal($latLon, 0, 0), 
          self::argVal($latLon, 1, 0)
        ));
      }
    }
    foreach (self::argVal($stopsInfo, 'routes', array()) as $routeInfo) {
      $routeID = $routeInfo['id'];
      
      if (!isset($mergedSegments[$routeID])) {
        error_log("Skipping unknown route '{$routeInfo['id']}'");
        continue;
      }
      
      foreach(self::argVal($routeInfo, 'stops', array()) as $stopIndex => $stopID) {
        $predictions = array();
        if (isset($stopPredictions[$routeID], $stopPredictions[$routeID][$stopID])) {
          sort($stopPredictions[$routeID][$stopID]);
          
          $predictions = array_filter($stopPredictions[$routeID][$stopID], 
            array($this, 'filterPredictions')); 
        }
        
        $mergedSegments[$routeID]->addStop($stopID, $stopIndex);
        $mergedSegments[$routeID]->setStopPredictions($stopID, $predictions);
      }
    }
    
    foreach ($mergedSegments as $routeID => $segment) {
      $this->getRoute($routeID)->addSegment($segment);
    }
  }
  
  private static function getTimeoutForCommand($action) {
    switch ($action) {
      case 'announcements':
      case 'setup': 
      case 'stops':
        return 30;

      case 'arrivals':
      case 'update':
        return 10;
    }
    return 30; // unknown command
  }

  private static function getCacheForCommand($action) {
    $cacheKey = $action;
    
    if (!isset(self::$caches[$cacheKey])) {
      $cacheTimeout = 20;
      $suffix = 'json';

      switch ($action) {
        case 'setup': 
        case 'stops':
          $cacheTimeout = Kurogo::getSiteVar('TRANSLOC_ROUTE_CACHE_TIMEOUT');
          break;
 
        case 'arrivals':
        case 'update':
          $cacheTimeout = Kurogo::getSiteVar('TRANSLOC_UPDATE_CACHE_TIMEOUT');
          break;
          
        case 'announcements':
          $cacheTimeout = Kurogo::getSiteVar('TRANSLOC_ANNOUNCEMENT_CACHE_TIMEOUT');
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
      self::$caches[$cacheKey]->setSuffix(".$cacheKey.$suffix");
    }
    
    return self::$caches[$cacheKey];
  }
  
  private static function getData($hostname, $action) {
    $cache = self::getCacheForCommand($action);
    $cacheName = $hostname;
    
    $results = false;
    if ($cache->isFresh($cacheName)) {
      $results = json_decode($cache->read($cacheName), true);
      
    } else {
      $params = array('v' => 1); // version 1 of api
      if ($action == 'update') {
        $params['nextstops'] = 'true';
      } else if ($action == 'announcements') {
        $params['contents'] = 'true';
      }
      
      $url = sprintf(Kurogo::getSiteVar('TRANSLOC_SERVICE_URL_FORMAT'), 
        $hostname, $action).http_build_query($params);

      //error_log("TranslocTransitDataParser requesting $url", 0);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::getTimeoutForCommand($action));
      $contents = curl_exec($ch);
      
      if ($contents === false) {
        error_log("TranslocTransitDataParser error reading '$url': ".curl_error($ch));
        error_log("TranslocTransitDataParser reading expired cache");
        $results = json_decode($cache->read($cacheName), true);
        
      } else {
        $results = json_decode($contents, true);
        if ($results) {
          //error_log("TranslocTransitDataParser got data", 0);
          $cache->write($contents, $cacheName);
          
        } else {
          error_log("TranslocTransitDataParser error parsing JSON from '$url'");
          error_log("TranslocTransitDataParser reading expired cache");
          $results = json_decode($cache->read($cacheName), true);
        }
      }
      
      curl_close($ch);
    }
    
    //error_log(print_r($results, true));
    return $results ? $results : array();
  }
  
  public function getRouteInfo($routeID, $time=null) {
    $routeInfo = parent::getRouteInfo($routeID, $time);
    $updateInfo = self::getData($this->translocHostname, 'update');

    $runningStops = array();
    
    if (isset($updateInfo['vehicles'])) {
      foreach ($updateInfo['vehicles'] as $vehicleInfo) {
        if (isset($vehicleInfo['r'], $vehicleInfo['next_stop']) && $vehicleInfo['r'] == $routeID) {
          $runningStops[$vehicleInfo['next_stop']] = true;
        }
      }
    }

    // Add upcoming stop information
    foreach ($routeInfo['stops'] as $stopID => $stopInfo) {
      $routeInfo['stops'][$stopID]['upcoming'] = isset($runningStops[$stopID]);
    }
    return $routeInfo;
  }

  public static function translocRouteIsRunning($hostname, $routeID) {
    $updateInfo = self::getData($hostname, 'update');
    $activeRoutes = is_array(self::argVal($updateInfo, 'active_routes', false)) ? 
      $updateInfo['active_routes'] : array();
    
    return in_array($routeID, $activeRoutes);
  }
}

// Special version of the TransitService class
class TranslocTransitService extends TransitService {
  private $routeID = null;
  private $hostname = null;

  function __construct($id, $hostname, $routeID) {
    parent::__construct($id);
    $this->hostname = $hostname;
    $this->routeID = $routeID;
  }

  public function isRunning($time) {
    return TranslocTransitDataParser::translocRouteIsRunning($this->hostname, $this->routeID);
  }
}

// Special version of the TransitSegment class
class TranslocTransitSegment extends TransitSegment {
  private $routeID = null;
  private $hostname = null;

  function __construct($id, $name, $service, $direction, $hostname, $routeID) {
    parent::__construct($id, $name, $service, $direction);
    $this->hostname = $hostname;
    $this->routeID = $routeID;
  }

  public function isRunning($time) {
    return TranslocTransitDataParser::translocRouteIsRunning($this->hostname, $this->routeID);
  }
}
