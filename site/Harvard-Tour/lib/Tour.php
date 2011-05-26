<?php

require_once('TourData.php');

class Tour {
  private $stops = array();
  private $inProgress = false;
  private $currentStopId = '';
  private $seenStopIds = array();

  function __construct($stopId = false, $seenStopIds = array()) {
    //$tourData = $this->loadTourData();
  
    foreach (_getTourStops() as $id => $stopData) {
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
     $content = file_get_contents(Kurogo::getSiteVar('TOUR_SERVICE_URL')."$nid.json");
     
     return json_decode($content, true);
  }
  
  private function loadTourData() {
    $tour = $this->getNodeData(Kurogo::getSiteVar('TOUR_NODE_ID'));
    
    $stopNids = array();
    
    if (isset($tour['language'], $tour['field_stops'], $tour['field_stops'][$tour['language']])) {
      foreach ($tour['field_stops'][$tour['language']] as $stopNid) {
        $stopNids[] = $stopNid['nid'];
      }
    }
    
    $tourData = array();
    foreach ($stopNids as $nid) {
      $stop = $this->getNodeData($nid);
      error_log(print_r($stop, true));
    }
    
    
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
          case 'photo':
          case 'video':
          case 'audio':
            $class = 'Tour'.ucfirst($content['type']);
            $this->lenses[$lens][] = new $class($content['url'], $content['title']);
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
        case 'audio':
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

class TourPhoto extends TourAsset {
  function getContent() {
    return '<img class="photo" src="'.$this->src.'" width="100%"/>'.
      ($this->title ? '<p class="caption">'.$this->title.'</p>' : '');
  }
}

class TourVideo extends TourAsset {
  function getContent() {
    return '<video src="'.$this->src.'" width="100%" controls>Video format not supported by this device</video>'.
      ($this->title ? '<p class="caption">'.$this->title.'</p>' : '');
  }

}

class TourAudio extends TourAsset {
}

abstract class TourAsset {
  protected $src = '';
  protected $title = '';
  
  function __construct($src, $title) {
    $this->src = $src;
    $this->title = $title;
  }
  
  function getSrc() {
    return FULL_URL_PREFIX.ltrim($this->src, '/');
  }
  
  function getTitle() {
    return $this->title;
  }
}
