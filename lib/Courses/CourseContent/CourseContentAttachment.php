<?php

class CourseContentAttachment
{
    protected $id;
    protected $title;
    protected $downloadMode=CourseContent::MODE_DOWNLOAD;
    protected $filename; // original filename of content
    protected $filesize;
    protected $url;
    protected $mimeType;
    protected $parentContent;

    public function getMimeType() {
        if ($this->mimeType) {
            return $this->mimeType;
        } elseif ($this->filename) {
            return mime_type($this->filename);
        } else {
            return null;
        }
    }

    public function setMimeType($mimeType) {
        $this->mimeType = $mimeType;
    }

    public function getContentClass(){
        $contentClassLookup = array(
                'text/plain' => 'file_txt',
                'text/html' => 'page',
                'text/css' => 'file_txt',
                'application/javascript' => 'file_txt',
                'application/json' => 'file_txt',
                'application/xml' => 'file_txt',
                'application/x-shockwave-flash' => '',
                'video/x-flv' => '',

                // images
                'image/png' => 'file_img',
                'image/jpeg' => 'file_img',
                'image/gif' => 'file_img',
                'image/bmp' => 'file_img',
                'image/vnd.microsoft.icon' => 'file_img',
                'image/tiff' => 'file_img',
                'image/svg+xml' => 'file_img',

                // archives
                'application/zip' => 'file_zip',
                'application/x-rar-compressed' => 'file_zip',
                'application/x-msdownload' => 'file_zip',
                'application/vnd.ms-cab-compressed' => 'file_zip',

                // audio/video
                'audio/mpeg' => 'file_video',
                'video/quicktime' => 'file_video',

                // adobe
                'application/pdf' => 'file_pdf',
                'image/vnd.adobe.photoshop' => 'file',
                'application/postscript' => 'file',

                // ms office
                'application/msword' => 'file_doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'file_doc',
                'application/rtf' => 'file_txt',
                'application/vnd.ms-excel' => 'file_xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'file_xls',
                'application/vnd.ms-powerpoint' => 'file_ppt',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'file_ppt',
                'application/vnd.ms-powerpoint.presentation.macroEnabled.12' => 'file_ppt',

                // open office
                'application/vnd.oasis.opendocument.text' => 'file_txt',
                'application/vnd.oasis.opendocument.spreadsheet' => 'file_xls',

                // blackberry
                'text/vnd.sun.j2me.app-descriptor' => 'file',
                'application/vnd.rim.cod' => 'file',
            );
        $mimeType = $this->getMimeType();
        if(array_key_exists($mimeType, $contentClassLookup)){
            return $contentClassLookup[$mimeType];
        }else{
            return 'file';
        }
    }

    public function getID() {
        return $this->id;
    }

    public function setID($id) {
        $this->id = $id;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setFileName($filename) {
        $this->filename = $filename;
    }

    public function getFileName() {
        return $this->filename;
    }

    public function setFilesize($filesize)  {
        $this->filesize = $filesize;
    }

    public function getFileSize() {
        return $this->filesize;
    }

    public function setURL($url) {
        $this->url = $url;
    }

    public function getURL() {
        return $this->url;
    }


    /**
     * Get downloadMode.
     *
     * @return downloadMode.
     */
    public function getDownloadMode() {
        if(empty($this->downloadMode)) {
            return self::MODE_DOWNLOAD;
        }
        return $this->downloadMode;
    }

    /**
     * Set downloadMode.
     *
     * @param downloadMode the value to set.
     */
    public function setDownloadMode($downloadMode) {
        $this->downloadMode = $downloadMode;
    }

    public function setParentContent(CourseContent $content) {
        $this->parentContent = $content;
    }

    public function getParentContent() {
        return $this->parentContent;
    }

    public function getContentFile(){
        return $this->url;
    }
}