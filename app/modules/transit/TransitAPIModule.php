<?php

includePackage('Transit');

class TransitAPIModule extends APIModule {
    protected $id = 'transit';
    protected $vmin = 1;
    protected $vmax = 3;
    protected $defaultNewsModel = 'TransitNewsDataModel';
    
    protected function formatBriefRouteInfo($routeId, $routeInfo) {
        return array(
            'id'              => "$routeId", // make sure numeric route names are strings
            'agency'          => $routeInfo['agency'],
            'title'           => $routeInfo['name'],
            'summary'         => $this->argVal($routeInfo, 'description', ''),
            'description'     => $this->argVal($routeInfo, 'summary', ''),
            'color'           => $routeInfo['color'],
            'frequency'       => $routeInfo['frequency'],
            'running'         => $routeInfo['running'] ? true : false,
            'live'            => $this->argVal($routeInfo, 'live', false) ? true : false,
            'view'            => $this->argVal($routeInfo, 'view', 'list'),
            'splitByHeadsign' => $this->argVal($routeInfo, 'splitByHeadsign', false),
        );
    }
    
    protected function formatFullRouteInfo($routeId, $routeInfo, $responseVersion) {
        // Get the basic attributes for the route
        $formatted = $this->formatBriefRouteInfo($routeId, $routeInfo);
        
        // Stop and vehicle icons
        if (isset($routeInfo['stopIconURL'])) {
            $formatted['stopIconURL'] = $routeInfo['stopIconURL'];
        }
        if (isset($routeInfo['vehicleIconURL'])) {
            $formatted['vehicleIconURL'] = $routeInfo['vehicleIconURL'];
        }
        
        // Schedule view or stop list view?
        if (isset($routeInfo['directions'])) {
            foreach ($routeInfo['directions'] as $directionID => $directionInfo) {
                $formatted['directions'][] = array(
                    'id'    => $directionID,
                    'name'  => $directionInfo['name'],
                    'stops' => $this->formatStopsInfoForRoute($routeId, $directionInfo['stops'], $responseVersion),
                );
            }
            
            if ($responseVersion < 3) {
                // pre version 3 the API provided a merged stop view
                $mergedDirections = TransitDataModel::mergeDirections($routeInfo['directions']);
                $mergedDirection = reset($mergedDirections);
                
                $formatted['stops'] = 
                    $this->formatStopsInfoForRoute($routeId, $mergedDirection['stops'], $responseVersion);
                
                // and only provided the directions field in schedule view, indexed by direction id
                if ($formatted['view'] == 'list') {
                    unset($formatted['directions']);
                    
                } else {
                    $oldStyleDirections = array();
                    foreach ($formatted['directions'] as $directionInfo) {
                        $directionID = $directionInfo['id'];
                        unset($directionInfo['id']);
                        $oldStyleDirections[$directionID] = $directionInfo;
                    }
                    $formatted['directions'] = $oldStyleDirections;
                }
            }
        }
        
        return $formatted;
    }
    
    protected function formatStopsInfoForRoute($routeId, $stops, $responseVersion) {
        $routeStopsInfo = array();
        
        foreach ($stops as $stopInfo) {
            $routeStopInfo = array(
                'id'      => strval($stopInfo['id']),
                'title'   => $stopInfo['name'],
                'coords'  => array(
                    'lat' => $stopInfo['coordinates']['lat'],
                    'lon' => $stopInfo['coordinates']['lon'],
                ),
                'arrives' => self::argVal($stopInfo, 'predictions', array()),
            );
            
            if ($responseVersion < 3) {
                $routeStopInfo['routeId'] = "$routeId"; // Provide old route id field
                $routeStopInfo['name'] = $stopInfo['name']; // Provide old name field
                unset($routeStopInfo['title']);
            }
            $routeStopsInfo[] = $routeStopInfo;
        }
        
        return $routeStopsInfo;
    }
    
