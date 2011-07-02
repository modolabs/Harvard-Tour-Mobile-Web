<?php
/**
  * Transit Data View
  * @package Transit
  */

class TransitDataView {
  private $config = array();
  private $parsers = array();
  private $daemonMode = false;
  private static $viewCache = null;
  private $globalIDSeparator = null;
  
  private static function getViewCache() {
    if (!isset(self::$viewCache)) {
      self::$viewCache = new DiskCache(
        Kurogo::getSiteVar('TRANSIT_CACHE_DIR'),
        Kurogo::getSiteVar('TRANSIT_ROUTE_LIST_CACHE_TIMEOUT'), TRUE);
      self::$viewCache->preserveFormat();
      self::$viewCache->setSuffix(".json");
    } 
    return self::$viewCache;
  }
  
  function __construct($transitConfig, $daemonMode=false) {
    $this->config = $transitConfig;
    $this->daemonMode = $daemonMode;
    
     $this->globalIDSeparator = Kurogo::getOptionalSiteVar('TRANSIT_GLOBAL_ID_SEPARATOR', '__');
    
    foreach ($this->config->getParserIDs() as $parserID) {
      $parser = array(
        'system' => $this->config->getSystem($parserID),
        'live'   => false,
        'static' => false,
      );
    
      if ($this->config->hasLiveParser($parserID)) {
        $parser['live'] = TransitDataParser::factory(
          $this->config->getLiveParserClass($parserID),
          $this->config->getLiveParserArgs($parserID),
          $this->config->getLiveParserOverrides($parserID),
          $this->config->getLiveParserRouteWhitelist($parserID),
          $daemonMode
        ); 
      }
      
      if ($this->config->hasStaticParser($parserID)) {
        $parser['static'] = TransitDataParser::factory(
          $this->config->getStaticParserClass($parserID),
          $this->config->getStaticParserArgs($parserID),
          $this->config->getStaticParserOverrides($parserID),
          $this->config->getStaticParserRouteWhitelist($parserID),
          $daemonMode
        ); 
      }
      
      $this->parsers[$parserID] = $parser;
    }
  }
  
  public function refreshLiveParsers() {
    foreach ($this->config->getParserIDs() as $parserID) {
      if ($this->config->hasLiveParser($parserID)) {
      
        unset($this->parsers[$parserID]['live']);
        $this->parsers[$parserID]['live'] = TransitDataParser::factory(
          $this->config->getLiveParserClass($parserID), 
          $this->config->getLiveParserArgs($parserID),
          $this->config->getLiveParserOverrides($parserID),
          $this->config->getLiveParserRouteWhitelist($parserID),
          $daemonMode
        );
      }
    }
  }
  
  public function getStopInfoForRoute($globalRouteID, $globalStopID) {  
    $stopInfo = array();
    $cacheName = "stopInfoForRoute.$globalRouteID.$globalStopID";
    $cache = self::getViewCache();
    
    if ($cache->isFresh($cacheName) && !$this->daemonMode) {
      $stopInfo = json_decode($cache->read($cacheName), true);
      
    } else {
      list($system, $routeID) = $this->getRealID($globalRouteID);
      list($system, $stopID)  = $this->getRealID($globalStopID);
      $parser = $this->parserForRoute($system, $routeID);
      
      if ($parser['live']) {
        $stopInfo = $parser['live']->getStopInfoForRoute($routeID, $stopID);
      }
      
      if ($parser['static']) {
        $staticStopInfo = $parser['static']->getStopInfoForRoute($routeID, $stopID);
      }
      
      if (!$stopInfo) {
        $stopInfo = $staticStopInfo;
      }
      
      if ($stopInfo) {
        if (!isset($stopInfo['arrives']) || $staticStopInfo['arrives'] < $stopInfo['arrives']) {
            $stopInfo['arrives'] = $staticStopInfo['arrives'];
        }
        if (!isset($stopInfo['predictions'])) {
          $stopInfo['predictions'] = $staticStopInfo['predictions'];
          
        } else if (count($staticStopInfo['predictions'])) {
          $stopInfo['predictions'] = array_merge($stopInfo['predictions'], $staticStopInfo['predictions']);
          
          $stopInfo['predictions'] = array_unique($stopInfo['predictions']);
          sort($stopInfo['predictions']);
        }
      }
      $cache->write(json_encode($stopInfo), $cacheName);
    }
    
    return $stopInfo;
  }
  
