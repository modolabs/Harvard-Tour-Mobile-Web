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
  * NextBusDataModel
  * @package Transit
  */

class NextBusDataModel extends TransitDataModel
{
    protected $DEFAULT_PARSER_CLASS = 'NextBusDataParser';
    protected $DEFAULT_RETRIEVER_CLASS = 'NextBusDataRetriever';

    protected $pathCache = null;
    protected $predictionDataLoaded = array();
    protected $nextBusPathCacheLifetime = 86400;
    
    protected function init($args) {
        if (isset($args['NEXTBUS_ROUTE_CACHE_LIFETIME'])) {
            $this->nextBusPathCacheLifetime = $args['NEXTBUS_ROUTE_CACHE_LIFETIME'];
        }
        
        // daemons should load cached files aggressively to beat user page loads
        if (defined('KUROGO_SHELL')) {
            TransitDataModel::updateCacheLifetimeForShell($this->nextBusPathCacheLifetime);
        }
        
        $this->pathCache = DataCache::factory('DataCache', $args);
        $this->pathCache->setCacheGroup('NextBus');
        $this->pathCache->setCacheLifetime($this->nextBusPathCacheLifetime);
        
        parent::init($args);
    }
    
    protected function isLive() {
        return true;
    }
    
    protected function getServiceName() {
        return 'NextBus';
    }
    
    protected function getServiceId() {
        return 'nextbus';
    }
    
    protected function getServiceLink() {
        return isset($this->initArgs['EXTERNAL_SERVICE_URL']) ? 
            $this->initArgs['EXTERNAL_SERVICE_URL'] : 'http://www.nextbus.com/';
    }
    
    public function getRouteVehicles($routeID) {
        $route = $this->getRoute($routeID);
        if (!$route) { return array(); }
        
        $vehicles = array();

        $xml = $this->queryNextBus('vehicleLocations', $route->getAgencyID(), array(
            'r' => $routeID,
            't' => '0',
        ));
        
        if ($xml) {
            foreach ($xml->getElementsByTagName('vehicle') as $vehicle) {
                $attributes = $vehicle->attributes;
                
                $vehicleID = $attributes->getNamedItem('id')->nodeValue;
                $vehicles[$vehicleID] = array(
                    'secsSinceReport' => 
                                intval($attributes->getNamedItem('secsSinceReport')->nodeValue),
                    'lat'      => $attributes->getNamedItem('lat')->nodeValue,
                    'lon'      => $attributes->getNamedItem('lon')->nodeValue,
                    'heading'  => $attributes->getNamedItem('heading')->nodeValue,
                    'speed'    => $attributes->getNamedItem('speedKmHr')->nodeValue,
                    'agency'   => $route->getAgencyID(),
                    'routeID'  => $routeID,
                );
                
                $vehicles[$vehicleID]['iconURL'] = 
                    $this->getMapIconUrlForRouteVehicle($routeID, $vehicles[$vehicleID]);
                
                if ($this->viewRouteAsLoop($routeID)) {
                    $vehicles[$vehicleID]['directionID'] = self::LOOP_DIRECTION;
                    
                } else if ($attributes->getNamedItem('dirTag')) {
                    $directionID = $this->getDirectionID($routeID, $attributes->getNamedItem('dirTag')->nodeValue);
                    if (in_array($directionID, $route->getDirections())) {
                        $vehicles[$vehicleID]['directionID'] = $directionID;
                    }
                } else {
                     $vehicles[$vehicleID]['directionID'] = '';
                }
            }
        }
        
        return $vehicles;
    }

    protected function updatePredictionData($routeID) {
        if (isset($this->predictionDataLoaded[$routeID])) {
            return; // already loaded
        }
        
        $route = $this->getRoute($routeID);
        if (!$route) { return; }
        
        $stopList = array();
        foreach ($route->getStops() as $stop) {
            $stopList[] = $routeID.'|null|'.$stop['stopID'];
        }
        
        if (count($stopList)) {
            $age = 0;
            $xml = $this->queryNextBus('predictionsForMultiStops', 
                $route->getAgencyID(), array('stops' => $stopList ), $age);
            
            if ($xml) {
                $routePredictions = array();

                foreach ($xml->getElementsByTagName('predictions') as $predictions) {
                    $stopID = $predictions->attributes->getNamedItem('stopTag')->nodeValue;
    
                    foreach ($predictions->getElementsByTagName('prediction') as $prediction) {
                        $attributes = $prediction->attributes;
                        $directionID = $this->getDirectionID($routeID, $attributes->getNamedItem('dirTag')->nodeValue);
                        
                        if (!isset($routePredictions[$directionID])) {
                            $routePredictions[$directionID] = array();
                        }
                        if (!isset($routePredictions[$directionID][$stopID])) {
                            $routePredictions[$directionID][$stopID] = array();
                        }
                        $routePredictions[$directionID][$stopID][] = 
                            intval($attributes->getNamedItem('seconds')->nodeValue) + $age + time();
                    }
                    unset($prediction);
                }
                unset($predictions);
                
                foreach ($routePredictions as $directionID => $directionPredictions) {
                    foreach ($directionPredictions as $stopID => $stopPredictions) {
                        $route->setStopPredictions($directionID, $stopID, $stopPredictions);
                    }
                }
            }
        }
        $this->predictionDataLoaded[$routeID] = true;
    }

