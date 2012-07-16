<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CourseCatalogCourse extends Course {

    protected $area;
    protected $areaCode;
    protected $sections=array();
    protected $type = CoursesDataModel::COURSE_TYPE_CATALOG;

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
