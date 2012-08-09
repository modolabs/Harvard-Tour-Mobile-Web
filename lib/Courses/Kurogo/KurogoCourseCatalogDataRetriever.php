<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Courses', 'CourseCatalog');
class KurogoCourseCatalogDataRetriever extends URLDataRetriever implements CourseCatalogDataRetriever {
    protected $areasParser;
    protected $areasURL;
    protected $coursesParser;
    protected $coursesURL;
    protected $serverURL;
    
    protected function setMode($mode) {
        $parserVar = $mode . 'Parser';
        $urlVar = $mode . 'URL';
        $this->setParser($this->$parserVar);
        $this->setBaseURL($this->$urlVar);
    }

    public function searchCourses($searchTerms, $options = array()) {
        $items = array();

        if(isset($options['area'])) {
            if (!$area = $this->getCatalogArea($options['area'], $options)) {
                return $items;
            }
            $areas = array($area);
        } else {
            $areas = $this->getCatalogAreas($options);
        }

        $courses = array();
        foreach ($areas as $area) {
            $options['area'] = $area->getID();
            $courses = array_merge($courses, $this->getCourses($options));
        }

        $filters['search'] = trim($searchTerms);
        foreach($courses as $course) {
            if($course->filterItem($filters)) {
                $items[] = $course;
            }
        }
        return $items;
    }

    public function getCourses($options = array()) {
        $this->setMode('courses');
        
        if (isset($options['area'])) {
            $this->addFilter('area', $options['area']);
        }

        if (isset($options['term'])) {
            $this->addFilter('term', strval($options['term']));
        }

        $courses = $this->getData();
        
        return $courses;
    }
    
    public function getCatalogArea($area, $options = array()) {
        $areas = $this->getCatalogAreas($options);
        foreach ($areas as $areaObj) {
            if ($areaObj->getID() == $area) {
                return $areaObj;
            }
        }
        
        return null;
    }
    
    public function getCatalogAreas($options = array()) {
        $this->setMode('areas');
        if (isset($options['term'])) {
            $this->addFilter('term', strval($options['term']));
        }
        
        if (isset($options['parent'])) {
            $this->addFilter('area', $options['parent']);
        }

        $areas =  $this->getData();
        return $areas;
    }
    
    public function getCourseByCommonId($courseID, $options) {
        $courses = $this->getCourses($options);
        foreach($courses as $course) {
            if($course->getCommonId() == $courseID) {
                return $course;
            }
        }
        return false;
    }
    
    public function getCourseById($courseNumber) {
        if ($course = $this->getCourses(array('courseNumber' => $courseNumber))) {
            return current($course);
        }
        return false;
    }
    
    private function setStandardFilters() {
    	$this->coursesURL = $this->serverURL . '/courses';
    	$this->areasURL = $this->serverURL . '/areas';
    }
    
    protected function init($args) {
        parent::init($args);
        $this->areasParser = DataParser::factory('KurogoAreasDataParser', $args);
        $this->coursesParser = DataParser::factory('KurogoCoursesDataParser', $args);
        if (!isset($args['SERVER_BASE_URL'])) {
            throw new KurogoConfigurationException("SERVER_BASE_URL not set");
        }
        $this->serverURL = rtrim($args['SERVER_BASE_URL'], '/');
        
        $this->setStandardFilters();
    }
}
