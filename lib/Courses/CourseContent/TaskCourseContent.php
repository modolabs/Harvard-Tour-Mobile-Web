<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class TaskCourseContent extends CalendarCourseContent
{
    protected $contentType = 'task';
    protected $dueDate;
    protected $finished;
    protected $links = array();
    
    public function setDueDate(DateTime $date) {
        $this->dueDate = $date;
    }

    public function getDueDate() {
        return $this->dueDate;
    }

    public function setFinished($fininshed) {
        $this->finished = $finished;
    }

    public function getFinished() {
        return $this->finished;
    }
    
    public function getLinks() {
        return $this->links;
    }

    public function addLink($title, $url) {
        $this->links[] = array('title'=>$title, 'url'=>$url);
    }

    public function sortBy(){
        $sortBy = parent::sortBy();
        if($this->getDueDate()){
            $sortBy = $this->getDueDate()->format('U');
        }
        return $sortBy;
    }
}
