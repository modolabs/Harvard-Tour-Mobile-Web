<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

class Tour {
  protected $stops = array();
  protected $firstGuidedTourStopId = '';
  protected $currentStopId = '';
  protected $inProgress = false;
  protected $lastupdate = 0;
  protected $legend = array();
  protected $pages = array(
    'welcome' => array(),
    'finish'  => array(),
    'help'    => array(),
  );
  
  function buildContentsFromTourData($dataContents) {
    $contents = array();
    foreach ($dataContents as $content) {
      switch ($content['type']) {
        case 'text':
          $contents[] = new TourText($content['text']);
          break;
        
        case 'links':
          $contents[] = new TourLinks($content['links']);
          break;
          
        case 'lensinfo':
          $contents[] = new TourLensInfo($content['lenses']);
          break;

        default: 
          error_log("Unknown content type {$content['type']}");
          break;
      }
    }
    
    return $contents;
  }

  function __construct($stopId = false, $firstStopId = false, $seenStopIds = array(), $useCache=true) {
    $parser = new TourDataParser($useCache);
    $tourData = $parser->getTourData();
    
    $this->lastupdate = $tourData['updated'];
    $this->legend = $this->buildContentsFromTourData($tourData['legend']);

    foreach ($tourData['pages'] as $pageKey => $pageContents) {
      $this->pages[$pageKey] = $this->buildContentsFromTourData($pageContents);
    }
    
    // Figure out which of the seen stop ids is in this tour
    $existingSeenStopIds = array();
    foreach ($seenStopIds as $seenStopId) {
      if (isset($tourData['stops'][$seenStopId])) {
        $existingSeenStopIds[] = $seenStopId;
      }
    }
    
    $guidedTourStopIdOrder = array_keys($tourData['stops']);
    $this->firstGuidedTourStopId = reset($guidedTourStopIdOrder);
    
    // Figure out current stop
    if ($stopId !== false && in_array($stopId, $guidedTourStopIdOrder)) {
      $this->currentStopId = $stopId;
      
    } else if (count($existingSeenStopIds)) {
      $this->currentStopId = reset($existingSeenStopIds);
      
    } else {
      $this->currentStopId = $this->firstGuidedTourStopId;
    }
    
    // Figure out if the tour is in progress
    if ($stopId && ($stopId != $this->firstGuidedTourStopId || count($seenStopIds))) {
      $this->inProgress = true;
    }
    
    // Get the real stop order as defined by the first stop the user visited
    $stopIdOrder = $guidedTourStopIdOrder;
    if ($firstStopId !== false && $firstStopId != $this->firstGuidedTourStopId) {
      $stopIdToGuidedTourIndex = array_flip($guidedTourStopIdOrder);
      $firstStopGuidedTourIndex = $stopIdToGuidedTourIndex[$firstStopId];
      
      $stopIdOrder = array_merge(
        array_slice($guidedTourStopIdOrder, $firstStopGuidedTourIndex),
        array_slice($guidedTourStopIdOrder, 0, $firstStopGuidedTourIndex));
    }
    
    foreach ($stopIdOrder as $id) {
      $this->stops[$id] = new TourStop($id, $tourData['stops'][$id]);

      if ($id == $this->currentStopId) {
        $this->stops[$id]->setIsCurrent($id == $this->currentStopId);
        
      } else if (in_array($id, $existingSeenStopIds)) {
        $this->stops[$id]->setWasVisited(true);
      }
    }
  }
  
  function getAllStops() {
    return array_values($this->stops);
  }
  
  function setStopById($stopId) {
    $pastCurrentStop = true;
    if (isset($this->stops[$stopId])) {
      $this->currentStopId = $stopId;
      $pastCurrentStop = false;
    }
    foreach ($this->stops as $id => $stop) {
      $visited = false;
      
      if (!$pastCurrentStop) {
        if ($id == $this->currentStopId) {
          $pastCurrentStop = true;
        } else {
          $visited = true;
        }
      }
      $stop->setWasVisited($visited);
      $stop->setIsCurrent($id == $this->currentStopId);
    }
  }
  
