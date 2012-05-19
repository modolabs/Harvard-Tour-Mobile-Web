<?php

class MoodleDownloadCourseContent extends DownloadCourseContent {
    public function getSubTitle() {

        $subTitle = '';
        if ($value = $this->getProperty('section')) {
            $subTitle = isset($value['name']) ? strip_tags($value['name']) : '';
        }

        return $subTitle;
    }

    public function getContentFile() {
        $url = $this->getFileURL();
        if ($retriever = $this->getContentRetriever()) {
            $file = $retriever->getFileForUrl($url, $this->getID() . '_' . $this->getFileName());
            return $file;
        }
    }

}