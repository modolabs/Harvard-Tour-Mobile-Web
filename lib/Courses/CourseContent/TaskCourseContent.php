<?php

class TaskCourseContent extends CalendarCourseContent
{
    protected $dueDate;
    protected $finished;
    
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
}
