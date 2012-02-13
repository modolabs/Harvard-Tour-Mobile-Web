<?php

abstract class CourseContentCourse extends Course {
    abstract public function getLastUpdate();
    abstract public function getUpdates($options);
    abstract public function getTasks($options);
    abstract public function getResources($options);
    abstract public function getGrades($options);
    abstract public function getInstructors();
    abstract public function getStudents();
    abstract public function getContentById($id, $options);
}