<?php

abstract class Course implements CourseInterface {

    protected $id;
    protected $commonID;
    protected $commonID_field;
    protected $courseNumber;
    protected $title;
    protected $description;
    protected $term;
    protected $retriever;
    
    public function setCommonID($commonID) {
        $this->commonID = $commonID;
    }

    public function setCommonIDField($field) {
        $this->commonID_field = $field;
    }
    
    public function getCommonID() {
        if ($this->commonID) {
            return $this->commonID;
        } elseif ($this->commonID_field) {
            $field = $this->commonID_field;
            return $this->$field;
        }
    }
    
    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter) {
                case 'search':
                    return (stripos($this->getTitle(), $value)!==FALSE) ||
                        (stripos($this->getDescription(), $value)!==FALSE);
                    break;
            }
        }

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

