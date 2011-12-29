<?php

includePackage('DataModel');

class CoursesDataModel extends DataModel {

    const CURRENT_TERM = 1;
    const ALL_TERMS = 2;
    
    protected $retrievers;
    
    public function getRetrieverModes() {
        return array('catalog', 'registation', 'content');
    }
    
    //returns an array of terms. 
    public function getAvailableTerms() {
        return self::CURRENT_TERM;
    }

    public function search($searchTerms, $options) {
        
    }
    
    //returns a Course object (may call all 3 retrievers to get the data)
    public function getCourseById($courseNumber) {
        $courseList = array();
        
        if ($this->canRetrieve('content')) {
            if ($course = $this->retrievers['content']->getCourseById($courseNumber)) {
                $courseList['content'] = $course;
            }
            if ($courseResource = $this->retrievers['content']->getCourseResourceById($courseNumber)) {
                $courseList['resource'] = $courseResource;
            }
        }
        
        if ($this->canRetrieve('catalog')) {
            if ($course = $this->retrievers['catalog']->getCourseById($courseNumber)) {
                $courseList['catalog'] = $course;
            }
        }

        return $courseList;
    }
    
    //gets grades for this user for the term (both registration and content)
    public function getGrades($term) {
        
    }
    
    //most recent activity from course
    public function getLastUpdate($courseID) {
        if ($this->canRetrieve('content')) {
            return $this->retrievers['content']->getLastUpdate($courseID);
        }
        
        return array();
    }
    
    public function canRetrieve($type) {
        if (isset($this->retrievers[$type]) && $this->retrievers[$type]) {
            return true;
        } else {
            return false;
        }
    }
    
    //use the CourseCatalogDataRetriever to get the courses
    /* options:
     *'area'=> a area code
     *'courseNumber' => a course number
     */
    public function getCatalogCourses($option) {
        if ($retriever = $this->canRetrieve('catalog')) {
            return $this->retrievers['catalog']->getCourses($option);
        }
        return array();
    }
    
    public function getRegistationCourses() {
        //there is some test data
        
    }
    
    //use the CourseContentDataRetriever to get the courses
    public function getContentCourses($options = array()) {
        if ($this->canRetrieve('content')) {
            return $this->retrievers['content']->getCourses($options);
        }
        return array();
    }
    
    //get the catalog areas
    public function getCatalogAreas($area = null) {
        if ($this->canRetrieve('catalog')) {
            return $this->retrievers['catalog']->getCatalogAreas($area);
        }
    }
    
    //get a catalog area
    public function getCatalogArea($area) {
        $areas = explode("|", $area);
        
        if ($areaCode = array_shift($areas)) {
            $area = null;
            if ($items = $this->getCatalogAreas()) {
                foreach ($items as $item) {
                    if ($areaCode == $item->getID()) {
                        $area = $item;
                    }
                }
            }
            while ($area && $areaCode = array_shift($areas)) {
                $area = $area->getArea($areaCode);
            }
        }
        
        return $area;
    }
    
    public function setCoursesRetriever($type, DataRetriever $retriever) {
        if ($retriever instanceOf $this->RETRIEVER_INTERFACE) {
            $this->retrievers[$type] = $retriever;
        } else {
            throw new KurogoException("Data Retriever " . get_class($retriever) . " must conform to $this->RETRIEVER_INTERFACE");
        }
    }
    
    protected function init($args) {
        $this->initArgs = $args;
        
        if (isset($args['catalog'])) {
            $arg = $args['catalog'];
            $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
            $catalogRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
            $this->setCoursesRetriever('catalog', $catalogRetriever);
        }
        
        if (isset($args['registation'])) {
            $arg = $args['registation'];
            $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
            $registationRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
            $this->setCoursesRetriever('registation', $registationRetriever);
        }
        
        if (isset($args['content'])) {
            $arg = $args['content'];
            $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
            $contentRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
            $this->setCoursesRetriever('content', $contentRetriever);
        }
    }
}