  function getCurrentStop() {
    return isset($this->stops[$this->currentStopId]) ? 
      $this->stops[$this->currentStopId] : reset($this->stops);
  }
  
  function getStopIndex($stopId) {
    $stopIdToIndex = array_flip(array_keys($this->stops));
    return isset($stopIdToIndex[$stopId]) ? $stopIdToIndex[$stopId] : 0;
  }
 
  function getFirstStop() {
    return reset($this->stops);
  }
  
  function getLastStop() {
    return end($this->stops);
  }
  
  function getFirstStopId() {
    return $this->getFirstStop()->getId();
  }
   
  function getLastStopId() {
    return $this->getLastStop()->getId();
  }
  
  function getPreviousStop() {
    $firstStopId = $this->getFirstStopId();
    $stopIds = array_keys($this->stops);
    $stopIndex = array_search($this->currentStopId, $stopIds);

    if ($this->currentStopId == $firstStopId) {
      // we are at the first stop the user visited
      return false;
    }

    if (isset($stopIds[$stopIndex-1])) {
      return $this->stops[$stopIds[$stopIndex-1]];
      
    } if ($stopIndex == 0 && $firstStopId != reset($stopIds)) {
      // We are at the first stop in the curated tour but that isn't where the user started
      return $this->stops[end($stopIds)]; // last stop
    }
    
    return false;
  }
  
  function getNextStop() {
    $stopIds = array_keys($this->stops);
    $stopIndex = array_search($this->currentStopId, $stopIds);
    
    if (isset($stopIds[$stopIndex+1])) {
      return $this->stops[$stopIds[$stopIndex+1]];
    }
    
    return false;
  }
  
  function getFirstGuidedTourStop() {
    return $this->stops[$this->firstGuidedTourStopId];
  }
  
  function isInProgress() {
    return $this->inProgress;
  }
  
  function getWelcomePageContents() {
    return $this->pages['welcome'];
  }
  
  function getFinishPageContents() {
    return $this->pages['finish'];
  }
  
  function getHelpPageContents() {
    return $this->pages['help'];
  }
  
  function getStopDetailLegendContents() {
    return $this->legend;
  }
  
  function getLastUpdate() {
    return $this->lastupdate;
  }
}

class TourDataParser {
  protected $data = array();
  protected $useCache = true;
  protected $cache = null;
  protected $cacheLifetime = 86400;
  
