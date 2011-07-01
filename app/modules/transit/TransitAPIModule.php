<?php

includePackage('Transit');

class TransitAPIModule extends APIModule {
  protected $id = 'transit';

  protected function formatRouteInfo($routeId, $routeInfo) {
    return array(
      'id'             => "$routeId", // make sure numeric route names are strings
      'agency'         => $routeInfo['agency'],
      'title'          => $routeInfo['name'],
      'summary'        => $this->argVal($routeInfo, 'description', ''),
      'description'    => $this->argVal($routeInfo, 'summary', ''),
      'color'          => $routeInfo['color'],
      'frequency'      => $routeInfo['frequency'],
      'running'        => $routeInfo['running'] ? true : false,
      'live'           => $this->argVal($routeInfo, 'live', false) ? true : false,
      'stopIconURL'    => $this->argVal($routeInfo, 'stopIconURL', ''),
      'vehicleIconURL' => $this->argVal($routeInfo, 'vehicleIconURL', ''),
    );
  }
  
  protected function formatStopInfo($stopId, $stopInfo) {
    $routes = array();
    foreach ($stopInfo['routes'] as $routeId => $routeInfo) {
      $routes[] = array(
        'routeId' => $routeId,
        'title'   => $routeInfo['name'],
        'running' => $routeInfo['running'],
        'arrives' => self::argVal($routeInfo, 'predictions', array()),
      );
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
  
  protected function formatStopInfoForRoute($routeId, $stopId, $stopInfo) {
    return array(
      'id'      => "$stopId",
      'routeId' => "$routeId",
      'title'   => $stopInfo['name'],
      'coords'  => array(
        'lat' => $stopInfo['coordinates']['lat'],
        'lon' => $stopInfo['coordinates']['lon'],
      ),
      'arrives' => $this->argVal($stopInfo, 'predictions', ''),
    );
  }
  
  protected function formatVehicleInfo($vehicleId, $vehicleInfo) {
    $vehicle = array(
      'id'         => $vehicleId,
      'nextStopId' => $vehicleInfo['nextStop'],
      'agencyId'   => $vehicleInfo['agencyID'],
      'routeId'    => $vehicleInfo['routeID'],
      'lastSeen'   => time() + $vehicleInfo['secsSinceReport'],
      'heading'    => $vehicleInfo['heading'],
      'coords'     => array(
        'lat' => $vehicleInfo['lat'],
        'lon' => $vehicleInfo['lon'],
      ),
    );
    
    if (isset($vehicleInfo['speed'])) {
      $vehicle['speed'] = $vehicleInfo['speed'];
    }
    
    if (isset($vehicleInfo['iconURL'])) {
      $vehicle['iconURL'] = $vehicleInfo['iconURL'];
    }

    return $vehicle;
  }
  
  protected function mergePaths($paths) {
    // the iPhone app does not understand paths which aren't in a loop.  Wheeee!
    $paths = array_values($paths);
  
    if (count($paths) > 1) {
      $foundPair = true;
      while ($foundPair) {
        $foundPair = false;
        for ($i = 0; $i < count($paths); $i++) {
          for ($j = 0; $j < count($paths); $j++) {
            if ($i == $j) { continue; }
            
            $path1 = array_values($paths[$i]);
            $path2 = array_values($paths[$j]);
            //error_log("Path 1 ($i): ".count($path1)." points");
            //error_log("Path 2 ($j): ".count($path2)." points");
            for ($x = 0; $x < count($path1)-1; $x++) {
              for ($y = 0; $y < count($path2)-1; $y++) {
                
                if ($path1[$x] == $path2[$y] && $path1[$x+1] == $path2[$y+1]) {
                  // Found a place to attach the paths!
                  $path1Segment1 = array_slice($path1, 0, $x+1);
                  $path1Segment2 = array_slice($path1, $x);
                  $path2Segment1 = array_slice($path2, 0, $y+1);
                  $path2Segment2 = array_slice($path2, $y);
                  
                  unset($paths[$i]);
                  unset($paths[$j]);
                  $paths[] = $this->mergeArrays(array(
                    $path1Segment1,
                    array_reverse($path2Segment1),
                    array_reverse($path2Segment2),
                    $path1Segment2,
                  ));
                  $foundPair = true;
                  break;
                } else if ($path1[$x] == $path2[$y+1] && $path1[$x+1] == $path2[$y]) {
                  // Found a place to attach the paths!
                  $path1Segment1 = array_slice($path1, 0, $x+1);
                  $path1Segment2 = array_slice($path1, $x);
                  
                  unset($paths[$i]);
                  unset($paths[$j]);
                  $paths[] = $this->mergeArrays(array(
                    $path1Segment1,
                    $path2,
                    $path1Segment2,
                  ));
                  $foundPair = true;
                }
              }
              if ($foundPair) { break; }
            }
            if ($foundPair) { break; }
          }
          if ($foundPair) { break; }
        }
      }
    }
  
    if (count($paths) > 1) {
      error_log("Warning!  Multiple path segments after merge.");
    }  
  
    // Last ditch effort... if there is still more than one we will just
    // merge and live with the criss-crosses
    $mergedPath = array();
    foreach ($paths as $path) {
      $mergedPath = array_merge($mergedPath, $path);
    }
    
    return $mergedPath;
  }
  
  protected function mergeArrays($arrays) {
    $result = array();
    foreach ($arrays as $array) {
      $result = array_merge($result, $array);
    }
    return array_values($result);
  }
  
  protected function initializeForCommand() {
    if ($this->command == '__stripGTFSToDB') {
        $gtfsToDB = new StripGTFSToDB();
        $gtfsToDB->addGTFS('mit', 
            array()); // all routes
        $gtfsToDB->addGTFS('mbta', 
            array('1', '701', '747'), // routes
            array('1' => 'mbta',),    // agency remap
            array(                    // route remap
                '01-1079'  => '1',
                '701-1079' => '701',
                '747-1079' => '747',
            ));
        
        if (!$gtfsToDB->convert()) {
            throw new Exception($gtfsToDB->getError());
        }
        
        $this->setResponse('<pre>'.$gtfsToDB->getMessages().'</pre>');
        $this->setResponseVersion(1);
        return;
    }

  
    $transitConfig = new TransitConfig($this->loadFeedData());
    $view = new TransitDataView($transitConfig);

    switch($this->command) {
      case 'info':
        $this->setResponse($this->getModuleSections('feeds-info'));
        $this->setResponseVersion(1);
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
        
        $response = $this->formatStopInfo($stopId, $stopInfo);
        
        $this->setResponse($response);
        $this->setResponseVersion(1);
        break;
        
      case 'routes':
        $response = array();
        $routesInfo = $view->getRoutes();
        foreach ($routesInfo as $routeId => $routeInfo) {
          $response[] = $this->formatRouteInfo($routeId, $routeInfo);
        }
        
        $this->setResponse($response);
        $this->setResponseVersion(1);
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
        
        $response = $this->formatRouteInfo($routeId, $routeInfo);
        
        $response['stops'] = array();
        foreach ($routeInfo['stops'] as $stopId => $stopInfo) {
          $response['stops'][] = $this->formatStopInfoForRoute($routeId, $stopId, $stopInfo);
        }
        
        $response['path'] = $this->mergePaths($view->getRoutePaths($routeId));
  
        $response['vehicles'] = array();
        foreach($view->getRouteVehicles($routeId) as $vehicleId => $vehicleInfo) {
          $response['vehicles'][] = $this->formatVehicleInfo($vehicleId, $vehicleInfo);
        }

        $this->setResponse($response);
        $this->setResponseVersion(1);
        break;
      
      case 'announcements':
        $newsConfigs = $view->getNews();
        
        $agencies = array();
        foreach ($newsConfigs as $newsConfig) {
          if (!isset($agencies[$newsConfig['agency']])) {
            $agencies[$newsConfig['agency']] = array(
              'name'          => $newsConfig['agency'],
              'announcements' => array(),
            );
          }
          $newsConfig['date'] = strftime('%Y/%m/%d', $newsConfig['date']);
          $agencies[$newsConfig['agency']]['announcements'][] = $newsConfig;
        }
        
        $this->setResponse(array_values($agencies));
        $this->setResponseVersion(1);
        break;
    }
  }
}
