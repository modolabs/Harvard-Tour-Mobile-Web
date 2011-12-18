<?php

class CourseObject implements KurogoObject {
    protected $term;
    protected $termCode;
    protected $title;
    protected $courseNumber;
    protected $catalogNumber;
    protected $area;
    protected $areaCode;
    protected $sections=array();
    protected $description;
    protected $requirements;
    
    public function getID() {
        return $this->catalogNumber;
    }
    
    public function setTerm($term) {
        $this->term = $term;
    }
    
    public function getTerm() {
        return $this->term;
    }

    public function setTermCode($termCode) {
        $this->termCode = $termCode;
    }

    public function getTermCode() {
        return $this->termCode;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function setCourseNumber($courseNumber) {
        $this->courseNumber = $courseNumber;
    }

    public function getCourseNumber() {
        return $this->courseNumber;
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

    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter) {
                case 'search':
                    return (stripos($this->getTitle(), $value)!==FALSE) ||
                        (stripos($this->getDescription(), $value)!==FALSE);
                    break;
            }
        }

        return true;
    }
}
