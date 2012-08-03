<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoCoursesDataParser extends JSONDataParser {

    public function parseData($data) {
        $data = parent::parseData($data);
        
        $courses = array();
        if (isset($data['response']) && $data['response']['total'] > 0) {
            foreach ($data['response']['results'] as $item) {
                $courses[] = $this->parseCourse($item);
            }
        }
        
        return $courses;
    }

    public function parseCourse($item) {
        $course = new KurogoCourseCatalogCourse();
        $course->setTermCode($item['term']);
        $course->setID($item['courseID']);
        $course->setCourseNumber($item['courseNumber']);
        $course->setArea($item['area']);
        $course->setAreaCode($item['areaCode']);
        $course->setTitle($item['title']);
        $course->setDescription($item['description']);
        
        if(isset($item['sections']) && is_array($item['sections'])) {
            foreach($item['sections'] as $section) {
                $course->addSection($this->parseSection($section));
            }
        }
        
        return $course;
    }

    public function parseSection($item) {
        $section = new KurogoCourseSection();
        $section->setClassNumber($item['classNumber']);
        $section->setSectionNumber($item['sectionNumber']);
        $section->setCredits($item['credits']);
        $section->setCreditLevel($item['creditLevel']);
        $section->setEnrollment($item['enrollment']);
        $section->setEnrollmentLimit($item['enrollmentLimit']);
        
        if(isset($item['schedules']) && is_array($item['schedules'])) {
            foreach($item['schedules'] as $schedule) {
                $section->addScheduleItem($this->parseSchedule($schedule));
            }
        }
        
        if (isset($item['instructors']) && $item['instructors']) {
        	foreach ($item['instructors'] as $value) {
                $instructor = new CourseUser();
                $instructor->setID($value['id']);
                $instructor->setFirstName($value['firstName']);
                $instructor->setLastName($value['lastName']);
                $section->addInstructor($instructor);
        	}
        }
        
        return $section;
    }

    public function parseSchedule($item) {
        $schedule = new KurogoCourseSchedule();
        $schedule->setDays($item['days']);
        $schedule->setStartTime($item['startTime']);
        $schedule->setEndTime($item['endTime']);
        $schedule->setStartDate($item['startDate']);
        $schedule->setEndDate($item['endDate']);
        $schedule->setBuilding($item['building']);
        $schedule->setRoom($item['room']);

        return $schedule;
    }
}

class KurogoCourseCatalogCourse extends CourseCatalogCourse {
    protected $commonID_field = 'courseNumber';
    
}

class KurogoCourseSection extends CourseSectionObject {
	protected $instructors;
	
	public function addInstructor(CourseUser $instructor) {
		$this->instructors[] = $instructor;
	}
	
	public function getInstructors() {
		return $this->instructors;
	}
}

class KurogoCourseSchedule extends CourseScheduleObject {
}
