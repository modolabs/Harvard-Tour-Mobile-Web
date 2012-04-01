<?php

interface CourseCatalogDataRetriever extends CourseDataInterface {

    public function getCatalogAreas($options = array());
    public function getCatalogArea($area, $options = array());
    
}
