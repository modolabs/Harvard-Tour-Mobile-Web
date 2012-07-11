<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CoursesDemoAreasDataParser extends JSONDataParser {

    public function parseData($data) {
        $data = parent::parseData($data);

        $areas = array();
        if($response = $data['response']) {

            foreach($response as $item) {
                $courseArea = new CourseJsonArea();
                $courseArea->setTitle($item['area_title']);
                $courseArea->setCode($item['area_code']);
                $courseArea->setParent($item['parent_code']);
                $courseArea->setDescription($item['area_description']);
                $courseArea->setRetriever($this->getResponseRetriever());
                $areas[] = $courseArea;
            }
        }
        return $areas;
    }
}

class CourseJsonArea extends CourseArea {
    protected $retriever;
    
    public function setRetriever(CoursesDemoCourseCatalogDataRetriever $retriever) {
        $this->retriever = $retriever;
    
    }
    public function getAreas($subareas=false) {
        if (!$this->areas) {
            $this->areas = $this->retriever->getCatalogAreas(array('parent'=>$this->getID()));
        }
        return $this->areas;
    }
}
            
