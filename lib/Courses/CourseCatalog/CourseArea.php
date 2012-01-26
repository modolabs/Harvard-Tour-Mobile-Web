<?php

class CourseArea 
{
    protected $title;
    protected $description;
    protected $code;
    protected $parent;
    protected $areas=array();
    
    public function getID() {
        return $this->code;
    }
    
    public function addArea(CourseArea $area) {
        $this->areas[$area->getCode()] = $area;
    }
    
    public function getAreas() {
        return $this->areas;
    }
    
    public function getArea($area) {
        return isset($this->areas[$area]) ? $this->areas[$area] : null;
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

    public function setCode($code) {
        $this->code = $code;
    }
    
    public function getCode() {
        return $this->code;
    }

    public function setParent($parent) {
        $this->parent = $parent;
    }
    
    public function getParent() {
        return $this->parent;
    }
}
