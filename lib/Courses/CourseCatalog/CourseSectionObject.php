<?php

class CourseSectionObject {
    protected $classNumber;
    protected $sectionNumber;
    protected $credits;
    protected $creditLevel;
    protected $schedule = array();
    protected $enrollment;
    protected $enrollmentLimit;
    protected $instructor;
    protected $instructorID;
    protected $attributes = array();

    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
    }
    
    public function getAttribute($attrib) {
        return isset($this->attributes[$attrib]) ? $this->attributes[$attrib] : '';
    }

    public function __toString() {
        return $this->sectionNumber;
    }
    
    public function addScheduleItem(CourseScheduleObject $schedule) {
        $this->schedule[] = $schedule;
    }    
    
    public function getSchedule($delimiter = " ") {
        $output = array();
        foreach ($this->schedule as $scheduleItem) {
            $output[] = strval($scheduleItem);
        }
        return implode($delimiter, $output);
    }
    
    public function getScheduleItems() {
        return $this->schedule;
    }
    
    public function setClassNumber($classNumber) {
        $this->classNumber = $classNumber;
    }

    public function getClassNumber() {
        return $this->classNumber;
    }

    public function setSectionNumber($sectionNumber) {
        $this->sectionNumber = $sectionNumber;
    }

    public function getSectionNumber() {
        return $this->sectionNumber;
    }

    public function setCredits($credits) {
        $this->credits = $credits;
    }

    public function getCredits() {
        return $this->credits;
    }

    public function setCreditLevel($creditLevel) {
        $this->creditLevel = $creditLevel;
    }
    
    public function getCreditLevel() {
        return $this->creditLevel;
    }
    
    public function setEnrollment($enrollment) {
        $this->enrollment = $enrollment;
    }
    
    public function getEnrollment() {
        return $this->enrollment;
    }
    
    public function setEnrollmentLimit($enrollmentLimit) {
        $this->enrollmentLimit = $enrollmentLimit;
    }

    public function getEnrollmentLimit() {
        return $this->enrollmentLimit;
    }

    public function setInstructor($instructor) {
        $this->instructor = $instructor;
    }
    
    public function getInstructor() {
        return $this->instructor;
    }

    public function setInstructorID($instructorID) {
        $this->instructorID = $instructorID;
    }

    public function getInstructorID() {
        return $this->instructorID;
    }
    
}
