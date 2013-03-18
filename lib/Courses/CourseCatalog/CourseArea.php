<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CourseArea 
{
    protected $title;
    protected $description;
    protected $code;
    protected $parent;
    protected $showCode = true;
    protected $areas=array();
    
    public function getID() {
        return $this->code;
    }
    
    public function addArea(CourseArea $area) {
        $this->areas[$area->getCode()] = $area;
    }
    
    public function getAreas($subareas = false) {
    	$areas = $this->areas;
    	if ($subareas) {
			foreach ($this->areas as $area) {
				$areas = array_merge($areas, $area->getAreas($subareas));
			}
		}
    	return $areas;
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

    public function setShowCode($showCode) {
        $this->showCode = $showCode;
    }
    
    public function showCode() {
        return $this->showCode;
    }
}
