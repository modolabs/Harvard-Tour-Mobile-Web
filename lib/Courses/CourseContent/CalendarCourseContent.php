<?php

class CalendarCourseContent extends CourseContent
{
    protected $date;
    
    public function setDate(DateTime $date) {
        $this->date = $date;
    }

    public function getDate() {
        return $this->date;
    }
}