    protected function loadData() {
        foreach ($this->agencyIDs as $agencyID) {
            //error_log("NextBus loading ".str_pad($agencyID, 20)." memory_get_usage(): ".memory_get_usage());
            
            $xml = $this->queryNextBus('routeList', $agencyID);
            
            if (!$xml) { continue; }
            
            $foundStops = array();
            
            foreach ($xml->getElementsByTagName('route') as $route) {
                $routeID = $route->attributes->getNamedItem('tag')->nodeValue;
                
                if (!$this->viewRoute($routeID)) { continue; }
                
                $this->addRoute(new TransitRoute(
                    $routeID, 
                    $agencyID, 
                    $route->attributes->getNamedItem('title')->nodeValue, 
                    '' // NextBus does not provide a description
                ));
                
                $xml = $this->queryNextBus('routeConfig', $agencyID, array( 'r' => $routeID ));
                if (!$xml) {
                    continue;
                }
                
                // Add the stops
                $stopOrder = array();
                foreach ($xml->getElementsByTagName('stop') as $stop) {
                    $attributes = $stop->attributes;
                    if (!$attributes->getNamedItem('title')) {
                        continue;
                    }
                    
                    $stopID = $attributes->getNamedItem('tag')->nodeValue;
                    if (!isset($foundStops[$stopID])) {
                        $this->addStop(new TransitStop(
                            $stopID, 
                            $attributes->getNamedItem('title')->nodeValue, 
                            '', 
                            $attributes->getNamedItem('lat')->nodeValue, 
                            $attributes->getNamedItem('lon')->nodeValue
                        ));
                        $foundStops[$stopID] = true;
                    }
                    $stopOrder[$stopID] = count($stopOrder);
                }
                unset($stop);
                
                $directions = array();
                $serviceID = $routeID.'_service';
                $routeService = new TransitService($serviceID, true);

                // Add the segments
                foreach ($xml->getElementsByTagName('direction') as $direction) {
                    $attributes = $direction->attributes;
                    if ($attributes->getNamedItem('useForUI')->nodeValue != 'true') {
                        continue;
                    }
                    
                    $directionID = $this->getDirectionID($routeID, $attributes->getNamedItem('tag')->nodeValue);
                    
                    if (!isset($directions[$directionID])) {
                        $directions[$directionID] = array(
                            'route'     => $routeID,
                            'name'      => $attributes->getNamedItem('title')->nodeValue,
                            'service'   => $serviceID,
                            'stops'     => array(),
                        );
                    } else {
                        if (strlen($directions[$directionID]['name'])) {
                            $directions[$directionID]['name'] .= ' / ';
                        }
                        $directions[$directionID]['name'] .= $attributes->getNamedItem('title')->nodeValue;
                    }
                    
                    foreach ($direction->getElementsByTagName('stop') as $index => $stop) {
                        $stopID = $stop->attributes->getNamedItem('tag')->nodeValue;
                        $directions[$directionID]['stops'][$stopID] = array(
                            'stop'     => $stopID,
                            'sequence' => $stopOrder[$stopID], //$index,
                        );
                    }
                    unset($stop);
                }
                unset($direction);
                
                $paths = $this->getPaths($xml, $agencyID, $routeID, $directions);
                
                foreach ($paths as $pathIndex => $path) {
                    $this->getRoute($routeID)->addPath(new TransitPath($pathIndex, $path));
                }
                unset($path);

                foreach ($directions as $directionID => $direction) {
                    $segmentID = $directionID;
                    if ($segmentID === self::LOOP_DIRECTION) {
                        $segmentID = $routeID;
                    }
                    
                    $segment = new TransitSegment(
                        $segmentID,
                        $direction['name'],
                        $routeService,
                        $directionID
                    );
                    foreach ($direction['stops'] as $stopID => $stop) {
                        $segment->addStop($stopID, $stop['sequence']);
                        $segment->setStopPredictions($directionID, $stopID, array());
                    }
                    $this->getRoute($direction['route'])->addSegment($segment);

                    unset($stop);
                }
            }
            //error_log("NextBus loaded ".str_pad($agencyID, 20)."  memory_get_usage(): ".memory_get_usage());
        }
    }
    
    protected function getDirectionID($routeID, $tag) {
        $directionID = $tag;
        
        // MBTA has this silly version number in the middle of their direction ids
        // which is inconsistent between route config and predictions.  If the 
        // direction id matches the MBTA pattern, strip out the version number
        $parts = explode('_', $tag);
        if (count($parts) > 2) {
            $first = reset($parts);
            $last = end($parts);
            if ($first == $routeID && ($last == '0' || $last == '1')) {
                $directionID = $first.'_'.$last;
            }
        }
        
        return $directionID;
    }
    
