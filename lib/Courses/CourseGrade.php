<?php

class CourseGrade {

    protected $id;
    protected $courseID;
    protected $gradeString;
    protected $totalPoints;
    protected $availablePoints;
    protected $gradePosted;
    
    public function setID($id) {
        $this->id = $id;
    }
    
    public function getID() {
        return $this->id;
    }
    
    public function setCourseID($id) {
        $this->courseID = $id;
    }
    
    public function getCourseID() {
        return $this->courseID;
    }
    
    public function setGradeString($value) {
        $this->gradeString = $value;
    }
    
    public function getGradeString() {
        return $this->gradeString;
    }
    
    public function setTotalPoints($point) {
        $this->totalPoints = $total;
    }
    
    public function getTotalPoints() {
        return $this->totalPoints;
    }
    
    public function setAvailablePoints($points) {
        $this->availablePoints = $points;
    }
    
    public function getAvailablePoints() {
        return $this->availablePoints;
    }
    
    public function setGradePosted($time) {
        $this->gradePosted = $time;
    }
    
    public function getGradePosted() {
        return $this->gradePosted;
    }
}
