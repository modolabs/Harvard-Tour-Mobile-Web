<?php

class LinkCourseContent extends CourseContent {
    protected $contentType = 'link';
    
    public function getSubtitle() {
        return $this->getURL();
    }    
}