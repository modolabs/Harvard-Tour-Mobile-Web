<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
