<?php

includePackage('DataModel');

class CoursesDataModel extends DataModel {

    const CURRENT_TERM = 1;
    const ALL_TERMS = 2;
    
    protected $retrievers;
    
    //returns an array of terms. 
    public function getAvailableTerms() {
        return $self:: CURRENT_TERM;
    }
    
    public function search($searchTerms, $options) {
        
    }
    
    /**
     * returns an array of Course objects
     * @param array $options
     *  'term'=> a term value or CoursesDataModel::CURRENT_TERM or CoursesDataModel::ALL_TERMS
     *  'section'=> a CourseCatalogSection - only used for catalogRetriever
     *  'kind'=> an array of retriever constants to limit by (i.e. catalog, registration, content) if empty then it will default to all available
     * @return Course object list
     */
    public function getCourses($options) {
        
    }
    
    //returns a Course object (may call all 3 retrievers to get the data)
    public function getCourseById($courseID) {
        
    }
    
    //gets grades for this user for the term (both registration and content)
    public function getGrades($term) {
        
    }
    
    public function canRetrieve($type) {
        if (isset($this->retrievers[$type]) && $this->retrievers[$type]) {
            return true;
        } else {
            return false;
        }
    }
    
    public function setRetriever($type, DataRetriever $retriever) {
        if ($retriever instanceOf $this->RETRIEVER_INTERFACE) {
            $this->retrievers[$type] = $retriever;
        } else {
            throw new KurogoException("Data Retriever " . get_class($retriever) . " must conform to $this->RETRIEVER_INTERFACE");
        }
    }
    
    protected function init($args) {
        $this->initArgs = $args;
        
        foreach ($args as $type => $arg) {
            //instantiate the retriever class and add it to the retrievers
            if (isset($arg['RETRIEVER_CLASS'])) {
                $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
                $retriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
                $this->setRetriever($type, $retriever);
            }
        }
        
        print_r($this->retrievers);
    }
}