    protected function mergePathsIfPointsMatch($path1, $path2) {
        $path1First = reset($path1);
        $path1Last = end($path1);
        $path2First = reset($path2);
        $path2Last = end($path2);
        
        if ($this->pointsEqual($path1Last, $path2First)) {
            return $this->getMergedPath($path1, $path2);
            
        } else if ($this->pointsEqual($path2Last, $path1First)) {
            return $this->getMergedPath($path2, $path1);
        }
        
        return false;
    }

    protected function getMergedPath($path1, $path2) {
        //error_log("Merging paths:");
        //$this->printPaths(array($path1, $path2));
        array_pop($path1);
        return array_merge($path1, $path2);
    }

    protected function pointsEqual($p1, $p2) {
        foreach ($p1 as $i => $c) {
            if (!isset($p2[$i]) || $p1[$i] != $p2[$i]) {
                return false;
            }
        }
        return true;
    }
    
    protected function pointsAreSubset($haystack, $needle) {
        $isSubset = false;
        
        $haystack = array_values($haystack);
        $needle = array_values($needle);
        
        for ($h = 0; $h < count($haystack); $h++) {
            if ($this->pointsEqual($haystack[$h], $needle[0])) {
                $isSubset = true;
                for ($n = 0; $n < count($needle); $n++) {
                    if (($h + $n) >= count($haystack) || 
                            !$this->pointsEqual($haystack[$h+$n], $needle[$n])) {
                        $isSubset = false;
                        break;
                    }
                }
                if ($isSubset) { break; }
            }
        }
        
        return $isSubset;
    }
    
    protected function printPaths($paths) {
        error_log("Paths:");
        foreach ($paths as $pathIndex => $path) {
            $index = str_pad($pathIndex, 35);
            $first = reset($path);
            $last = end($path);
            $firstPoint = str_pad($first['lat'].', ', 12).str_pad($first['lon'], 12);
            $lastPoint  = str_pad($last['lat'].', ', 12).str_pad($last['lon'], 12);
            error_log("    path $index ($firstPoint) -> ($lastPoint)");
        }
    }
    
    protected function getPaths(&$xml, $agencyID, $routeID, &$directions) {
        
        $paths = array();
        
        $cacheKey = 'paths_'.$agencyID.'_'.$routeID;
        
        if (!$paths = $this->pathCache->get($cacheKey)) {
            // Note: this code assumes that direction id strings all have the same length
            // We'd check for actual direction id matches but sometimes the MBTA 
            // uses direction ids which are no longer in the list of directions in 
            // the route config

            foreach ($xml->getElementsByTagName('path') as $path) {
                $points = array();
                foreach ($path->getElementsByTagName('point') as $point) {
                    $attributes = $point->attributes;
                    $points[] = array(
                        'lat' => $attributes->getNamedItem('lat')->nodeValue,
                        'lon' => $attributes->getNamedItem('lon')->nodeValue,
                    );
                }
                $paths[] = $points;
            }
            unset($point);
            unset($path);
            
            //$this->printPaths($paths);
            
            // Match up path segments by endpoint
            $foundPair = true;
            while ($foundPair) {
                $foundPair = false;
                foreach ($paths as $pathIndex => $path) {
                    foreach ($paths as $comparePathIndex => $comparePath) {
                        if ($pathIndex == $comparePathIndex) { continue; }
                        
                        $merged = $this->mergePathsIfPointsMatch($path, $comparePath);
                        if ($merged) {
                            $paths[] = $merged;
                            $foundPair = true;
                            
                            unset($paths[$pathIndex]);
                            unset($paths[$comparePathIndex]);
                            break;
                        }
                    }
                    if ($foundPair) { break; }
                }
                unset($path);
                unset($comparePath);
            }
            
            // Eliminate path subsets.
            // Not sure why these are here but NextBus sometimes has extra segments
            if (count($paths) > 1) {
                $foundSubset = true;
                while ($foundSubset) {
                    $foundSubset = false;
                    foreach ($paths as $pathIndex => $path) {
                        foreach ($paths as $comparePathIndex => $comparePath) {
                            if ($pathIndex == $comparePathIndex) { continue; }
                            
                            if (count($path) >= count($comparePath)) {
                                if ($this->pointsAreSubset($path, $comparePath)) {
                                    $foundSubset = true;
                                    unset($paths[$comparePathIndex]);
                                }
                            } else {
                                if ($this->pointsAreSubset($comparePath, $path)) {
                                    $foundSubset = true;
                                    unset($paths[$pathIndex]);
                                }
                            }
                        }
                        if ($foundSubset) { break; }
                    }
                    unset($path);
                    unset($comparePath);
                }
            }
            $paths = array_values($paths);
            
            $this->pathCache->set($cacheKey, $paths);
        }
        
        return $paths;
    }

    protected function queryNextBus($command, $agency, $params=array(), &$age=null) {
        $params['command'] = $command;
        $params['a'] = $agency;
        $this->retriever->setParameters($params);
        
        return $this->retriever->getDataAndAge($age);
    }
}
