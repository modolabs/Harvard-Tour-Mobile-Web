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
            if ($result) {
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
    
    // used to avoid warnings when looking at the wrong agency
    public function hasStop($id) {
        // ensure the data required by TransitDataModel is loaded
        $this->getStop($id);

        return isset($this->stops[$id]);
    }
    
    public function getStopInfo($stopID) {
        $stopInfo = array();
        
        $time = TransitTime::getCurrentTime();

        // get all route IDs associated with this stop.
        $sql = "SELECT DISTINCT t.route_id AS route_id"
                    ."  FROM stop_times s, trips t"
                    ." WHERE s.stop_id = ?"
                    ."   AND s.trip_id = t.trip_id";
        $params = array($stopID);
        $result = $this->query($sql, $params);
        if ($result) {
            // rest of this function is mostly like the parent
            // but we call this->getRoute and this->getStop
            $routePredictions = array();
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $routeID = $row['route_id'];
                $timestampRange = $this->getValidTimeRangeForRouteTimestamp($routeID, $time);
                $route = $this->getRoute($routeID);
                if ($route) {
                    $this->updatePredictionData($routeID);
                    
                    $routePredictions[$routeID]['directions'] = $this->getRouteDirectionPredictionsForStop($routeID, $stopID, $timestampRange);
                    $routePredictions[$routeID]['running'] = $route->isRunning($timestampRange, $inService) && $inService;
                    $routePredictions[$routeID]['name'] = $route->getName();
                    $routePredictions[$routeID]['agency'] = $route->getAgencyID();
                    $routePredictions[$routeID]['live'] = $this->isLive();
                }
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
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if (!$this->viewRoute($row['route_id'])) { continue; }
                
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

    protected $startTT = null;
    protected $endTT = null;

    // for stop-time based segments
    protected $firstTripFrequency = null;
    protected $firstStopTime = NULL;
    
    // maintain a reference to the route so we can make queries through it
    public function __construct($id, $name, $service, $direction, $route) {
        parent::__construct($id, $name, $service, $direction);
        $this->route = $route;
        
        // Set up ranges which are needed for other calls like isRunning()
        $sql = "SELECT * FROM frequencies WHERE trip_id = ?";
        $params = array($this->getID());
        $result = $this->route->query($sql, $params);
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $startTT = TransitTime::createFromString($row['start_time']);
                $endTT = TransitTime::createFromString($row['end_time']);
                $frequency = $row['headway_secs'];

                if ($this->startTT === null || $startTT < $this->startTT) {
                    $this->startTT = $startTT;
                    $this->firstStopTime = $row['start_time'];
                }

                if ($this->endTT === null || $endTT > $this->endTT) {
                    $this->endTT = $endTT;
                }
                
                $this->addFrequency($startTT, $endTT, $frequency);
            }
            if ($this->frequencies) {
                usort($this->frequencies, array(get_class($this), 'frequencyCompare'));
            }
        }
        
        if (!$this->hasFrequencies()) { // this function works after the above sql query
            $sql = "SELECT * FROM stop_times WHERE trip_id = ? ORDER BY stop_sequence";
            $params = array($this->getID());
            $result = $this->route->query($sql, $params);
            if ($result) {
                while ($row = $result->fetch()) {
                    $arrivalTT = TransitTime::createFromString($row['arrival_time']);
                    $departureTT = TransitTime::createFromString($row['departure_time']);

                    if (!$departureTT) {
                        $departureTT = $arrivalTT;
                    }

                    if ($this->startTT === null || $arrivalTT < $this->startTT) {
                        $this->startTT = $arrivalTT;
                        $this->firstStopTime = $row['arrival_time'];
                    }

                    if ($this->endTT === null || $departureTT > $this->endTT) {
                        $this->endTT = $departureTT;
                    }
                }
            }
        }
    }
    
    public function getFirstStopTime() {
        return $this->firstStopTime;
    }

    public function getFirstTripFrequency() {
        return $this->firstTripFrequency;
    }

    private static function frequencyCompare($a, $b) {
        if ($a['start'] < $b['start']) {
            return -1;
        }
        if ($a['start'] > $b['start']) {
            return 1;
        }
        return 0;
    }
    
    public function getFrequency($timestampRange) {
        // we can call hasFrequencies as soon as the above is finished
        if (!$this->hasFrequencies()) {
            // for frequency-based trips, $this->firstTripFrequency corresponds
            // to headway_secs in the frequencies.txt file. since we aren't
            // using frequencies.txt, we can overload firstTripFrequency to use
            // as the time between us and the next segment.
            if (!$this->firstTripFrequency) {
                $sql = 'SELECT s.departure_time, s.stop_id, t.*'
                      .'  FROM stop_times s, trips t'
                      .' WHERE t.route_id = ?'
                      .'   AND s.trip_id = t.trip_id'
                      ."   AND s.departure_time > ?"
                      .' ORDER BY s.departure_time';
                $params = array($this->route->getID(), $this->firstStopTime);

                // find the time difference between two departures from the same stop.
                // since not all trips pass through all stops, find the first stop
                // that is repeated.
                $firstTripTimes = array();
                $result = $this->route->query($sql, $params);
                while ($row = $result->fetch()) {
                    $stop = $row['stop_id'];
                    if (isset($row['direction_id'])) {
                        // if multiple directions provide the same stop, we only want
                        // the time interval between stops on the same direction
                        $stop .= '@@'.$row['direction_id'];
                    }
                    if (isset($firstTripTimes[$stop])) {
                        $startTT = TransitTime::createFromString($firstTripTimes[$stop]);
                        $endTT = TransitTime::createFromString($row['departure_time']);
                        $this->firstTripFrequency = TransitTime::getDifferenceInSeconds($startTT, $endTT);
                        break;
                    }
                    $firstTripTimes[$stop] = $row['departure_time'];
                }
            }
            return $this->firstTripFrequency;

        } else {
            return parent::getFrequency($timestampRange);
        }
    }

    public function isRunning($validTimeRange) {
        return TransitTime::timesRangeIsValidForTimestampRange($this->startTT, $this->endTT, $validTimeRange);
    }
    
    public function getStops() {
        if (!count($this->stops)) {
            $sql = 'SELECT arrival_time, departure_time, stop_id, stop_sequence'
                        .'  FROM stop_times'
                        ." WHERE trip_id = ?"
                        .' ORDER BY stop_sequence';
            $params = array($this->getID());
            $result = $this->route->query($sql, $params);
            if ($result) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $stopIndex = intval($row['stop_sequence']);
                    $arrivesOffsetTT = TransitTime::createFromString($row['arrival_time']);
                    $departsOffsetTT = TransitTime::createFromString($row['departure_time']);
                    
                    $stopInfo = array(
                        'stopID' => $row['stop_id'],
                        'i' => $stopIndex,
                        'arrives' => TransitTime::createFromString($row['arrival_time']),
                        'departs' => TransitTime::createFromString($row['departure_time']),
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

    private $hasFrequenciesTable;
    private $segmentStatesById = array();

    function __construct($id, $agencyID, $name, $description, $model) {
        parent::__construct($id, $agencyID, $name, $description);
        $this->model = $model;
    }
    
    public function query($sql, $params=array()) {
        return $this->model->query($sql, $params);
    }

    public function isRunning($timestampRange, &$inService=null) {
        $inService = false;
        
        $this->getDirections();
        foreach ($this->directions as $directionID => $direction) {
            foreach ($direction['segments'] as $segment) {
                $inService = true; // if a segment exists it is in service
                TransitDataModel::dlog("Route {$this->id} ({$this->name}) is in service", TransitDataModel::DLOG_IS_RUNNING);
                
                if ($segment->isRunning($timestampRange)) {
                    TransitDataModel::dlog("Route {$this->id} ({$this->name}) is running", TransitDataModel::DLOG_IS_RUNNING);
                    return true;
                }
            }
        }
        
        TransitDataModel::dlog("Route {$this->id} ({$this->name}) is not running", TransitDataModel::DLOG_IS_RUNNING);
        return false;
    }
    
    public function getServiceFrequency($timestampRange, $transitMaxArrivalDelay) {
        // Time between shuttles at the same stop
        $frequency = 0;
        
        foreach ($this->directions as $direction) {
            foreach ($direction['segments'] as $segment) {
                if ($segment->isRunning($timestampRange)) {
                    $frequency = $segment->getFrequency($timestampRange);
                    if ($frequency > 0) { break; }
                }
                if ($frequency > 0) { break; }
            }
            if ($frequency > 0) { break; }
        }
        
        return $frequency;
    }
    
    public function getDirections() {
        if (!count($this->directions)) {
            $timestampRange = array(TransitTime::getCurrentTime(), TransitTime::getCurrentTime());
            $datetime = TransitTime::getLocalDatetimeFromTimestampRange($timestampRange);
            
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
            // the same service id will get returned multiple times
            // so build up arrays of them and then unique before
            // creating the query
            $additionalServices = array();
            $exceptionServices = array();
            while ($row = $result->fetch()) {
                if ($row['exception_type'] == 1) {
                    $additionalServices[] = $row['service_id'];
                } else {
                    $exceptionServices[] = $row['service_id'];
                }
            }

            foreach (array_unique($additionalServices) as $serviceID) {
                $params[] = $serviceID;
                $additionClause .= 't.service_id = ? OR ';
            }
            foreach (array_unique($exceptionServices) as $serviceID) {
                $params[] = $serviceID;
                $exceptions[] = 't.service_id <> ?';
            }
            $exceptionClause = count($exceptions) ? ' AND ('.implode(' OR ', $exceptions).')' : '';
            $params[] = $date; // start_date
            $params[] = $date; // end_date

            // get all segments that run today regardless of what time it is
            // presence of a segment indicates the route is in service
            $services = array();

            // trip_id, service_id are required
            // trip_headsign, direction_id appear optionally
            $sql = 'SELECT t.*'
                  .'  FROM trips t, calendar c'
                  .' WHERE route_id = ?'
                  .'   AND t.service_id = c.service_id'
                  .$exceptionClause
                  .'   AND ('
                  .$additionClause
                  ."(c.$dayOfWeek = 1 AND c.start_date <= ? AND c.end_date >= ?))";
            $result = $this->query($sql, $params);
            if ($result) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $segmentID = $row['trip_id'];

                    $serviceID = $row['service_id'];
                    $direction = !isset($row['direction_id']) ? TransitDataModel::LOOP_DIRECTION : $row['direction_id'];
                    $headsign = isset($row['trip_headsign']) ? $row['trip_headsign'] : null;
                    if (!isset($services[$serviceID])) {
                        $services[$serviceID] = new TransitService($serviceID, true /* always running */);
                    }
                    $segment = new GTFSTransitSegment(
                        $segmentID,
                        $headsign,
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
