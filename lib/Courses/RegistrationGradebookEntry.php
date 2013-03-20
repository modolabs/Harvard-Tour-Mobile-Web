<?php

class RegistrationGradebookEntry {

    protected $title;
    protected $subtitle;
    protected $grades = array();

    public function getTitle()
    {
        return $this->title;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getSubtitle()
    {
        return $this->subtitle;
    }

    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    public function getGrades()
    {
        return $this->grades;
    }

    public function setGrades($grades)
    {
        $this->grades = $grades;
        return $this;
    }

    public function addGrade(RegistrationGrade $grade){
        $this->grades[] = $grade;
    }
}
