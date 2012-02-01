<?php

includePackage('DataModel');

class CoursesDataModel extends DataModel {

    const CURRENT_TERM = 1;
    const ALL_TERMS = 2;
    protected $retrievers=array();
    
    //returns an array of terms. 
    public function getAvailableTerms() {
        return array(self::getCurrentTerm());
    }
    
    protected function getCurrentTerm() {
        $term = new CourseTermCurrent();
        return $term;
    }
    
    public function getTerm($termCode) {
        if ($termCode==self::CURRENT_TERM) {
            return self::getCurrentTerm();
        } else {
            /** @TODO retrieve term values */
            return null;
        }
    }

    //returns a Course object (may call all 3 retrievers to get the data)
    public function getCourseByCommonID($courseID, $options) {
        $combinedCourse = new CombinedCourse();
        $ok = false;
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
    protected function init($args) {
        $this->initArgs = $args;
        if (isset($args['catalog'])) {
        	includePackage('Courses','CourseCatalog');
            $arg = $args['catalog'];
            $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
            $catalogRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
            $this->setCoursesRetriever('catalog', $catalogRetriever);
        }
        
        if (isset($args['registration'])) {
        	includePackage('Courses','CourseRegistration');
            $arg = $args['registration'];
            $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
            $registationRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
            $this->setCoursesRetriever('registration', $registationRetriever);
        }
        
        if (isset($args['content'])) {
        	includePackage('Courses','CourseContent');
            $arg = $args['content'];
            $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
            $contentRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
            $this->setCoursesRetriever('content', $contentRetriever);
        }
    }
}