  public function getStopInfo($globalStopID) {
    $stopInfo = array();
    $cacheName = "stopInfo.$globalStopID";
    $cache = self::getViewCache();
    
    if ($cache->isFresh($cacheName) && !$this->daemonMode) {
      $stopInfo = json_decode($cache->read($cacheName), true);
      
    } else {
      list($system, $stopID) = $this->getRealID($globalStopID);
    
      foreach ($this->parsersForStop($system, $stopID) as $parser) {
        $parserInfo = false;
        
        if ($parser['live']) {
          $parserInfo = $parser['live']->getStopInfo($stopID);
        }
        
        if ($parser['static']) {
          $staticParserInfo = $parser['static']->getStopInfo($stopID);
        }
        
        if (!$parserInfo) {
          $parserInfo = $staticParserInfo;
        } else if (isset($staticParserInfo['routes'])) {
          // if live parser returns routes that are actually not in service
          foreach (array_keys($parserInfo['routes']) as $routeID) {
            if (!isset($staticParserInfo['routes'][$routeID])) {
              unset($parserInfo['routes'][$routeID]);
            }
          }
  
          foreach ($staticParserInfo['routes'] as $routeID => $routeInfo) {
            if (!isset($parserInfo['routes'][$routeID])
                || !isset($parserInfo['routes'][$routeID]['predictions'])) {
              $parserInfo['routes'][$routeID] = $routeInfo;
            }
            
            // Use static route names if available
            if (isset($routeInfo['name']) && $routeInfo['name']) {
              $parserInfo['routes'][$routeID]['name'] = $routeInfo['name'];
            }
          }
          
          // Use static stop names if available
          if (isset($staticParserInfo['name']) && $staticParserInfo['name']) {
            $parserInfo['name'] = $staticParserInfo['name'];
          }
        }
        
        if ($parserInfo) {
          if (!count($stopInfo)) {
            $stopInfo = $parserInfo;
          } else {
            foreach ($parserInfo['routes'] as $routeID => $stopTimes) {
              if (!isset($stopInfo['routes'][$routeID])) {
                $stopInfo['routes'][$routeID] = $stopTimes;
              } else {
                if (!isset($stopTimes['predictions'])) {
                  $stopInfo['routes'][$routeID]['predictions'] = $stopTimes['predictions'];
                  
                } else if (count($stopTimes['predictions'])) {
                  $stopInfo['routes'][$routeID]['predictions'] = array_merge(
                    $stopInfo['routes'][$routeID]['predictions'], $stopTimes['predictions']);
                  
                  $stopInfo['routes'][$routeID]['predictions'] = array_unique($stopInfo['routes'][$routeID]['predictions']);
                  sort($stopInfo['routes'][$routeID]['predictions']);
                }
              }
            }
          }
        }
      }
      $this->remapStopInfo($parser['system'], $stopInfo);
      
      $cache->write(json_encode($stopInfo), $cacheName);
    }
    return $stopInfo;
  }

  public function getMapImageForStop($globalStopID, $width=270, $height=270) {
    $image = false;
    list($system, $stopID) = $this->getRealID($globalStopID);
    $parser = reset($this->parsersForStop($system, $stopID));
    
    if ($parser['live']) {
      $image = $parser['live']->getMapImageForStop($stopID, $width, $height);
    }
    
    if (!$image && $parser['static']) {
      $image = $parser['static']->getMapImageForStop($stopID, $width, $height);
    }
    
    return $image;
  }

