<?php

class CourseCatalogCourse extends Course {

    protected $area;
    protected $areaCode;
    protected $sections=array();

    public function setArea($area) {
        $this->area = $area;
    }

    public function getArea() {
        return $this->area;
    }
    
    public function setAreaCode($areaCode) {
        $this->areaCode = $areaCode;
    }

    public function getAreaCode() {
        return $this->areaCode;
    }
    
    public function addSection(CourseSectionObject $section) {
        $this->sections[$section->getSectionNumber()] = $section;
    }

    public function getSections() {
        return $this->sections;
    }
    
    public function getSection($section) {
        return isset($this->sections[$section]) ? $this->sections[$section] : null;
    }

}
