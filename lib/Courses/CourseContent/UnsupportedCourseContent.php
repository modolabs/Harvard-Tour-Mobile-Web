<?php

class UnsupportedCourseContent extends CourseContent {
    protected $contentType = 'unsupported';

    public function getContentClass() {
        return 'file';
    }

}
