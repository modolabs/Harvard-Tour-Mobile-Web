<?php

require_once('TourData.php');

class Tour {
  private $stops = array();
  private $inProgress = false;
  private $currentStopId = '';
  private $seenStopIds = array();
  private $cache = null;
  private $cacheLifetime = 3600;

  function __construct($stopId = false, $seenStopIds = array()) {
    foreach (/*_getTourStops()*/$this->loadTourData() as $id => $stopData) {
      $this->stops[$id] = new TourStop($id, $stopData);
    }
    
    $firstStopId = reset(array_keys($this->stops));
    
    if ($stopId && isset($this->stops[$stopId])) {
      $this->currentStopId = $stopId;
    } else {
      $this->currentStopId = $firstStopId;
    }
    
    if ($stopId && ($stopId != $firstStopId || count($seenStopIds))) {
      $this->inProgress = true;
    }
    
    foreach ($seenStopIds as $seenStopId) {
      if (isset($this->stops[$seenStopId])) {
        $this->seenStopIds[] = $seenStopId;
      }
    }
    
    foreach ($this->stops as $id => $stop) {
      if ($id == $this->currentStopId) {
        $stop->setIsCurrent($id == $this->currentStopId);
      } else {
        $stop->setWasVisited(in_array($id, $seenStopIds));
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
  
  function getStop() {
    return isset($this->stops[$this->currentStopId]) ? 
      $this->stops[$this->currentStopId] : false;
  }
  
  function getFirstStopId() {
    if (count($this->seenStopIds)) {
      return reset($this->seenStopIds);
      
    } else {
      return $this->currentStopId;
    }
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
    $firstStopId = $this->getFirstStopId();
    $stopIds = array_keys($this->stops);
    $stopIndex = array_search($this->currentStopId, $stopIds);

    if (isset($stopIds[$stopIndex+1])) {
      // only return next stop if it isn't the stop the user chose first
      if ($stopIds[$stopIndex+1] != $firstStopId) {
        return $this->stops[$stopIds[$stopIndex+1]];
      }
      
    } if ($stopIndex == count($stopIds)-1 && $firstStopId != reset($stopIds)) {
      // We are at the last stop in the curated tour but that isn't where the user started
      return reset($this->stops); // first stop
    }
    
    return false;
  }
  
  function getFirstStop() {
    return reset($this->stops);
  }
  
  function getLastStop() {
    return end($this->stops);
  }
  
  function isInProgress() {
    return $this->inProgress;
  }
  
  function startOver() {
    $this->inProgress = false;
    $this->currentStopId = reset(array_keys($this->stops));
    foreach ($this->stops as $id => $stop) {
      $stop->setIsCurrent($id == $this->currentStopId);
      $stop->setWasVisited(false);
    }
  }
  
  private function getNodeData($nid) {
    $cacheName = "node_$nid";

    if (!$this->cache) {
      $this->cache = new DiskCache(CACHE_DIR."/tour", $this->cacheLifetime, TRUE);
    }

    if ($this->cache->isFresh($cacheName)) {
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
  
  private function loadTourData() {
    $tourNode = $this->getNodeData(Kurogo::getSiteVar('TOUR_NODE_ID'));
    $stopsNodes = $this->getNodeField($tourNode, 'field_stops', array());
    
    $stopNids = array();
    foreach ($stopsNodes as $stopNode) {
      if (isset($stopNode['nid'])) {
        $stopNids[] = $stopNode['nid'];
      }
    }
    
    $tourData = array();
    foreach ($stopNids as $nid) {
      $stopNode = $this->getNodeData($nid);
      //error_log(print_r($stopNode, true));
      
      $location = $this->getNodeField($stopNode, 'field_location', array());
      if (count($location)) {
        $location = reset($location); // grab first location (only 1 location allowed)
      }
      
      $stopData = array(
        'title'     => $this->getNodeField($stopNode, 'title'),
        'subtitle'  => $this->getNodeHTML($stopNode, 'field_subtitle'),
        'building'  => $this->getNodeHTML($stopNode, 'field_building'),
        'photo'     => $this->getNodePhoto($stopNode, 'field_approach_photo'),
        'thumbnail' => $this->getNodePhoto($stopNode, 'field_approach_thumbnail'),
        'coords'    => array(
          'lat' => $this->argVal($location, 'lat', 0),
          'lon' => $this->argVal($location, 'lng', 0),
        ),
        'lenses'    => array(),
      );
      
      // Info Pane
      $photo = $this->getNodePhoto($stopNode, 'field_photo');
      $text = $this->getNodeHTMLArray($stopNode, 'field_text');
      
      $stopData['lenses']['info'] = array();
      if ($photo) { 
        $stopData['lenses']['info'][] = $photo; 
      }
      if ($text) { 
        $stopData['lenses']['info'] = array_merge($stopData['lenses']['info'], $text); 
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
          
          switch ($lensNode['type']) {
            case 'lens_content_photo_text':
              $photo = $this->getNodePhoto($lensNode, 'field_photo');
              $text = $this->getNodeHTMLArray($lensNode, 'field_text');
              
              $stopData['lenses'][$lensKey] = array();
              if ($photo) { 
                $stopData['lenses'][$lensKey][] = $photo; 
              }
              if ($text) { 
                $stopData['lenses'][$lensKey] = array_merge($stopData['lenses'][$lensKey], $text); 
              }
              break;
              
            case 'lens_content_slideshow':
              $stopData['lenses'][$lensKey] = array(
                array(
                  'type'   => 'slideshow',
                  'slides' => $this->getNodePhotos($lensNode, 'field_photos'),
                ),
              );
              break;
              
            case 'lens_content_video':
              $stopData['lenses'][$lensKey] = array($this->getNodeVideo($lensNode));

              $text = $this->getNodeHTMLArray($lensNode, 'field_text');
              if ($text) {
                $stopData['lenses'][$lensKey] = array_merge($stopData['lenses'][$lensKey], $text); 
              }
              break;
              
          }
          
          //error_log(print_r($lensNode, true));
        }
      }
      
      $tourData[] = $stopData;
    }
    
    //error_log(print_r($tourData, true));
    return $tourData;
  }
  
  protected function getURLForNodeFileURI($node) {
    if (isset($node['uri'])) {
      if (preg_match(';^public://(.+)$;', $node['uri'], $matches)) {
        return Kurogo::getSiteVar('TOUR_SERVICE_FILE_PREFIX').$matches[1];
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
  
  protected function getNodePhotos($node, $fieldName) {
    $photos = array();
    
    $nodePhotos = $this->getNodeField($node, $fieldName, array());
    
    foreach ($nodePhotos as $nodePhoto) {
      $photoURL = $this->getURLForNodeFileURI($nodePhoto);
      if (!$photoURL) { continue; }
      
      $photos[] = array(
        'type'  => 'photo',
        'url'   => $photoURL,
        'title' => $this->argVal($nodePhoto, 'title', ''),
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
      $safeValue = $this->argVal($nodeHTML, 'safe_value', '');
      
      if (!substr_compare($safeValue, '<p>',  0,                      3) && 
          !substr_compare($safeValue, '</p>', strlen($safeValue) - 5, 4)) {
        $safeValue = substr($safeValue, 3, strlen($safeValue) - 8);
      }
    
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
  
  private function argVal($array, $key, $default='') {
    return isset($array[$key]) ? $array[$key] : $default;
  }
}

class TourStop {
  private $id         = '';
  private $title      = '';
  private $subtitle   = '';
  private $coords     = array('lat' => 0, 'lon' => 0);
  private $buildingId = '';
  private $photo      = null;
  private $thumbnail  = null;
  private $lenses     = array();
  private $isCurrent  = false;
  private $wasVisited = false;

  function __construct($id, $data) {
    $this->id = $id;
    
    $this->title    = $data['title'];
    $this->subtitle = $data['subtitle'];
    $this->coords   = $data['coords'];
    $this->building = $data['building'];
    $this->photo     = new TourPhoto($data['photo']['url'], $data['photo']['title']);
    $this->thumbnail = new TourPhoto($data['thumbnail']['url'], $data['thumbnail']['title']);
    
    foreach ($data['lenses'] as $lens => $contents) {
      if (!$contents) { continue; }
    
      if (!isset($this->lenses[$lens])) {
        $this->lenses[$lens] = array();
      }
      
      foreach ($contents as $content) {
        switch ($content['type']) {
          case 'video':
            $this->lenses[$lens][] = new TourVideo(
              $content['url'], $content['youtube'], $content['title']);
            break;
            
          case 'photo':
            $this->lenses[$lens][] = new TourPhoto($content['url'], $content['title']);
            break;
          
          case 'text':
            $this->lenses[$lens][] = new TourText($content['text']);
            break;
          
          case 'slideshow':
            $this->lenses[$lens][] = new TourSlideshow($content['slides']);
            break;
          
          default: 
            error_log("Unknown content type {$content['type']}");
            break;
        }
      }
    }
  }
  
  function getId() {
    return $this->id;
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

  function getAvailableLenses() {
    return array_keys($this->lenses);
  }
  
  function getLensContents($lens) {
    return isset($this->lenses[$lens]) ? $this->lenses[$lens] : false;
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

class TourText {
  private $html = '';
  
  function __construct($html) {
    $this->html = $html;
  }
  
  function getContent() {
    return '<p>'.$this->html.'</p>';
  }
}

class TourSlideshow {
  private $slides = array();
  
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
    return '<video src="'.$this->src.'" width="100%" controls></video>'.
      ($this->title ? '<p class="caption">'.$this->title.'</p>' : '');
  }
  
  /*
      '<a class="videoLink" href="'.$this->src.'">'.
      '<div class="playButton"><div></div></div>'.
      '<img src="'.$this->srcStill.'" /></a>'
      
  protected function getYouTubeData($id) {
    $cache = $this->getCacheForQuery('youtube');
    $cacheName = $id;
    
    if ($cache->isFresh($cacheName)) {
      $results = $cache->read($cacheName);
    } else {
      $url = 'http://gdata.youtube.com/feeds/mobile/videos/'.$id.'?'.http_build_query(array(
        'v'      => 2,
        'format' => 6, // RTSP streaming URL for mobile video playback
        'alt'    => 'jsonc',
      ));
      
      $results = json_decode(file_get_contents($url), true);
      if (isset($results['data'])) {
        $cache->write($results, $cacheName);
      }
    }
    
    return $results;
  }*/
}
