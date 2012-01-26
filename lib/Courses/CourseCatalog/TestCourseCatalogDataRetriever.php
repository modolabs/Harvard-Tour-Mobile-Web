<?php

class TestCourseCatalogDataRetriever extends URLDataRetriever implements CourseCatalogDataRetriever {

    protected $DEFAULT_PARSER_CLASS='CoursesXMLDataParser';
    protected $areasFeed;
    protected $coursesFeed;
    
    public function getCourses($options = array()) {
        if ($this->coursesFeed && isset($this->coursesFeed['BASE_URL']) && $this->coursesFeed['BASE_URL']) {
            $args = $this->coursesFeed;
            
            $this->setBaseURL($args['BASE_URL']);
            
            //set the dynamic parser
            $args['PARSER_CLASS'] = isset($args['PARSER_CLASS']) && $args['PARSER_CLASS'] ? $args['PARSER_CLASS']: $this->DEFAULT_PARSER_CLASS;
            $parser = DataParser::factory($args['PARSER_CLASS'], $args);
            $this->setParser($parser);
            
            foreach (array('area', 'courseNumber') as $field) {
                if (isset($options[$field])) {
                    $this->setOption($field, $options[$field]);
                }
            }
            
            $courses = $this->getData();
            return $courses;
        }
    }
    
    public function getCatalogAreas($area) {
        if ($this->areasFeed && isset($this->areasFeed['BASE_URL']) && $this->areasFeed['BASE_URL']) {
            $args = $this->areasFeed;
            $this->setBaseURL($args['BASE_URL']);
            
            //set the dynamic parser
            $args['PARSER_CLASS'] = isset($args['PARSER_CLASS']) && $args['PARSER_CLASS'] ? $args['PARSER_CLASS']: $this->DEFAULT_PARSER_CLASS;
            $parser = DataParser::factory($args['PARSER_CLASS'], $args);
            $this->setParser($parser);
            
            $this->setOption('area', $area);
            
            $areas = $this->getData();
            return $areas;
        }
        
        return array();
    }
    
    public function getAvailableTerms() {
        
    }
    
    public function getCourseById($courseNumber) {
        if ($course = $this->getCourses(array('courseNumber' => $courseNumber))) {
            return current($course);
        }
        return false;
    }
    
    public function getGrades($options) {
        
    }

	private function sortByField($contentA, $contentB) {
	}
	
    protected function sortCourseContent($courseContents, $sort) {
    }
    
    public function getLastUpdate($courseID) {
    }
    
    public function getCourseContent($courseID) {
    }
    
    protected function init($args) {
    
        parent::init($args);
        if (isset($args['courses']) && $args['courses']) {
            $this->coursesFeed = $args['courses'];
        }
        
        if (isset($args['areas']) && $args['areas']) {
            $this->areasFeed = $args['areas'];
        }
    }
}