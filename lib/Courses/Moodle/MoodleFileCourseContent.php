<?php

class MoodleFileCourseContent extends FileCourseContent
{

    public function getAttachment(){
        if($attachments = $this->getAttachments()){
            return current($attachments);
        }
    }
}
