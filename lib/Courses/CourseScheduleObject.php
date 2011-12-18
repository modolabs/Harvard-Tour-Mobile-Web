<?php

includePackage('DateTime');
class CourseScheduleObject 
{
    protected $days;
    protected $startTime;
    protected $endTime;
    protected $startDate;
    protected $endDate;
    protected $building;
    protected $room;
    protected $range;
    
    public function setDays($days) {
        $this->days = $days;
    }
    
    public function getDays() {
        return $this->days;
    }

    public function setStartTime($startTime) {
        $this->startTime = $startTime;
    }
    
    public function setEndTime($endTime) {
        $this->endTime = $endTime;
    }

    public function setStartDate($date) {
        $this->startDate = $date;
    }

    public function setEndDate($date) {
        $this->endDate = $date;
    }
    
    public function setBuilding($building) {
        $this->building = $building;
    }

    public function setRoom($room) {
        $this->room = $room;
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
