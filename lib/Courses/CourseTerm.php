<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CourseTerm {

    protected $id;
    protected $title;
    protected $startDate;
    protected $endDate;
    protected $attributes=array();
        
    public function __toString() {
        return strval($this->id);
    }
    
    public function setID($id) {
        $this->id = $id;
    }
    
    public function getID() {
        return $this->id;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function setStartDate($date) {
        $this->startDate = $date;
    }
    
    public function getStartDate() {
        return $this->startDate;
    }
    
    public function setEndDate($date) {
        $this->endDate = $date;
    }
    
    public function getEndDate() {
        return $this->endDate;
    }

    public function setAttributes($attribs) {
        if (is_array($attribs)) {
            $this->attributes = $attribs;
        }
    }
    
    public function getAttributes() {
        return $this->attributes;
    }
    
    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
    }
    
    public function getAttribute($attrib) {
        return isset($this->attributes[$attrib]) ? $this->attributes[$attrib] : '';
    }
}
