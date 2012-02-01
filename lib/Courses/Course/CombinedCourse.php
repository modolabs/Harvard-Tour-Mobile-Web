<?php

class CombinedCourse implements CourseInterface
{
    protected $id;
    protected $courses = array();
    
    public function getTitle($type=null) {
        if ($type==null) {
            $type = key($this->courses);
        }

        if ($course = $this->getCourse($type)) {
            return $course->getTitle();
        }        
    }
    
    public function getCourse($type) {
        return isset($this->courses[$type]) ? $this->courses[$type] : null;
    }

    public function filterItem($filters) {
        return true;
    }
    
    public function addCourse($type, Course $course) {
        $this->courses[$type] = $course;
        $this->id = $course->getCommonID();
    }

    public function getID($type=null) {
        return $this->id;
    }
}
