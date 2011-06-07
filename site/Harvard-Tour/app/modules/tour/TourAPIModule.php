<?php

class TourAPIModule extends APIModule {
  protected $id = 'tour';
  
  protected function getBriefStopDetails($stop) {
    $coords = $stop->getCoords();
    
    $lenses = array();
    foreach ($stop->getAvailableLenses() as $lensKey) {
      $lenses[$lensKey] = array(
        'updated' => $stop->getLensLastUpdate($lensKey),
      );
    }
    
    return array(
      'details' => array(
        'id'        => $stop->getId(),
        'title'     => $stop->getTitle(),
        'subtitle'  => $stop->getSubtitle(),
        'photo'     => $stop->getPhotoSrc(),
        'thumbnail' => $stop->getThumbnailSrc(),
        'lat'       => $coords['lat'],
        'lon'       => $coords['lon'],
        'updated'   => $stop->getLastUpdate(),
      ),
      'lenses' => $lenses,
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
    foreach ($stopDetails['lenses'] as $lensKey => $contents) {
      $stopDetails['lenses'][$lensKey]['contents'] = array();
      
      foreach ($stop->getLensContents($lensKey) as $lensContent) {
        $stopDetails['lenses'][$lensKey]['contents'][] = $this->formatLensContent($lensContent);
      }
    }
    
    return $stopDetails;
  }
  
  protected function getAllStopsBriefDetails($tour) {
    $stopsDetails = array();
    
    foreach ($tour->getAllStops() as $stop) {
      $stopsDetails[] = $this->getBriefStopDetails($stop);
    }
    return $stopsDetails;
  }
  
  protected function getTourDetails($tour) {
    $tourDetails = array(
      'pages' => array(
        'welcome' => array(),
        'finish'  => array(),
        'help'    => array(),
      ),
      'updated' => $tour->getLastUpdate(),
    );
    
    $pages = array('welcome', 'finish', 'help');
    foreach (array_keys($tourDetails['pages']) as $page) {
      $pageObjects = array();
    
      switch ($page) {
        case 'welcome':
          $pageObjects = $tour->getWelcomePageContents();
          break;
          
        case 'finish':
          $pageObjects = $tour->getFinishPageContents();
          break;
          
        case 'help':
          $pageObjects = $tour->getHelpPageContents();
          break;
      }
      
      foreach ($pageObjects as $pageObject) {
        $tourDetails['pages'][$page][] = $pageObject->getContent();
      }
    }
    
    return $tourDetails;
  }
  
  protected function initializeForCommand() {
    $tour = new Tour($this->getArg('id', null));
    
    switch ($this->command) {
      case 'tour':
        $response = array(
          'details' => $this->getTourDetails($tour),
          'stops'   => $this->getAllStopsBriefDetails($tour),
        );
        
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
