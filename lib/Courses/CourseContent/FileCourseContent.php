<?php

class FileCourseContent extends CourseContent
{
    protected $contentType = 'file';

    public function getContentClass() {
        if ($attachment = current($this->attachments)) {
            return $attachment->getContentClass();
        }
        
        return parent::getContentClass();
    }
}
