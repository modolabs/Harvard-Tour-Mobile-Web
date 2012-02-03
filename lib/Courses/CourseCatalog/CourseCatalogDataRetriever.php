<?php

interface CourseCatalogDataRetriever extends CourseDataInterface {

    public function getCatalogAreas($area=null);
    public function getCatalogArea($area);
    
}