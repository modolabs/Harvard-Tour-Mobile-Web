<?php

class TourWebModule extends WebModule {
  protected $id = 'tour';
  private $tour = null;
  private $stop = '';
  
  protected function markerImages() {
    return array(
      'self' => 'http://chart.apis.google.com/chart?'.http_build_query(array(
        'chst' => 'd_simple_text_icon_left',
        'chld' => ' |9|000000|glyphish_walk|16|000000',
      )),
      'current' => 'http://chart.apis.google.com/chart?'.http_build_query(array(
        'chst' => 'd_map_xpin_letter_withshadow',
        'chld' => 'pin||DD0000|DD0000',
      )),
      'visited' => 'http://chart.apis.google.com/chart?'.http_build_query(array(
        'chst' => 'd_map_pin_icon_withshadow',
        'chld' => 'pin|glyphish_todo|CCCCCC',
      )),
      'other' => 'http://chart.apis.google.com/chart?'.http_build_query(array(
        'chst' => 'd_map_xpin_letter_withshadow',
        'chld' => 'pin||CCCCCC|CCCCCC',
      )),
    );
  }
  
  protected function initializeMap($stops) {
    $tourStops = array();
    
    $stopIds = array_keys($stops);
    $currentStopIndex = array_search($this->stop->getId(), $stopIds);
    
    foreach ($stops as $stopIndex => $stop) {
      $coords = $stop->getCoords();
      
      $tourStops[] = array(
        'id'        => $stop->getId(),
        'url'       => $this->buildBreadcrumbUrl('map', array('id' => $stop->getId())),
        'title'     => $stop->getTitle(),
        'subtitle'  => $stop->getSubtitle(),
        'photo'     => $stop->getPhotoSrc(),
        'thumbnail' => $stop->getThumbnailSrc(),
        'lat'       => $coords['lat'],
        'lon'       => $coords['lon'],
        'visited'   => $currentStopIndex > $stopIndex,
        'current'   => $currentStopIndex == $stopIndex,
      );
    }
    $center = array(
      'lat' => 42.374464, 
      'lon' => -71.117232,
    );
    
    $scriptText = "\n".
      'var centerCoords = '.json_encode($center)."\n".
      'var tourStops = '.json_encode($tourStops).";\n".
      'var tourIcons = '.json_encode($this->markerImages()).";\n";

    $this->addExternalJavascript('http://maps.google.com/maps/api/js?sensor=true');
    $this->addInlineJavascript($scriptText);
    $this->addOnLoad('showMap(centerCoords, tourStops, tourIcons);');
  }
  
  protected function hasTabForKey($tabKey, &$tabJavascripts) {
    return true;
  }
  
  protected function initializeForPage() {
    $this->tour = new Tour();
    
    $stopId = $this->getArg('id', $this->tour->getFirstStop()->getId());
    $this->stop = $this->tour->getStop($stopId);
    
    switch ($this->page) {
      case 'index':
        // Just static content
        break;
        
      case 'start':
        break;
        
      case 'finish':
        break;
        
      case 'overview':
        $view = $this->getArg('view', 'map');
        
        $this->initializeMap($this->tour->getAllStops());
        
        $args = $this->args;
        $args['view'] = 'map';
        $mapViewURL = $this->buildBreadcrumbURL($this->page, $args, false);
        $args['view'] = 'list';
        $listViewURL = $this->buildBreadcrumbURL($this->page, $args, false);
        
        $this->assign('mapViewURL',  $mapViewURL);
        $this->assign('listViewURL', $listViewURL);
        $this->assign('view',        $view);
        $this->assign('stop',        array(
          'id'        => $this->stop->getId(),
          'url'       => $this->buildBreadcrumbUrl('map', array(
            'id' => $this->stop->getId())
          ),
          'title'     => $this->stop->getTitle(),
          'subtitle'  => $this->stop->getSubtitle(),
          'photo'     => $this->stop->getPhotoSrc(),
          'thumbnail' => $this->stop->getThumbnailSrc(),
        ));
        break;
        
      case 'approach':
        break;
        
      case 'detail':
        $detailConfig = $this->loadPageConfigFile('detail', 'detailConfig');        
        $tabKeys = array();
        $tabJavascripts = array();

        $possibleTabs = $detailConfig['tabs']['tabkeys'];
        foreach ($possibleTabs as $tabKey) {
          if ($this->hasTabForKey($tabKey, $tabJavascripts)) {
            $tabKeys[] = $tabKey;
          }
        }

        $this->assign('tabKeys', $tabKeys);
        $this->enableTabs($tabKeys, null, $tabJavascripts);
        break;
        
    }
  }
}
