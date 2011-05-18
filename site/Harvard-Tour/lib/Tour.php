<?php

require_once('TourData.php');

class Tour {
  private $stops = array();
  private $currentStopId = '';

  function __construct() {
    foreach (_getTourStops() as $id => $stopData) {
      $this->stops[$id] = new TourStop($id, $stopData);
    }
    $this->currentStopId = reset(array_keys($this->stops));
    //error_log(print_r($this->stops, true));
  }
  
  function getAllStops() {
    return array_values($this->stops);
  }
  
  function setStop($stopId) {
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
  
  function getPreviousStop() {
    $stopIds = array_keys($this->stops);
    $stopIndex = array_search($this->currentStopId, $stopIds);
    
    if ($stopIndex > 0) {
      return $this->stops[$stopIds[$stopIndex-1]];
    }
    return false;
  }
  
  function getNextStop() {
    $stopIds = array_keys($this->stops);
    $stopIndex = array_search($this->currentStopId, $stopIds);
    
    if ($stopIndex < count($stopIds)-1) {
      return $this->stops[$stopIds[$stopIndex+1]];
    }
    return false;
  }
  
  function getFirstStop() {
    return reset($this->stops);
  }
  
  function getLastStop() {
    return end($this->stops);
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
