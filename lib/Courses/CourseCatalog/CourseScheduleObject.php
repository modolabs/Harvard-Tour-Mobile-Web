<?php

includePackage('DateTime');
class CourseScheduleObject 
{
    protected $title;
    protected $days;
    protected $startTime;
    protected $endTime;
    protected $startDate;
    protected $endDate;
    protected $building;
    protected $room;
    protected $range;
        
    public function __toString() {
        return $this->getSchedule();
    }
    
    public function getSchedule() {
        $output = array();
        $output[] = $this->timeOutput();
        $output[] = $this->locationOutput(); 
        return implode(" - ", array_filter($output));
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }
    
    protected function locationOutput() {
        return trim(sprintf("%s %s", $this->building, $this->room));
    }
    
    protected function timeOutput() {
        return trim(sprintf("%s %s%s%s", $this->days, $this->startTime, $this->startTime && $this->endTime ? "-" : "", $this->endTime));
    }
    
    public function setDays($days) {
        $this->days = $days;
    }
    
    public function getDays() {
        return $this->days;
    }

    public function setStartTime($startTime) {
        $this->startTime = $startTime;
    }
    
    public function getStartTime() {
        return $this->startTime;
    }
    
    public function setEndTime($endTime) {
        $this->endTime = $endTime;
    }

    public function getEndTime() {
        return $this->endTime;
    }

    public function setStartDate($date) {
        $this->startDate = $date;
    }

    public function getStartDate() {
        return $this->startDate;
    }

    public function setEndDate($date) {
        $this->endDate = $date;
    }

    public function getEndDate() {
        return $this->endDate;
    }
    
    public function setBuilding($building) {
        $this->building = $building;
    }
    
    public function getBuilding() {
        return $this->building;
    }

    public function setRoom($room) {
        $this->room = $room;
    }

    public function getRoom() {
        return $this->room;
    }

    public function getTime($type) {
        if ($type == 'start') {
            $strTime = $this->startDate . ' ' . $this->startTime;
        } else {
            $strTime = $this->endDate . ' ' . $this->endTime;
        }
        $timeZone = Kurogo::siteTimezone();
        $dateTime = new DateTime($strTime, $timeZone);

        return $dateTime->format('U');
    }
    
    public function getTimeRange() {
        if (!$this->range) {
            $startTime = $this->getTime('start');
            $endTime = $this->getTime('end');
            
            $this->range = new TimeRange($startTime, $endTime);
        }
        
        return $this->range;
    }
}
