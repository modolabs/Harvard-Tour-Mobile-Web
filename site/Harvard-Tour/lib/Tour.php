<?php

require_once('TourData.php');

class Tour {
  private $stops = array();

  function __construct() {
    foreach (_getTourStops() as $id => $stopData) {
      $this->stops[$id] = new TourStop($id, $stopData);
    }
    error_log(print_r($this->stops, true));
  }
  
  function getAllStops() {
    return array_values($this->stops);
  }
  
  function getStop($stopId) {
    return isset($this->stops[$stopId]) ? $this->stops[$stopId] : false;
  }
  
  function getFirstStop() {
    return reset($this->stops);
  }
}

class TourStop {
  private $id         = '';
  private $title      = '';
  private $subtitle   = '';
  private $coords     = array('lat' => 0, 'lon' => 0);
  private $buildingId = '';
  private $photo      = null;
  private $lenses     = array();

  function __construct($id, $data) {
    $this->id = $id;
    
    $this->title    = $data['title'];
    $this->subtitle = $data['subtitle'];
    $this->coords   = $data['coords'];
    $this->building = $data['building'];
    $this->photo    = new TourPhoto($data['photo']['url'], $data['photo']['title']);
    
    foreach ($data['lenses'] as $type => $contents) {
      if (!$contents) { continue; }
    
      if (!isset($this->lenses[$type])) {
        $this->lenses[$type] = array();
      }
      
      foreach ($contents as $content) {
      
        switch ($content['type']) {
          case 'photo':
          case 'video':
          case 'audio':
            $class = 'Tour'.ucfirst($content['type']);
            $this->lenses[$type][] = new $class($content['url'], $content['title']);
            break;
          
          case 'text':
            $this->lenses[$type][] = new TourText($content['text']);
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
    return $this->photo->getSrc();
  }

  function getAvailableLenses() {
    return array_keys($this->lenses);
  }
  
  function getLensContent($lens) {
    return isset($this->lenses[$lens]) ? $this->lenses[$lens] : false;
  }
}

class TourText {
  private $html = '';
  
  function __construct($html) {
    $this->html = $html;
  }
  
  function getContent() {
    return $this->html;
  }
}

class TourPhoto extends TourAsset {
}

class TourVideo extends TourAsset {
}

class TourAudio extends TourAsset {
}

abstract class TourAsset {
  private $src = '';
  private $title = '';
  
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
}
