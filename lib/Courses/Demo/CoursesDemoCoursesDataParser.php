<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CoursesDemoCoursesDataParser extends JSONDataParser {

    public function parseData($data) {
        $data = parent::parseData($data);
        if($data['error']) {
            return array();
        }else {
            $items = array();
            if(is_array($data['response']) && $data['count'] > 0) {
                $this->setTotalItems($data['count']);
                foreach($data['response'] as $item) {
                    $items[] = $this->parseCourse($item);
                }
            }
            return $items;
        }
    }

    public function parseCourse($item) {
        $course = new CourseJsonObject();
        $course->setTermCode($item['term_code']);
        $course->setID($item['course_id']);
        $course->setCourseNumber($item['course_number']);
        $course->setArea($item['area_code']);
        $course->setAreaCode($item['area_code']);
//        $course->setCatalogNumber($item['catalog_number']);
        $course->setTitle($item['course_title']);
        $course->setDescription($item['course_description']);
        $course->setAttribute('requirements', $item['requirements']);
        if(isset($item['sections']) && is_array($item['sections'])) {
            foreach($item['sections'] as $section) {
                $course->addSection($this->parseSection($section));
            }
        }
        
        return $course;
    }

    public function parseSection($item) {
        $section = new CourseSectionJsonObject();
        $section->setClassNumber($item['class_number']);
        $section->setSectionNumber($item['section_number']);
        $section->setCredits($item['credits']);
        $section->setCreditLevel($item['credit_level']);
        $section->setEnrollment($item['enrollment']);
        $section->setEnrollmentLimit($item['enrollment_limit']);
        if (isset($item['instructors'])) {
        	foreach ($item['instructors'] as $value) {
        		$instructor = new CourseUser();
        		//$instructor->setID($value['user_id']);
        		$instructor->setFirstName($value['first_name']);
        		$instructor->setLastName($value['last_name']);
        		//$instructor->setEmail($value['email']);
        		$section->addInstructor($instructor);
        	}
        }
        //$section->setInstructor($item['instructor_name']);
        //$section->setInstructorID($item['instructor_id']);
        if(isset($item['schedules']) && is_array($item['schedules'])) {
            foreach($item['schedules'] as $schedule) {
                $section->addScheduleItem($this->parseSchedule($schedule));
            }
        }
        
        return $section;
    }

    public function parseSchedule($item) {
        $schedule = new CourseScheduleJsonObject();
        $schedule->setDays($item['days']);
        $schedule->setStartTime($item['start_time']);
        $schedule->setEndTime($item['end_time']);
        $schedule->setStartDate($item['start_date']);
        $schedule->setEndDate($item['end_date']);
        $schedule->setBuilding($item['building']);
        $schedule->setRoom($item['locations']);

        return $schedule;
    }
}

class CourseJsonObject extends CourseCatalogCourse {
    protected $commonID_field = 'id';
    
    public function getInstructors() {
        $instructors = array();
        if ($sections = $this->getSections()) {
            foreach ($sections as $section) {
                $instructors = array_merge($instructors, $section->getInstructors());
            }
        }
        return $instructors;
    }
}

class CourseSectionJsonObject extends CourseSectionObject {
	protected $instructors;
	
	public function addInstructor(CourseUser $instructor) {
		$this->instructors[] = $instructor;
	}
	
	public function getInstructors() {
		return $this->instructors;
	}
}

class CourseScheduleJsonObject extends CourseScheduleObject {
}
