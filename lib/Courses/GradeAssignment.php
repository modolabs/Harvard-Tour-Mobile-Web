<?php

class GradeAssignment {
    protected $id;
    protected $title;
    protected $description;
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

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
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

    // Determine 'correct/final' grade to return.
    public function getGrade(){
        //Use most recent attempt for now
        $grades = $this->getGradeScores();
        uasort($grades, array($this, 'sortByDate'));
        if(!empty($grades)){
            return current($grades);
        }
        return null;
    }

    public function addGradeScore(GradeScore $gradeScore){
        $this->gradeScores[$gradeScore->getId()] = $gradeScore;
    }

    public function getGradeScores(){
        return $this->gradeScores;
    }

    private function sortByDate($a, $b){
        $aDate = $a->getDate() ? $a->getDate()->format('U') : 0;
        $bDate = $b->getDate() ? $b->getDate()->format('U') : 0;
        if($aDate == $bDate()){
            return 0;
        }

        return ($aDate() > $bDate()) ? -1 : 1;
    }
}
