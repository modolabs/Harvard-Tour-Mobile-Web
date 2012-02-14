<?php

class LinkCourseContent extends CourseContent {
    protected $contentType = 'link';
    protected $type;
    protected $url;
    
    public function getType() {
    	return $this->type;
    }
    
    public function setType($type) {
    	$this->type = $type;
    }
    
    public function getURL() {
    	return $this->url;
    }
    
    public function setURL($url) {
    	$this->url = $url;
    }
    
}