  function __construct($useCache=true) {
    $this->useCache = $useCache;

    $tourNode = $this->getNodeData(Kurogo::getSiteVar('TOUR_NODE_ID'));
    //error_log(print_r($tourNode, true));
    
    $this->data = array(
      'pages' => array(
        'welcome' => array(),
        'help'    => array(),
        'finish'  => array(),
      ),
      'stops'   => array(),
      'legend'  => array(),
      'updated' => $this->getNodeLastUpdate($tourNode),
    );
    
    $pageContents = array(
      'welcome' => array(),
      'help'    => array(),
      'finish'  => array(),
    );
    
    // Tour Welcome Screen
    $pageContents['welcome'][] = $this->getNodeHTMLArray($tourNode, 'field_welcome');
    $pageContents['welcome'][] = $this->getNodeLensInfo($tourNode, 'field_welcome_lenses');
    $pageContents['welcome'][] = $this->getNodeHTMLArray($tourNode, 'field_welcome_footer');
    
    // Help Page
    $pageContents['help'][] = $this->getNodeHTMLArray($tourNode, 'field_help');
    $pageContents['help'][] = $this->getNodeLensInfo($tourNode, 'field_help_lenses');
    $pageContents['help'][] = $this->getNodeHTMLArray($tourNode, 'field_help_middle');
    $pageContents['help'][] = $this->getNodeLinks($tourNode, 'field_help_links');
    $pageContents['help'][] = $this->getNodeHTMLArray($tourNode, 'field_help_middle_2');
    $pageContents['help'][] = $this->getNodeLinks($tourNode, 'field_help_links_2');
    $pageContents['help'][] = $this->getNodeHTMLArray($tourNode, 'field_help_footer');
    
    // Finish Page
    $pageContents['finish'][] = $this->getNodeHTMLArray($tourNode, 'field_finish');
    $pageContents['finish'][] = $this->getNodeLinks($tourNode, 'field_finish_links');
    $pageContents['finish'][] = $this->getNodeHTMLArray($tourNode, 'field_finish_footer');
    
    foreach ($pageContents as $page => $contents) {
      foreach ($contents as $content) {
        if (isset($content['type'])) {
          $this->data['pages'][$page][] = $content;
          
        } else if ($content) {
          $this->data['pages'][$page] = array_merge($this->data['pages'][$page], $content);
        }
      }
    }
    //error_log(print_r($this->data['pages'], true));
    
    // Stop Detail Legend
    $this->data['legend'][] = $this->getNodeLensInfo($tourNode, 'field_stop_legend_lenses');
    
    // Stops
    $stopsNodes = $this->getNodeField($tourNode, 'field_stops', array());
    
    $stopNids = array();
    foreach ($stopsNodes as $stopNode) {
      if (isset($stopNode['nid'])) {
        $stopNids[] = $stopNode['nid'];
      }
    }
    
    foreach ($stopNids as $stopIndex => $nid) {
      $stopNode = $this->getNodeData($nid);
      //error_log(print_r($stopNode, true));
      
      $location = $this->getNodeField($stopNode, 'field_location', array());
      if (count($location)) {
        $location = reset($location); // grab first location (only 1 location allowed)
      }
      
      $stopData = array(
        'updated'   => $this->getNodeLastUpdate($stopNode),
        'title'     => $this->getNodeFieldUTF8($stopNode, 'title'),
        'subtitle'  => $this->getNodeHTML($stopNode, 'field_subtitle'),
        'building'  => $this->getNodeHTML($stopNode, 'field_building'),
        'photo'     => $this->getNodePhoto($stopNode, 'field_approach_photo'),
        'thumbnail' => $this->getNodePhoto($stopNode, 'field_approach_thumbnail'),
        'coords'    => array(
          'lat' => floatval($this->argVal($location, 'lat', 0)),
          'lon' => floatval($this->argVal($location, 'lng', 0)),
        ),
        'lenses'    => array(),
      );
      
      // Info Pane
      $photo = $this->getNodePhoto($stopNode, 'field_photo');
      $text = $this->getNodeHTMLArray($stopNode, 'field_text');
      
      $stopData['lenses']['info'] = array(
        'updated'  => $this->getNodeLastUpdate($stopNode),
        'contents' => array(),
      );
      if ($photo) { 
        $stopData['lenses']['info']['contents'][] = $photo; 
      }
      if ($text) { 
        $stopData['lenses']['info']['contents'] = array_merge($stopData['lenses']['info']['contents'], $text); 
      }
      
      // Other Lenses
      $lensKeyMapping = array(
        'insideout'  => 'field_insideout',
        'fastfacts'  => 'field_fastfacts',
        'innovation' => 'field_innovation',
        'history'    => 'field_history',
      );
      
      foreach ($lensKeyMapping as $lensKey => $nodeKey) {
        $lensNodes = $this->getNodeField($stopNode, $nodeKey, array());
        if (count($lensNodes) && isset($lensNodes[0], $lensNodes[0]['nid'])) {
          $lensNode = $this->getNodeData($lensNodes[0]['nid']);
          if (!isset($lensNode['type'])) { continue; }
          
          $lensData = array(
            'updated'  => $this->getNodeLastUpdate($lensNode),
            'contents' => array(),
          );
          
          switch ($lensNode['type']) {
            case 'lens_content_photo_text':
              $photo = $this->getNodePhoto($lensNode, 'field_photo');
              $text = $this->getNodeHTMLArray($lensNode, 'field_text');
              
              if ($photo) { 
                $lensData['contents'][] = $photo; 
              }
              if ($text) { 
                $lensData['contents'] = array_merge($lensData['contents'], $text); 
              }
              break;
              
            case 'lens_content_slideshow':
              $lensData['contents'][] = array(
                'type'   => 'slideshow',
                'slides' => $this->getNodePhotos($lensNode, 'field_photos'),
              );
              break;
              
            case 'lens_content_video':
              $lensData['contents'] = array($this->getNodeVideo($lensNode));

              $text = $this->getNodeHTMLArray($lensNode, 'field_text');
              if ($text) {
                $lensData['contents'] = array_merge($lensData['contents'], $text); 
              }
              break;    
          }
          
          if (count($lensData['contents'])) {
            $stopData['lenses'][$lensKey] = $lensData;
          }
          
          //error_log(print_r($lensNode, true));
        }
      }
      
      $this->data['stops']["s$stopIndex"] = $stopData;
    }

    //error_log(print_r($this->data, true));    
    return $this->data;
  }
  
