<?php

/**
  * GTFSDataModel
  * @package Transit
  */

// TODO for this class
// - abstract away db engine
// - determine value of keeping fetched routes and stops in memory
class GTFSDataModel extends TransitDataModel
{
    protected $DEFAULT_PARSER_CLASS = 'PassthroughDataParser';
    protected $DEFAULT_RETRIEVER_CLASS = 'DatabaseDataRetriever';
    
    protected function init($args) {
        if (!isset($args['DB_FILE'])) {
            throw new KurogoConfigurationException("No database file found for gtfs parser in feeds.ini");
        }

        if (!isset($args['DB_TYPE'])) {
            $args['DB_TYPE'] = 'sqlite';
        }
        
        parent::init($args);
    }

    public function query($sql, $params=array()) {
        //error_log($sql);
        $this->retriever->setSQL($sql);
        $this->retriever->setParameters($params);
        return $this->retriever->getData();
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
            } else {
                $row = $result->fetch(PDO::FETCH_ASSOC);
                $this->addStop(new TransitStop(
                    $row['stop_id'],
                    $row['stop_name'], // may be null
                    $row['stop_desc'], // may be null
                    $row['stop_lat'],
                    $row['stop_lon']
                    ));
            }
        }
        
        return parent::getStop($id);
    }
    
    public function getStopInfoForRoute($routeID, $stopID) {
        // ensure the data required by TransitDataModel is loaded
        $this->getStop($stopID);
        
        return parent::getStopInfoForRoute($routeID, $stopID);
    }
    
    // used to avoid warnings when looking at the wrong agency
    public function hasStop($id) {
        // ensure the data required by TransitDataModel is loaded
        $this->getStop($id);

        return isset($this->stops[$id]);
    }
    
    public function getStopInfo($stopID) {
        $stopInfo = array();
        
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
        } else {
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
        }
        
        return $stopInfo;
    }
    
    protected function getRouteName($row) {
        $names = array();
        if (isset($row['route_short_name']) && $row['route_short_name']) {
            $names[] = $row['route_short_name'];
        }
        if (isset($row['route_long_name']) && $row['route_long_name']) {
            $names[] = $row['route_long_name'];
        }
        
        return $names ? implode(' ', $names) : $row['route_id'];
    }
    
    protected function loadData() {
        $agencyID = reset($this->agencyIDs);
        
        $sql = "SELECT * from routes";
        $result = $this->query($sql);
        if (!$result) {
            Kurogo::log(LOG_ERR, 'could not load routes: '.print_r($db->errorInfo(), true), 'transit');
        } else {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $agencyID = isset($row['agency_id']) && in_array($row['agency_id'], $this->agencyIDs) ? 
                    $row['agency_id'] : reset($this->agencyIDs);
                    
                $route = new GTFSTransitRoute(
                    $row['route_id'],
                    $agencyID,
                    $this->getRouteName($row),
                    $row['route_desc'], // may be null
                    $this
                );
                
                $this->addRoute($route);
            }
        }
    }
}

class GTFSTransitSegment extends TransitSegment
{
    protected $route = null;
    
    // for frequency-based segments
    protected $firstTripTime = NULL;
    protected $firstTripFrequency = 0;
    
    // for stop-time based segments
    protected $firstStopTime = NULL;
    protected $secondStopTime = NULL;
    
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
    
    protected function loadFrequencies() {
        $sql = 'SELECT *'
                    .'  FROM frequencies'
                    ." WHERE trip_id = ?";
        $params = array($this->getID());
        $result = $this->route->query($sql, $params);
        $firstTrip = 999999;
        $firstFrequency = 0;
        if (!$result) {
            Kurogo::log(LOG_ERR, 'could not load frequencies: '.print_r($db->errorInfo(), true), 'transit');
        } else {
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
            if ($result) {
                if ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $sequence = $row[0];
                    $sql = 'SELECT departure_time'
                                .'  FROM stop_times'
                                .' WHERE stop_sequence = ?'
                                .'   AND trip_id = ?';
                    $params = array($sequence, $this->getID());
                    $result = $this->route->query($sql, $params);
                    if (!$result) {
                        Kurogo::log(LOG_ERR, 'could not load stop times: '.print_r($db->errorInfo(), true), 'transit');
                    } else {
                        if (!$row = $result->fetch(PDO::FETCH_ASSOC)) {
                            return 0;
                        }
                        $this->firstStopTime = $row['departure_time'];
                    }
                }
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
        if ($this->hasPredictions()) {
            return true;
        }
        
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
            if (!$result) {
                Kurogo::log(LOG_ERR, 'could not load stop times: '.print_r($db->errorInfo(), true), 'transit');
            } else {
                $firstTT = TransitTime::createFromString($this->firstStopTime);
                $lastRow = $result->fetch(PDO::FETCH_ASSOC); // discard rest of results
                $lastTT = TransitTime::createFromString($lastRow['departure_time']);
                return TransitTime::isTimeInRange($time, $firstTT, $lastTT);
            }
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
            if (!$result) {
                Kurogo::log(LOG_ERR, 'could not load stops: '.print_r($db->errorInfo(), true), 'transit');
            } else {
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
        }
        
        return $this->stops;
    }

}

class GTFSTransitRoute extends TransitRoute
{
    protected $model = null;

    function __construct($id, $agencyID, $name, $description, $model) {
        parent::__construct($id, $agencyID, $name, $description);
        $this->model = $model;
    }
    
    public function query($sql, $params=array()) {
        return $this->model->query($sql, $params);
    }

    public function isRunning($time, &$inService=null, &$runningSegmentNames=null) {
        $isRunning = false;
        $inService = false;
        $runningSegmentNames = array();

        $this->getDirections();
        foreach ($this->directions as $directionID => $direction) {
            foreach ($direction['segments'] as $segment) {
                $inService = true; // if a segment exists it is in service
                if ($segment->isRunning($time)) {
                    $name = $segment->getName();
                    if (isset($name) && !isset($runningSegmentNames[$name])) {
                        //error_log("   Route ".$this->getName()." has named running segment '$name' (direction '$directionID')");
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
                if ($row['exception_type'] == 1) {
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

            if (!$result) {
                Kurogo::log(LOG_ERR, 'could not load directions: '.print_r($db->errorInfo(), true), 'transit');
            } else {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $serviceID = $row['service_id'];
                    $direction = ($row['direction_id'] === NULL) ? 'loop' : $row['direction_id'];
                    if (!isset($services[$serviceID])) {
                        $services[$serviceID] = new TransitService($serviceID, true /* always running */);
                    }
                    $segment = new GTFSTransitSegment(
                        $row['trip_id'],
                        $row['trip_headsign'],
                        $services[$serviceID],
                        $direction,
                        $this
                        );
                    $this->addSegment($segment);
                }
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
