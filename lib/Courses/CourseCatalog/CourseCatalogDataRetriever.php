<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

interface CourseCatalogDataRetriever extends CourseDataInterface {

    public function getCatalogAreas($options = array());
    public function getCatalogArea($area, $options = array());
    public function searchCourses($searchTerms, $options = array());
    
}
