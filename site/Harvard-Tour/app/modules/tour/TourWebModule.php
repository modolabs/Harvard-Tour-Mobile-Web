<?php

class TourWebModule extends WebModule {
  protected $id = 'tour';
  private $tour = null;
  private $stop = '';
  const CURRENT_STOP_COOKIE = 'currentStop';
  const VISITED_STOPS_COOKIE = 'visitedStops';
  const COOKIE_DURATION = 31536000;  // 1 year
  const MAP_VIEW_OVERVIEW = 'overview';
  const MAP_VIEW_APPROACH = 'approach';
  const NEW_TOUR_PARAM = 'new';
  
  protected function initialize() {
    $stopId = false;
    $seenStopIds = array();
    
    if (isset($this->args['id'])) {
      $stopId = $this->args['id']; // user came from a page that set the stop
    }
    
    $newTour = $this->getArg(self::NEW_TOUR_PARAM, false);
    if ($newTour) {
      $expires = time() - 3600; // drop cookies on new tour
      setcookie(self::CURRENT_STOP_COOKIE,  '', $expires, COOKIE_PATH);
      setcookie(self::VISITED_STOPS_COOKIE, '', $expires, COOKIE_PATH);
    
    } else {
      if ($stopId === false && isset($_COOKIE[self::CURRENT_STOP_COOKIE]) && 
                                     $_COOKIE[self::CURRENT_STOP_COOKIE]) {
        $stopId = $_COOKIE[self::CURRENT_STOP_COOKIE]; // cookie is set
      }
    
      if (isset($_COOKIE[self::VISITED_STOPS_COOKIE])) {
        $seenStopIds = explode(',', $_COOKIE[self::VISITED_STOPS_COOKIE]);
      }
      
      // Add current stop if we are viewing a detail page
      if ($stopId !== false && $this->page == 'detail') {
        $seenStopIds[] = $stopId;
      }
    }
    
    $this->tour = new Tour($stopId, $seenStopIds);
    $this->stop = $this->tour->getCurrentStop();
    
    if (!$newTour) {
      // store new state
      $expires = time() + self::COOKIE_DURATION;
      setcookie(self::CURRENT_STOP_COOKIE,  $this->stop->getId(),       $expires, COOKIE_PATH);
      setcookie(self::VISITED_STOPS_COOKIE, implode(',', $seenStopIds), $expires, COOKIE_PATH);
    }
  }
  
  protected function markerImages() {
    return array(
      'current' => array(
        'src'      => FULL_URL_PREFIX.'modules/tour/images/map-pin-current@2x.png',
        'anchor'   => array(40, 40),
        'size'     => array(80, 80),
        'realSize' => array(160, 160),
        'shape'    => array(
          'coords' => array(27, 4, 53, 42),
          'type'   => 'rect',
        ),
      ),
      'visited' => array(
        'src' => FULL_URL_PREFIX.'modules/tour/images/map-pin-past@2x.png',
        'anchor'   => array(40, 40),
        'size'     => array(80, 80),
        'realSize' => array(160, 160),
        'shape'    => array(
          'coords' => array(27, 4, 53, 42),
          'type'   => 'rect',
        ),
      ),
      'other'   => array(
        'src' => FULL_URL_PREFIX.'modules/tour/images/map-pin@2x.png',
        'anchor'   => array(40, 40),
        'size'     => array(80, 80),
        'realSize' => array(160, 160),
        'shape'    => array(
          'coords' => array(27, 4, 53, 42),
          'type'   => 'rect',
        ),
      ),
    );
  }
  
  protected function getOverviewMapCenter() {
    return array(
      'lat' => 42.374464, 
      'lon' => -71.117232,
    );
  }
  
  protected function initializeMap($view) {
    switch ($this->platform) {
      case 'blackberry':
      case 'bbplus':
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
    }
    $markerImages = $this->markerImages();
    
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
      $markers = 'shadow:false|icon:'.$markerImages['visited']['src'];
      if ($_SERVER['SERVER_NAME'] == 'localhost') {
        $markers = 'color:0xCCCCCC|label:V';
      }
      $staticMap .= '&'.http_build_query(array(
        'markers' => $markers.$visited,
      ));
    }
    