    protected function formatStopInfo($stopId, $stopInfo, $responseVersion) {
        $routes = array();
        foreach ($stopInfo['routes'] as $routeId => $routeInfo) {
            $directions = array();
            foreach (self::argVal($routeInfo, 'directions', array()) as $directionID => $directionInfo) {
                $directions[] = array(
                    'id'      => $directionID,
                    'title'   => self::argVal($directionInfo, 'name', ''),
                    'arrives' => self::argVal($directionInfo, 'predictions', array()),
                );
            }
            
            $route = array(
                'id'         => $routeId,
                'title'      => $routeInfo['name'],
                'running'    => $routeInfo['running'],
                'directions' => $directions,
            );

            if ($responseVersion < 3) {
                $predictions = array();
                foreach ($directions as $directionID => $directionInfo) {
                    $predictions = array_merge($predictions, $directionInfo['arrives']);
                }
                sort($predictions);

                $route['arrives'] = $predictions;
                unset($route['directions']);
                
                $route['routeId'] = $routeId;
                unset($route['id']);
            }
            
            $routes[] = $route;
        }
    
        return array(
            'id'     => "$stopId",
            'title'  => $stopInfo['name'],
            'coords' => array(
                'lat' => $stopInfo['coordinates']['lat'],
                'lon' => $stopInfo['coordinates']['lon'],
            ),
            'routes' => $routes,
        );
    }
    
    protected function formatVehicleInfo($vehicleId, $vehicleInfo) {
        $vehicle = array(
            'id'         => $vehicleId,
            'agency'     => $vehicleInfo['agency'],
            'routeId'    => $vehicleInfo['routeID'],
            'lastSeen'   => time() + $vehicleInfo['secsSinceReport'],
            'heading'    => $vehicleInfo['heading'],
            'coords'     => array(
                'lat' => $vehicleInfo['lat'],
                'lon' => $vehicleInfo['lon'],
            ),
        );
        
        if (isset($vehicleInfo['nextStop'])) {
            $vehicle['nextStop'] = $vehicleInfo['nextStop'];
        }
        
        if (isset($vehicleInfo['speed'])) {
            $vehicle['speed'] = $vehicleInfo['speed'];
        }
        
        if (isset($vehicleInfo['iconURL'])) {
            $vehicle['iconURL'] = $vehicleInfo['iconURL'];
        }
    
        return $vehicle;
    }
    
