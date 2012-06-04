<?php

class GradeAssignment {
    protected $id;
    protected $title;
    protected $possiblePoints;
    protected $dateCreated;
    protected $dateModified;
    protected $dueDate;
    protected $gradeScores = array();

    public function getId()
    {
        return $this->id;
    }

    public function setId($newId)
    {
        $this->id = $newId;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($newTitle)
    {
        $this->title = $newTitle;
        return $this;
    }

    public function getPossiblePoints()
    {
        return $this->possiblePoints;
    }

    public function setPossiblePoints($newPossiblePoints)
    {
        $this->possiblePoints = $newPossiblePoints;
        return $this;
    }

    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = new DateTime('@'.$dateCreated);
        return $this;
    }

    public function getDateModified()
    {
        return $this->dateModified;
    }

    public function setDateModified($dateModified)
    {
        $this->dateModified = new DateTime('@'.$dateModified);
        return $this;
    }

    public function getDueDate()
    {
        return $this->dueDate;
    }

    public function setDueDate($newDueDate)
    {
        $this->dueDate = $newDueDate;
        return $this;
    }

    public function addGradeScore(GradeScore $gradeScore){
        $this->gradeScores[$gradeScore->getId()] = $gradeScore;
    }

    public function getLatestGradeScore(){
        // TODO: sort grade scores by date, return most recent
        return current($this->gradeScores);
    }
}
