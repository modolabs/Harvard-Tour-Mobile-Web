<?php

class CombinedCourse implements CourseInterface
{
    protected $id;
    protected $courses = array();
    protected $attributes = array();
    
    public function standardAttributes() {
        return array(
            'ID', 
            'courseNumber', 
            'title',
            'description', 
            'term',
        );
    }

    public function constructAttributes($type, Course $course) {
    	foreach($this->standardAttributes() as $attribute) {
	        $method = "get".ucfirst($attribute);
	        $value = $course->$method();
	        if($value) {
		        $this->attributes[$type][$attribute] = $value;
	        }else{
				$this->attributes[$type][$attribute] = null;
	        }
    	}
    }
    
    public function getTitle($type=null) {
        if ($type==null) {
            $type = key($this->courses);
        }

        if ($course = $this->getCourse($type)) {
            return $course->getTitle();
        }        
    }
    
    public function getInstructors() {
        return array();
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
        $this->constructAttributes($type, $course);
    }

    public function getID($type=null) {
        return $this->id;
    }
    
    public function getField($field, $type=null) {
        if ($type==null) {
        	//if type is not defined, then find field in each courses
            $types = array_keys($this->courses);
        }else{
        	$types = array($type);
        }

        foreach($types as $type) {
            if (array_key_exists($type, $this->attributes)) {
                if(array_key_exists($field, $this->attributes[$type])) {
                	if(!empty($this->attributes[$type][$field])) {
			        	return $this->attributes[$type][$field];
                	}
                }
	        }
        }
        return NULL;
    }
}
