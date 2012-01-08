<?php

class CourseTermCurrent extends CourseTerm
{
    public function __construct() {
        $this->setTitle('Current Term');
        $this->setID(CoursesDataModel::CURRENT_TERM);
    }
}
