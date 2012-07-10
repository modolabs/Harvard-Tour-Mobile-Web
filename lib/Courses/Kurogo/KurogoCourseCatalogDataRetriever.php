<?php

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

    /**
     * @brief searchCourses 
     *
     * TODO: implement search courses method
     *
     * @param string $searchTerms
     * @param array $options
     *
     * @return array
     */
    public function searchCourses($searchTerms, $options = array()) {
    	$this->setMode('courses');
    	$baseUrl = $this->coursesURL . '/search';
    	$this->setBaseURL($baseUrl);
    	
    	$this->addFilter('filter', $searchTerms);
    	if (isset($options['term'])) {
    		$this->addFilter('term', $options['term']->getID());
    	}
    	if (isset($options['area'])) {
    		$this->addFilter('area', $options['area']);
    	}

    	return $this->getData();
    }
    
    public function getCourses($options = array()) {
        $this->setMode('courses');
        
        if (isset($options['area'])) {
            $this->addFilter('area', $options['area']);
        }

        if (isset($options['term'])) {
            $this->addFilter('term', $options['term']);
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
            $this->setOption('term', strval($options['term']));
        }
        
        if (isset($options['parent'])) {
            $this->addFilter('area', $options['parent']);
        }

        $areas =  $this->getData();
        return $areas;
    }
    
    public function getAvailableTerms() {
        
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
