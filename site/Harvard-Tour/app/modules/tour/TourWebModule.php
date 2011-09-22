<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

class TourWebModule extends WebModule {
  protected $id = 'tour';
  private $tour = null;
  private $stop = '';
  private $currentStopPage = 'approach';
  const CURRENT_STOP_COOKIE = 'currentStop';
  const CURRENT_PAGE_COOKIE = 'currentPage';
  const FIRST_STOP_COOKIE = 'firstStop';
  const VISITED_STOPS_COOKIE = 'visitedStops';
  const COOKIE_DURATION = 31536000;  // 1 year
  const MAP_VIEW_OVERVIEW = 'overview';
  const MAP_VIEW_APPROACH = 'approach';
  const NEW_TOUR_PARAM = 'new';
  
  protected function initialize() {
    $stopId = false;
    $firstStopId = false;
    $seenStopIds = array();
    
    if (isset($this->args['id'])) {
      $stopId = $this->args['id']; // user came from a page that set the stop
    }
    
    if (isset($this->args['firstStopId'])) {
      $firstStopId = $this->args['firstStopId']; // user came from a page that set the stop
    }
    
    if ($stopId === false && self::argVal($_COOKIE, self::CURRENT_STOP_COOKIE, '')) {
      $stopId = $_COOKIE[self::CURRENT_STOP_COOKIE]; // cookie is set
    }
    
    // Remember which page we are on
    if (isset($_COOKIE[self::CURRENT_PAGE_COOKIE])) {
      $this->currentStopPage = $_COOKIE[self::CURRENT_PAGE_COOKIE];
    }
    if ($this->page == 'map') {
      if ($this->getArg('view', self::MAP_VIEW_OVERVIEW) != self::MAP_VIEW_OVERVIEW) {
        $this->currentStopPage = 'approach';
      } else if ($this->getArg(self::NEW_TOUR_PARAM, false)) {
        $this->currentStopPage = 'start';
      }
    }
    if ($this->page == 'detail') {
      $this->currentStopPage = 'detail';
    }
 
    if ($firstStopId === false && self::argVal($_COOKIE, self::FIRST_STOP_COOKIE, '')) {
      $firstStopId = $_COOKIE[self::FIRST_STOP_COOKIE]; // cookie is set
    }
  
    if (isset($_COOKIE[self::VISITED_STOPS_COOKIE])) {
      $seenStopIds = explode(',', $_COOKIE[self::VISITED_STOPS_COOKIE]);
    }
    
    // Add current stop if we are viewing a detail page
    if ($stopId !== false && $this->page == 'detail') {
      $seenStopIds[] = $stopId;
    }
    
    $this->tour = new Tour($stopId, $firstStopId, $seenStopIds);
    $this->stop = $this->tour->getCurrentStop();
    
    // store new state
    $expires = time() + self::COOKIE_DURATION;
    setcookie(self::CURRENT_STOP_COOKIE,  $this->stop->getId(),          $expires, COOKIE_PATH);
    setcookie(self::CURRENT_PAGE_COOKIE,  $this->currentStopPage,        $expires, COOKIE_PATH);
    setcookie(self::FIRST_STOP_COOKIE,    $this->tour->getFirstStopId(), $expires, COOKIE_PATH);
    setcookie(self::VISITED_STOPS_COOKIE, implode(',', $seenStopIds),    $expires, COOKIE_PATH);
  }
  
  function startNewTour() {
    // drop cookies on new tour
    $expires = time() - 3600;
    setcookie(self::CURRENT_STOP_COOKIE,  '', $expires, COOKIE_PATH);
    setcookie(self::CURRENT_PAGE_COOKIE,  '', $expires, COOKIE_PATH);
    setcookie(self::FIRST_STOP_COOKIE,    '', $expires, COOKIE_PATH);
    setcookie(self::VISITED_STOPS_COOKIE, '', $expires, COOKIE_PATH);
  }
  
