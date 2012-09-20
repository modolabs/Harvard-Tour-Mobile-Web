<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CoursesDemoCourseContentCourse extends CourseContentCourse {
    protected $commonID_field = 'courseNumber';
    protected $instructors = array();
    protected $students = array();
    protected $schedule = array();
    
    public function setAttributes($attribs) {
        if (is_array($attribs)) {
            $this->attributes = $attribs;
        }
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
    }

    public function getAttribute($attrib) {
        return isset($this->attributes[$attrib]) ? $this->attributes[$attrib] : '';
    }

    /*
    public function getStudents() {
        if ($retriever = $this->getRetriever()) {
            $users = $retriever->getUsersByCourseId($this->getID());
            $Students = array();
            foreach ($users as $user){
                if ($this->isStudent($user)) {
                    $Students[] = $user;
                }
            }
            return $Students;
        }
    }

	public function getInstructors() {
		if ($retriever = $this->getRetriever()) {
			$users = $retriever->getUsersByCourseId($this->getID());
			$instructorList = array();
		    foreach ($users as $user){
	            if ($this->isInstructor($user)) {
	                $instructorList[] = $user;
	            }
            }
			return $instructorList;
		}
	}
    */
    
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
    
    public function addInstructor(CourseUser $instructor) {
		$this->instructors[] = $instructor;
	}
	
	public function getInstructors() {
		return $this->instructors;
	}
	
	public function getStudents() {
	    return array();
	}
	
    public function getLastUpdate() {
        if ($courseContents = $this->getUpdates()) {
            $courseContents = $this->sortCourseContent($courseContents, 'publishedDate');
            return current($courseContents);
        }
        return array();
    }

    protected function sortCourseContent($courseContents, $sort) {
        if (empty($courseContents)) {
            return array();
        }

		$this->sortType = $sort;

		uasort($courseContents, array($this, "sortByField"));

        return $courseContents;
    }

	private function sortByField($contentA, $contentB) {
        if ($this->sortType == 'publishedDate') {
            $contentA_time = $contentA->getPublishedDate() ? $contentA->getPublishedDate()->format('U') : 0;
            $contentB_time = $contentB->getPublishedDate() ? $contentB->getPublishedDate()->format('U') : 0;
            return $contentA_time < $contentB_time;
       } else {
            $func = 'get' . $this->sortType;
            return strcasecmp($contentA->$func(), $contentB->$func());
        }
	}

    public function getAnnouncements($options=array()) {
        if($retriever = $this->getRetriever()){
        	$term = $this->getTerm();
        	$options['term'] = $term->getID();
        	$options['classNumber'] = $this->getAttribute('class_number');
        	
            return $retriever->getAnnouncements($this, $options);
        }
    }

    public function getUpdates($options=array()) {
        if ($retriever = $this->getRetriever()) {
        	$term = $this->getTerm();
        	$options['term'] = $term->getID();
        	$options['classNumber'] = $this->getAttribute('class_number');
            return $retriever->getUpdates($this, $options);
        }
    }

    public function getTasks($options=array()) {
        if ($retriever = $this->getRetriever()) {
            $term = $this->getTerm();
        	$options['term'] = $term->getID();
        	$options['classNumber'] = $this->getAttribute('class_number');
            return $retriever->getTasks($this, $options);
        }
        return array();
    }

    public function getResources($options=array()) {
        if ($retriever = $this->getRetriever()) {
            $term = $this->getTerm();
        	$options['term'] = $term->getID();
        	$options['classNumber'] = $this->getAttribute('class_number');
            return $retriever->getResources($this, $options);
        }
    }

    public function getGrades($options=array()) {
    }

    public function getTaskById($id, $options=array()) {
        $tasks = $this->getTasks($options);
        foreach ($tasks as $item) {
            if ($item->getID()==$id) {
                return $item;
            }
        }

        return null;
    }

    public function getContentById($id, $options=array()) {
        $type = isset($options['type']) ? $options['type'] : '';
        if ($retriever = $this->getRetriever()) {
        	switch ($type) {
        		case 'announcement':
        			$content = $this->getAnnouncements($options);
        			break;
        		case 'resource':
        			$content = $this->getResources($options);
        			break;
        		default:
        			$content = $this->getUpdates($options);
        			break;
        	}
        	
            //$content = $retriever->getCourseContent($this->getID(), $options);
            foreach ($content as $item) {
                if ($item->getID()==$id) {
                    return $item;
                }
            }
        }

        return null;
    }

    public function getFileForContent($id, $options=array()) {
        if ($content = $this->getContentById($id, $options)) {
            $url = $content->getFileURL();
            if ($retriever = $this->getRetriever()) {
                return $retriever->getFileForUrl($url, $id . '_' . $content->getFileName());
            }
        }

        return null;
    }

    public function isInstructor(CourseUser $user) {
        return false;
    }

    public function isStudent(CourseUser $user) {
        return false;
    } 
}