  public function getTourData() {
    return $this->data;
  }
  
  protected function getNodeLensInfo($node, $fieldName) {
    $lensInfoNids = $this->getNodeField($node, $fieldName, array());
    if (count($lensInfoNids)) {
      $lensInfoNid = reset($lensInfoNids);
      $lensInfoNode = $this->getNodeData($lensInfoNid['nid']);
      
      return array(
        'type' => 'lensinfo',
        'lenses' => array(
          'info' => array(
            'name' => $this->getNodeHTML($lensInfoNode, 'field_info_name'),
            'desc' => $this->getNodeHTML($lensInfoNode, 'field_info_desc'),
          ),
          'insideout' => array(
            'name' => $this->getNodeHTML($lensInfoNode, 'field_insideout_name'),
            'desc' => $this->getNodeHTML($lensInfoNode, 'field_insideout_desc'),
          ),
          'fastfacts' => array(
            'name' => $this->getNodeHTML($lensInfoNode, 'field_fastfacts_name'),
            'desc' => $this->getNodeHTML($lensInfoNode, 'field_fastfacts_desc'),
          ),
          'innovation' => array(
            'name' => $this->getNodeHTML($lensInfoNode, 'field_innovation_name'),
            'desc' => $this->getNodeHTML($lensInfoNode, 'field_innovation_desc'),
          ),
          'history' => array(
            'name' => $this->getNodeHTML($lensInfoNode, 'field_history_name'),
            'desc' => $this->getNodeHTML($lensInfoNode, 'field_history_desc'),
          ),
        ),
      );
    }
    return array();
  }
  
  protected function getNodeLinks($node, $fieldName) {
    $links = $this->getNodeField($node, $fieldName, array());
    if (count($links)) {
      $linkDetails = array();
      foreach ($links as $link) {
        $linkDetails[] = array(
          'title'    => $this->argValUTF8($link, 'title'),
          'subtitle' => $this->argValUTF8($link, 'subtitle'),
          'url'      => $this->argVal($link, 'url'),
        );
      }
      return array(
        'type' => 'links',
        'links' => $linkDetails,
      );
    }
    
    return array();
  }
  
  protected function getURLForNodeFileURI($node) {
    if (isset($node['uri'])) {
      if (preg_match(';^public://(.+)$;', $node['uri'], $matches)) {
        return Kurogo::getSiteVar('TOUR_SERVICE_FILE_PREFIX').str_replace(' ', '%20', $matches[1]);
      }
    }
    return '';
  }
  
  protected function getNodePhoto($node, $fieldName) {
    $photos = $this->getNodePhotos($node, $fieldName);
    if (count($photos)) {
      return reset($photos);
    } else {
      return array();
    }
  }
  
  protected function getNodeLastUpdate($node) {
    return intval($this->getNodeField($node, 'changed', time()));
  }
  
