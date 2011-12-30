<?php

class LinkCourseContent extends CourseContent {
    protected $contentType = 'link';
    protected $type;
    protected $fileurl;
    public function getType() {
    	return $this->type;
    }
    
    public function setType($type) {
    	$this->type = $type;
    }
    
    public function getFileurl() {
    	return $this->fileurl;
    }
    
    public function setFileurl($fileurl) {
    	$this->fileurl = $fileurl;
    }
    
}