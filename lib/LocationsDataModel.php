<?php

includePackage('Calendar');
class LocationsDataModel extends CalendarDataModel {

    protected function init($args) {
        parent::init($args);
    }
    
    public function getCurrentEvent() {
        $current = new DateTime();
        $current->setTime(date('H'), floor(date('i')/5)*5, 0);
        
        if ($nextEvent = $this->getNextEvent(true)) {
            if ($nextEvent->get_start() < $current->format('U')) {
                return $nextEvent;
            }
        }
        
        return null;
    }
}