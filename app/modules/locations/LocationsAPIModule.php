<?php

includePackage('Locations');
class LocationsAPIModule extends APIModule {
    protected $id = 'locations';
    protected $vmin = 1;
    protected $vmax = 1;
    
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

    public function initializeForCommand() {
       	$this->setResponseVersion(1);
       	$this->timezone = Kurogo::siteTimezone();
       	$this->feeds = $this->loadFeedData();
        switch ($this->command) {
            case 'schedule':
            	$id = $this->getArg('id');
				$date = $this->getArg('date', date('Y-m-d', time()));
            	
            	$feed = $this->getLocationFeed($id);
                
                // get title, subtitle and maplocation
                
                $start = new DateTime($date, $this->timezone);
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
                    $event['starttime'] = $item->get_start();
                    $event['endtime'] = $item->get_end();
                    $event['description'] = $item->get_description();
                    $event['status'] = $item->getRange()->contains(new TimeRange(time()))?"open":"closed";
                    $events[] = $event;
                }
            	$response = $events;
                $this->setResponse($events);
                break;
            case 'status':
                $id = $this->getArg('id');
                $feedObject = $this->getLocationFeed($id);
                $currentEvents = $feedObject->getCurrentEvents();
                $response = array();
                
                if (count($currentEvents)>0) {

                    $response['status'] = 'open';
                    $response['current'] = array();
                    foreach ($currentEvents as $event) {
                        $response['current'][] = $event->get_summary() . ': ' . $this->timeText($event, true);
                    }
                } else {

                    $nextEvent = $feedObject->getNextEvent(true);
                    $response['status'] = 'closed';
                    if ($nextEvent) {
                        $response['next'] = $nextEvent->get_summary() . ': ' . $this->timeText($nextEvent);
                    }
                }
                
                $this->setResponse($response);
                break;
            
            case 'locations':
            	foreach($this->feeds as $id => $feedData){
            		$feed = array(
            			'id'=>$feedData['INDEX'],
            			'title'=>$feedData['TITLE'],
            			'subtitle'=>isset($feedData['SUBTITLE']) ? $feedData['SUBTITLE'] : "",
	            		'maplocation'=>$feedData['MAP_LOCATION'],
	            		'description'=>isset($feedData['DESCRIPTION']) ? $feedData['DESCRIPTION'] : ""
	            	);
            		$feeds[] = $feed;
            	}
            	$response = array(
            	    'description'=>$this->getModuleVar('description','strings'),
            	    'locations'=>$feeds
            	);
            	$this->setResponse($response);
            	break;
            default:
                $this->invalidCommand();
                break;
        }
    }

}
