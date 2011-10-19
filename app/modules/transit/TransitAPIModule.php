<?php

includePackage('Transit');

class TransitAPIModule extends APIModule {
  protected $id = 'transit';

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
    );
  }
  
  protected function formatFullRouteInfo($routeId, $routeInfo) {
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
      $formatted['directions'] = $routeInfo['directions'];
      $formatted['splitByHeadsign'] = $this->argVal($routeInfo, 'splitByHeadsign', false);
      
    } else {
      $formatted['stops'] = array();
      foreach ($routeInfo['stops'] as $stopId => $stopInfo) {
        $formatted['stops'][] = $this->formatStopInfoForRoute($routeId, $stopId, $stopInfo);
      }
    }
    
    return $formatted;
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
      'arrives' => $this->argVal($stopInfo, 'predictions', array()),
    );
  }
  
  protected function formatVehicleInfo($vehicleId, $vehicleInfo) {
    $vehicle = array(
      'id'         => $vehicleId,
      'agencyId'   => $vehicleInfo['agencyID'],
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
    if ($this->command == '__stripGTFSToDB') {
        if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1') {
            throw new Exception("__stripGTFSToDB can only be run from localhost");
        }
        
        $gtfsConfig = $this->getModuleSections('feeds-gtfs');
        
        $gtfsToDB = new StripGTFSToDB();
        foreach ($gtfsConfig as $gtfsIndex => $gtfsData) {
            $gtfsToDB->addGTFS($gtfsIndex, $gtfsData);
        }
        
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
          $response[] = $this->formatBriefRouteInfo($routeId, $routeInfo);
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
        
        $response = $this->formatFullRouteInfo($routeId, $routeInfo);
        
        // Add route paths (if any)
        // Note: these line segments are not necessarily a loop
        $response['paths'] = array_values($view->getRoutePaths($routeId));
        
        // Add route vehicles (if any)
        $response['vehicles'] = array();
        foreach($view->getRouteVehicles($routeId) as $vehicleId => $vehicleInfo) {
          $response['vehicles'][] = $this->formatVehicleInfo($vehicleId, $vehicleInfo);
        }
        
        $this->setResponse($response);
        $this->setResponseVersion(1);
        break;
      
      case 'announcements':
        $newsConfigs = $view->getNewsForRoutes();
        
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
        
      case 'vehicleMarkers':
        // used by mobile web for live route maps
        $routeID = $this->getArg('id');
        
        if (!$routeID) {
          throw new Exception('No route parameter');
        }
        
        $markers = array();
        foreach ($view->getRouteVehicles($routeID) as $vehicleID => $vehicle) {
          $markers[$vehicleID] = array(
            'lat' => $vehicle['lat'],
            'lon' => $vehicle['lon'],
            'imageURL' => $vehicle['iconURL'],
            'title' => '',
          );
        }
        
        $this->setResponse($markers);
        $this->setResponseVersion(1);
        break;        
    }
  }
}
