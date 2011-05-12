<?php

class TourWebModule extends WebModule {
  protected $id = 'tour';
  
  protected function markerImageForSelf() {
    return 'http://chart.apis.google.com/chart?'.http_build_query(array(
      'chst' => 'd_simple_text_icon_left',
      'chld' => ' |9|000000|glyphish_walk|16|000000',
    ));
  }
  
  protected function markerImageForStop($stop) {
    if (isset($stop['next']) && $stop['next']) {
      return 'http://chart.apis.google.com/chart?'.http_build_query(array(
        'chst' => 'd_map_xpin_letter',
        'chld' => 'pin||DD0000|DD0000',
      ));
    } else {
      return 'http://chart.apis.google.com/chart?'.http_build_query(array(
        'chst' => 'd_map_xpin_letter',
        'chld' => 'pin||CCCCCC|CCCCCC',
      ));
    }
  }
  
  protected function initializeMap($stops) {
    $tourStops = array();
    foreach ($stops as $stop) {
      $tourStops[] = array(
        'title'   => htmlspecialchars($stop['title']), 
        'address' => $stop['subtitle'],
        'lat'     => $stop['latlon'][0],
        'lon'     => $stop['latlon'][1],
        'icon'    => $this->markerImageForStop($stop),
        'label'   => '<div class="map_infowindow"><div class="map_name">'.
          htmlspecialchars($stop['title']).'</div><div class="map_address">'.
          htmlspecialchars($stop['subtitle']).'</div></div>',
      );
    }
    $scriptText = 'var tourStops = '.json_encode($tourStops).";\n".
      'var selfIconSrc = '.json_encode($this->markerImageForSelf()).";\n";

    $this->addExternalJavascript('http://maps.google.com/maps/api/js?sensor=true');
    $this->addInlineJavascript($scriptText);
    $this->addOnLoad('showMap(tourStops, selfIconSrc);');
  }
  
  protected function hasTabForKey($tabKey, &$tabJavascripts) {
    return true;
  }
  
  protected function initializeForPage() {
    switch ($this->page) {
      case 'index':
        // Just static content
        break;
        
      case 'start':
        break;
        
      case 'finish':
        break;
        
      case 'overview':
        break;
        
      case 'map':
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
        
      case 'photo':
        break;
        
    }
  }
}