    if ($current) {
      $markers = 'shadow:false|icon:'.$markerImages['current']['src'];
      if ($_SERVER['SERVER_NAME'] == 'localhost') {
        $markers = 'color:0xDD0000|label:C';
      }
      $staticMap .= '&'.http_build_query(array(
        'markers' => $markers.$current,
      ));
    }
    
    if ($other) {
      $markers = 'shadow:false|icon:'.$markerImages['other']['src'];
      if ($_SERVER['SERVER_NAME'] == 'localhost') {
        $markers = 'color:0xCCCCCC';
      }
      $staticMap .= '&'.http_build_query(array(
        'markers' => $markers.$other,
      ));
    }
    //$staticMap = 'http://66.228.68.52:8995/staticmap.png';
    
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
      'var tourIcons = '.json_encode($this->markerImages()).";\n".
      'var currentStopIndex = '.$currentStopIndex.";\n".
      'var selectedStopIndex = '.$currentStopIndex.";\n";

    $this->addExternalJavascript('http://maps.google.com/maps/api/js?sensor=true');
    $this->addInlineJavascript($scriptText);
    $this->addOnLoad('showMap();');
    $this->addOnOrientationChange('resizeMapOnChange();');
  }
  
  protected function hasTabForKey($tabKey, &$tabJavascripts) {
    return true;
  }
  
  protected function getBriefStopDetails($stop) {
    $coords = $stop->getCoords();
    
    return array(
      'id'        => $stop->getId(),
      'title'     => $stop->getTitle(),
      'subtitle'  => $stop->getSubtitle(),
      'url'       => $this->buildTourURL('map', array(
        'view' => self::MAP_VIEW_APPROACH, 
        'id'   => $stop->getId()
      )),
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
      foreach ($stop->getLensContents($lens) as $lensContent) {
        $stopDetails['lenses'][$lens][] = $lensContent->getContent();
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
      case 'index':
        if ($this->tour->isInProgress()) {
          $this->assign('resumeURL', $this->buildTourURL('map', array(
            'view' => self::MAP_VIEW_APPROACH,
          )));
        }
        
        $this->assign('startURL', $this->buildTourURL('map', array(
          'view' => self::MAP_VIEW_OVERVIEW,
          'id'   => $this->tour->getFirstGuidedTourStop()->getId(),
          self::NEW_TOUR_PARAM  => 1,
        )));
        
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
        
        $this->addOnLoad('setupStopList();');

        $this->assign('stops',       $this->getAllStopsDetails());
        $this->assign('mapViewURL',  $mapViewURL);
        $this->assign('newTour',     $newTour);
        $this->assign('doneURL',     $this->getArg('doneURL', $this->buildTourURL('index')));
        break;
      
      case 'map':
        $view = $this->getArg('view', self::MAP_VIEW_OVERVIEW);
        
        $this->initializeMap($view);
        
        $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
        $this->addOnLoad('setupSubtitleEllipsis();');

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
        }

        $this->assign('view', $view);
        break;
        
      case 'detail':
        $detailConfig = $this->loadPageConfigFile('detail', 'detailConfig');        
        $tabKeys = array_keys($stopInfo['lenses']);
        $tabJavascripts = array();
        
        $this->addOnLoad('setupVideoFrames();');
        $this->addOnOrientationChange('setTimeout(resizeVideoFrames, 0);');
        $this->addInlineJavascript('var hideTabs = '.json_encode(array_slice($tabKeys, 1)).';');
        
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
        $this->enableTabs($tabKeys, null, $tabJavascripts);

        $this->assign('tabKeys', $tabKeys);
        $this->assign('prevURL', $prevURL);
        $this->assign('nextURL', $nextURL);
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
