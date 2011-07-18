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
      'arrives' => $this->argVal($stopInfo, 'predictions', array()),
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
  
  protected function initializeForCommand() {
    if ($this->command == '__stripGTFSToDB') {
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
        
        // Note: these line segments are not necessarily a loop
        $response['paths'] = array_values($view->getRoutePaths($routeId));
  
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
    }
  }
}
