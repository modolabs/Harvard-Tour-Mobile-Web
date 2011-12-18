<?php

interface CourseDataInterface {
    public function getCourses($options);
    public function getAvailableTerms();
    public function getCourseById($courseID);
}