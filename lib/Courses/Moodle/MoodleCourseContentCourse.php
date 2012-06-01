<?php

class MoodleCourseContentCourse extends CourseContentCourse {

	public function getStudents() {
		if ($retriever = $this->getRetriever()) {
			$users = $retriever->getUsersByCourseId($this->getID());
			$Students = array();
		    foreach ($users as $user){
		        if ($this->isStudent($user)) {
		            $Students[] = $user;
		        }
		    }
			return $Students;
		}
	}

	public function getInstructors(){
		if ($retriever = $this->getRetriever()) {
			$users = $retriever->getUsersByCourseId($this->getID());
			$instructorLish = array();
		    foreach ($users as $user){
	            if ($this->isInstructor($user)) {
	                $instructorList[] = $user;
	            }
            }
			return $instructorList;
		}
	}

    public function getLastUpdate() {
        if ($courseContents = $this->getUpdates()) {
            $courseContents = $this->sortCourseContent($courseContents, 'publishedDate');
            return current($courseContents);
        }
        return array();
    }

    protected function sortCourseContent($courseContents, $sort) {
        if (empty($courseContents)) {
            return array();
        }

		$this->sortType = $sort;

		uasort($courseContents, array($this, "sortByField"));

        return $courseContents;
    }

	private function sortByField($contentA, $contentB) {
        if ($this->sortType == 'publishedDate') {
            $contentA_time = $contentA->getPublishedDate() ? $contentA->getPublishedDate()->format('U') : 0;
            $contentB_time = $contentB->getPublishedDate() ? $contentB->getPublishedDate()->format('U') : 0;
            return $contentA_time < $contentB_time;
       } else {
            $func = 'get' . $this->sortType;
            return strcasecmp($contentA->$func(), $contentB->$func());
        }
	}

    public function getLastAnnouncement($options){
        if($announcements = $this->getAnnouncements($options)){
            $announcements = $this->sortCourseContent($announcements, 'publishedDate');
            return current($announcements);
        }
        return array();
    }

    public function getAnnouncements($options=array()){
        if($retriever = $this->getRetriever()){
            return $retriever->getAnnouncements($this->getID(), $options);
        }
    }

    public function getUpdates($options=array()) {
        if ($retriever = $this->getRetriever()) {
            return $retriever->getUpdates($this->getID(), $options);
        }
    }

    public function getTasks($options=array()) {
        return array();
    }

    public function getResources($options=array()) {
        if ($retriever = $this->getRetriever()) {
            return $retriever->getCourseContent($this->getID(), $options);
        }
    }

    public function getGrades($options=array()) {
    }

    public function getTaskById($id, $options=array()) {
        $tasks = $this->getTasks($options);
        foreach ($tasks as $item) {
            if ($item->getID()==$id) {
                return $item;
            }
        }

        return null;
    }

    public function getContentById($id, $options=array()) {
        if ($retriever = $this->getRetriever()) {
            $content = $retriever->getCourseContent($this->getID(), $options);
            foreach ($content as $item) {
                if ($item->getID()==$id) {
                    return $item;
                }
            }
        }

        return null;
    }

    public function getContentByParentId($options = array()){
        throw new KurogoConfigurationException("Browse Tab not supported in Moodle. Please remove the Broswe tab from coursetabs.ini");
    }

    public function getFileForContent($id, $options=array()) {
        if ($content = $this->getContentById($id, $options)) {
            $url = $content->getFileURL();
            if ($retriever = $this->getRetriever()) {
                return $retriever->getFileForUrl($url, $id . '_' . $content->getFileName());
            }
        }

        return null;
    }

    public function isInstructor(CourseUser $user) {
        if ($roles = $user->getRoles()) {
            if($roles[0]['roleid'] == 3){ // if rileId eq 3 is Teacher in moodle
                return true;
            }
        }

        return false;
    }

    public function isStudent(CourseUser $user) {
        if ($roles = $user->getRoles()) {
            if($roles[0]['roleid'] == 5){ // if rileId eq 5 is Student in moodle
                return true;
            }
        }

        return false;
    }
}