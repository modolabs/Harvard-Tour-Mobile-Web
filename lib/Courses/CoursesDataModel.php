<?php

includePackage('DataModel');

class CoursesDataModel extends DataModel {

    const CURRENT_TERM = 1;
    const ALL_TERMS = 2;
    
    protected $retrievers;
    
    public function getRetrieverModes() {
        return array('catalog', 'registation', 'content');
    }
    
    public function isValidRetrieverMode($mode = '') {
        $modes = $this->getRetrieverModes();
        
        return in_array($mode, $modes);
    }
    
    //returns an array of terms. 
    public function getAvailableTerms() {
        return self::CURRENT_TERM;
    }

    public function search($searchTerms, $options) {
        
    }
    
    //returns a Course object (may call all 3 retrievers to get the data)
    public function getCourseById($courseID) {
        
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
    public function getCatalogCourses() {
        if ($retriever = $this->canRetrieve('catalog')) {
            
        }
        return array();
    }
    
    public function getRegistationCourses() {
        
    }
    
    //use the CourseContentDataRetriever to get the courses
    public function getContentCourses($options = array()) {
        if ($this->canRetrieve('content')) {
            //binding the refer course id from Registration and Catalog
            return $this->retrievers['content']->getCourses($options);
        }
        return array();
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
            $this->setCoursesRetriever('catalog', $retriever);
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
