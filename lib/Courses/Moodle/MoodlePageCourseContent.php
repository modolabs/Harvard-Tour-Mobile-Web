<?php

class MoodlePageCourseContent extends PageCourseContent {   
    public function getContent() {
        if($retriever = $this->getContentRetriever()){
            $content = $retriever->getFileForUrl($this->getURL(), '');
            return $content;
        }
    }
}