  protected function getStaticMarkerImages() {
    return array(
      'current' => FULL_URL_PREFIX.'modules/tour/images/map-pin-current.png',
      'visited' => FULL_URL_PREFIX.'modules/tour/images/map-pin-past.png',
      'other'   => FULL_URL_PREFIX.'modules/tour/images/map-pin.png',
    );
  }
  
  protected function getDynamicMarkerImages() {
    return array(
      'current' => array(
        'src'      => FULL_URL_PREFIX.'modules/tour/images/map-pin-current@2x.png',
        'anchor'   => array(13, 37),
        'size'     => array(28, 40),
      ),
      'visited' => array(
        'src' => FULL_URL_PREFIX.'modules/tour/images/map-pin-past@2x.png',
        'anchor'   => array(13, 37),
        'size'     => array(28, 40),
      ),
      'other'   => array(
        'src' => FULL_URL_PREFIX.'modules/tour/images/map-pin@2x.png',
        'anchor'   => array(13, 37),
        'size'     => array(28, 40),
      ),
      'shadow'   => array(
        'src' => FULL_URL_PREFIX.'modules/tour/images/map-pin-shadow@2x.png',
        'anchor'   => array(7, 27),
        'size'     => array(44, 29),
      ),
      'self'   => array(
        'src' => FULL_URL_PREFIX.'modules/tour/images/map-location@2x.png',
        'anchor'   => array(8, 8),
        'size'     => array(16, 16),
      ),
    );
  }
  
  protected function getOverviewMapCenter() {
    return array(
      'lat' => floatval(Kurogo::getSiteVar('TOUR_CENTER_LAT')),
      'lon' => floatval(Kurogo::getSiteVar('TOUR_CENTER_LON')),
    );
  }
  
  protected function initializeMap($view) {
    switch ($this->platform) {
      case 'blackberry':
      case 'bbplus':
      case 'winphone7': // does not support touchmove and friends
        return $this->initializeStaticMap($view);
        
      default:
        return $this->initializeDynamicMap($view);
    }
  }
  
  protected function initializeStaticMap($view) {
	// Default Google Static Map size for any Compliant phones other than BlackBerries that can't use Google Dynamic Maps
    $x = 314;
    $y = 270;
    if ($this->platform == 'bbplus') {   // Google Static Map size for wide 480x360 screen 
      $x = 464;
      $y = 280;
    } else if ($this->platform == 'blackberry') {   // Google Static Map size for 360px-wide Storm, Storm2, and Torch screens
      $x = 354;
      $y = 272;
    } else if ($this->platform == 'winphone7') {
      $x = 320;
      $y = 262;
    }
    $markerImages = $this->getStaticMarkerImages();
    
    $staticMap = 'http://maps.google.com/maps/api/staticmap?sensor=false&size='.$x.'x'.$y;
    if ($view == self::MAP_VIEW_OVERVIEW) {
      $center = $this->getOverviewMapCenter();
    
      $staticMap .= '&center='.$center['lat'].','.$center['lon'];
    } else {
      $staticMap .= '&zoom=17';
    }
    
    $visited = '';
    $current = '';
    $other   = '';
    $tourStops = $this->getAllStopsDetails();
    foreach ($tourStops as $stop) {
      if ($stop['visited']) {
        $visited .= '|'.$stop['lat'].','.$stop['lon'];
        
      } else if ($stop['current']) {
        $current .= '|'.$stop['lat'].','.$stop['lon'];
        if ($view != self::MAP_VIEW_OVERVIEW) {
          $staticMap .= '&center='.$stop['lat'].','.$stop['lon'];
        }
        
      } else {
        $other .= '|'.$stop['lat'].','.$stop['lon'];
      }
    }
    
    if ($visited) {
      $markers = 'icon:'.$markerImages['visited'];
      if ($_SERVER['SERVER_NAME'] == 'localhost') {
        $markers = 'color:0xCCCCCC|label:V';
      }
      $staticMap .= '&'.http_build_query(array(
        'markers' => $markers.$visited,
      ));
    }
    
    if ($current) {
      $markers = 'icon:'.$markerImages['current'];
      if ($_SERVER['SERVER_NAME'] == 'localhost') {
        $markers = 'color:0xDD0000|label:C';
      }
      $staticMap .= '&'.http_build_query(array(
        'markers' => $markers.$current,
      ));
    }
    
    if ($other) {
      $markers = 'icon:'.$markerImages['other'];
      if ($_SERVER['SERVER_NAME'] == 'localhost') {
        $markers = 'color:0xCCCCCC';
      }
      $staticMap .= '&'.http_build_query(array(
        'markers' => $markers.$other,
      ));
    }
    
    $this->assign('staticMap', $staticMap);
  }
  
