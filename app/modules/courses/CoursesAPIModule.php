<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Courses');
includePackage('DateTime');

class CoursesAPIModule extends APIModule {

    protected $id = 'courses';
    protected $controller;
    protected $selectedTerm;
    protected $defaultModel = 'CoursesDataModel';
    protected $Term;
    protected $showCourseNumber;
    
    protected function assignTerm(){
        $feedTerms = $this->controller->getAvailableTerms();

        $term = $this->getArg('term', CoursesDataModel::CURRENT_TERM);
        if (!$Term = $this->controller->getTerm($term)) {
            $Term = $this->controller->getCurrentTerm();
        }

        
        $this->controller->setCurrentTerm($Term);
        return $Term;
    }
    
    protected function formatArea(CourseArea $area) {
        $item = array(
            'code'  => $area->getCode(),
            'title' => $area->getTitle(),
            'parent'=> $area->getParent(),
            'description' => $area->getDescription(),
        );
        
        return $item;
    }
        
    protected function formatSchedule(CourseScheduleObject $schedule) {
        $item = array(
            'days' => $schedule->getDays(),
            'startTime' => $schedule->getStartTime(),
            'endTime' => $schedule->getEndTime(),
            'startDate' => $schedule->getStartDate(),
            'endDate' => $schedule->getEndDate(),
            'building' => $schedule->getBuilding(),
            'room' => $schedule->getRoom()
        );
        
        return $item;
    }
        	
    protected function formatInstructor(CourseUser $instructor) {
        $item = array(
            'firstName' => $instructor->getFirstName(),
            'lastName'  => $instructor->getLastName()
        );
        
        return $item;
    }
    
    protected function formatSection(CourseSectionObject $section) {
        $item = array(
            'classNumber' => $section->getClassNumber(),
            'sectionNumber' => $section->getSectionNumber(),
            'credits' => $section->getCredits(),
            'creditLevel' => $section->getCreditLevel(),
            'enrollment' => $section->getEnrollment(),
            'enrollmentLimit' => $section->getEnrollmentLimit(),
        );
        
        if ($schedules = $section->getScheduleItems()) {
            $item['schedules'] = array();
            foreach ($schedules as $schedule) {
                $item['schedules'][] = $this->formatSchedule($schedule);
            }
        }
        
        if (method_exists($section, 'getInstructors')) {
            if ($instructors = $section->getInstructors()) {
                $item['instructors'] = array();
                foreach ($instructors as $instructor) {
                    $item['instructors'][] = $this->formatInstructor($instructor);
                }
            }
        }
        
        return $item;
    }
    
    protected function formatCourse(CourseInterface $course) {
        $item = array(
            'courseID' => $course->getID(),
            'title'    => $course->getTitle(),
            'courseNumber' => $course->getField('courseNumber'),
            'description' => $course->getField('description'),
        );
        
        if ($catalogCourse = $course->getCourse('catalog')) {
            $item['area'] = $catalogCourse->getArea();
            $item['areaCode'] = $catalogCourse->getAreaCode();
            if ($term = $catalogCourse->getTerm()) {
                $item['term'] = strval($term);
            }
            if ($sections = $catalogCourse->getSections()) {
                $item['sections'] = array();
                foreach ($sections as $section) {
                    $item['sections'][] = $this->formatSection($section);
                }
            }
        }
        
        return $item;
    }
    
    protected function initializeForCommand() {
        if(!$this->feeds = $this->loadFeedData()){
            throw new KurogoConfigurationException("Feeds configuration cannot be empty.");
        }
        $this->controller = CoursesDataModel::factory($this->defaultModel, $this->feeds);
        //load showCourseNumber setting
        $this->showCourseNumber = $this->getOptionalModuleVar('SHOW_COURSENUMBER_IN_LIST', 1);
        $this->term = $this->assignTerm();

        switch($this->command) {
            case 'areas':
                $options = array('term' => strval($this->Term));
                if ($area = $this->getArg('area', '')) {
                    $options['parent'] = $area;
                }
                
                $areas = array();
                if ($items = $this->controller->getCatalogAreas($options)) {
                    foreach ($items as $area) {
                        $areas[] = $this->formatArea($area);
                    }
                }

                $response = array(
                    'total'   => count($areas),
                    'results' => $areas,
                );
                
                $this->setResponse($response);
                $this->setResponseVersion(1);
                
                break;

            case 'courses':
                $area = $this->getArg('area', '');
                $options = array(
                    'term' => strval($this->term),
                    'area' => $area,
                    'type' => 'catalog',
                );
                
                $courses = array();
                if ($items = $this->controller->getCourses($options)) {
                    foreach ($items as $item) {
                        $courses[] = $this->formatCourse($item);
                    }
                }
                
                $response = array(
                    'total'   => count($courses),
                    'results' => $courses,
                );
                
                $this->setResponse($response);
                $this->setResponseVersion(1);
                
                break;
                
            default:
                 $this->invalidCommand();
                 break;
        }
    }
}
