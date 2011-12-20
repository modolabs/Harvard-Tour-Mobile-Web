<?php

interface CourseCatalogDataRetriever extends CourseDataInterface {

    public function getCatalogSections($options);
    
}