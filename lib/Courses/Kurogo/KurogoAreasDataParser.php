<?php

class KurogoAreasDataParser extends JSONDataParser {

    public function parseData($data) {
        $data = parent::parseData($data);
        
        $areas = array();
        if (isset($data['response']) && $data['response']['total'] > 0) {
            foreach ($data['response']['results'] as $item) {
                $area = new KurogoCourseArea();
                $area->setCode($item['code']);
                $area->setTitle($item['title']);
                $area->setParent($item['parent']);
                $area->setDescription($item['description']);
                $area->setRetriever($this->getResponseRetriever());
                $areas[] = $area;
            }
        }
        
        return $areas;
    }
}

class KurogoCourseArea extends CourseArea {
    protected $retriever;
    
    public function setRetriever(KurogoCourseCatalogDataRetriever $retriever) {
        $this->retriever = $retriever;
    
    }
    public function getAreas($subareas=false) {
        if (!$this->areas) {
            $this->areas = $this->retriever->getCatalogAreas(array('parent'=>$this->getID()));
        }
        return $this->areas;
    }
}
            
