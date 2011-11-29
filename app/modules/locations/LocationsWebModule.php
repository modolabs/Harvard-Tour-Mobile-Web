<?php

class LocationsWebModule extends WebModule {
    protected $id = 'locations';
    
    protected $feeds = array();
    
    
    public function getLocationFeed($id) {
        if (!isset($this->feeds[$id])) {
            throw new KurogoDataException('Unable to load data for location '. $id);
        }
        
        $feedData = $this->feeds[$id];
        $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'LocationsDataModel';
        
        return LocationsDataModel::factory($dataModel, $feedData);
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
                    $feed = $this->getLocationFeed($id);
                    print_r($feed);
                    exit;
                }
                print_r($this->feeds);
                exit;
                break;
            case 'detail':
                $id = $this->getArg('id');
                // specified date for events
                $date = $this->getArg('date', date('Y-m-d', time()));
                $feed = $this->getLocationFeed($id);
                // get title, subtitle and maplocation
                $title = $feed->getTitle();
                $subtitle = $feed->getSubtitle();
                $mapLocation = $feed->getMapLocation();
                $start = new DateTime($date, $this->timezone);
                $end = clone $start;
                $start->setTime(0,0,0);
                $end->setTime(23,59,59);
                // set start and end date for items
                $feed->setStartDate($start);
                $feed->setEndDate($end);
                $items = $feed->items();
                $events = array();
                // format events data
                foreach($items as $item) {
                    $event['title'] = $item->get_summary();
                    $event['subtitle'] = date("H:i:s", $item->get_start()) . " - " . date("H:i:s", $item->get_end());
                    $events[] = $event;
                }
                $nextDate = date("Y-m-d", strtotime("+1 day", strtotime($date)));
                $nextDateString = date("F j", strtotime("+1 day", strtotime($date)));
                $nextDetail = array(
                    'title' => "See next day's info",
                    'url' => $this->buildBreadcrumbURL('detail', array('id' => $id, 'date' => $nextDate), true)
                );
                $map = Kurogo::moduleLinkForValue('map', $mapLocation, $this);
                // change tile for the map link
                $mapLink['title'] = "Search on Map";
                $mapLink['url'] = $map['url'];
                $title = array(
                    'title' => $title,
                    'subtitle' => $subtitle
                );
                $this->assign('title', $title);
                $this->assign('nextDetail', $nextDetail);
                $this->assign('mapLink', $mapLink);
                $this->assign('events', $events);
                break;
        }
    }
}
