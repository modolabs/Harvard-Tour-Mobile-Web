<?php

abstract class Course implements KurogoObject {

    protected $id;
    protected $courseNumber;
    protected $title;
    protected $description;
    protected $term;
    protected $retriever;
    
    public function filterItem($filters) {
        return true;
    }
    
    public function getID() {
        return $this->id;
    }

    public function setID($id) {
        $this->id = $id;
    }
    
    public function setCourseNumber($courseNumber) {
        $this->courseNumber = $courseNumber;
    }
    
    public function getCourseNumber() {
        return $this->courseNumber;
    }
    
    public function setRetriever(CourseContentDataRetriever $retriever) {
        $this->retriever = $retriever;
    }

    public function getRetriever() {
        return $this->retriever;
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
