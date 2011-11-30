<?php

class LocationsWebModule extends WebModule {
    protected $id = 'locations';
    
    protected $feeds = array();
    protected $timezone;
    
    public function getLocationFeed($id) {
        if (!isset($this->feeds[$id])) {
            throw new KurogoDataException($this->getLocalizedString('ERROR_NO_LOCATION_FEED', $id));
        }
        
        $feedData = $this->feeds[$id];
        $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'LocationsDataModel';
        
        return LocationsDataModel::factory($dataModel, $feedData);
    }

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
    
    public function linkForLocation($id) {        
        $feed = $this->getLocationFeed($id);

        $status = "";
        $statusString = "";
        $current = "";
        $next = "";
        
        $currentEvent = $feed->getCurrentEvent();
        $nextEvent = $feed->getNextEvent(true);
        
        if ($currentEvent) {
            
            $status = 'open';
            $statusString = $this->getLocalizedString('STATUS_CLOSE_STRING') . DateFormatter::formatDate($currentEvent->get_end(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
            $current = "<br />" . $this->getLocalizedString('CURRENT_EVENT') . $currentEvent->get_summary() . ' at ' . $this->timeText($currentEvent);
        } else {
            $status = 'closed';
            if ($nextEvent) {
                $statusString = $this->getLocalizedString('STATUS_OPEN_STRING') . DateFormatter::formatDate($nextEvent->get_start(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
                $next = "<br />" . $this->getLocalizedString('NEXT_EVENT') . $nextEvent->get_summary() . ' at ' . $this->timeText($nextEvent);
            }
        }
                
        $options = array(
            'id' => $id
        );
        
        return array(
            'title'    => $feed->getTitle(),
            'subtitle' => sprintf("%s <br /> %s %s %s", $statusString, $feed->getSubtitle(), $current, $next),
            'url'      => $this->buildBreadcrumbURL('detail', $options, true),
            'listclass'=> $status
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
                    $location = $this->linkForLocation($id);
                    $locations[] = $location;
                }

                $this->assign('description', $this->getModuleVar('description','strings'));
                $this->assign('locations', $locations);
                
                break;
            case 'detail':
                $id = $this->getArg('id');
                // specified date for events
                $current = $this->getArg('time', time(), FILTER_VALIDATE_INT);
                //$date = $this->getArg('date', date('Y-m-d', time()));
                $next    = strtotime("+1 day", $current);
                $prev    = strtotime("-1 day", $current);
                
                $feed = $this->getLocationFeed($id);
                
                // get title, subtitle and maplocation
                $title = $feed->getTitle();
                $subtitle = $feed->getSubtitle();
                $mapLocation = $feed->getMapLocation();
                $this->setLogData($id, $feed->getTitle());
                
                $start = new DateTime(date('Y-m-d H:i:s', $current), $this->timezone);
                $start->setTime(0,0,0);
                $end = clone $start;
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
                
                $nextURL = $this->buildBreadcrumbURL('detail', array('id' => $id, 'time' => $next), false);
                $prevURL = $this->buildBreadcrumbURL('detail', array('id' => $id, 'time' => $prev), false);
                
                $dayRange = new DayRange(time());
                
                $map = Kurogo::moduleLinkForValue('map', $mapLocation, $this);
                // change tile for the map link
                $mapLink['title'] = $subtitle;
                $mapLink['url'] = $map['url'];
                $mapLink['class'] = 'map';

                $this->assign('title', $title);
                $this->assign('description', $feed->getDescription());
                $this->assign('location',array($mapLink));
                $this->assign('mapLink', $mapLink);
                $this->assign('current', $current);
                $this->assign('events', $events);
                $this->assign('next',    $next);
                $this->assign('prev',    $prev);
                $this->assign('nextURL', $nextURL);
                $this->assign('prevURL', $prevURL);
                $this->assign('titleDateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
                $this->assign('linkDateFormat', $this->getLocalizedString('SHORT_DATE_FORMAT'));
                $this->assign('isToday', $dayRange->contains(new TimeRange($current)));
                break;
        }
    }
}
