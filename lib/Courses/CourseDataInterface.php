<?php

interface CourseDataInterface {
    /**
     * returns an array of Course objects
     * @param array $options
     *  'term'=> a term value or CoursesDataModel::CURRENT_TERM or CoursesDataModel::ALL_TERMS
     *  'section'=> a CourseCatalogSection - only used for catalogRetriever
     *  'kind'=> an array of retriever constants to limit by (i.e. catalog, registration, content) if empty then it will default to all available
     * @return Course object list
     */
    public function getCourses($options);
        
    public function getCourseById($courseID);

    public function getCourseByCommonId($courseID, $options);

}
