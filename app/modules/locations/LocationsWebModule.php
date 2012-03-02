<?php

includePackage('Locations');
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
        $breadCrumbs = $this->page != 'pane';
        $feed = $this->getLocationFeed($id);

        $status = "";
        if ($subtitle = $feed->getSubtitle()) {
            $subtitle .= "<br />";
        }
        
        $currentEvents = $feed->getCurrentEvents();
        $nextEvent = $feed->getNextEvent(true);
        
        if (count($currentEvents)>0) {
            $status = 'open';
            $events = array();
            $lastTime = null;
            foreach ($currentEvents as $event) {
                if ($event->get_end()>$lastTime) {
                    $lastTime = $event->get_end();
                }
                $events[] = $event->get_summary() . ': ' . $this->timeText($event, true);
            }
            $subtitle .= implode("<br />", $events);
        } else {
            $status = 'closed';
            if ($nextEvent) {
                $subtitle .= $this->getLocalizedString('NEXT_EVENT') . $nextEvent->get_summary() . ': ' . $this->timeText($nextEvent);
            }
        }
        
        if ($this->page == 'pane') {
            $subtitle = '';
        }
                
        $options = array(
            'id' => $id
        );
        
        return array(
            'title'    => $feed->getTitle(),
            'subtitle' => $subtitle, 
            'url'      => $this->buildBreadcrumbURL('detail', $options, $breadCrumbs),
            'listclass'=> $status
        );
    }
    
    protected function linkForSechedule(KurogoObject $event, $data=null) {
        $subtitle = DateFormatter::formatDateRange($event->get_range(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);

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
            case 'pane':
                //pane page makes sure that open items are always at the top closed at bottom()
                $locations = array('open'=>array(), 'closed'=>array());
                
                foreach ($this->feeds as $id => $feedData) {
                    $location = $this->linkForLocation($id);
                    $locations[$location['listclass']][] = $location;
                }
                
                $locations = array_merge($locations['open'], $locations['closed']);

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
                
                if ($mapLocation) {
                    $this->assign('location', array(Kurogo::moduleLinkForValue('map', $mapLocation, $this)));
                }

                $this->assign('title', $title);
                $this->assign('description', $feed->getDescription());
                $this->assign('current', $current);
                $this->assign('events', $events);
                $this->assign('next',    $next);
                $this->assign('prev',    $prev);
                $this->assign('nextURL', $nextURL);
                $this->assign('prevURL', $prevURL);
                $this->assign('titleDateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
                $this->assign('linkDateFormat', $this->getLocalizedString('SHORT_DAY_FORMAT'));
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
