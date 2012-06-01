<?php

class DownloadCourseContent extends CourseContent {
    protected $contentType = 'file';
    protected $type;
    protected $filename; // original filename of content
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
        if ($filename = $this->getContentFile()) {
            return mime_type($filename);
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
        return $this->fileurl;
    }

    public function getContentClass(){
        $contentClassLookup = array(
                'text/plain' => 'file_txt',
                'text/html' => 'file_txt',
                'text/html' => 'file_txt',
                'text/html' => 'file_txt',
                'text/css' => 'file_txt',
                'application/javascript' => 'file_txt',
                'application/json' => 'file_txt',
                'application/xml' => 'file_txt',
                'application/x-shockwave-flash' => '',
                'video/x-flv' => '',

                // images
                'image/png' => 'file_img',
                'image/jpeg' => 'file_img',
                'image/jpeg' => 'file_img',
                'image/jpeg' => 'file_img',
                'image/gif' => 'file_img',
                'image/bmp' => 'file_img',
                'image/vnd.microsoft.icon' => 'file_img',
                'image/tiff' => 'file_img',
                'image/tiff' => 'file_img',
                'image/svg+xml' => 'file_img',
                'image/svg+xml' => 'file_img',

                // archives
                'application/zip' => 'file_zip',
                'application/x-rar-compressed' => 'file_zip',
                'application/x-msdownload' => 'file_zip',
                'application/x-msdownload' => 'file_zip',
                'application/vnd.ms-cab-compressed' => 'file_zip',

                // audio/video
                'audio/mpeg' => 'file_video',
                'video/quicktime' => 'file_video',
                'video/quicktime' => 'file_video',

                // adobe
                'application/pdf' => 'file_pdf',
                'image/vnd.adobe.photoshop' => 'file',
                'application/postscript' => 'file',
                'application/postscript' => 'file',
                'application/postscript' => 'file',

                // ms office
                'application/msword' => 'file_doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'file_doc',
                'application/rtf' => 'file_txt',
                'application/vnd.ms-excel' => 'file_xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'file_xls',
                'application/vnd.ms-powerpoint' => 'file_ppt',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'file_ppt',

                // open office
                'application/vnd.oasis.opendocument.text' => 'file_txt',
                'application/vnd.oasis.opendocument.spreadsheet' => 'file_xls',

                // blackberry
                'text/vnd.sun.j2me.app-descriptor' => 'file',
                'application/vnd.rim.cod' => 'file',
            );
        $mimeType = $this->getContentMimeType();
        if(array_key_exists($mimeType, $contentClassLookup)){
            return $contentClassLookup[$mimeType];
        }else{
            return 'file';
        }
    }
}