<?php

includePackage('Courses', 'CourseContent');
interface CourseContentDataRetriever extends CourseDataInterface {

    public function getGrades($options);
    
    //returns the most recent "qualified" content. This will not include all content types
    public function getLastUpdate($courseID);
    
    //returns an array of CourseContent
    public function getCourseContent($options);
    
    public function searchCourseContent($searchTerms, $options);
    
}
