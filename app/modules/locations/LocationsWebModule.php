<?php

class LocationsWebModule extends WebModule
{
    protected $id = 'locations';
    
    protected $feeds = array();
    protected $timezone;
    
    public function getLocationFeed($id) {
        if (!isset($this->feeds[$id])) {
            throw new KurogoDataException('Unable to load data for location '. $id);
        }
        
        $feedData = $this->feeds[$id];
        $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'LocationsDataModel';
        
        return LocationsDataModel::factory($dataModel, $feedData);
    }
    /*
    $subtitle = $this->timeText($event);
      if ($briefLocation = $event->get_location()) {
        $subtitle .= " | $briefLocation";
      }
      
      $options = array(
        'id'   => $event->get_uid(),
        'time' => $event->get_start()
      );
      
      foreach (array('type','calendar','searchTerms','timeframe','catid','filter') as $field) {
          if (isset($data[$field])) {
              $options[$field] = $data[$field];
          }
      }

      $addBreadcrumb = isset($data['addBreadcrumb']) ? $data['addBreadcrumb'] : true;
      $noBreadcrumbs = isset($data['noBreadcrumbs']) ? $data['noBreadcrumbs'] : false;

      if ($noBreadcrumbs) {
        $url = $this->buildURL('detail', $options);
      } else {
        $url = $this->buildBreadcrumbURL('detail', $options, $addBreadcrumb);
      }

      return array(
        'url'       => $url,
        'title'     => $event->get_summary(),
        'subtitle'  => $subtitle
      );
      
     */
    protected function timeText($event, $timeOnly=false) {
        if ($timeOnly) {
            if ($event->get_end() - $event->get_start() == -1) {
                return DateFormatter::formatDate($event->get_start(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
            } else {
                return DateFormatter::formatDateRange($event->getRange(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
            }
        } else {
            return DateFormatter::formatDateRange($event->getRange(), DateFormatter::SHORT_STYLE, DateFormatter::SHORT_STYLE);
        }
    }
    
    public function linkForLocation($id, $data) {        
        $feed = $this->getLocationFeed($id);

        $status = "";
        $statusString = "";
        $current = "";
        $next = "";
        
        $currentEvent = $feed->getCurrentEvent();
        $nextEvent = $feed->getNextEvent(true);
        
        if ($currentEvent) {
            $status = 'open';
            $statusString = "will closed:" . DateFormatter::formatDate($currentEvent->get_end(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
            $current = "<br />current: " . $currentEvent->get_summary() . ' at ' . $this->timeText($currentEvent);
        } else {
            $status = 'closed';
            if ($nextEvent) {
                $statusString = "will open:" . DateFormatter::formatDate($nextEvent->get_start(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
                $next = "<br />next: " . $nextEvent->get_summary() . ' at ' . $this->timeText($nextEvent);
            }
        }
        
        $statusImg = $status ? '<img src="/modules/locations/images/dining-status-'.$status.'.png" />' : '';
        
        $options = array(
            'id' => $id
        );
        
        return array(
            'title'    => sprintf('%s %s', $statusImg, $data['TITLE']),
            'subtitle' => sprintf("%s <br /> %s %s %s", $statusString, $data['SUBTITLE'], $current, $next),
            'url'      => $this->buildBreadcrumbURL('detail', $options, true)
        );
    }
    
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
        $this->timezone = Kurogo::siteTimezone();
    } 
    
    protected function initializeForPage() {
        
        switch ($this->page) {
            
            case 'index':
                $locations = array();
                
                foreach ($this->feeds as $id => $feedData) {
                    $location = $this->linkForLocation($id, $feedData);
                    $locations[] = $location;
                }

                $this->assign('locations', $locations);
                
                break;
        }
    }
}