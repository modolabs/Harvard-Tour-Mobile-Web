<?php

class MoodleDownloadCourseContent extends DownloadCourseContent {

    public function getContentFile() {
        $url = $this->getFileURL();
        if ($retriever = $this->getContentRetriever()) {
            $file = $retriever->getFileForUrl($url, $this->getID() . '_' . $this->getFileName());
            return $file;
        }
    }

}