  public function getMapImageForRoute($globalRouteID, $width=270, $height=270) {
    $image = false;
    list($system, $routeID) = $this->getRealID($globalRouteID);
    $parser = $this->parserForRoute($system, $routeID);
    
    if ($parser['live']) {
      $image = $parser['live']->getMapImageForRoute($routeID, $width, $height);
    }
    
    if (!$image && $parser['static']) {
      $image = $parser['static']->getMapImageForRoute($routeID, $width, $height);
    }
    
    return $image;
  }
  
  public function getRouteInfo($globalRouteID, $time=null) {
    $routeInfo = array();
    $cacheName = "routeInfo.$globalRouteID";
    $cache = self::getViewCache();
    
    if ($cache->isFresh($cacheName) && $time == null && !$this->daemonMode) {
      $routeInfo = json_decode($cache->read($cacheName), true);
      
    } else {
      list($system, $routeID) = $this->getRealID($globalRouteID);
      $parser = $this->parserForRoute($system, $routeID);
      
      if ($parser['live']) {
        $routeInfo = $parser['live']->getRouteInfo($routeID, $time);
        if (count($routeInfo)) {
          $routeInfo['live'] = true;
        }
      }
      
      if ($parser['static']) {
        $staticRouteInfo = $parser['static']->getRouteInfo($routeID, $time);
        
        if (!count($routeInfo)) {
          $routeInfo = $staticRouteInfo;
        
        } else if (count($staticRouteInfo)) {
          if (strlen($staticRouteInfo['name'])) {
            // static name is better
            $routeInfo['name'] = $staticRouteInfo['name'];
          }
          if (strlen($staticRouteInfo['description'])) {
            // static description is better
            $routeInfo['description'] = $staticRouteInfo['description'];
          }
          if ($staticRouteInfo['frequency'] != 0) { // prefer static
            $routeInfo['frequency'] = $staticRouteInfo['frequency'];
          }
          if (!count($routeInfo['stops'])) {
            $routeInfo['stops'] = $staticRouteInfo['stops'];
          
          } else {
            // Use the static first stop, not the prediction first stop
            // Use static stop names if available
            $firstStop = reset(array_keys($staticRouteInfo['stops']));
            $foundFirstStop = false;
            $moveToEnd = array();
            foreach ($routeInfo['stops'] as $stopID => $stop) {
              $staticStopID = $stopID;
            
              if (!isset($staticRouteInfo['stops'][$staticStopID])) {
                // NextBus sometimes has _ar suffixes on it.  Try stripping them
                $parts = explode('_', $stopID);
                if (isset($staticRouteInfo['stops'][$parts[0]])) {
                  //error_log("Warning: static route does not have live stop id $stopID, using {$parts[0]}");
                  $staticStopID = $parts[0];
                }
              }
              
              if (isset($staticRouteInfo['stops'][$staticStopID])) {
                $routeInfo['stops'][$stopID]['name'] = $staticRouteInfo['stops'][$staticStopID]['name'];
  
                if (!$stop['hasTiming'] && $staticRouteInfo['stops'][$staticStopID]['hasTiming']) {
                  $routeInfo['stops'][$stopID]['arrives'] = $staticRouteInfo['stops'][$staticStopID]['arrives'];
                  
                  if (isset($staticRouteInfo['stops'][$staticStopID]['predictions'])) {
                    $routeInfo['stops'][$stopID]['predictions'] = $staticRouteInfo['stops'][$staticStopID]['predictions'];
                  } else {
                    unset($routeInfo['stops'][$stopID]['predictions']);
                  }
                }
              } else {
                error_log("Warning: static route info does not have live stop id $stopID");
              }
              
              if ($foundFirstStop || TransitDataParser::isSameStop($stopID, $firstStop)) {
                $foundFirstStop = true;
              } else {
                $moveToEnd[$stopID] = $stop;
                unset($routeInfo['stops'][$stopID]);
              }
            }
            $routeInfo['stops'] += $moveToEnd;
            
            uasort($routeInfo['stops'], array('TransitDataParser', 'sortStops'));
          }
        }
      }
      
      if (count($routeInfo)) {
        $now = time();
        
        // Walk the stops to figure out which is upcoming
        $stopIDs     = array_keys($routeInfo['stops']);
        $firstStopID = reset($stopIDs);
        
        $firstStopPrevID  = end($stopIDs);
        if (TransitDataParser::isSameStop($firstStopID, $firstStopPrevID)) {
          $firstStopPrevID = prev($stopIDs);
        }
        
        foreach ($stopIDs as $index => $stopID) {
          if (!isset($routeInfo['stops'][$stopID]['upcoming'])) {
            $arrives = $routeInfo['stops'][$stopID]['arrives'];
      
            if ($stopID == $firstStopID) {
              $prevArrives = $routeInfo['stops'][$firstStopPrevID]['arrives'];
            } else {
              $prevArrives = $routeInfo['stops'][$stopIDs[$index-1]]['arrives'];
            }
      
            // Suppress any soonest stops which are more than 2 hours from now
            $routeInfo['stops'][$stopID]['upcoming'] = 
                (abs($arrives - $now) < Kurogo::getSiteVar('TRANSIT_MAX_ARRIVAL_DELAY')) && 
                $arrives <= $prevArrives;
          }
        }
        
        $routeInfo['lastupdate'] = $now;
      }
      $this->remapRouteInfo($parser['system'], $routeInfo);

      if ($time == null) {
        $cache->write(json_encode($routeInfo), $cacheName);
      }
    }
    
    return $routeInfo;    
  }
  