  protected function getNodePhotos($node, $fieldName) {
    $photos = array();
    
    $nodePhotos = $this->getNodeField($node, $fieldName, array());
    
    foreach ($nodePhotos as $nodePhoto) {
      $photoURL = $this->getURLForNodeFileURI($nodePhoto);
      if (!$photoURL) { continue; }
      
      $photos[] = array(
        'type'  => 'photo',
        'url'   => $photoURL,
        'title' => $this->argValUTF8($nodePhoto, 'title', ''),
      );
    }
    
    return $photos;
  }
  
  protected function getNodeVideo($node) {
    $nodeMPEG4 = reset($this->getNodeField($node, 'field_mpeg4', array()));
    
    return array(
      'type'    => 'video',
      'title'   => $this->getNodeHTML($node, 'field_caption'),
      'url'     => $this->getURLForNodeFileURI($nodeMPEG4),
      'youtube' => $this->getNodeHTML($node, 'field_youtube'),
    );
  }
  
  protected function getNodeHTML($node, $fieldName) {
    $htmlArray = $this->getNodeHTMLArray($node, $fieldName);
    if (count($htmlArray)) {
      $firstHTML = reset($htmlArray);
      return $firstHTML['text'];
    } else { 
      return '';
    }
  }
  
  protected function getNodeHTMLArray($node, $fieldName) {
    $htmlArray = array();
    
    $nodeHTMLs = $this->getNodeField($node, $fieldName, array());
    foreach ($nodeHTMLs as $nodeHTML) {
      $safeValue = $this->argValUTF8($nodeHTML, 'safe_value', '');
      
      $htmlArray[] = array(
        'type' => 'text',
        'text' => $safeValue,
      );
    }
    
    return $htmlArray;
  }
  
  protected function getNodeField($node, $fieldName, $default='') {
    if (isset($node[$fieldName])) {
      if (is_array($node[$fieldName]) && isset($node['language'], $node[$fieldName][$node['language']])) {
        return $node[$fieldName][$node['language']];
      } else {
        return $node[$fieldName];
      }
    }
    return $default;
  }
  
  protected function getNodeFieldUTF8($node, $fieldName, $default='') {
    $value = $this->getNodeField($node, $fieldName, $default);
    if (is_string($value)) {
      return mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8');
    }
    return $value;
  }
  
  protected function getNodeData($nid) {
    $cacheName = "node_$nid";

    if (!$this->cache) {
      $this->cache = new DiskCache(CACHE_DIR."/tour", $this->cacheLifetime, TRUE);
    }

    if ($this->useCache && $this->cache->isFresh($cacheName)) {
      $results = $this->cache->read($cacheName);
      
    } else {
      $content = file_get_contents(Kurogo::getSiteVar('TOUR_SERVICE_URL')."$nid.json");
      $results = json_decode($content, true);
      
      if ($results) {
        $this->cache->write($results, $cacheName);
        
      } else {
        error_log("Error while making foursquare API request: '$content'");
        $results = $this->cache->read($cacheName);
      }
    }
    
    return $results;
  }
  
  protected function argVal($array, $key, $default='') {
    return isset($array[$key]) ? $array[$key] : $default;
  }
  
  protected function argValUTF8($array, $key, $default='') {
    return mb_convert_encoding($this->argVal($array, $key, $default), 'HTML-ENTITIES', 'UTF-8');
  }
}

class TourStop {
  protected $lastupdate = 0;
  protected $id         = '';
  protected $title      = '';
  protected $subtitle   = '';
  protected $coords     = array('lat' => 0, 'lon' => 0);
  protected $buildingId = '';
  protected $photo      = null;
  protected $thumbnail  = null;
  protected $lenses     = array();
  protected $isCurrent  = false;
  protected $wasVisited = false;

