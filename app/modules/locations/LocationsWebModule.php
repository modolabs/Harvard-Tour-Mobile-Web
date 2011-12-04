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
    
    protected function valueForType($type, $value) {
        $valueForType = $value;
  
        switch ($type) {
            case 'datetime':
                $valueForType = DateFormatter::formatDateRange($value, DateFormatter::LONG_STYLE, DateFormatter::NO_STYLE);
                if ($value instanceOf TimeRange) {
                    $timeString = DateFormatter::formatDateRange($value, DateFormatter::NO_STYLE, DateFormatter::MEDIUM_STYLE);
                    $valueForType .= "<br />\n" . $timeString;
                }
                break;

            case 'url':
                $valueForType = str_replace("http://http://", "http://", $value);
                if (strlen($valueForType) && !preg_match('/^http\:\/\//', $valueForType)) {
                    $valueForType = 'http://'.$valueForType;
                }
                break;
        
            case 'phone':
                $valueForType = PhoneFormatter::formatPhone($value);
                break;
      
            case 'email':
                $valueForType = str_replace('@', '@&shy;', $value);
                break;
        
            case 'category':
                $valueForType = $this->formatTitle($value);
                break;
        }
    
        return $valueForType;
    }
  
    protected function urlForType($type, $value) {
        $urlForType = null;
  
        switch ($type) {
            case 'url':
                $urlForType = str_replace("http://http://", "http://", $value);
                if (strlen($urlForType) && !preg_match('/^http\:\/\//', $urlForType)) {
                    $urlForType = 'http://'.$urlForType;
                }
                break;
        
            case 'phone':
                $urlForType = PhoneFormatter::getPhoneURL($value);
                break;
        
            case 'email':
                $urlForType = "mailto:$value";
                break;
        
            case 'category':
                $urlForType = $this->categoryURL($value, false);
                break;
        }
    
        return $urlForType;
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
    
    protected function linkForSechedule(KurogoObject $event, $data=null) {
        $subtitle = date("H:i:s", $event->get_start()) . " - " . date("H:i:s", $event->get_end());

        $options = array(
            'id'   => $event->get_uid(),
            'time' => $event->get_start()
        );
        
        if (isset($data['section'])) {
            $options['section'] = $data['section'];
        }
        
        $class = '';
        $url = $this->buildBreadcrumbURL('schedule', $options, true);
        if ($event->getRange()->contains(new TimeRange(time()))) {
            $class = 'open';
        } else {
            $class = 'closed';
        }
                    
        return array(
            'title'     => $event->get_summary(),
            'subtitle'  => $subtitle,
            'url'       => $url,
            'listclass' => $class
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
                $options = array(
                    'section' => $id
                );
                foreach($items as $item) {
                    $event = $this->linkForSechedule($item, $options);
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
            case 'schedule':
                $section = $this->getArg('section');
                $id = $this->getArg('id');
                
                $feed = $this->getLocationFeed($section);
                $time = $this->getArg('time', time(), FILTER_VALIDATE_INT);
                
                if ($event = $feed->getItem($id, $time)) {
                    $this->assign('event', $event);
                } else {
                    throw new KurogoUserException($this->getLocalizedString('EVENT_NOT_FOUND'));
                }
                
                $eventFields = $this->getModuleSections('schedule-detail');
                $fields = array();
                foreach ($eventFields as $key => $info) {
                    $field = array();
          
                    $value = $event->get_attribute($key);
                    if (empty($value)) { continue; }

                    if (isset($info['label'])) {
                        $field['label'] = $info['label'];
                    }
          
                    if (isset($info['class'])) {
                        $field['class'] = $info['class'];
                    }
                    
                    if (isset($info['type'])) {
                        $field['title'] = $this->valueForType($info['type'], $value);
                        $field['url']   = $this->urlForType($info['type'], $value);
                    } elseif (isset($info['module'])) {
                        $field = array_merge($field, Kurogo::moduleLinkForValue($info['module'], $value, $this, $event));
                    } else {
                        $field['title'] = nl2br($value);
                    }
          
                    $fields[] = $field;
                }  
                $this->assign('fields', $fields);
                
                break;
        }
    }
}
