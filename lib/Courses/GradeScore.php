<?php

class GradeScore {
    protected $id;
    protected $date;
    protected $score;
    protected $studentComment;
    protected $status = GradeScore::SCORE_STATUS_GRADED;

    const SCORE_STATUS_GRADED        = 'SCORE_STATUS_GRADED';
    const SCORE_STATUS_NEEDS_GRADING = 'SCORE_STATUS_NEEDS_GRADING';
    const SCORE_STATUS_EXEMPT        = 'SCORE_STATUS_EXEMPT';
    const SCORE_STATUS_NO_GRADE      = 'SCORE_STATUS_NO_GRADE';

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getDate() {
        return $this->date;
    }

    public function setDate($date) {
        $this->date = new DateTime('@'.$date);
        return $this;
    }

    public function getScore() {
        return $this->score;
    }

    public function setScore($score) {
        $this->score = $score;
        return $this;
    }

    public function getStudentComment()
    {
        return $this->studentComment;
    }

    public function setStudentComment($comment)
    {
        $this->studentComment = $comment;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
}