  function __construct($id, $data) {
    $this->id = $id;
    
    $this->lastupdate = $data['updated'];
    $this->title      = $data['title'];
    $this->subtitle   = $data['subtitle'];
    $this->coords     = $data['coords'];
    $this->building   = $data['building'];
    $this->photo      = new TourPhoto($data['photo']['url'], $data['photo']['title']);
    $this->thumbnail  = new TourPhoto($data['thumbnail']['url'], $data['thumbnail']['title']);
    
    foreach ($data['lenses'] as $lens => $lensData) {
      if (isset($lensData['contents']) && $lensData['contents']) {
        $this->lenses[$lens] = new TourLens($lensData);
      }
    }
  }
  
  function getId() {
    return strval($this->id);
  }
  
  function getTitle() {
    return $this->title;
  }
  
  function getSubtitle() {
    return $this->subtitle;
  }

  function getCoords() {
    return $this->coords;
  }
  
  function getPhotoSrc() {
    return $this->photo->getSrc();
  }

  function getThumbnailSrc() {
    return $this->thumbnail->getSrc();
  }
  
  function getLastUpdate() {
    return $this->lastupdate;
  }

  function getAvailableLenses() {
    return array_keys($this->lenses);
  }
  
  function getLensLastUpdate($lens) {
    return isset($this->lenses[$lens]) ? $this->lenses[$lens]->getLastUpdate() : 0;
  }
  
  function getLensContents($lens) {
    return isset($this->lenses[$lens]) ? $this->lenses[$lens]->getContents() : false;
  }

  function isCurrent() {
    return $this->isCurrent;
  }
  function setIsCurrent($isCurrent) {
    $this->isCurrent = $isCurrent;
  }
  
  function wasVisited() {
    return $this->wasVisited;
  }
  function setWasVisited($wasVisited) {
    $this->wasVisited = $wasVisited;
  }
}

class TourLens {
  protected $lastupdate = 0;
  protected $contents = array();

  function __construct($data) {
    $this->lastupdate = $data['updated'];
    
    foreach ($data['contents'] as $content) {
      switch ($content['type']) {
        case 'video':
          $this->contents[] = new TourVideo($content['url'], $content['youtube'], $content['title']);
          break;
          
        case 'photo':
          $this->contents[] = new TourPhoto($content['url'], $content['title']);
          break;
        
        case 'text':
          $this->contents[] = new TourText($content['text']);
          break;
        
        case 'slideshow':
          $this->contents[] = new TourSlideshow($content['slides']);
          break;
        
        default: 
          error_log("Unknown content type {$content['type']}");
          break;
      }
    }
  }
  
  function getLastUpdate() {
    return $this->lastupdate;
  }
  
  function getContents() {
    return $this->contents;
  }
}

class TourLensInfo {
  protected $lenses = array();
  
  function __construct($lensInfoData) {
    foreach ($lensInfoData as $lensKey => $lensInfo) {
      $this->lenses[] = array(
        'id'          => $lensKey,
        'name'        => $lensInfo['name'],
        'description' => $lensInfo['desc'],
      );
    }
  }
  
  function getContent() {
    return $this->lenses;
  }
}

class TourLinks {
  protected $links = array();
  
  function __construct($linksData) {
    foreach ($linksData as $linkData) {
      $class = 'external';
      $linkTarget = '_blank';
      if (strncmp($linkData['url'], 'tel:', 4) == 0) {
        $class = 'phone';
        $linkTarget = '';
      } else if (strncmp($linkData['url'], 'mailto:', 7) == 0) {
        $class = 'email';
        $linkTarget = '';
      }
    
      $this->links[] = array(
        'title'      => $linkData['title'],
        'subtitle'   => $linkData['subtitle'],
        'url'        => $linkData['url'],
        'linkTarget' => $linkTarget,
        'class'      => 'action '.$class,
      );
    }
  }
  
  function getContent() {
    return $this->links;
  }
}

class TourText {
  protected $html = '';
  
  function __construct($html) {
    $this->html = $html;
  }
  
  function getContent() {
    return $this->html;
  }
}

class TourSlideshow {
  protected $slides = array();
  
