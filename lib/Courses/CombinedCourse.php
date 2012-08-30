<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CombinedCourse implements CourseInterface
{
    protected $id;
    protected $courses = array();
    protected $attributes = array();
    
    public function getCourseOptions() {
        $options = array(
            'courseID'=>$this->getID()
        );
        if ($Term = $this->getTerm()) {
            $options['term'] = strval($Term);
        }
        return $options;
    }
    
    public function getTerm($type = null) {
        if ($type) {
            if ($course = $this->getCourse($type)) {
                return $course->getTerm();
            }
            return null;

        } else {
            $Term = null;
            reset($this->courses);
            foreach ($this->courses as $course) {
                if ($_Term = $course->getTerm()) {
                    if ($Term && strval($_Term) != strval($Term)) {
                        throw new KurogoDebugException("Different terms found for $this->id");
                    }
                    $Term = $_Term;
                }
            }
            return $Term;
        }
        
    }
    
    public function standardAttributes() {
        return array(
            'ID', 
            'courseNumber', 
            'title',
            'description', 
            'term',
        );
    }
    
    public function showTerm() {
        reset($this->courses);
    	foreach ($this->courses as $course) {
    		if ($course->showTerm()) {
    			return true;
    		}
    	}
    	
    	return false;
    }

    public function checkInStandardAttributes($attribute) {
    	return in_array($attribute, $this->standardAttributes());
    }
    
    public function setAttribute($attribute, $type) {
        $method = "get".ucfirst($attribute);
        if($type) {
	        $value = @call_user_func(array($this->courses[$type], $method));
        }else{
            reset($this->courses);
        	foreach($this->courses as $courseType=>$course) {
        		$value = @call_user_func(array($course, $method));
        		if($value) {
        			$type = $courseType;
        			break;
        		}
        	}
        }
        if($value) {
	        $this->attributes[$type][$attribute] = $value;
        }else{
			return false;
        }
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
            reset($this->courses);
            $type = key($this->courses);
        }

        if ($course = $this->getCourse($type)) {
            return $course->getTitle();
        }        
    }

    public function getDescription($type=null) {
        if ($type==null) {
            reset($this->courses);
            $type = key($this->courses);
        }

        if ($course = $this->getCourse($type)) {
            return $course->getDescription();
        }        
    }

    public function getInstructors($type=null) {
        if ($type==null) {
            reset($this->courses);
            $type = key($this->courses);
        }

        if ($course = $this->getCourse($type)) {
            return $course->getInstructors();
        }        
    }

    public function getTimes($type=null) {
        if ($type==null) {
            reset($this->courses);
            $type = key($this->courses);
        }

        if ($course = $this->getCourse($type)) {
            return $course->getTimes();
        }        
    }

    public function getTas($type=null) {
        if ($type==null) {
            reset($this->courses);
            $type = key($this->courses);
        }

        if ($course = $this->getCourse($type)) {
            return $course->getTas();
        }        
    }
    
    public function getCourse($key) {
        return isset($this->courses[$key]) ? $this->courses[$key] : null;
    }

    public function getCoursebyType($type){
        return isset($this->courses[$type]) ? $this->courses[$type] : null;
    }

    public function filterItem($filters) {
        return true;
    }
    
    public function addCourse(Course $course) {
        // Get the type. Combined courses may only have one course per type.
        $type = $course->getType();
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
    
    public function getArea($type=null) {
        if ($type==null) {
            reset($this->courses);
            $type = key($this->courses);
        }

        if ($course = $this->getCourse($type)) {
            return $course->getArea();
        }        
    }
}
