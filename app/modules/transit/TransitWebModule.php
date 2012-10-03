<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

Kurogo::includePackage('Transit');
Kurogo::includePackage('Maps');

class TransitWebModule extends WebModule {
    protected $id = 'transit';
    protected $defaultNewsModel = 'TransitNewsDataModel';
    protected $newsFeeds = array();
    protected $collapseRouteTabs = false;
    const RELOAD_TIME = 60;
    
    protected function initialize() {
        $config = $this->getModuleSection('module');
        if(isset($config['collapse_route_tabs'])){
            $this->collapseRouteTabs = $config['collapse_route_tabs'];
        }
    }   
  
    protected function getNewsForRoutes() {
        $news = array();
        
        if (!$this->newsFeeds) {
            $newsConfig = $this->getModuleSections('feeds-news');
            foreach ($newsConfig as $agencyID => $feedData) {
                $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : $this->defaultNewsModel;
                $controller = TransitNewsDataModel::factory($modelClass, $feedData);
                
                if ($controller) {
                    $this->newsFeeds[$agencyID] = $controller;
                }
            }
        }
        
        foreach ($this->newsFeeds as $agencyID => $feed) {
            $feed->setStart(0);
            $feed->setLimit(null);
            $items = $feed->items();
            if ($items) {
                if (!isset($news[$agencyID])) {
                    $heading = isset($newsConfig[$agencyID]['TITLE']) ? 
                        $newsConfig[$agencyID]['TITLE'] : $agencyID;
                    
                    $news[$agencyID] = array(
                        'heading' => $heading,
                        'items' => array(),
                    );
                }
              
                foreach ($items as $item) {
                    $content = $item->getContent();
                    if (!$content) {
                        $content = $item->getDescription();
                    }
                
                    $news[$agencyID]['items'][$item->getID()] = array(
                        'title' => $item->getTitle(),
                        'date'  => $item->getPubDate()->format('U'),
                        'url'   => $content ? $this->newsURL($item->getID()) : $item->getLink(),
                        'html'  => $content,
                    );
                }
            }
        }
        
        return $news;
    }

    protected function routeURL($routeID, $directionID=null, $addBreadcrumb=true, $noBreadcrumb=false, $paneLink=false) {
        $args = array(
            'id' => $routeID,
        );
        if ($directionID && $directionID !== TransitDataModel::LOOP_DIRECTION) {
            $args['direction'] = $directionID;
        }
      
        if ($paneLink || $noBreadcrumb) {
            return $this->buildURL('route', $args);
        } else {
            return $this->buildBreadcrumbURL('route', $args, $addBreadcrumb);
        }
    }
  
    protected function newsURL($newsID, $addBreadcrumb=true) {
        return $this->buildBreadcrumbURL('announcement', array(
            'id' => $newsID,      
        ), $addBreadcrumb);
    }
  
    protected function stopURL($stopID, $addBreadcrumb=true) {
        return $this->buildBreadcrumbURL('stop', array(
            'id' => $stopID,      
        ), $addBreadcrumb);
    }
    
    protected function gtfs2db() {
        if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1') {
            throw new KurogoException("GTFS database conversion can only be run from localhost");
        }
        
        $title = 'Success!';
        $message = 'Generated GTFS Database';
        $preformatted = '';
        $this->setTemplatePage(strtolower($this->page));
        
        try {
            $gtfsConfig = $this->getModuleSections('feeds-gtfs');

            $gtfsToDB = new StripGTFSToDB();
            foreach ($gtfsConfig as $gtfsIndex => $gtfsData) {
                $gtfsToDB->addGTFS($gtfsIndex, $gtfsData);
            }
            
            if (!$gtfsToDB->convert()) {  
                throw new Exception($gtfsToDB->getError());
            }
            $preformatted = $gtfsToDB->getMessages();

        } catch (Exception $e) {
            $title = 'Error!';
            $message = $e->getMessage();
        }
        $now = new DateTime();
        
