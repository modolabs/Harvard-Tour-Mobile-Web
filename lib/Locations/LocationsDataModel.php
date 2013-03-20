<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Calendar');
class LocationsDataModel extends CalendarDataModel {
    protected $subtitle;
    protected $description;
    protected $mapLocation;

    protected function init($args) {
        parent::init($args);

        if(isset($args['SUBTITLE']) && strlen($args['SUBTITLE']) > 0) {
            $this->subtitle = $args['SUBTITLE'];
        }

        if(isset($args['MAP_LOCATION']) && strlen($args['MAP_LOCATION']) > 0) {
            $this->mapLocation = $args['MAP_LOCATION'];
        }

        if(isset($args['DESCRIPTION']) && strlen($args['DESCRIPTION']) > 0) {
            $this->description = $args['DESCRIPTION'];
        }
    }

    public function getCurrentEvents() {
        $current = new DateTime();
        $current->setTime(date('H'), floor(date('i')/5)*5, 0);
        $this->setStartDate($current);
        $this->setEndDate($current);

        $calendar = $this->getCalendar();
        $startTimestamp = $this->startTimestamp() ? $this->startTimestamp() : CalendarDataController::START_TIME_LIMIT;
        $endTimestamp = $this->endTimestamp() ? $this->endTimestamp() : CalendarDataController::END_TIME_LIMIT;
        $range = new TimeRange($startTimestamp, $endTimestamp);
        
        return $calendar->getEventsInRange($range);
    }

    public function getCurrentEvent() {
        $events = $this->getCurrentEvents();
        return is_array($events) ? current($events) : null;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getMapLocation() {
        return $this->mapLocation;
    }

    public function getSubtitle() {
        return $this->subtitle;
    }

    /**
     * setTime 
     * used for passing time to retriever
     * 
     * @access public
     * @return void
     */
    public function setTime($time) {
        $this->setOption('time', $time);
    }
}