  protected function initializeDynamicMap($view) {
    // Add prefix to urls which will be set via Javascript
    $fitToBounds = array();
    $currentStopIndex = 0;
    
    $tourStops = $this->getAllStopsDetails();
    foreach ($tourStops as $i => $tourStop) {
      $tourStops[$i]['index'] = $i;
      if ($tourStop['current']) {
        $currentStopIndex = $i;
      } else {
        $tourStops[$i]['jumpText'] = '';
      }
      if ($view == self::MAP_VIEW_OVERVIEW || 
          $tourStop['current'] ||
          (isset($tourStops[$i+1]) && $tourStops[$i+1]['current'])) {
        $fitToBounds[] = array('lat' => $tourStop['lat'], 'lon' => $tourStop['lon']);
      }
      if ($view == self::MAP_VIEW_OVERVIEW) {
        $tourStops[$i]['url'] = URL_PREFIX.ltrim($tourStop['url'], '/');
      } else {
        $tourStops[$i]['url'] = URL_PREFIX.ltrim($this->buildTourURL('detail', array(
          'id' => $tourStop['id'],
        )), '/');
      }
    }
    
    $scriptText = "\n".
      'var centerCoords = '.json_encode($this->getOverviewMapCenter())."\n".
      'var fitToBounds = '.json_encode($fitToBounds)."\n".
      'var tourStops = '.json_encode($tourStops).";\n".
      'var tourIcons = '.json_encode($this->getDynamicMarkerImages()).";\n".
      'var currentStopIndex = '.$currentStopIndex.";\n".
      'var selectedStopIndex = '.$currentStopIndex.";\n";

    $this->addExternalJavascript('http://maps.google.com/maps/api/js?sensor=true');
    $this->addInlineJavascript($scriptText);
    $this->addOnLoad('showMap();');
    $this->addOnOrientationChange('resizeMapOnChange();');
  }
    
  protected function getBriefStopDetails($stop) {
    $coords = $stop->getCoords();
    
    $urlParams = array(
      'view' => self::MAP_VIEW_APPROACH, 
      'id'   => $stop->getId(),
    );
    if ($this->getArg(self::NEW_TOUR_PARAM, false)) {
      $urlParams['firstStopId'] = $stop->getId();
    }
    
    return array(
      'id'        => $stop->getId(),
      'title'     => $stop->getTitle(),
      'subtitle'  => $stop->getSubtitle(),
      'url'       => $this->buildTourURL('map', $urlParams),
      'photo'     => $stop->getPhotoSrc(),
      'thumbnail' => $stop->getThumbnailSrc(),
      'lat'       => $coords['lat'],
      'lon'       => $coords['lon'],
      'visited'   => $stop->wasVisited(),
      'current'   => $stop->isCurrent(),
      'lenses'    => array_fill_keys($stop->getAvailableLenses(), array()),
    );
  }
  
