<?php

class DownloadCourseContent extends CourseContent {
    protected $contentType = 'file';
    protected $type;
    protected $filename;
    protected $filepath;
    protected $filesize;
    protected $fileurl;
    protected $timecreated;
    protected $timemodified;
    protected $sortorder;
    protected $userid;
    protected $author;
    protected $license;
    protected $cacheFile;
    
    public function getContentMimeType() {
        if ($this->filename) {
            return mime_type($this->filename);
        }
        return null;
    }
    
	public function getType() {
		return $this->type;
	}
	
	public function setType($type) {
		$this->type = $type;
	}  
	
	public function getFilename() {
		return $this->filename;
	}
	
	public function setFilename($filename) {
		$this->filename = $filename;
	}
	
	public function getFilepath() {
		return $this->filepath;
	}
	
	public function setFilepath($filepath) {
		$this->filepath = $filepath;
	}
	public function getFilesize() {
		return $this->filesize;
	}
	
	public function getFile() {
	    $this->retrieveFile($this->getFileurl());
	}
	
	public function setFilesize($filesize) {
		$this->filesize = $filesize;
	}
	
	
	
	public function getFileurl() {
		return $this->fileurl;
	}
	
	public function setFileurl($fileurl) {
		$this->fileurl = $fileurl;
	}
	public function getTimecreated() {
		return $this->timecreated;
	}
	
	public function setTimecreated($timecreated) {
		$this->timecreated = $timecreated;
	}
	public function getTimemodified() {
		return $this->timemodified;
	}
	
	public function setTimemodified($timemodified) {
		$this->timemodified = $timemodified;
	}
	public function getSortorder() {
		return $this->sortorder;
	}
	
	public function setSortorder($sortorder) {
		$this->sortorder = $sortorder;
	}
	public function getUserid() {
		return $this->userid;
	}
	
	public function setUserid($userid) {
		$this->userid = $userid;
	}
	public function getAuthor() {
		return $this->author;
	}
	
	public function setAuthor($author) {
		$this->author = $author;
	}
	public function getLicense() {
		return $this->license;
	}
	
	public function setLicense($license) {
		$this->license = $license;
	}
	
    public function setCacheFile($file) {
        $this->cacheFile = $file;
    }
    
    public function getCacheFile() {
        return $this->cacheFile;
    }
    
    public function getContentFile() {
        return $this->fileUrl;
    }
}