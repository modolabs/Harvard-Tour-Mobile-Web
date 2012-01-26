<?php

includePackage('DataModel');
class CourseAreasDataModel extends ItemListDataModel {
    public function getArea($area) {
        $areas = explode("|", $area);
        
        if ($areaCode = array_shift($areas)) {
            $area = $this->getItem($areaCode);
            while ($area && $areaCode = array_shift($areas)) { 
                $area = $area->getArea($areaCode);
            }
        }

        return $area;
    }
        
    public function getAreas($area=null) {
        $this->setOption('area', $area);
        $areas = $this->items();
        return $areas;        
    }
}
