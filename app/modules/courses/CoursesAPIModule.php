<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Courses');
includePackage('DateTime');

class CoursesAPIModule extends APIModule {

    protected $id = 'courses';
    protected $controller;
    protected $selectedTerm;
    protected $defaultModel = 'CoursesDataModel';
    protected $Term;
    protected $showCourseNumber;
    
    protected function assignTerm(){
        $feedTerms = $this->controller->getAvailableTerms();

        $term = $this->getArg('term', CoursesDataModel::CURRENT_TERM);
        if (!$Term = $this->controller->getTerm($term)) {
            $Term = $this->controller->getCurrentTerm();
        }

        $this->controller->setCurrentTerm($Term);
        return $Term;
    }

    abstract protected function initializeForCommand();
}