  protected function getStopDetails($stop) {
    $stopDetails = $this->getBriefStopDetails($stop);
    
    $lenses = $stop->getAvailableLenses();
    foreach ($stopDetails['lenses'] as $lens => $contents) {
      $previousContentIsVideo = false;
      foreach ($stop->getLensContents($lens) as $lensContent) {
        $content = $lensContent->getContent();
        
        if ($lensContent instanceOf TourVideo) {
          $previousContentIsVideo = true;
          
        } else if ($previousContentIsVideo) {
          $imageSuffix = '@2x.png';
          switch ($this->platform) {
            case 'blackberry':
            case 'bbplus':
            case 'winphone7':
              $imageSuffix = '.png';
          }
          $content = preg_replace('/^(<(p|div)[^>]*>)/', '\1<img class="headphones" src="modules/tour/images/headphones'.$imageSuffix.'" alt="has an audio track" width="20" height="16" /> ', $content);
          $previousContentIsVideo = false;
        }
        $stopDetails['lenses'][$lens][] = $content;
      }
    }
    
    return $stopDetails;
  }
  
  protected function getAllStopsDetails() {
    $stopsDetails = array();
    
    foreach ($this->tour->getAllStops() as $stop) {
      $stopsDetails[] = $this->getBriefStopDetails($stop);
    }
    return $stopsDetails;
  }
  
  protected function getPageContents($page) {
    $pageContents = array();
    
    $pageObjects = array();
    switch ($page) {
      case 'welcome':
        $pageObjects = $this->tour->getWelcomePageContents();
        break;
        
      case 'finish':
        $pageObjects = $this->tour->getFinishPageContents();
        break;
        
      case 'help':
        $pageObjects = $this->tour->getHelpPageContents();
        break;
        
      case 'legend':
        $pageObjects = $this->tour->getStopDetailLegendContents();
        break;
    }
    
    foreach ($pageObjects as $pageObject) {
      $pageContents[] = $pageObject->getContent();
    }
    
    return $pageContents;
  }
  
  protected function buildTourURL($page, $args=array(), $newTour=false) {
    if (!isset($args['id'])) {
      $args['id'] = $this->stop->getId();
    }
    return $this->buildURL($page, $args);
  }
  
