<?php

class LinkCourseContent extends CourseContent {
    protected $contentType = 'link';
    protected $url;
    
    public function getSubtitle() {
        return $this->getURL();
    }
    
    public function getURL() {
    	return $this->url;
    }
    
    public function setURL($url) {
    	$this->url = $url;
    }
    
}