<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CalendarCourseContent extends CourseContent
{
    protected $contentType = 'calendar';
    protected $date;
    
    public function setDate(DateTime $date) {
        $this->date = $date;
    }

    public function getDate() {
        return $this->date;
    }
    
    public function getDateTime() {
        if ($date = $this->getDate()) {
            return $data->format('U');
        }
        
        return 0;
    }

    public function sortBy(){
        $sortBy = parent::sortBy();
        if($this->getDate()){
            $sortBy = $this->getDate()->format('U');
        }
        return $sortBy;
    }
}