  public function getRoutePaths($globalRouteID) {
    $paths = array();
    
    list($system, $routeID) = $this->getRealID($globalRouteID);
    $parser = $this->parserForRoute($system, $routeID);
    
    if ($parser['live']) {
      $paths = $parser['live']->getRoutePaths($routeID);
    } else if ($parser['static']) {
      $paths = $parser['static']->getRoutePaths($routeID);
    }
    
    return $paths;
  }
  
  public function getRouteVehicles($globalRouteID) {
    $vehicles = array();
    
    list($system, $routeID) = $this->getRealID($globalRouteID);
    $parser = $this->parserForRoute($system, $routeID);

    if ($parser['live']) {
      $vehicles = $parser['live']->getRouteVehicles($routeID);
    } else if ($parser['static']) {
      $vehicles = $parser['static']->getRouteVehicles($routeID);
    }
    
    return $vehicles;
  }
  
  public function getNewsForRoutes() {
    $allNews = array();
    
    foreach ($this->parsers as $parser) {
      $news = array();

      if ($parser['live']) {
        $news = $this->remapNews($parser['system'], $parser['live']->getNewsForRoutes());
      }
      
      if ($parser['static']) {
        $staticNews = $this->remapNews($parser['system'], $parser['static']->getNewsForRoutes());
        if (!count($news)) {
          $news = $staticNews;
        
        } else if (count($staticNews)) {
          $news = $news + $staticNews;
        }
      }
      $allNews += $news;
    }
    
    return $allNews;
  }
  
  public function getServiceInfoForRoute($globalRouteID) {
    $info = false;
    
    list($system, $routeID) = $this->getRealID($globalRouteID);
    $parser = $this->parserForRoute($system, $routeID);
    
    if ($parser['live']) {
      $info = $parser['live']->getServiceInfo();
    }
    
    if (!$info && $parser['static']) {
      $info = $parser['static']->getServiceInfo();
    }
    
    return $info;
  }
  
