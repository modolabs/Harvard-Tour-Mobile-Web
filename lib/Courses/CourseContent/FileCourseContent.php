<?php

class FileCourseContent extends CourseContent
{
    protected $contentType = 'file';

    public function getContentClass() {
        $attachments = $this->getAttachments();
        if(count($attachments) > 1){
            return 'multi';
        }elseif(count($attachments) == 1){
            $attachment = current($attachments);
            return $attachment->getContentClass();
        }

        return parent::getContentClass();
    }
}
