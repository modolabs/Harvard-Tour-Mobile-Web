<?php

class MoodlePageCourseContent extends PageCourseContent {
    public function getSubTitle() {

        $subTitle = '';
        if ($value = $this->getProperty('section')) {
            $subTitle = isset($value['name']) ? strip_tags($value['name']) : '';
        }

        return $subTitle;
    }
}