    protected function initializeForCommand() {
        $responseVersion = ($this->requestedVersion >= $this->vmin && $this->requestedVersion <= $this->vmax) ? 
            $this->requestedVersion : $this->vmax;
        
        $view = DataModel::factory("TransitViewDataModel", $this->loadFeedData());
        
        switch($this->command) {
          case 'info':
            $keyRemap = array(
                'titles'    => 'title',
                'subtitles' => 'subtitle',
                'urls'      => 'url',
                'classes'   => 'class',
                'infokeys'  => 'content',
            );
          
            $infoText = $this->getModuleSections('feeds-info');
            $info = $this->getModuleSections('api-index');
            
            $agencies = array();
            foreach ($info['agencies'] as $agencyID => $agencyName) {
                $agencies[] = array(
                    'id'    => $agencyID,
                    'title' => $agencyName,
                );
            }
            
            $results = array(
                'agencies' => $agencies,
                'sections' => array(),
            );
            
            foreach ($info['infosections'] as $sectionKey => $sectionTitle) {
                $section = $info[$sectionKey];
                
                if (isset($section['titles'])) {
                    $itemList = array();
                    foreach ($section as $key => $values) {
                        foreach ($values as $i => $value) {
                            if (!isset($itemList[$i])) {
                                $itemList[$i] = array();
                            }
                            $listKey = isset($keyRemap[$key]) ? $keyRemap[$key] : $key;
                            $listValue = $value;
                            
                            if ($key == 'infokeys') {
                                if (isset($infoText['info'][$value])) {
                                    $listValue = $infoText['info'][$value];
                                } else {
                                    Kurogo::log(LOG_ERR, "Transit api-index.ini error: no info section for $value", 'transit');
                                }
                            }
                            
                            $itemList[$i][$listKey] = $listValue;
                        }
                    }
                    $results['sections'][] = array(
                        'key'   => $sectionKey,
                        'title' => isset($info['infosections'][$sectionKey]) ? $info['infosections'][$sectionKey] : "",
                        'items' => $itemList,
                    );
                }
            }
            $this->setResponse($results);
            $this->setResponseVersion($responseVersion);
            break;
          
          case 'stop':
              $response = array();
      
              $stopId = $this->getArg('id');
              if (!isset($stopId)) {
                  throw new Exception("Stop id not set");
              }
              
              $stopInfo = $view->getStopInfo($stopId);
              if (!$stopInfo) {
                  throw new Exception("No such stop '$stopId'");
              }
              
              $response = $this->formatStopInfo($stopId, $stopInfo, $responseVersion);
              
              $this->setResponse($response);
              $this->setResponseVersion($responseVersion);
              break;
            
          case 'routes':
              $response = array();
              $routesInfo = $view->getRoutes();
              foreach ($routesInfo as $routeId => $routeInfo) {
                  $response[] = $this->formatBriefRouteInfo($routeId, $routeInfo);
              }
              
              $this->setResponse($response);
              $this->setResponseVersion($responseVersion);
              break;
          
          case 'route':
              $routeId = $this->getArg('id');
              
              if (!$routeId) {
                  throw new Exception('No route parameter');
              }
              
              $routeInfo = $view->getRouteInfo($routeId);
              if (!$routeInfo) {
                  throw new Exception("No such route '$routeId'");
              }
              
              $response = $this->formatFullRouteInfo($routeId, $routeInfo, $responseVersion);
              
              // Add route paths (if any)
              // Note: these line segments are not necessarily a loop
              $response['paths'] = array_values($view->getRoutePaths($routeId));
              
              // Add route vehicles (if any)
              $response['vehicles'] = array();
              foreach($view->getRouteVehicles($routeId) as $vehicleId => $vehicleInfo) {
                  $response['vehicles'][] = $this->formatVehicleInfo($vehicleId, $vehicleInfo);
              }
              
              $this->setResponse($response);
              $this->setResponseVersion($responseVersion);
              break;
          
          case 'announcements':
              $response = array();
        
              $newsFeeds = array();
              $newsConfig = $this->getModuleSections('feeds-news');
              foreach ($newsConfig as $agencyID => $feedData) {
                  $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : $this->defaultNewsModel;
                  $feed = TransitNewsDataModel::factory($modelClass, $feedData);
                  if (!$feed) { continue; }
                  
                  $feed->setStart(0);
                  $feed->setLimit(null);
                  $items = $feed->items();
                  if ($items) {
                      if (!isset($response[$agencyID])) {
                          $heading = isset($newsConfig[$agencyID]['TITLE']) ? 
                              $newsConfig[$agencyID]['TITLE'] : $agencyID;
                          
                          $response[$agencyID] = array(
                              'announcements' => array(),
                          );
                          if ($responseVersion > 1) {
                              $response[$agencyID]['title'] = $heading;
                              $response[$agencyID]['agency'] = $agencyID;
                          } else {
                              $response[$agencyID]['name'] = $agencyID;
                          }
                      }
                      
                      foreach ($items as $item) {
                          $content = $item->getContent();
                          if (!$content) {
                              $content = $item->getDescription();
                          }
                      
                          $response[$agencyID]['announcements'][] = array(
                              'agency' => $agencyID,
                              'title' => $item->getTitle(),
                              'date' => $item->getPubDate()->format('Y/m/d'),
                              'timestamp' => $item->getPubTimestamp(),
                              'urgent' => false,
                              'html'  => $content,
                          );
                      }
                  }
              }
              
              $this->setResponse(array_values($response));
              $this->setResponseVersion($responseVersion);
              break;
          
          case 'vehicles':
              $routeId = $this->getArg('id');
              
              if (!$routeId) {
                  throw new Exception('No route parameter');
              }
              
              $routeVehicles = $view->getRouteVehicles($routeId);
              
              $response = array();
              foreach($routeVehicles as $vehicleId => $vehicleInfo) {
                  $response[] = $this->formatVehicleInfo($vehicleId, $vehicleInfo);
              }
              
              $this->setResponse($response);
              $this->setResponseVersion($responseVersion);
              break;
        }
    }
}
