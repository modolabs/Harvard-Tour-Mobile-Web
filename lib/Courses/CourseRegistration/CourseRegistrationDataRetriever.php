<?php

interface CourseRegistrationDataRetriever extends CourseDataInterface {

    public function getGrades($term);
}