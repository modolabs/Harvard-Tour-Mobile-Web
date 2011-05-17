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
        'chst' => 'd_map_xpin_icon_withshadow',
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
    
    foreach ($stops as $stop) {
      $coords = $stop->getCoords();
      
      $tourStops[] = array(
        'id'        => $stop->getId(),
        'url'       => $this->buildBreadcrumbUrl('approach', array('id' => $stop->getId())),
        'title'     => $stop->getTitle(),
        'subtitle'  => $stop->getSubtitle(),
        'photo'     => $stop->getPhotoSrc(),
        'thumbnail' => $stop->getThumbnailSrc(),
        'lat'       => $coords['lat'],
        'lon'       => $coords['lon'],
        'visited'   => $stop->wasVisited(),
        'current'   => $stop->isCurrent(),
      );
    }
    
    $center = array(
      'lat' => 42.374464, 
      'lon' => -71.117232,
    );
    
    $stopOverviewMode = $this->page != 'approach' ? 'true' : 'false';
    
    $scriptText = "\n".
      'var centerCoords = '.json_encode($center)."\n".
      'var tourStops = '.json_encode($tourStops).";\n".
      'var tourIcons = '.json_encode($this->markerImages()).";\n";

    $this->addExternalJavascript('http://maps.google.com/maps/api/js?sensor=true');
    $this->addInlineJavascript($scriptText);
    $this->addOnLoad('showMap(centerCoords, tourStops, tourIcons, '.$stopOverviewMode.');');
  }
  
  protected function hasTabForKey($tabKey, &$tabJavascripts) {
    return true;
  }
  
  protected function initializeForPage() {
    $this->tour = new Tour();
    
    $this->tour->setStop($this->getArg('id', $this->tour->getFirstStop()->getId()));
    $this->stop = $this->tour->getStop();
    
    $stopInfo = array(
      'id'        => $this->stop->getId(),
      'title'     => $this->stop->getTitle(),
      'subtitle'  => $this->stop->getSubtitle(),
      'photo'     => $this->stop->getPhotoSrc(),
      'thumbnail' => $this->stop->getThumbnailSrc(),
      'lenses'    => array(),
    );
    $lenses = $this->stop->getAvailableLenses();
    foreach ($lenses as $lens) {
      $stopInfo['lenses'][$lens] = $this->stop->getLensContents($lens);
    }
    
    switch ($this->page) {
      case 'index':
        // Just static content
        break;
        
      case 'start':
        break;
        
      case 'finish':
        $prevURL = false;
        $prevStop = $this->tour->getLastStop();
        if ($prevStop) {
          $prevURL = $this->buildBreadcrumbURL('detail', array(
            'id' => $prevStop->getId(),
          ));
        } else {
          $prevURL = $this->buildBreadcrumbURL('overview', array());          
        }
        $nextURL = false;
        
        $this->assign('prevURL', $prevURL);
        $this->assign('nextURL', $nextURL);
        break;
        
      case 'overview':
        $view = $this->getArg('view', 'map');
        
        $this->initializeMap($this->tour->getAllStops());
        
        $args = $this->args;
        $args['view'] = 'map';
        $mapViewURL = $this->buildBreadcrumbURL($this->page, $args, false);
        $args['view'] = 'list';
        $listViewURL = $this->buildBreadcrumbURL($this->page, $args, false);
        
        $stopInfo['url'] = $this->buildBreadcrumbUrl('approach', array(
          'id' => $this->stop->getId()
        ));
        
        $this->assign('mapViewURL',  $mapViewURL);
        $this->assign('listViewURL', $listViewURL);
        $this->assign('view',        $view);
        $this->assign('stop',        $stopInfo);
        break;
        
      case 'approach':
        $this->initializeMap($this->tour->getAllStops());
      
        $prevURL = false;
        $prevStop = $this->tour->getPreviousStop();
        if ($prevStop) {
          $prevURL = $this->buildBreadcrumbURL('detail', array(
            'id' => $prevStop->getId(),
          ));
        } else {
          $prevURL = $this->buildBreadcrumbURL('overview', array());          
        }
        $nextURL = $this->buildBreadcrumbURL('detail', array(
          'id' => $this->stop->getId(),
        ));
        
        $this->assign('prevURL', $prevURL);
        $this->assign('nextURL', $nextURL);
        $this->assign('stop',    $stopInfo);
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

        $prevURL = $this->buildBreadcrumbURL('approach', array(
          'id' => $this->stop->getId(),
        ));
        $nextURL = false;
        $nextStop = $this->tour->getNextStop();
        if ($nextStop) {
          $nextURL = $this->buildBreadcrumbURL('approach', array(
            'id' => $nextStop->getId(),
          ));
        } else {
          $nextURL = $this->buildBreadcrumbURL('finish', array());          
        }
        
        $this->assign('prevURL', $prevURL);
        $this->assign('nextURL', $nextURL);
        $this->assign('tabKeys', $tabKeys);
        $this->enableTabs($tabKeys, null, $tabJavascripts);
        $this->assign('stop',    $stopInfo);
        break;
        
    }
  }
}