  private function getAllRoutes($time=null) {
    $allRoutes = array();
    $cacheName = 'allRoutes';
    $cache = self::getViewCache();
    
    if ($cache->isFresh($cacheName) && $time == null && !$this->daemonMode) {
      $allRoutes = json_decode($cache->read($cacheName), true);
      
    } else {
      foreach ($this->parsers as $parser) {
        $routes = array();
        
        if ($parser['live']) {
          $routes = $this->remapRoutes($parser['system'], $parser['live']->getRoutes($time));
        }
        
        if ($parser['static']) {
          $staticRoutes = $this->remapRoutes($parser['system'], $parser['static']->getRoutes($time));
          if (!count($routes)) {
            $routes = $staticRoutes;
          } else {
            foreach ($routes as $routeID => $routeInfo) {
              if (isset($staticRoutes[$routeID])) {
                if (!$routeInfo['running']) {
                  $routes[$routeID] = $staticRoutes[$routeID];
                } else {
                  // static name is better
                  $routes[$routeID]['name'] = $staticRoutes[$routeID]['name'];
                  $routes[$routeID]['description'] = $staticRoutes[$routeID]['description'];
                  
                  if ($staticRoutes[$routeID]['frequency'] != 0) {
                    $routes[$routeID]['frequency'] = $staticRoutes[$routeID]['frequency'];
                  }
                }
              }
            }
            // Pull in static routes with no live data
            foreach ($staticRoutes as $routeID => $staticRouteInfo) {
              if (!isset($routes[$routeID])) {
                $routes[$routeID] = $staticRouteInfo;
              }
            }
          }
        }
        $allRoutes += $routes;
      }
      if ($time == null) {
        $cache->write(json_encode($allRoutes), $cacheName);
      }
    }
    
    return $allRoutes;
  }
 
  public function getRoutes($time=null) {
    $routes = $this->getAllRoutes($time);

    // Remove routes that are not in service
    foreach ($routes as $routeID => $routeInfo) {
      if (!$routeInfo['inService']) {
        unset($routes[$routeID]);
      }
    }
    
    return $routes;
  }
  
  public function getInactiveRoutes($time=null) {
    $routes = $this->getAllRoutes($time);

    // Remove routes that are in service
    foreach ($routes as $routeID => $routeInfo) {
      if ($routeInfo['inService']) {
        unset($routes[$routeID]);
      }
    }
    
    return $routes;
  }

  // Private functions
  private function remapStopInfo($system, &$stopInfo) {
    if (isset($stopInfo['routes'])) {
      $routes = array();
      foreach ($stopInfo['routes'] as $routeID => $routeInfo) {
        $routes[$this->getGlobalID($system, $routeID)] = $routeInfo;
      }
      $stopInfo['routes'] = $routes;
    }
  }
  
  private function remapRouteInfo($system, &$routeInfo) {
    if (isset($routeInfo['stops'])) {
      $stops = array();
      foreach ($routeInfo['stops'] as $stopID => $stopInfo) {
        $stops[$this->getGlobalID($system, $stopID)] = $stopInfo;
      }
      $routeInfo['stops'] = $stops;
    }
  }
  
  private function remapRoutes($system, $routes) {
    $mappedRoutes = array();
    
    foreach ($routes as $routeID => $routeInfo) {
      $mappedRoutes[$this->getGlobalID($system, $routeID)] = $routeInfo;
    }
    
    return $mappedRoutes;
  }
  
  private function remapNews($system, $news) {
    $mappedNews = array();
    
    foreach ($news as $routeID => $newsItems) {
      $mappedNews[$this->getGlobalID($system, $routeID)] = $newsItems;
    }
    
    return $mappedNews;
  }

  private function parserForRoute($system, $routeID) {
    foreach ($this->parsers as $parser) {
      if ($parser['system'] != $system) { continue; }
    
      if ($parser['live'] && $parser['live']->hasRoute($routeID)) {
        return $parser;
      }
      if ($parser['static'] && $parser['static']->hasRoute($routeID)) {
        return $parser;
      }
    }
    return array('live' => false, 'static' => false);
  }
  
  private function parsersForStop($system, $stopID) {
    $parsers = array();
  
    foreach ($this->parsers as $parser) {
      if ($parser['system'] != $system) { continue; }
    
      if (($parser['live'] && $parser['live']->hasStop($stopID)) ||
          ($parser['static'] && $parser['static']->hasStop($stopID))) {
        $parsers[] = $parser;
      }
    }
    return $parsers;
  }
  
  private function getGlobalID($system, $realID) {
    return $system.$this->globalIDSeparator.$realID;
  }
  
  private function getRealID($globalID) {
    $parts = explode($this->globalIDSeparator, $globalID);
    if (count($parts) == 2) {
      return $parts;
    } else {
      throw new Exception("Invalid global view ID '$globalID'");
    }
  }
}
