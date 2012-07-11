<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
    
    protected function arrayForEvent($event) {
        return array(
            'uid' => $event->get_uid(),
            'title'=> $event->get_summary(),
            'starttime'=> $event->get_start(),
            'endtime' => $event->get_end(),
            'description' => $event->get_description(),
            'status' => $event->getRange()->contains(new TimeRange(time()))?"open":"closed"
        );
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
                    $events[] = $this->arrayForEvent($item);
                }
            	$response['events'] = $events;
                $this->setResponse($response);
                break;
            case 'status':
                $id = $this->getArg('id');
                $feedObject = $this->getLocationFeed($id);
                $currentEvents = $feedObject->getCurrentEvents();
                $response = array();
                
                if (count($currentEvents)>0) {

                    $response['status'] = 'open';
                    $response['current'] = array();
                    foreach ($currentEvents as $item) {
                        $response['current'][] = $this->arrayForEvent($item);
                    }
                } else {

                    $item = $feedObject->getNextEvent(true);
                    $response['status'] = 'closed';
                    $response['next'] = null;
                    if ($item) {
                        $response['next'] = $this->arrayForEvent($item);
                    }
                }
                
                $this->setResponse($response);
                break;
            
            case 'index':
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
