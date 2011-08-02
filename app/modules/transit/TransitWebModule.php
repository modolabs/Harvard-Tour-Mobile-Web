<?php

Kurogo::includePackage('Transit');
Kurogo::includePackage('Maps');

class TransitWebModule extends WebModule {
  protected $id = 'transit';
  const RELOAD_TIME = 60;
  
  protected function initialize() {
  }

  private function timesURL($routeID, $addBreadcrumb=true, $noBreadcrumb=false, $paneLink=false) {
    $args = array(
      'id' => $routeID,
    );
  
    if ($paneLink || $noBreadcrumb) {
      return $this->buildURL('route', $args);
    } else {
      return $this->buildBreadcrumbURL('route', $args, $addBreadcrumb);
    }
  }

  private function newsURL($newsID, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('announcement', array(
      'id' => $newsID,      
    ), $addBreadcrumb);
  }
  
  private function stopURL($stopID, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('stop', array(
      'id' => $stopID,      
    ), $addBreadcrumb);
  }
  
  private static function routeSort($a, $b) {
    return strnatcmp($a['title'], $b['title']);
  }

  protected function initializeForPage() {
    $transitConfig = new TransitConfig($this->loadFeedData());
    $view = new TransitDataView($transitConfig);

    $args = $this->args;
    $args['t'] = time();
    $this->assign('refreshURL', $this->buildBreadcrumbURL($this->page, $args, false));
  
    switch ($this->page) {
      case 'pane':
        $routeConfigs = $view->getRoutes();
        
        $routes = array();
        foreach ($routeConfigs as $routeID => $routeConfig) {
          if ($routeConfig['running']) {
            $routes[] = array(
              'title' => $routeConfig['name'],
              'subtitle' => $routeConfig['description'],
              'url'   => $this->timesURL($routeID, false, true, true),
            );
          }
        }
        uasort($routes, array(get_class($this), 'routeSort'));
        
        $this->assign('routes', $routes);
        break;
      
      case 'index':
        $indexConfig = $this->loadPageConfigFile('index', 'indexConfig');        
        $tabs = array();
        
        //
        // Running and Offline Panes
        //
        $routeConfigs = $view->getRoutes();
        $runningRoutes = array();
        $offlineRoutes = array();

        foreach ($routeConfigs as $routeID => $routeConfig) {
          $agencyID = $routeConfig['agency'];
          $entry = array(
            'title' => $routeConfig['name'],
            'url'   => $this->timesURL($routeID),
          );
          
          if ($routeConfig['running']) {
            if (!isset($runningRoutes[$agencyID])) {
              $heading = isset($indexConfig['agencies'][$agencyID]) ? 
                $indexConfig['agencies'][$agencyID] : $agencyID;
            
              $runningRoutes[$agencyID] = array(
                'heading' => $heading,
                'items' => array(),
              );
            }
            $runningRoutes[$agencyID]['items'][$routeID] = $entry;
          } else {
            if (!isset($offlineRoutes[$agencyID])) {
              $heading = isset($indexConfig['agencies'][$agencyID]) ? 
                $indexConfig['agencies'][$agencyID] : $agencyID;
            
              $offlineRoutes[$agencyID] = array(
                'heading' => $heading,
                'items' => array(),
              );
            }
            $offlineRoutes[$agencyID]['items'][$routeID] = $entry;
          }
        }
        foreach ($runningRoutes as $agencyID => $section) {
          uasort($runningRoutes[$agencyID]['items'], array(get_class($this), 'routeSort'));
        }
        foreach ($offlineRoutes as $agencyID => $section) {
          uasort($offlineRoutes[$agencyID]['items'], array(get_class($this), 'routeSort'));
        }
        if ($runningRoutes) {
          $tabs[] = 'running';
        }
        if ($offlineRoutes) {
          $tabs[] = 'offline';
        }

        //
        // News Pane
        //
        $newsConfigs = $view->getNewsForRoutes();
        
        $news = array();
        foreach ($newsConfigs as $newsID => $newsConfig) {
          $agencyID = $newsConfig['agency'];
        
          if (!isset($news[$agencyID])) {
            $heading = isset($indexConfig['agencies'][$agencyID]) ? 
              $indexConfig['agencies'][$agencyID] : $agencyID;
          
            $news[$agencyID] = array(
              'heading' => $heading,
              'items' => array(),
            );
          }
          $news[$agencyID]['items'][$newsID] = array(
            'title' => $newsConfig['title'],
            'date'  => $newsConfig['date'],
            'url'   => $this->newsURL($newsID),
          );
        }
        if ($news) {
          $tabs[] = 'news';
        }
        
        //
        // Info Pane
        //
        $infosections = array();
        foreach ($indexConfig['infosections'] as $key => $heading) {
          $infosection = array(
            'heading' => $heading,
            'items'   => array(),
          );
          foreach ($indexConfig[$key]['titles'] as $index => $title) {
            $infosection['items'][] = array(
              'title'    => $title,
              'url'      => isset($indexConfig[$key]['urls'])      ? $indexConfig[$key]['urls'][$index]      : null,
              'subtitle' => isset($indexConfig[$key]['subtitles']) ? $indexConfig[$key]['subtitles'][$index] : null,
              'class'    => isset($indexConfig[$key]['classes'])   ? $indexConfig[$key]['classes'][$index]   : null,
            );
          }
          if (count($infosection['items'])) {
            $infosections[] = $infosection;
          }
        }
        if ($infosections) {
          $tabs[] = 'info';
        }
        
        $this->enableTabs($tabs);
        
        $this->assign('runningRoutes', $runningRoutes);
        $this->assign('offlineRoutes', $offlineRoutes);
        $this->assign('news',          $news);
        $this->assign('infosections',  $infosections);
        break;
        
      case 'route':
        $routeID = $this->getArg('id');
        $isAjax = $this->getArg('ajax', 0);
        
        unset($this->args['ajax']); // do not propagate 
        
        $routeInfo = $view->getRouteInfo($routeID);
        
        foreach ($routeInfo['stops'] as $stopID => $stop) {
          $routeInfo['stops'][$stopID]['url']   = $this->stopURL($stopID);
          $routeInfo['stops'][$stopID]['title'] = $stop['name'];
          
          if ($stop['upcoming']) {
            $routeInfo['stops'][$stopID]['title'] = "<strong>{$stop['name']}</strong>";
            $routeInfo['stops'][$stopID]['imgAlt'] = $this->getModuleVar('busImageAltText');
          }
          
          if ($stop['upcoming'] || $this->pagetype != 'basic') {
            $routeInfo['stops'][$stopID]['img'] = '/modules/transit/images/';
          }
          switch ($this->pagetype) {
            case 'basic':
              if ($stop['upcoming']) {
                $routeInfo['stops'][$stopID]['img'] .= 'shuttle.gif';
              }
              break;
            
            case 'touch':
              $routeInfo['stops'][$stopID]['img'] .= $stop['upcoming'] ? 'shuttle.gif' : 'shuttle-spacer.gif';
              break;
              
            default:
              $routeInfo['stops'][$stopID]['img'] .= $stop['upcoming'] ? 'shuttle.png' : 'shuttle-spacer.png';
              break;
          }
        }
        $this->assign('routeInfo', $routeInfo);

        // Ajax page view
        if ($isAjax) {
          $this->setTemplatePage('routeajax');
          break;
        }

        $this->enableTabs(array('map', 'stops'));
        
        $mapImageWidth = $mapImageHeight = 270;
        if ($this->pagetype == 'basic') {
          $mapImageWidth = $mapImageHeight = 200;
        } else if ($this->pagetype == 'tablet') {
          $mapImageWidth = $mapImageHeight = 350;
        }
        $this->assign('mapImageWidth',  $mapImageWidth);
        $this->assign('mapImageHeight', $mapImageHeight);

        $staticImage = $view->getMapImageForRoute($routeID, $mapImageWidth, $mapImageHeight);
        $paths = $view->getRoutePaths($routeID);
        $markers = array();
        foreach ($view->getRouteVehicles($routeID) as $vehicle) {
          $markers[$vehicle['routeID']] = array(
            'lat' => $vehicle['lat'],
            'lon' => $vehicle['lon'],
            'imageURL' => $vehicle['iconURL'],
            'title' => '',
          );
        }
        $markerUpdateURL = FULL_URL_BASE.API_URL_PREFIX."/{$this->configModule}/vehicleMarkers?id={$routeID}";
        $this->initMap($staticImage, $markers, $markerUpdateURL, $paths, $routeInfo['color']);
        
        $this->addOnOrientationChange('setOrientation(getOrientation());');

        $this->assign('lastRefresh', time());
        $this->assign('serviceInfo', $view->getServiceInfoForRoute($routeID));
        break;
      
      case 'stop':
        $stopID = $this->getArg('id');
        $isAjax = $this->getArg('ajax', 0);
        
        unset($this->args['ajax']); // do not propagate 

        $stopInfo = $view->getStopInfo($stopID);
        
        $runningRoutes = array();
        $offlineRoutes = array();
        foreach ($stopInfo['routes'] as $routeID => $routeInfo) {
          $entry = array(
            'title' => $routeInfo['name'],
            'url'   => $this->timesURL($routeID, false, true), // no breadcrumbs
          );
          if (isset($routeInfo['predictions'])) {
            $entry['predictions'] = $routeInfo['predictions'];
          }

          if ($routeInfo['running']) {
            $runningRoutes[$routeID] = $entry;
          } else {
            $offlineRoutes[$routeID] = $entry;
          }
        }
        uasort($runningRoutes, array(get_class($this), 'routeSort'));
        uasort($offlineRoutes, array(get_class($this), 'routeSort'));
        
        $this->assign('runningRoutes', $runningRoutes);
        $this->assign('offlineRoutes', $offlineRoutes);
        
        // Ajax page view
        if ($isAjax) {
          $this->setTemplatePage('stopajax');
          break;
        }
        
        $serviceInfo = false;
        if (count($runningRoutes)) {
          $serviceInfo = $view->getServiceInfoForRoute(reset(array_keys($runningRoutes)));
        } else if (count($offlineRoutes)) {
          $serviceInfo = $view->getServiceInfoForRoute(reset(array_keys($offlineRoutes)));
        }
        
        $mapImageWidth = 298;
        if ($this->pagetype == 'basic') {
          $mapImageWidth = 200;
        }
        if ($this->pagetype == 'tablet') {
          $mapImageWidth = 600;
          $mapImageHeight = floor($mapImageWidth/2);
        } else {
          $mapImageHeight = floor($mapImageWidth/1.5);
        }
        $this->assign('mapImageWidth',  $mapImageWidth);
        $this->assign('mapImageHeight', $mapImageHeight);

        $staticImage = $view->getMapImageForStop($stopID, $mapImageWidth, $mapImageHeight);
        $marker = $stopInfo['coordinates'];
        $markers = array(array(
          'lat' => $stopInfo['coordinates']['lat'],
          'lon' => $stopInfo['coordinates']['lon'],
          'imageURL' => $stopInfo['stopIconURL'],
          'title' => '',
        ));
        $this->initMap($staticImage, $markers);
        
        $this->assign('stopName',      $stopInfo['name']);
        $this->assign('lastRefresh',   time());
        $this->assign('serviceInfo',   $serviceInfo);
        break;
      
      case 'info':
        $infoType = $this->getArg('id');
        
        $infoConfig = $this->getModuleSections('feeds-info');
        
        if (!isset($infoConfig['info']) || !isset($infoConfig['info'][$infoType]) || 
            !strlen($infoConfig['info'][$infoType])) {
          $this->redirectTo('index', array());
        }
        
        if ($this->pagetype == 'basic' || $this->pagetype == 'touch') {
          $infoConfig['info'][$infoType] = str_replace('.png"', '.gif"', $infoConfig['info'][$infoType]);
        }
        
        $this->addInlineCSS('h2 { padding-top: 10pt; }');

        $this->assign('content', $infoConfig['info'][$infoType]);
        break;
        
      case 'announcement':
        $newsConfigs = $view->getNewsForRoutes();
        $newsID = $this->getArg('id');
        
        if (!isset($newsConfigs[$newsID])) {
          $this->redirectTo('index', array());
        }

        $this->assign('title',   $newsConfigs[$newsID]['title']);        
        $this->assign('date',    $newsConfigs[$newsID]['date']);        
        $this->assign('content', $newsConfigs[$newsID]['html']);        
        break;
        
      case 'stoplist':
        // ajaxy goodness
        $routeID = $this->getArg('id');
        $this->page = $this->getArg('page', 'route');
        $this->setupRouteInfo($view, $routeID);
        break;
    }
  }
  