  function __construct($data) {
    foreach ($data as $content) {
      switch ($content['type']) {
        case 'photo':
        case 'video':
          $class = 'Tour'.ucfirst($content['type']);
          $this->slides[] = new $class($content['url'], $content['title']);
          break;
        
        case 'text':
          $this->slides[] = new TourText($content['text']);
          break;
        
        default: 
          error_log("Unknown content type {$content['type']}");
          break;
      }
    }
  }
  
  function getContent() {
    $content = array();
  
    foreach ($this->slides as $slide) {
      $content[] = $slide->getContent();
    }
    
    return $content;
  }
  
  function getSlides() {
    return $this->slides;
  }
}

class TourPhoto {
  protected $src = '';
  protected $title = '';
  
  function __construct($src, $title) {
    $this->src = $src;
    $this->title = $title;
  }
  
  function getSrc() {
    return $this->src;
  }
  
  function getTitle() {
    return $this->title;
  }

  function getContent() {
    return '<img class="photo" src="'.$this->src.'" width="100%"/>'.
      ($this->title ? '<p class="caption">'.$this->title.'</p>' : '');
  }
}

class TourVideo {
  protected $title = '';
  protected $youTubeId = '';
  protected $src = '';
  protected $cache = null;
  protected $cacheLifetime = 3600;
  
  function __construct($src, $youTubeId, $title) {
    $this->src = $src;
    $this->youTubeId = $youTubeId;
    $this->title = $title;
  }
  
  public function getSrc() {
    return $this->src;
  }
  
  public function getTitle() {
    return $this->title;
  }

  public function getContent() {
    $content = '<p>Video not available</p>';
    
    $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
    $platform = $GLOBALS['deviceClassifier']->getPlatform();
    if ($pagetype == 'compliant') {
      $forceHTML5 = false;
      switch ($platform) {
        case 'iphone':
        case 'ipad':
        case 'android':
          $forceHTML5 = true;
        case 'computer':
          // Supports YouTube iframe:
          $content = '<iframe class="videoFrame" id="videoFrame_'.$this->youTubeId.
            '" src="http://www.youtube.com/embed/'.$this->youTubeId.
            ($forceHTML5 ? '?html5=1&controls=0&' : '?').'rel=0" '.
            'width="240" height="195" frameborder="0"></iframe>';
          break;
        
        default:
          $data = $this->getYouTubeData();
          if (isset($data['data'],
                    $data['data']['content'],
                    $data['data']['content'][6],
                    $data['data']['thumbnail'],
                    $data['data']['thumbnail']['hqDefault'])) {
            
            $url = $data['data']['content'][6]; // Blackberries do rtsp only
            if ($platform == 'winphone7') {
              // Intent url to launch YouTube native player (also works for Androids with player installed)
              $url = 'vnd.youtube:'.$this->youTubeId.'?vndapp=youtube_mobile&vndclient=mv-google&vndel=watch';
            }
            $content = '<a class="videoLink" href="'.$url.'">'.
              '<div class="playButton"><div></div></div>'.
              '<img src="'.$data['data']['thumbnail']['hqDefault'].'" /></a>';
          }
          break;
      }
    }
    
    return $content.($this->title ? '<p class="caption">'.$this->title.'</p>' : '');
  }
      
  protected function getYouTubeData() {
    if (!$this->cache) {
      $this->cache = new DiskCache(CACHE_DIR."/tour/youtube", $this->cacheLifetime, TRUE);
    }

    $cacheName = $this->youTubeId;
    
    if ($this->cache->isFresh($cacheName)) {
      $results = $this->cache->read($cacheName);
    } else {
      $url = 'http://gdata.youtube.com/feeds/mobile/videos/'.$this->youTubeId.'?'.http_build_query(array(
        'v'      => 2,
        'format' => 6, // RTSP streaming URL for mobile video playback
        'alt'    => 'jsonc',
      ));
      
      $results = json_decode(file_get_contents($url), true);
      if (isset($results['data'])) {
        $this->cache->write($results, $cacheName);
      }
    }
    
    return $results;
  }
}
