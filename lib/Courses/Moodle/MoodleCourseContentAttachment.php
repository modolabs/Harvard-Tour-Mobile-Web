<?php

class MoodleCourseContentAttachment extends CourseContentAttachment
{
    public function getContentFile(){
        $parent = $this->getParentContent();
        if ($retriever = $parent->getContentRetriever()) {
            $file = $retriever->getFileForUrl($this->getURL(), $this->getFileName());
            return $file;
        }
    }
}