<?php

includePackage('Locations');
class LocationsAPIModule extends APIModule {
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
            case 'list':
            	foreach($this->feeds as $id => $feedData){
            		$feedObject = $this->getLocationFeed($id);
            		$currentEvent = $feedObject->getCurrentEvent();
            		$status = $currentEvent?"open":"closed";
            		$feed= array(
            			'id'=>$feedData['INDEX'],
            			'title'=>$feedData['TITLE'],
	            		'subtitle'=>$feedData['SUBTITLE'],
	            		'maplocation'=>$feedData['MAP_LOCATION'],
	            		'description'=>$feedData['DESCRIPTION'],
	            		'status'=>$status
            		);
            		$feeds[] = $feed;
            	}
            	$response = $feeds;
            	$this->setResponse($response);
            	break;
            default:
                $this->invalidCommand();
                break;
        }
    }

}
