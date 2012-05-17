<?php

class CourseCatalogCourse extends Course {

    protected $catalogNumber;
    protected $area;
    protected $areaCode;
    protected $sections=array();
    protected $requirements;

    public function getID() {
        return $this->catalogNumber;
    }
    
    public function setTermCode($termCode) {
        if (!$this->term) {
            $this->term = new CourseTerm();
        }
        $this->term->setID($termCode);
    }
    
    public function setCatalogNumber($catalogNumber) {
        $this->catalogNumber = $catalogNumber;
    }

    public function getCatalogNumber() {
        return $this->catalogNumber;
    }
    
    public function setArea($area) {
        $this->area = $area;
    }

    public function getArea() {
        return $this->area;
    }
    
    public function setDescription($description) {
        $this->description = $description;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setRequirements($requirements) {
        $this->requirements = $requirements;
    }

    public function getRequirements() {
        return $this->requirements;
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
