<?php

includePackage('Transit');

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
    return strcmp($a['title'], $b['title']);
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
        
        //
        // News Pane
        //
        $newsConfigs = $view->getNews();
        
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
        
        $this->enableTabs(array(
          'running',
          'offline',
          'news',
          'info',
        ));
        
        $this->assign('runningRoutes', $runningRoutes);
        $this->assign('offlineRoutes', $offlineRoutes);
        $this->assign('news',          $news);
        $this->assign('infosections',  $infosections);
        break;
        
      case 'route':
        $routeID = $this->getArg('id');
        
        $routeConfig = $this->getModuleSection('module');

        $routeInfo = $view->getRouteInfo($routeID);
        foreach ($routeInfo['stops'] as $stopID => $stop) {
          $routeInfo['stops'][$stopID]['url']   = $this->stopURL($stopID);
          $routeInfo['stops'][$stopID]['title'] = $stop['name'];
          
          if ($stop['upcoming']) {
            $routeInfo['stops'][$stopID]['title'] = "<strong>{$stop['name']}</strong>";
            $routeInfo['stops'][$stopID]['imgAlt'] = Kurogo::getSiteVar('busImageAltText');
          }
          
          if ($stop['upcoming'] || $this->pagetype != 'basic') {
            $routeInfo['stops'][$stopID]['img'] = '/common/images/';
          }
          switch ($this->pagetype) {
            case 'basic':
              if ($stop['upcoming']) {
                $routeInfo['stops'][$stopID]['img'] .= 'bus.gif';
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

        $this->enableTabs(array('map', 'stops'));
        
        $mapImageSize = 270;
        if ($this->pagetype == 'basic') {
          $mapImageSize = 200;
        } else if ($this->pagetype == 'tablet') {
          $mapImageSize = 350;
        }

        $this->addOnLoad('rotateScreen(); autoReload('.self::RELOAD_TIME.');');
        $this->addOnOrientationChange('rotateScreen();');

        $this->assign('mapImageSrc',    $view->getMapImageForRoute($routeID, $mapImageSize, $mapImageSize));
        $this->assign('mapImageSize',   $mapImageSize);
        $this->assign('lastRefresh',    time());
        $this->assign('autoReloadTime', self::RELOAD_TIME);
        $this->assign('routeInfo',      $routeInfo);
        $this->assign('routeConfig',    $routeConfig);
        break;
      
      case 'stop':
        $stopID = $this->getArg('id');
        
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
        $this->addOnLoad('autoReload('.self::RELOAD_TIME.');');
        
        $this->assign('mapImageSrc',    $view->getMapImageForStop($stopID, $mapImageWidth, $mapImageHeight));
        $this->assign('mapImageWidth',  $mapImageWidth);
        $this->assign('mapImageHeight', $mapImageHeight);
        $this->assign('stopName',       $stopInfo['name']);
        $this->assign('runningRoutes',  $runningRoutes);
        $this->assign('offlineRoutes',  $offlineRoutes);
        $this->assign('lastRefresh',    time());
        $this->assign('autoReloadTime', self::RELOAD_TIME);
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
        $newsConfigs = $view->getNews();
        $newsID = $this->getArg('id');
        
        if (!isset($newsConfigs[$newsID])) {
          $this->redirectTo('index', array());
        }

        $this->assign('title',   $newsConfigs[$newsID]['title']);        
        $this->assign('date',    $newsConfigs[$newsID]['date']);        
        $this->assign('content', $newsConfigs[$newsID]['html']);        
        break;
    }
  }
}
