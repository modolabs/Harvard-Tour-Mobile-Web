<?php

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
    
    protected function formatArea(CourseArea $area) {
        $item = array(
            'area'  => $area->getCode(),
            'title' => $area->getTitle(),
            'parent'=> $area->getParent(),
        );
        
        return $item;
    }
    
    protected function formatCourse(CourseInterface $course) {
        $item = array(
            'courseID' => $course->getID(),
            'title'    => $course->getTitle(),
            'courseNumber' => $course->getField('courseNumber')
        );
        
        return $item;
    }
    
    protected function initializeForCommand() {
        if(!$this->feeds = $this->loadFeedData()){
            throw new KurogoConfigurationException("Feeds configuration cannot be empty.");
        }
        $this->controller = CoursesDataModel::factory($this->defaultModel, $this->feeds);
        //load showCourseNumber setting
        $this->showCourseNumber = $this->getOptionalModuleVar('SHOW_COURSENUMBER_IN_LIST', 1);
        $this->term = $this->assignTerm();

        switch($this->command) {
            case 'areas':
                $options = array('term' => strval($this->Term));
                if ($area = $this->getArg('area', '')) {
                    $options['parent'] = $area;
                }
                
                $areas = array();
                if ($items = $this->controller->getCatalogAreas($options)) {
                    foreach ($items as $area) {
                        $areas[] = $this->formatArea($area);
                    }
                }

                $response = array(
                    'total'   => count($areas),
                    'results' => $areas,
                );
                
                $this->setResponse($response);
                $this->setResponseVersion(1);
                
                break;

            case 'courses':
                $area = $this->getArg('area', '');
                $options = array(
                    'term' => strval($this->term),
                    'area' => $area,
                    'type' => 'catalog',
                );
                
                $courses = array();
                if ($items = $this->controller->getCourses($options)) {
                    foreach ($items as $item) {
                        $courses[] = $this->formatCourse($item);
                    }
                }
                
                $response = array(
                    'total'   => count($courses),
                    'results' => $courses,
                );
                
                $this->setResponse($response);
                $this->setResponseVersion(1);
                
                break;
                
            default:
                 $this->invalidCommand();
                 break;
        }
    }
}