<?php

/**
  * GTFS Database Parser
  * @package Transit
  */

// TODO for this class
// - abstract away db engine
// - determine value of keeping fetched routes and stops in memory
class GTFSDatabaseTransitDataParser extends TransitDataParser {

  // if we use multiple gtfs files and one databases per file
  // maintain a central reference
  protected static $gtfsPaths = array();
  protected static $dbRefs = array();
  
  // for gtfs files that have multiple agencies, map all
  // agencies to a single canonical agency to simplify db referencing
  protected $agency;
  
  // For routes where not all vehicles stop at all stops
  protected $stopOrders = array();
  
  // Set this to true for stop order debugging
  // Do not leave this set to true because it modifies the REST API output
  protected $debugStopOrder = false;
  
  public static function getDB($agencyID) {
    if (!isset(self::$dbRefs[$agencyID])) {
      $file = self::$gtfsPaths[$agencyID];
      if (!file_exists($file)) {
        Kurogo::log(LOG_ERR, "no GTFS db at '$file'", 'transit');
        return;
      }

      $db = new PDO('sqlite:'.$file);
      if (!$db) {
        Kurogo::log(LOG_ERR, "could not open db at '$file'", 'transit');
        return;
      }
      self::$dbRefs[$agencyID] = $db;
    }
    return self::$dbRefs[$agencyID];
  }
  
  public static function dbQuery($agencyID, $sql, $params) {
    //error_log($sql);
    $db = self::getDB($agencyID);
    if (!$result = $db->prepare($sql)) {
      Kurogo::log(LOG_ERR, "failed to prepare statement: $sql", 'transit');
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    if (!$result->execute($params)) {
      Kurogo::log(LOG_ERR, "failed to execute statement '$sql' with parameters "
        .print_r($params, true)." / returned error: ".print_r($db->errorInfo(), true), 'transit');
    }
    return $result;
  }
  
  protected function query($sql, $params=array()) {
    return self::dbQuery($this->agency, $sql, $params);
  }

  // superclass overrides

  protected function isLive() {
    return false;
  }
  
  protected function getStop($id) {
    if (!isset($this->stops[$id])) {
      $sql = "SELECT * FROM stops where stop_id = ?";
      $params = array($id);
      $result = $this->query($sql, $params);
      if (!$result) {
        Kurogo::log(LOG_ERR, "error fetching stop: ".print_r($db->errorInfo(), true), 'transit');
      }
      $row = $result->fetch(PDO::FETCH_ASSOC);
      $this->addStop(new TransitStop(
        $row['stop_id'],
        $row['stop_name'], // may be null
        $row['stop_desc'], // may be null
        $row['stop_lat'],
        $row['stop_lon']
        ));
    }
    
    return parent::getStop($id);
  }
  
  public function getStopInfoForRoute($routeID, $stopID) {
    // ensure the data required by TransitDataParser is loaded
    $this->getStop($stopID);
    
    return parent::getStopInfoForRoute($routeID, $stopID);
  }
  
  // used to avoid warnings when looking at the wrong agency
  public function hasStop($id) {
    // ensure the data required by TransitDataParser is loaded
    $this->getStop($id);

    return isset($this->stops[$id]);
  }
  
  public function getStopInfo($stopID) {
    // get all route IDs associated with this stop.
    $now = TransitTime::getCurrentTime();
    $sql = "SELECT DISTINCT t.route_id AS route_id"
          ."  FROM stop_times s, trips t"
          ." WHERE s.stop_id = ?"
          ."   AND s.trip_id = t.trip_id";
    $params = array($stopID);
    $result = $this->query($sql, $params);
    if (!$result) {
      Kurogo::log(LOG_ERR, "error fetching stop info: ".print_r($db->errorInfo(), true), 'transit');
    }

    // rest of this function is mostly like the parent
    // but we call this->getRoute and this->getStop
    $routePredictions = array();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
      $routeID = $row['route_id'];
      $route = $this->getRoute($routeID);
      $this->updatePredictionData($routeID);
      
      $routePredictions[$routeID]['predictions'] = $route->getPredictionsForStop($stopID, $now);
      $routePredictions[$routeID]['running'] = $route->isRunning($now, $inService) && $inService;
      $routePredictions[$routeID]['name'] = $route->getName();
      $routePredictions[$routeID]['agency'] = $route->getAgencyID();
      $routePredictions[$routeID]['live'] = $this->isLive();
    }

    $stop = $this->getStop($stopID);    
    $stopInfo = array(
      'name'        => $stop->getName(),
      'description' => $stop->getDescription(),
      'coordinates' => $stop->getCoordinates(),
      'stopIconURL' => $this->getMapIconUrlForRouteStopPin(),
      'routes'      => $routePredictions,
    );
    
    $this->applyStopInfoOverrides($stopID, $stopInfo);

    return $stopInfo;
  }
  
