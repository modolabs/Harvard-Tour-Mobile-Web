<?php

interface CourseRegistrationInterface extends CourseDataInterface {
    public function getGrades($term);
}