  protected function setupRouteInfo($view, $routeID) {
    
    return $routeInfo;
  }
  
  protected function initMap($staticImage, $markers, $markerUpdateURL='', $paths=array(), $pathColor=null, $needsHTMLUpdate=true) {
    $MapDevice = new MapDevice($this->pagetype, $this->platform);
    
    if ($MapDevice->pageSupportsDynamicMap()) {
      $htmlUpdateURL = '';
      if ($needsHTMLUpdate) {
        $htmlUpdateURL = FULL_URL_PREFIX.$this->buildURL($this->page, array_merge(array('ajax' => 1), $this->args));
      }

      $this->addExternalJavascript('http://maps.google.com/maps/api/js?sensor=true');
      $this->addInlineJavascript("\n".
        'var mapMarkers = '.json_encode($markers).";\n".
        'var mapPaths = '.json_encode($paths).";\n".
        'var mapPathColor = "'.$pathColor."\";\n".
        'var markerUpdateURL = "'.$markerUpdateURL."\";\n".
        'var htmlUpdateURL = "'.$htmlUpdateURL."\";\n".
        'var markerUpdateFrequency = '.Kurogo::getOptionalSiteVar('MAP_MARKER_UPDATE_FREQ', 2).";\n".
        'var listUpdateFrequency = '.Kurogo::getOptionalSiteVar('STOP_LIST_UPDATE_FREQ', 20).";\n"
      );
      $this->addOnLoad('showMap();');
      
    } else {
        $this->addOnLoad('autoReload('.self::RELOAD_TIME.');');
        $this->assign('autoReloadTime', self::RELOAD_TIME);
        $this->assign('mapImageSrc', $staticImage);
    }
    
    $this->assign('staticMap', !$MapDevice->pageSupportsDynamicMap());
  }
}