  protected function loadData() {
    // Use first of specified agency ids.  Ignore any agency ids in gtfs file
    $agencyIDs = isset($this->args['agencies']) ? explode(',', $this->args['agencies']) : array();

    if (!count($agencyIDs)) {
      Kurogo::log(LOG_ERR, "no agency IDs found for gtfs parser in feeds.ini", 'transit');
      return;
    }

    $this->agency = $agencyIDs[0];
    $dbfile = $this->args['db'];
    self::$gtfsPaths[$this->agency] = Kurogo::getSiteVar('GTFS_DIR').'/'.$dbfile;
    
    $sql = "SELECT * from routes";
    $result = $this->query($sql);
    if (!$result) {
      Kurogo::log(LOG_ERR, 'could not load routes: '.print_r($db->errorInfo(), true), 'transit');
    }
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
      $routeID = $row['route_id'];
      if (isset($row['route_long_name']) && $row['route_long_name']) {
        $routeName = $row['route_long_name'];
      } else if (isset($row['route_short_name']) && $row['route_short_name']) {
        $routeName = $row['route_short_name'];
      } else {
        $routeName = $routeID;
      }
      
      $route = new GTFSDatabaseTransitRoute(
        $routeID,
        $this->agency,
        $routeName,
        $row['route_desc'] // may be null
        );
  
      $this->addRoute($route);
    }
  }
  
  public function getRoutes($time=null) {
    if (!isset($time)) {
      $time = TransitTime::getCurrentTime();
    }
    
    $routes = parent::getRoutes($time);
    
    if ($routes && isset($this->args['scheduleView']) && $this->args['scheduleView']) {
      $runningRange = array($time, $time + Kurogo::getSiteVar('GTFS_TRANSIT_ROUTE_RUNNING_PADDING'));
      foreach ($this->routes as $routeID => $route) {
        $routes[$routeID]['running'] = $route->isRunning($runningRange);
        $routes[$routeID]['scheduleView'] = true;
      }
    }
    
    return $routes;
  }
  
  public function getRouteInfo($routeID, $time=null) {
    if (!$time) {
      $time = TransitTime::getCurrentTime();
    }
    $routeInfo = parent::getRouteInfo($routeID, $time);
    
    if ($routeInfo && isset($this->args['scheduleView']) && $this->args['scheduleView']) {
      $routeInfo['scheduleView'] = true;
      
      $route = $this->getRoute($routeID);
      if (!$route) {
        Kurogo::log(LOG_WARNING, __FUNCTION__."(): Warning no such route '$routeID'", 'transit');
        return array();
      }
      $agencyID = $route->getAgencyID();

      $runningRange = array($time, $time + Kurogo::getSiteVar('GTFS_TRANSIT_ROUTE_RUNNING_PADDING'));
      $routeInfo['running'] = $route->isRunning($runningRange);
      
      // For routes where each headsign has a different stop list
      // not sure why these aren't just separate routes
      $splitByHeadsigns = isset($this->args['splitByHeadsign']) ? explode(',', $this->args['splitByHeadsign']) : array();
      $routeInfo['splitByHeadsign'] = in_array($routeID, $splitByHeadsigns);
      
      $routeInfo['directions'] = array();
  
      if ($routeInfo['splitByHeadsign']) {
        $allSegments = array();
        foreach ($route->getDirections() as $direction) {
          $allSegments = array_merge($allSegments, $route->getSegmentsForDirection($direction));
        }
        
        $directionsByHeadsign = array();
        foreach ($allSegments as $segment) {
          if (!$segment->getService()->isRunning($time)) {
            continue;
          }
          
          $headsignName = $segment->getName();
          if (!isset($directionsByHeadsign[$headsignName])) {
            $directionsByHeadsign[$headsignName] = array();
          }
          $directionsByHeadsign[$headsignName][] = $segment;
        }
        
        foreach ($directionsByHeadsign as $direction => $segments) {
          $info = $this->getDirectionInfo($agencyID, $routeID, $direction, $segments, $time);
          
          if ($info['segments']) {
            $routeInfo['directions'][$direction] = $info;
            if (!$routeInfo['directions'][$direction]['name']) {
              $routeInfo['directions'][$direction]['name'] = $direction; // key is headsign
            }
          }
        }
      } else {
        foreach ($route->getDirections() as $direction) {
          $segments = $route->getSegmentsForDirection($direction);
          
          $routeInfo['directions'][$direction] = $this->getDirectionInfo($agencyID, $routeID, $direction, $segments, $time);
        }
      }
        
      // sort segments
      foreach ($routeInfo['directions'] as $d => $directionInfo) {
        usort($routeInfo['directions'][$d]['segments'], array(get_class(), 'sortDirectionSegments'));
      }
      //error_log(print_r($routeInfo['directions'], true));
    }
        
    return $routeInfo;
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
  
  protected function getDirectionInfo($agencyID, $routeID, $direction, $directionSegments, $time) {
    $directionName = '';
    
    $stopArray = $this->lookupStopOrder($agencyID, $routeID, $direction, &$directionName);      
    if (!$stopArray) {
      // No stop order in config, build with graph
      $segmentStopOrders = array(); // reset this
      $stopCounts = array();  // Keep track of stops that appear more than once in a segment
      
      foreach ($directionSegments as $segment) {
        if (!$segment->getService()->isRunning($time)) {
          continue;
        }
        
        $segmentStopOrder = array();
        $segmentStopCounts = array();
        foreach ($segment->getStops() as $stopIndex => $stopInfo) {
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
        
        $stopArray[] = array(
          'id' => $parts ? $parts[0] : $stopID,
          'i'  => null,
        );
      }
    }
    
    $segments = array();
    $runningRange = array($time, $time + Kurogo::getSiteVar('GTFS_TRANSIT_ROUTE_SHOWN_PADDING'));
    foreach ($directionSegments as $segment) {
      if (!$segment->isRunning($runningRange)) { continue; }
      
      $segmentInfo = array(
        'id'   => $segment->getID(),
        'name' => $segment->getName(),
        'stops' => $stopArray,
      );

      //error_log(print_r($segment->getStops(), true));
      $remainingStopsIndex = 0;
      foreach ($segment->getStops() as $i => $stopInfo) {
        $arrives = TransitTime::getTimestampOnDate($stopInfo['arrives'], $time);
        
        for ($j = $remainingStopsIndex; $j < count($segmentInfo['stops']); $j++) {
          if ($segmentInfo['stops'][$j]['id'] == $stopInfo['stopID']) {
            $remainingStopsIndex = $j+1;
            if ($this->debugStopOrder) {
              $segmentInfo['stops'][$j]['i'] = $stopInfo['i']; // useful for debugging stop sorting issues
            }
            $segmentInfo['stops'][$j]['arrives'] = $arrives;
            break;
          }
        }
        if ($j == count($segmentInfo['stops'])) {
          Kurogo::log(LOG_WARNING, "Unable to place stop {$stopInfo['stopID']} for direction '$directionName' starting at index $remainingStopsIndex", 'transit');
        }
      }
      $segments[] = $segmentInfo;
    }
    
    // Useful for debugging stop sorting issues
    // very noisy output so we really don't want this most of the time
    if ($this->debugStopOrder) {
      foreach ($segments as $i => $segmentInfo) {
        error_log("Trip {$segmentInfo['id']}");
        foreach ($segmentInfo['stops'] as $stop) {
          error_log("\t\t".str_pad($stop['id'], 8).' => '.(isset($stop['i']) ? $stop['i'] : 'skipped'));
        }
      }
    }
    
    foreach ($stopArray as $i => $stopInfo) {
      $stop = $this->getStop($stopInfo['id']);
      if ($stop) {
        $stopArray[$i]['name'] = $stop->getName();
      } else {
        Kurogo::log(LOG_WARNING, "Attempt to look up invalid stop {$stopInfo['id']}", 'transit');
      }
    }
    
    return array(
      'name'     => $directionName,
      'segments' => $segments,
      'stops'    => $stopArray,
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

  public function lookupStopOrder($agencyID, $routeID, $directionID, &$directionName) {
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
}

class GTFSDatabaseTransitService extends TransitService {
  public static function isAddition($exceptionType) {
    return $exceptionType == 1;
  }

  // never construct classes for non-running services
  public function isRunning($time) {
    return true;
  }
}

class GTFSDatabaseTransitSegment extends TransitSegment {

  private $route;
  
  // for frequency-based segments
  private $firstTripTime = NULL;
  private $firstTripFrequency = 0;
  
  // for stop-time based segments
  private $firstStopTime = NULL;
  private $secondStopTime = NULL;
  
  // maintain a reference to the route so we can make queries through it
  public function __construct($id, $name, $service, $direction, $route) {
    parent::__construct($id, $name, $service, $direction);
    $this->route = $route;
    $this->loadFrequencies();
  }
  
  public function getFirstStopTime() {
    return $this->firstStopTime;
  }
  
  public function getFirstTripFrequency() {
    return $this->firstTripFrequency;
  }
  
  public function getFirstTripTime() {
    return $this->firstTripTime;
  }
  
  private function loadFrequencies() {
    $sql = 'SELECT *'
          .'  FROM frequencies'
          ." WHERE trip_id = ?";
    $params = array($this->getID());
    $result = $this->route->query($sql, $params);
    $firstTrip = 999999;
    $firstFrequency = 0;
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
      $startTT = TransitTime::createFromString($row['start_time']);
      $endTT = TransitTime::createFromString($row['end_time']);
      $frequency = $row['headway_secs'];
      
      if ($startTT < $firstTrip) {
        $firstTrip = $startTT;
        $firstFrequency = intval($frequency);
      }

      $this->addFrequency($startTT, $endTT, $frequency);
    }

    if ($firstTrip != 999999) {
      $this->firstTripTime = $firstTrip;
    }

    if ($firstFrequency != 0) {
      $this->firstTripFrequency = $firstFrequency;
    }
    
    if (!$this->hasFrequencies()) { // this function works after the above sql query
      $sql = 'SELECT MIN(stop_sequence) FROM stop_times WHERE trip_id = ?';
      $params = array($this->getID());
      $result = $this->route->query($sql, $params);
      if ($row = $result->fetch(PDO::FETCH_NUM)) {
        $sequence = $row[0];
        $sql = 'SELECT departure_time'
              .'  FROM stop_times'
              .' WHERE stop_sequence = ?'
              .'   AND trip_id = ?';
        $params = array($sequence, $this->getID());
        $result = $this->route->query($sql, $params);
        if (!$row = $result->fetch(PDO::FETCH_ASSOC)) {
          return 0;
        }
        $this->firstStopTime = $row['departure_time'];
      }
    }
  }
  
  public function getFrequency($time) {
    // we can call hasFrequencies as soon as the above is finished
    if (!$this->hasFrequencies()) {
      if ($this->secondStopTime === NULL) {

        $sql = 'SELECT s.departure_time AS departure_time'
              .'  FROM stop_times s, trips t'
              .' WHERE s.stop_sequence = 1'
              ."   AND t.route_id = ?"
              .'   AND s.trip_id = t.trip_id'
              ."   AND s.departure_time > ?"
              .' ORDER BY s.departure_time';
        $params = array($this->route->getID(), $this->firstStopTime);
        $result = $this->route->query($sql, $params);
        if ($row = $result->fetch()) {
          $this->secondStopTime = $row['departure_time'];
        } else {
          $sql = str_replace('>', '<', $sql) . ' DESC';
          $result = $this->route->query($sql, $params);
          if ($row = $result->fetch()) {
            $this->secondStopTime = $this->firstStopTime;
            $this->firstStopTime = $row['departure_time'];
          }
        }
      }
      
      if (isset($this->firstStopTime) && isset($this->secondStopTime)) {
        $startTT = TransitTime::createFromString($this->firstStopTime);
        $endTT = TransitTime::createFromString($this->secondStopTime);
        return $endTT - $startTT;
      }

      return 0;

    } else {
      return parent::getFrequency($time);
    }
  }
  
  public function isRunning($time) {
    if ($this->hasPredictions())
      return true;
  
    if ($this->hasFrequencies()) {
      // parent's loop works since we always populate frequencies
      foreach ($this->frequencies as $index => $frequencyInfo) {
        if (TransitTime::isTimeInRange($time, $frequencyInfo['start'], $frequencyInfo['end'])) {
          return true;
        }
      }
      
    } else {
      if (!isset($this->firstStopTime)) {
        Kurogo::log(LOG_WARNING, 'Segment '.$this->getID().' has no stop times', 'transit');
        return false;
      }
      
      // for now just use departure time (as opposed to arrival time)
      $sql = 'SELECT departure_time'
            .'  FROM stop_times'
            ." WHERE trip_id = ?"
            .' ORDER BY stop_sequence DESC'; // not sure if it's better to sort on departure_time
      $params = array($this->getID());
      $result = $this->route->query($sql, $params);
      $firstTT = TransitTime::createFromString($this->firstStopTime);
      $lastRow = $result->fetch(PDO::FETCH_ASSOC); // discard rest of results
      $lastTT = TransitTime::createFromString($lastRow['departure_time']);
      return TransitTime::isTimeInRange($time, $firstTT, $lastTT);
    }
    return false;
  }
  
  public function getStops() {
    if (!count($this->stops)) {
      $now = TransitTime::getCurrentTime();

      $sql = 'SELECT arrival_time, departure_time, stop_id, stop_sequence'
            .'  FROM stop_times'
            ." WHERE trip_id = ?"
            .' ORDER BY stop_sequence';
      $params = array($this->getID());
      $result = $this->route->query($sql, $params);
      while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $stopIndex = intval($row['stop_sequence']);
        $arrivesTT = TransitTime::createFromString($row['arrival_time']);
        $departsTT = TransitTime::createFromString($row['departure_time']);
        $stopInfo = array(
          'stopID' => $row['stop_id'],
          'i' => $stopIndex,
          'arrives' => $arrivesTT,
          'departs' => $departsTT,
          'hasTiming' => true,
          );
        $this->stops[] = $stopInfo;
      }
    }
    
    return $this->stops;
  }

}

class GTFSDatabaseTransitRoute extends TransitRoute {

  public function query($sql, $params=array()) {
    return GTFSDatabaseTransitDataParser::dbQuery($this->getAgencyID(), $sql, $params);
  }

  public function isRunning($time, &$inService=null, &$runningSegmentNames=null) {
    $isRunning = false;
    $inService = false;
    $runningSegmentNames = array();

    $this->getDirections();
    foreach ($this->directions as $direction) {
      foreach ($direction['segments'] as $segment) {
        $inService = true; // GTFSDatabaseTransitService objects are only created if they are in service
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
    $runningSegmentNames = array_values($runningSegmentNames);
    return $isRunning;
  }
  
  public function getServiceFrequency($time) {
    // Time between shuttles at the same stop
    $frequency = 0;
    $firstTripTime = 999999;
    $firstSegment = NULL;
    
    if ($this->segmentsUseFrequencies()) {
      foreach ($this->directions as $direction) {
        foreach ($direction['segments'] as $segment) {
          if ($segment->isRunning($time)) {
            $frequency = $segment->getFrequency($time);
            if ($frequency > 0) { break; }
          }
          if ($frequency > 0) { break; }

          if (($aTripTime = $segment->getFirstTripTime()) < $firstTripTime) {
            $firstTripTime = $aTripTime;
            $firstSegment = $segment;
          }
          
        }
        if ($frequency > 0) { break; }
      }
      
      if ($frequency == 0) {
        $frequency = $segment->getFirstTripFrequency();
      }

    } else {
      // if nothing is running, these will be populated.
      // relying on the fact that only in-service segments are ever created
      $firstStopTime = '99:99:99';
      $secondStopTime = '99:99:99';
    
      $this->getDirections();
      foreach ($this->directions as $direction) {
        foreach ($direction['segments'] as $segment) {
          if ($segment->isRunning($time)) {
            $frequency = $segment->getFrequency($time);
            if ($frequency > 0) { break; }
          }
          if ($frequency > 0) { break; }
          if (($aStopTime = $segment->getFirstStopTime()) < $firstStopTime) {
            $firstStopTime = $aStopTime;
          }
          else if ($aStopTime < $secondStopTime) {
            $secondStopTime = $aStopTime;
          }
        }
        if ($frequency > 0) { break; }
      }

      if ($frequency == 0 && $firstStopTime != '99:99:99' && $secondStopTime != '99:99:99') {
        $startTT = TransitTime::createFromString($firstStopTime);
        $endTT = TransitTime::createFromString($secondStopTime);
        $frequency = $endTT - $startTT;
      }
    }
    
    return $frequency;
  }
  
  public function getDirections() {
    if (!count($this->directions)) {
      $now = TransitTime::getCurrentTime();
      $datetime = TransitTime::getLocalDatetimeFromTimestamp($now);
      
      $date = $datetime->format('Ymd');
      $dayOfWeek = strtolower($datetime->format('l'));
      
      $segments = array();
      
      // exceptions in calendar_dates take precedence, so query this first
      $additions = array();
      $exceptions = array();
      $sql = 'SELECT t.service_id AS service_id, c.exception_type AS exception_type'
            .'  FROM trips t, calendar_dates c'
            ." WHERE route_id = ?"
            .'   AND t.service_id = c.service_id'
            ."   AND c.date = ?";
      $params = array($this->getID(), $date);
      $result = $this->query($sql, $params);
      $additionClause = '';

      $params = array($this->getID());
      while ($row = $result->fetch()) {
        $params[] = $row['service_id'];
        if (GTFSDatabaseTransitService::isAddition($row['exception_type'])) {
          $additionClause .= 't.service_id = ? OR ';
        } else {
          $exceptions[] = 't.service_id <> ?';
        }
      }
      $exceptionClause = count($exceptions) ? ' AND ('.implode(' OR ', $exceptions).')' : '';
      $params[] = $date; // start_date
      $params[] = $date; // end_date

      // get all segments that run today regardless of what time it is
      // presence of a segment indicates the route is in service
      $services = array();
      $sql = 'SELECT t.trip_id AS trip_id, t.service_id AS service_id, t.trip_headsign AS trip_headsign, t.direction_id AS direction_id'
            .'  FROM trips t, calendar c'
            .' WHERE route_id = ?'
            .'   AND t.service_id = c.service_id'
            .$exceptionClause
            .'   AND ('
            .$additionClause
            ."(c.$dayOfWeek = 1 AND c.start_date <= ? AND c.end_date >= ?))";
      $result = $this->query($sql, $params);

      while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $serviceID = $row['service_id'];
        $direction = ($row['direction_id'] === NULL) ? 'loop' : $row['direction_id'];
        if (!isset($services[$serviceID])) {
          $services[$serviceID] = new GTFSDatabaseTransitService($serviceID);
        }
        $segment = new GTFSDatabaseTransitSegment(
          $row['trip_id'],
          $row['trip_headsign'],
          $services[$serviceID],
          $direction,
          $this
          );
        $this->addSegment($segment);
      }
    }
    
    return parent::getDirections();
  }
  
  public function getDirection($id) {
    $this->getDirections();
    return parent::getDirection($id);
  }
  
  public function getSegmentsForDirection($direction) {
    $this->getDirections(); // make sure directions are populated
    return parent::getSegmentsForDirection($direction);
  }
}
