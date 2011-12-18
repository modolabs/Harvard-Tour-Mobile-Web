<?php

abstract class CourseContent implements KurogoObject {

    protected $id;
    protected $courseID;
    protected $contentRetriever;
    protected $contentType;
    protected $title;
    protected $description;
    protected $publishedDate;
    protected $priority;
    
    public function setID($id) {
        $this->id = $id;
    }
    
    public function getID() {
        return $this->id;
    }
    
    public function setCourseID($id) {
        $this->courseID = $id;
    }
    
    public function getCourseID() {
        return $this->courseID;
    }
    
    public function setContentRetriever(CourseContentDataRetriever $retriever) {
        $this->contentRetriever = $retriever;
    }
    
    public function getContentRetriever() {
        return $this->contentRetriever;
    }
    
    public function setContentType($type) {
        $this->contentType = $type;
    }
    
    public function getContentType() {
        return $this->contentType;
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
    
    public static function getPriorities() {
	    return array('none', 'high', 'middle', 'low');
    }
    
    public function setPriority($priority = '') {
        if (in_array($priority, self::getPriorities())) {
            $this->priority = $priority;
        }
    }
    
    public function getPriority() {
        return $this->priority ? $this->priority : 'none';
    }
}