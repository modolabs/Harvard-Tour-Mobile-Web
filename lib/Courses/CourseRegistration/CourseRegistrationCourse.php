<?php

abstract class CourseRegistrationCourse extends Course {

    protected $enrolled;
    public function setEnrolled($enrolled) {
        $this->enrolled = $enrolled;
    }
    
    public function isEnrolled() {
        return $this->enrolled;
    }
    
    public function canDrop() {
        return $this->isEnrolled();
    }
    
}