  protected function initializeForPage() {
    $stopInfo = $this->getStopDetails($this->stop);
    
    $showMapLink = true;
    $showHelpLink = true;
    
    if ($this->pagetype != 'compliant' && $this->page != 'index') {
      $this->redirectTo('index');
    }
    
    switch ($this->page) {
      case 'start':
        $this->startNewTour();
        
        $this->redirectTo('map', array(
          'view' => self::MAP_VIEW_OVERVIEW,
          'id'   => $this->tour->getFirstGuidedTourStop()->getId(),
          self::NEW_TOUR_PARAM  => 1,
        ));
        break;
      
      case 'startover':
        $this->startNewTour();
        
        $this->redirectTo('index');
        break;
    
      case 'index':
        if ($this->tour->isInProgress()) {
          if ($this->currentStopPage == 'approach') {
            $this->assign('resumeURL', $this->buildTourURL('map', array(
              'view'   => self::MAP_VIEW_APPROACH,
            )));
            
          } else if ($this->currentStopPage == 'detail') {
            $this->assign('resumeURL', $this->buildTourURL('detail'));
            
          } else {
            $this->assign('resumeURL', $this->buildTourURL('map', array(
              'view' => self::MAP_VIEW_OVERVIEW,
              self::NEW_TOUR_PARAM  => 1,
            )));
          }
        }
        
        $this->assign('startURL', $this->buildTourURL('start'));
        
        $this->assign('contents', $this->getPageContents('welcome'));
        break;
      
      case 'tourhelp':
        $showHelpLink = false;
        $this->assign('contents', $this->getPageContents('help'));
        $this->assign('doneURL',  $this->getArg('doneURL', $this->buildTourURL('index')));
        break;
      
      case 'finish':
        $showMapLink = false;
        $showHelpLink = false;

        $prevURL = false;
        $prevStop = $this->tour->getLastStop();
        if ($prevStop) {
          $prevURL = $this->buildTourURL('detail', array(
            'id' => $prevStop->getId(),
          ));
        } else {
          $prevURL = $this->buildTourURL('map', array(
            'view' => self::MAP_VIEW_OVERVIEW_PARAM,
          ));
        }
        $nextURL = false;
        
        $this->assign('startOverURL', $this->buildURL('startover'));
        $this->assign('prevURL',  $prevURL);
        $this->assign('nextURL',  $nextURL);
        $this->assign('contents', $this->getPageContents('finish'));
        break;
      
      case 'list':
        $newTour = $this->getArg(self::NEW_TOUR_PARAM, false);
        
        $args = $this->args;
        $args['view'] = self::MAP_VIEW_OVERVIEW;
        if ($newTour) {
          $args[self::NEW_TOUR_PARAM] = 1;
        }
        $mapViewURL = $this->buildTourURL('map', $args);

        $showMapLink = false;
        
        $this->assign('stops',       $this->getAllStopsDetails());
        $this->assign('mapViewURL',  $mapViewURL);
        $this->assign('newTour',     $newTour);
        $this->assign('doneURL',     $this->getArg('doneURL', $this->buildTourURL('index')));
        break;
      
      case 'map':
        $view = $this->getArg('view', self::MAP_VIEW_OVERVIEW);
        
        $this->initializeMap($view);
        
        if ($view == self::MAP_VIEW_OVERVIEW) {
          $stopInfo['url'] = $this->buildTourURL('map', array(
            'view' => self::MAP_VIEW_APPROACH
          ));

          $showMapLink = false;
          
          $this->assign('listViewURL', $this->buildTourURL('list', $this->args));
          $this->assign('newTour',     $this->getArg(self::NEW_TOUR_PARAM, false));
          $this->assign('doneURL',     $this->getArg('doneURL', $this->buildTourURL('index')));
          
        } else {
          // Approach
          $prevURL = false;
          $prevStop = $this->tour->getPreviousStop();
          if ($prevStop) {
            $prevURL = $this->buildTourURL('detail', array(
              'id' => $prevStop->getId(),
            ));
          } else {
            $prevURL = $this->buildTourURL('map', array(
              'view' => self::MAP_VIEW_OVERVIEW,
              self::NEW_TOUR_PARAM  => 1,
            ));
          }
          $nextURL = $this->buildTourURL('detail');
          
          $this->assign('prevURL', $prevURL);
          $this->assign('nextURL', $nextURL);          
          
          // For static map help text
          $this->assign('listViewURL', $this->buildTourURL('list', array(
            'doneURL' => $this->buildTourURL($this->page, $this->args),
          )));
        }

        $this->assign('view', $view);
        break;
        
      case 'detail':
        $detailConfig = $this->loadPageConfigFile('detail', 'detailConfig');        

        $tabKeys = array_keys($stopInfo['lenses']);
        $tabJavascripts = array();
        $this->enableTabs($tabKeys, null, $tabJavascripts);
        $this->addInlineJavascript('var currentTourTab = "'.reset($tabKeys).'";');
        
        $this->addOnLoad('setupVideoFrames();checkTourTab();');
        $this->addOnOrientationChange('setTimeout(resizeVideoFrames, 0);');
        
        $prevURL = $this->buildTourURL('map', array(
          'view' => self::MAP_VIEW_APPROACH
        ));
        $nextURL = false;
        $nextStop = $this->tour->getNextStop();
        if ($nextStop) {
          $nextURL = $this->buildTourURL('map', array(
            'view'   => self::MAP_VIEW_APPROACH,
            'id'     => $nextStop->getId(),
          ));
        } else {
          $nextURL = $this->buildTourURL('finish');
        }
        
        $this->assign('prevURL', $prevURL);
        $this->assign('nextURL', $nextURL);
        $this->assign('legend',  $this->getPageContents('legend'));
        break;
        
    }
    
    $this->assign('stop', $stopInfo);
    
    if ($showMapLink) {
      $this->assign('mapLink', $this->buildTourURL('map', array(
        'view'    => self::MAP_VIEW_OVERVIEW,
        'doneURL' => $this->buildTourURL($this->page, $this->args),
      )));
    }
    if ($showHelpLink) {
      $this->assign('helpLink', $this->buildTourURL('tourhelp', array(
        'doneURL' => $this->buildTourURL($this->page, $this->args),
      )));
    }

  }
}
