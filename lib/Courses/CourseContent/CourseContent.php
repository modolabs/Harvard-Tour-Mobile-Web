<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class CourseContent implements KurogoObject {

    protected $id;
    protected $parentID;
    protected $courseID;
    protected $contentRetriever;
    protected $contentCourse = null;
    protected $contentType;
    protected $title;
    protected $subtitle;
    protected $description;
    protected $url;
    protected $authorID;
    protected $author;
    protected $publishedDate;
    protected $endDate;
    protected $viewMode = self::MODE_PAGE;
    protected $attributes = array();
    protected $attachments = array();
    const MODE_PAGE = 1;
    const MODE_DOWNLOAD = 2;
    const MODE_URL = 3;

    public function filterItem($filters) {
        return true;
    }

    public function getSubTitle() {
        return $this->subtitle;
    }

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

    public function setContentCourse(CourseContentCourse $contentCourse) {
        $this->contentCourse = $contentCourse;
    }

    public function getParentID(){
        return $this->parentID;
    }

    public function setParentID($parentID){
        $this->parentID = $parentID;
    }

    public function getContentCourse() {
        //Lazy load the contentCourse
        if (!$this->contentCourse) {
            if($retriver = $this->getContentRetriever()){
                if($course = $retriver->getCourseById($this->courseID)){
                    $this->contentCourse = $course;
                }
            }
        }
        return $this->contentCourse;
    }

    public function getContentType() {
        return $this->contentType;
    }

    public function getContentClass() {
        return $this->getContentType();
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

    public function setAuthor($author) {
        $this->author = $author;
    }

    public function getAuthor() {
        if (!$this->author) {
            // if the authorID is set then attempt to retrieve the user's name
            if ($this->authorID && $retriever = $this->getContentRetriever()) {
                if ($user = $retriever->getUserByID($this->authorID)) {
                    $this->author = $user->getFullName();
                }
            }
        }
        return $this->author;
    }

    public function setAuthorID($authorID) {
        $this->authorID = $authorID;
    }

    public function getAuthorID() {
        return $this->authorID;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setPublishedDate($dateTime) {
        $this->publishedDate = $dateTime;
    }

    public function getPublishedDate() {
        return $this->publishedDate;
    }

    public function setEndDate($dateTime) {
        $this->endDate = $dateTime;
    }

    public function getEndDate() {
        return $this->endDate;
    }

    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
    }

    public function getAttribute($key) {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    /**
     * Get viewMode.
     *
     * @return viewMode.
     */
    public function getViewMode() {
        return $this->viewMode;
    }

    /**
     * Set viewMode.
     *
     * @param viewMode the value to set.
     */
    public function setViewMode($viewMode) {
        $this->viewMode = $viewMode;
    }

    public function sortBy(){
        return $this->getPublishedDate() ? $this->getPublishedDate()->format('U') : 0;
    }
    
    public function addAttachment(CourseContentAttachment $attachment) {
        $this->attachments[] = $attachment;
    }

    public function getAttachment($key) {
        return isset($this->attachments[$key]) ? $this->attachments[$key] : null;
    }

    public function getAttachments() {
        return $this->attachments;
    }
}
