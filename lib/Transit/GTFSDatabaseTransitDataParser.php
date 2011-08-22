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
  protected static $agencyMap = array();
  protected $agency;
  
  public static function getDB($agencyID) {
    $agency = self::$agencyMap[$agencyID];
  
    if (!isset(self::$dbRefs[$agency])) {
      $file = self::$gtfsPaths[$agency];
      //error_log($file);
      if (!file_exists($file)) {
        error_log("no GTFS db at '$file'");
        return;
      }

      $db = new PDO('sqlite:'.$file);
      if (!$db) {
        error_log("could not open db at '$file'");
        return;
      }
      self::$dbRefs[$agency] = $db;
    }
    return self::$dbRefs[$agency];
  }
  
  public static function dbQuery($agencyID, $sql, $params) {
    //error_log($sql);
    $db = self::getDB($agencyID);
    if (!$result = $db->prepare($sql)) {
      error_log("failed to prepare statement: $sql");
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    if (!$result->execute($params)) {
      error_log("failed to execute statement with parameters"
        .print_r($params, true).": $sql");
      error_log(print_r($db->errorInfo(), true));
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
        error_log("error fetching stop: ".print_r($db->errorInfo(),true));
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
      error_log("error fetching stop info: ".print_r($db->errorInfo(),true));
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
    $agencyIDs = explode(',', $this->args['agencies']);

    if (!count($agencyIDs)) {
      error_log("no agency IDs found");
      return;
    }
  
    $this->agency = $agencyIDs[0];
    foreach ($agencyIDs as $agencyID) {
      self::$agencyMap[$agencyID] = $this->agency;
    }
    $dbfile = $this->args['db'];
    self::$gtfsPaths[$this->agency] = Kurogo::getSiteVar('GTFS_DIR').'/'.$dbfile;
    
    $sql = "SELECT * from routes";
    $result = $this->query($sql);
    if (!$result) {
      error_log('could not load routes');
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
      
      // Agency ID is optional in routes table
      if (!$row['agency_id']) {
        $row['agency_id'] = $this->agency;
      }
      
      $route = new GTFSDatabaseTransitRoute(
        $routeID,
        $row['agency_id'],
        $routeName,
        $row['route_desc'] // may be null
        );
  
      $this->addRoute($route);
    }
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
        error_log('Warning! Segment '.$this->getID().' has no stop times');
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
