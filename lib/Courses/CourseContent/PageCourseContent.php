<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class PageCourseContent extends CourseContent {
    protected $contentType = 'page';
    protected $type;
    protected $fileurl;
    protected $filename;
    protected $timemodified;
    protected $content;
    
    public function getContent() {
        return $this->content;
    }
    
    public function setContent($content) {
        $this->content = $content;
    }

    public function getSubtitle(){
        return 'Document';
    }

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
    
    public function getFilename() {
    	return $this->filename;
    }
    
    public function setFilename($filename) {
    	$this->filename = $filename;
    }
    
    public function getTimemodified() {
    	return $this->timemodified;
    }
    
    public function setTimemodified($timemodified) {
    	$this->timemodified = $timemodified;
    }
}