        $this->assign('title', $title);
        $this->assign('date', $now->format('U'));
        $this->assign('message', $message);
        $this->assign('preformatted', $preformatted);
    }
  
    protected function initializeForPage() {
        if (strtolower($this->page) == 'gtfs2db') {
            $this->gtfs2db();
            return;
        }
        
        $view = DataModel::factory("TransitViewDataModel", $this->loadFeedData());
    
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
                            'url'   => $this->routeURL($routeID, null, false, true, true),
                        );
                    }
                }
                
                $this->assign('routes', $routes);
                break;
            
            case 'index':
                $indexConfig = $this->loadPageConfigFile('index', 'indexConfig');
                $tabs = array();
                
                //
                // Running and Offline Panes
                //
                $routeConfigs = $view->getRoutes();
                $runningRoutes = array_fill_keys(array_keys($indexConfig['agencies']), false);
                $offlineRoutes = array_fill_keys(array_keys($indexConfig['agencies']), false);
        
                foreach ($routeConfigs as $routeID => $routeConfig) {
                    $agencyID = $routeConfig['agency'];
                    $entry = array(
                        'title' => $routeConfig['name'],
                        'url'   => $this->routeURL($routeID),
                        'class' => $routeConfig['running'] ? 'running' : 'offline',
                    );
                    
                    if ($routeConfig['running'] || $this->collapseRouteTabs) {
                        if (!isset($runningRoutes[$agencyID]) || !$runningRoutes[$agencyID]) {
                            $heading = isset($indexConfig['agencies'][$agencyID]) ? 
                                $indexConfig['agencies'][$agencyID] : $agencyID;
                        
                            $runningRoutes[$agencyID] = array(
                                'heading' => $heading,
                                'items' => array(),
                            );
                        }
                        $runningRoutes[$agencyID]['items'][$routeID] = $entry;
                        if ($this->collapseRouteTabs && !$routeConfig['running']) {
                            $runningRoutes[$agencyID]['items'][$routeID]['class'] = 'offline';
                        }
                    } else {
                        if (!isset($offlineRoutes[$agencyID]) || !$offlineRoutes[$agencyID]) {
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
                
                // Remove empty sections
                $runningRoutes = array_filter($runningRoutes);
                $offlineRoutes = array_filter($offlineRoutes);
        
                // Display running and offline routes in separate tabs if we have both
                if ($runningRoutes) {
                    $tabs[] = 'running';
                }
                if ($offlineRoutes) {
                    $tabs[] = 'offline';
                }
                $this->assign('runningRoutes', $runningRoutes);
                $this->assign('offlineRoutes', $offlineRoutes);
        
                //
                // News Pane
                //
                $news = $this->getNewsForRoutes();
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
                
                $direction = $this->getArg('direction', null);
                if (count($routeInfo['directions']) == 1) {
                    $directionIDs = array_keys($routeInfo['directions']);
                    $direction = reset($directionIDs);
                }
                
                if (isset($direction) && isset($routeInfo['directions'][$direction])) {
                    $this->assign('direction', $direction);
                    
                    switch ($routeInfo['view']) {
                        case 'schedule':
                            foreach ($routeInfo['directions'][$direction]['stops'] as $i => $stop) {
                                $routeInfo['directions'][$direction]['stops'][$i]['url'] = $this->stopURL($stop['id']);
                            }
                            break;
                            
                        case 'list':
                        default:
                            foreach ($routeInfo['directions'][$direction]['stops'] as $i => $stop) {
                                $routeInfo['directions'][$direction]['stops'][$i]['url']   = $this->stopURL($stop['id']);
                                $routeInfo['directions'][$direction]['stops'][$i]['title'] = $stop['name'];
                                
                                if ($stop['upcoming']) {
                                    $routeInfo['directions'][$direction]['stops'][$i]['title'] = "<strong>{$stop['name']}</strong>";
                                    $routeInfo['directions'][$direction]['stops'][$i]['imgAlt'] = $this->getLocalizedString('CURRENT_STOP_ICON_ALT_TEXT');
                                }
                                
                                if ($stop['upcoming'] || $this->pagetype != 'basic') {
                                    $routeInfo['directions'][$direction]['stops'][$i]['img'] = '/modules/transit/images/';
                                }
                                switch ($this->pagetype) {
                                    case 'basic':
                                        if ($stop['upcoming']) {
                                            $routeInfo['directions'][$direction]['stops'][$i]['img'] .= 'shuttle.gif';
                                        }
                                        break;
                                    
                                    case 'touch':
                                        $routeInfo['directions'][$direction]['stops'][$i]['img'] .= $stop['upcoming'] ? 'shuttle.gif' : 'shuttle-spacer.gif';
                                        break;
                                      
                                    default:
                                        $routeInfo['directions'][$direction]['stops'][$i]['img'] .= $stop['upcoming'] ? 'shuttle.png' : 'shuttle-spacer.png';
                                        break;
                                }
                            }
                            break;
                    }
      
                    $tabs = array();
                    $tabsJavascript = array();
                    
                    if ($routeInfo['directions'][$direction]['stops']) {
                        array_unshift($tabs, 'stops');
                        array_unshift($tabsJavascript, '');
                        $this->assign('hasStops', true);
                    }
                    
                    $paths = $view->getRoutePaths($routeID);
                    $vehicles = $view->getRouteVehicles($routeID);
                    if ($paths || $vehicles) {
                        array_unshift($tabs, 'map');
                        array_unshift($tabsJavascript, 'mapResizeHandler()');
                        $this->assign('hasRouteMap', true);
                        
                        $this->initMapForRoute($routeID, $routeInfo, $paths, $vehicles, $view);
                    } else {
                        $this->initListUpdate();
                    }
                    
                    if (count($tabs) > 1) {
                        $this->enableTabs($tabs);
                    }
                    
                } else if (count($routeInfo['directions'])) {
                    $this->setPageTitles('Directions');
                    $this->setBreadcrumbLongTitle($routeInfo['name'].' Directions');
            
                    $this->setTemplatePage('directions');
                    
                    $directionArgs = $this->args;
                    $directionsList = array();
                    foreach ($routeInfo['directions'] as $direction => $directionInfo) {
                        $directionArgs['direction'] = $direction;
                        $directionRunning = count($directionInfo['segments']) > 0;
                        
                        $directionList[] = array(
                            'title' => $directionInfo['name'],
                            'url'   => $this->buildBreadcrumbURL($this->page, $directionArgs),
                            'class' => $directionRunning ? 'running' : 'offline',
                        );
                    }
                    
                    $this->assign('directionList', $directionList);
                }
                
                $this->assign('routeInfo', $routeInfo);
        
                // Ajax page view
                if ($isAjax) {
                    $this->setTemplatePage('routeajax');
                    break;
                }
                
                $this->assign('lastRefresh',      time());
                $this->assign('serviceInfo',      $view->getServiceInfoForRoute($routeID));
                $this->assign('stopTimeHelpText', $this->getOptionalModuleVar('stopTimeHelpText', ''));
                break;
            
            case 'stop':
                $stopID = $this->getArg('id');
                $isAjax = $this->getArg('ajax', 0);
                
                unset($this->args['ajax']); // do not propagate 
        
                $stopInfo = $view->getStopInfo($stopID);
                
                $runningRouteIDs = array();
                $runningRoutes = array();
                $offlineRouteIDs = array();
                $offlineRoutes = array();
                foreach ($stopInfo['routes'] as $routeID => $routeInfo) {
                    foreach ($routeInfo['directions'] as $directionID => $directionInfo) {
                        $entry = array(
                            'title' => $routeInfo['name'],
                            'url'   => $this->routeURL($routeID, $directionID, false, true), // no breadcrumbs
                        );
                        if (isset($directionInfo['predictions'])) {
                            $entry['predictions'] = $directionInfo['predictions'];
                        }
                        if ($directionInfo['name'] && $directionID != TransitDataModel::LOOP_DIRECTION) {
                            $entry['title'] .= '<br/>'.$directionInfo['name'];
                        }
          
                        if ($directionInfo['running']) {
                            $runningRoutes[] = $entry;
                            $runningRouteIDs[] = $routeID;
                        } else {
                            $offlineRoutes[] = $entry;
                            $offlineRouteIDs[] = $routeID;
                        }
                    }
                }
                
                $this->assign('runningRoutes', $runningRoutes);
                $this->assign('offlineRoutes', $offlineRoutes);
                
                // Ajax page view
                if ($isAjax) {
                    $this->setTemplatePage('stopajax');
                    break;
                }
                
                $serviceInfo = false;
                if (count($runningRoutes)) {
                    $serviceInfo = $view->getServiceInfoForRoute(reset($runningRouteIDs));
                } else if (count($offlineRoutes)) {
                    $serviceInfo = $view->getServiceInfoForRoute(reset($offlineRouteIDs));
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
                $markers = array($stopID => array(
                    'lat' => $stopInfo['coordinates']['lat'],
                    'lon' => $stopInfo['coordinates']['lon'],
                    'iconURL' => $stopInfo['stopIconURL'],
                    'title' => '',
                ));
                $this->initMap($staticImage, $markers);
                
                $this->assign('stopName',      $stopInfo['name']);
                $this->assign('lastRefresh',   time());
                $this->assign('serviceInfo',   $serviceInfo);
                break;
            
            case 'fullscreen':
                $type = $this->getArg('type');
                if ($type == 'route') {
                    $routeID = $this->getArg('id');
                    
                    $routeInfo = $view->getRouteInfo($routeID);
                    $paths = $view->getRoutePaths($routeID);
                    $vehicles = $view->getRouteVehicles($routeID);
                    if ($routeInfo && ($paths || $vehicles)) {
                        $this->initMapForRoute($routeID, $routeInfo, $paths, $vehicles, $view);
                    } else {
                        $this->redirectTo('route', array(
                            'id' => $routeID,
                        ));
                    }
                  
                } else if ($type == 'stop') {
                    $stopID = $this->getArg('id');
                  
                    $stopInfo = $view->getStopInfo($stopID);
                    if ($stopInfo) {
                        $this->initMapForStop($stopID, $stopInfo, $view);
                    } else {
                        $this->redirectTo('stop', array(
                            'id' => $stopID,
                        ));
                    }
                } else {
                    $this->redirectTo('index');
                }
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
                $newsID = $this->getArg('id');
                $news = $this->getNewsForRoutes();
                
                $found = false;
                foreach ($news as $agencyID => $agencyNews) {
                    if (isset($agencyNews['items'][$newsID])) {
                        $this->assign('title',   $agencyNews['items'][$newsID]['title']);
                        $this->assign('date',    $agencyNews['items'][$newsID]['date']);
                        $this->assign('content', $agencyNews['items'][$newsID]['html']);
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $this->redirectTo('index', array());
                }
                break;
        }
    }
    
    function initMapForRoute($routeID, $routeInfo, $paths, $vehicles, $view) {
        $mapImageWidth = $mapImageHeight = 270;
        if ($this->pagetype == 'basic') {
            $mapImageWidth = $mapImageHeight = 200;
        } else if ($this->pagetype == 'tablet') {
            $mapImageWidth = $mapImageHeight = 350;
        }
        $this->assign('mapImageWidth',  $mapImageWidth);
        $this->assign('mapImageHeight', $mapImageHeight);
    
        $staticImage = $view->getMapImageForRoute($routeID, $mapImageWidth, $mapImageHeight);
        $markers = array();
        foreach ($vehicles as $vehicleID => $vehicle) {
            $markers[$vehicleID] = array(
                'lat' => $vehicle['lat'],
                'lon' => $vehicle['lon'],
                'iconURL' => $vehicle['iconURL'],
                'title' => '',
            );
        }
        $markerUpdateURL = FULL_URL_BASE.API_URL_PREFIX."/{$this->configModule}/vehicles?id={$routeID}";
        $this->initMap($staticImage, $markers, $markerUpdateURL, $paths, $routeInfo['color']);
        
        $this->addOnOrientationChange('setOrientation(getOrientation());');
    }
    
    function initMapForStop($stopID, $stopInfo, $view) {
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
        $markers = array($stopID => array(
            'lat' => $stopInfo['coordinates']['lat'],
            'lon' => $stopInfo['coordinates']['lon'],
            'iconURL' => $stopInfo['stopIconURL'],
            'title' => '',
        ));
        $this->initMap($staticImage, $markers);
    }
    
    protected function initMap($staticImage, $markers, $markerUpdateURL='', $paths=array(), $pathColor=null) {
        $MapDevice = new MapDevice($this->pagetype, $this->platform);
        
        if ($MapDevice->pageSupportsDynamicMap()) {
            // Fit detail map to screen if it is the route map or if it is the stop map on tablet:
            $fitMapToScreen = $this->pagetype == 'tablet' || $this->page == 'route';
            
            $this->addExternalJavascript('http://maps.googleapis.com/maps/api/js?sensor=true');
            $this->addInlineJavascript("\n".
                'var mapMarkers = '.json_encode($markers).";\n".
                'var mapPaths = '.json_encode($paths).";\n".
                'var mapPathColor = "'.$pathColor."\";\n".
                'var markerUpdateURL = "'.$markerUpdateURL."\";\n".
                'var markerUpdateFrequency = '.$this->getOptionalModuleVar('MAP_MARKER_UPDATE_FREQ', 4).";\n".
                'var userLocationMarkerURL = "'.FULL_URL_PREFIX."modules/map/images/map-location@2x.png\";\n".
                'var isFullscreen = '.($this->page == 'fullscreen' ? 'true' : 'false').";\n".
                'var fitMapToScreen = '.($fitMapToScreen ? 'true' : 'false').";\n"
            );
            
            $this->addOnLoad('showMap();');
            $this->addOnOrientationChange('handleMapResize();');
            $this->initListUpdate();
            
        } else {
            $this->addOnLoad('autoReload('.self::RELOAD_TIME.');');
            $this->assign('autoReloadTime', self::RELOAD_TIME);
            $this->assign('mapImageSrc', $staticImage);
        }
        
        $this->assign('staticMap', !$MapDevice->pageSupportsDynamicMap());
          
        if ($this->page == 'fullscreen') {
            $this->assign('fullscreen', true);
            $this->assign('returnURL', $this->buildBreadcrumbURL($this->getArg('type'), array(
                'id' => $this->getArg('id'),
            ), false));
        } else {
            $this->assign('fullscreenURL', $this->buildBreadcrumbURL('fullscreen', array(
                'type' => $this->page,
                'id' => $this->getArg('id'),
            ), false));
        }
    }
    
    function initListUpdate() {
        $listUpdateURL = FULL_URL_PREFIX.ltrim($this->buildURL($this->page, array_merge(array('ajax' => 1), $this->args)), '/');
        $this->addInlineJavascript("\n".
            'var htmlUpdateURL = "'.$listUpdateURL."\";\n".
            'var listUpdateFrequency = '.$this->getOptionalModuleVar('STOP_LIST_UPDATE_FREQ', 20).";\n"
        );
        $this->addOnLoad('initListUpdate();');
    }
}
