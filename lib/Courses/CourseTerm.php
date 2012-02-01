<?php

class CourseTerm {

    protected $id;
    protected $title;
    protected $startDate;
    protected $endDate;
        
    public function __toString() {
        return strval($this->id);
    }
    
    public function setID($id) {
        $this->id = $id;
    }
    
    public function getID() {
        return $this->id;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function getTitle() {
        return $this->title;
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
}
