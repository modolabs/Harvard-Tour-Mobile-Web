<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('DataModel');

class CoursesDataModel extends DataModel {

    const CURRENT_TERM = 1;
    const ALL_TERMS = 2;
    protected $retrievers=array();
    protected $termsRetriever;
    protected $currentTerm;
    
    //returns an array of terms. 
    public function getAvailableTerms() {
        if ($this->termsRetriever) {
            return $this->termsRetriever->getAvailableTerms();
        } else {
            return array($this->getCurrentTerm());
        }
    }
    
    public function getCurrentTerm() {
        if($this->currentTerm) {
            $term = $this->currentTerm;
        } elseif ($this->termsRetriever) {
            if (!$this->termsRetriever->getTerm(self::CURRENT_TERM)) {
                throw new KurogoDataException("Unable to retrieve Current Term");
            }
        } else {
            $term = new CourseTermCurrent();
        }
        return $term;
    }

    public function setCurrentTerm(CourseTerm $term) {
        $this->currentTerm = $term;
    }
    
    public function getTerm($termCode) {
        if ($this->termsRetriever) {
            return $this->termsRetriever->getTerm($termCode);
        } elseif ($termCode==self::CURRENT_TERM) {
            return $this->getCurrentTerm();
        } else {
            /** @TODO retrieve term values */
            return null;
        }
    }

    //returns a Course object (may call all 3 retrievers to get the data)
    public function getCourseByCommonID($courseID, $options) {
        $combinedCourse = new CombinedCourse();
        $ok = false;
        if (strlen($courseID)==0) {
            return false;
        }
        foreach ($this->retrievers as $type=>$retriever) {
            if ($course = $retriever->getCourseByCommonID($courseID, $options)) {
                $combinedCourse->addCourse($type, $course);
                $ok = true;
            }
        }
        
        return $ok ? $combinedCourse : null;
    }
    
    public function canRetrieve($type) {
        if (isset($this->retrievers[$type]) && $this->retrievers[$type]) {
            return true;
        } else {
            return false;
        }
    }

    public function getRetriever($type=null) {
        return isset($this->retrievers[$type]) ? $this->retrievers[$type] : null;
    }

    public function getCatalogAreas($options=array()) {
        if ($retriever = $this->getRetriever('catalog')) {
            return $retriever->getCatalogAreas($options);
        }
    }

    public function getCatalogArea($area, $options=array()) {
        if ($retriever = $this->getRetriever('catalog')) {
            return $retriever->getCatalogArea($area, $options);
        }
    }

    public function search($searchTerms, $options) {
        $courses = array();
        if ($retriever = $this->getRetriever('catalog')) {
            $retrieverCourses = $retriever->searchCourses($searchTerms, $options);
            foreach ($retrieverCourses as $course) {
                if (!isset($courses[$course->getCommonID()])) {
                    $courses[$course->getCommonID()] = new CombinedCourse();
                }

                $combinedCourse = $courses[$course->getCommonID()];
                $combinedCourse->addCourse('catalog', $course);
            }
        }
        return $courses;
    }

    public function getCourses($options) {
        
        $courses = array();

        if (isset($options['type'])) {
            $types = array($options['type']);
        } elseif (isset($options['types'])) {
            $types = $options['types'];
        } else {
            $types = array_keys($this->retrievers);
        }
        
        foreach ($types as $type) {
            if ($this->canRetrieve($type)) {
                $retrieverCourses = $this->retrievers[$type]->getCourses($options);
                foreach ($retrieverCourses as $course) {
                    if (!isset($courses[$course->getCommonID()])) {
                        $courses[$course->getCommonID()] = new CombinedCourse();
                    }
                    
                    $combinedCourse = $courses[$course->getCommonID()];
                    $combinedCourse->addCourse($type, $course);
                }
            }
        }
                
        return $courses;
    }
    
    public function setCoursesRetriever($type, DataRetriever $retriever) {
        $interface = 'Course' . ucfirst($type) . 'DataRetriever';
        if ($retriever instanceOf $interface) {
            $this->retrievers[$type] = $retriever;
        } else {
            throw new KurogoException("Data Retriever " . get_class($retriever) . " must conform to $interface");
        }
    }

    public function setTermsRetriever(TermsDataRetriever $retriever) {
        $this->termsRetriever = $retriever;
    }
    
    protected function init($args) {
        $this->initArgs = $args;
        if (isset($args['terms'])) {
            if($enabled = Kurogo::arrayVal($args['terms'], 'ENABLED', true)){
                $arg = $args['terms'];
                $termRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
                $this->setTermsRetriever($termRetriever);
            }
        }

        if (isset($args['catalog'])) {
            if($enabled = Kurogo::arrayVal($args['catalog'], 'ENABLED', true)){
            	includePackage('Courses','CourseCatalog');
                $arg = $args['catalog'];
                $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
                $arg['TERMS_RETRIEVER'] = $this->termsRetriever;
                $catalogRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
                $this->setCoursesRetriever('catalog', $catalogRetriever);
            }
        }

        
        if (isset($args['registration'])) {
            if($enabled = Kurogo::arrayVal($args['registration'], 'ENABLED', true)){
            	includePackage('Courses','CourseRegistration');
                $arg = $args['registration'];
                $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
                $arg['TERMS_RETRIEVER'] = $this->termsRetriever;
                $registationRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
                $this->setCoursesRetriever('registration', $registationRetriever);
            }
        }
        
        if (isset($args['content'])) {
            if($enabled = Kurogo::arrayVal($args['content'], 'ENABLED', true)){
            	includePackage('Courses','CourseContent');
                $arg = $args['content'];
                $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
                $arg['TERMS_RETRIEVER'] = $this->termsRetriever;
                $contentRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
                $this->setCoursesRetriever('content', $contentRetriever);
            }
        }
    }
}
