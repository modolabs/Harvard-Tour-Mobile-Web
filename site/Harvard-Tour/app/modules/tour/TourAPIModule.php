<?php

class TourAPIModule extends APIModule {
  protected $id = 'tour';
  
  protected function getBriefStopDetails($stop) {
    $coords = $stop->getCoords();
    
    return array(
      'id'        => $stop->getId(),
      'title'     => $stop->getTitle(),
      'subtitle'  => $stop->getSubtitle(),
      'photo'     => $stop->getPhotoSrc(),
      'thumbnail' => $stop->getThumbnailSrc(),
      'lat'       => $coords['lat'],
      'lon'       => $coords['lon'],
      'lenses'    => array_fill_keys($stop->getAvailableLenses(), array()),
    );
  }
  
  protected function formatLensContent($content) {
    switch (get_class($content)) {
      case 'TourText':
        return array(
          'type' => 'html',
          'html' =>  $content->getContent(),
        );
        
      case 'TourPhoto':
        return array(
          'type'  => 'photo',
          'url'   => $content->getSrc(),
          'title' => $content->getTitle(),
        );
        
      case 'TourVideo':
        return array(
          'type'  => 'video',
          'url'   => $content->getSrc(),
          'title' => $content->getTitle(),
        );
        
      case 'TourSlideshow':
        $slides = array();
        foreach ($content->getSlides() as $slideContent) {
          $slides[] = $this->formatLensContent($slideContent);
        }
        return array(
          'type' => 'slideshow',
          'slides' => $slides,
        );
    }
    return null;
  }
  
  protected function getStopDetails($stop) {
    $stopDetails = $this->getBriefStopDetails($stop);
    
    $lenses = $stop->getAvailableLenses();
    foreach ($stopDetails['lenses'] as $lens => $contents) {
      foreach ($stop->getLensContents($lens) as $lensContent) {
        $stopDetails['lenses'][$lens][] = $this->formatLensContent($lensContent);
      }
    }
    
    return $stopDetails;
  }
  
  protected function getAllStopsDetails($tour) {
    $stopsDetails = array();
    
    foreach ($tour->getAllStops() as $stop) {
      $stopsDetails[] = $this->getBriefStopDetails($stop);
    }
    return $stopsDetails;
  }
  
  protected function  initializeForCommand() {
    $tour = new Tour($this->getArg('id', null));
    
    switch ($this->command) {
      case 'stops':
        $response = $this->getAllStopsDetails($tour);
        
        $this->setResponse($response);
        $this->setResponseVersion(1);
        break;
        
      case 'stop':
        $response = $this->getStopDetails($tour->getStop());
        
        $this->setResponse($response);
        $this->setResponseVersion(1);
        break;
    }
  }
}
