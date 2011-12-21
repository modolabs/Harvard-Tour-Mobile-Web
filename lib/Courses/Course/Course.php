<?php

class Course implements KurogoObject {

    protected $courseNumber;
    protected $retrieverIds;
    protected $title;
    protected $description;
    protected $term;
    protected $retrievers;
    
    public function filterItem($filters) {
        return true;
    }
    
    public function getID() {
        return md5(serialize($this->retrieverIds));
    }
    
    public function setCourseNumber($courseNumber) {
        $this->courseNumber = $courseNumber;
    }
    
    public function getCourseNumber() {
        return $this->courseNumber;
    }
    
    public function addRetrieverId($type, $id) {
        $this->retrieverIds[$type] = $id;
    }
    
    public function setRetrieverIds($ids) {
        $this->retrieverIds = $ids;
    }
    
    public function getRetrieverId($type) {
        return isset($this->retrieverIds[$type]) ? $this->retrieverIds[$type] : '';
    }
    
    public function setRetrievers($retrievers) {
        $this->retrievers = $retrievers;
    }
    
    public function setRetriever($type, DataRetriever $retriever) {
        $this->retrievers[$type] = $retriever;
    }
    
    public function canRetrieve($type) {
        if (isset($this->retrievers[$type]) && $this->retrievers[$type]) {
            return $this->retrievers[$type];
        }
        
        return false;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function setDescription($description) {
        $this->description = $description;
    }

    public function getDescription() {
        return $this->description;
    }
    
    public function setTerm(CourseTerm $term) {
        $this->term = $term;
    }
    
    public function getTerm() {
        return $this->term;
    }
}
