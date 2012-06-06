<?php

class CoursesDemoCourseContentDataRetriever extends URLDataRetriever implements CourseContentDataRetriever {
    protected $DEFAULT_PARSER_CLASS = 'CoursesDemoCourseContentDataParser';
    protected $userID;
    protected $contentBaseURL;

    public function getCourses($options) {
        if (!$this->userID) {
            return array();
        }
        
        if (isset($options['term'])) {
            $this->addParameter('term', strval($options['term']));
        }

        $this->setOption('action', 'getCourses');
        $courses = $this->getData();
        return $courses;
    }
    
    public function getAvailableTerms() {
    }
    
    public function getCourseById($courseID) {
    }

    protected function setContentMode($type, $options = array()) {
    	$baseUrl = sprintf($this->contentBaseURL, $type);
    	$this->setBaseURL($baseUrl);
    	if (isset($options['term'])) {
    		$this->addParameter('term', $options['term']);
    	}
    	
    	if (isset($options['classNumber'])) {
    		$this->addParameter('classNumber', $options['classNumber']);
    	}
    }
    
    public function getResources(CoursesDemoCourseContentCourse $course, $options=array()) {
        $resources = array();
        if ($course) {
            $this->clearInternalCache();
            $this->setContentMode('resources', $options);
            $this->setOption('action', 'getResources');
            $this->setOption('contentCourse', $course);
            if ($contents = $this->getData()) {
                $group = isset($options['group']) ? $options['group'] : null;
                switch ($group) {
            		case 'topic':
            			foreach ($contents as $courseContentObj) {
            				$topic = $courseContentObj->getAttribute('topic');
            				$resources[$topic][] = $courseContentObj;
            			}
            			break;
            		case 'type':
	            		foreach ($contents as $item) {
	            		    $resources[$item->getContentType()][] = $item;
		            	}
            			break;
            		case 'date':
            			$resources[] = $contents;
            			break;
            		default:
            			return $contents;
            			break;
            	}
            }
        }
        
        return $resources;
    }
    
    public function getTasks(CoursesDemoCourseContentCourse $course, $options=array()) {
        $tasks = array();
        if ($course) {
            $this->clearInternalCache();
    	    $this->setContentMode('tasks', $options);
    	    $this->setOption('action', 'getTasks');
            $this->setOption('contentCourse', $course);
            
            if ($contents = $this->getData()) {
                $group = isset($options['group']) ? $options['group'] : null;
                switch ($group) {
                    case 'date':
                        $tasks[] = $contents;
                        break;
    
                    case 'course':
                        foreach ($contents as $item) {
                            $tasks[$course->getTitle()][] = $item;
                        }
                        break;
                    case 'priority':
                        foreach ($contents as $item) {
                            $tasks[$item->getAttribute('priority')][] = $item;
                        }
                    	break;
                    case 'topic':
                        foreach ($contents as $item) {
                            $tasks[$item->getAttribute('topic')][] = $item;
                        }
                        break;
                    default:
                        $tasks = array_reverse($contents);
                        break;
            	}
            }
        }
        
        return $tasks;
    }
    
    /**
     * @brief getAnnouncements 
     *
     * @param array $options
     *
     * @return array
     */
    public function getAnnouncements(CoursesDemoCourseContentCourse $course, $options) {
        if ($course) {
            $this->clearInternalCache();
            $this->setContentMode('announcements', $options);
    	    $this->setOption('action', 'getAnnouncements');
            $this->setOption('contentCourse', $course);
            
            return $this->getData();
        }
        return array();
    }

    /**
     * @brief getUpdates 
     *
     * @param array $options
     *
     * @return array
     */
    public function getUpdates(CoursesDemoCourseContentCourse $course, $options=array()) {
    	$this->clearInternalCache();
    	$this->setContentMode('updates', $options);
    	$this->setOption('action', 'getUpdates');
    	$this->setOption('contentCourse', $course);
    	
    	return $this->getData();
    }

    /**
     * @brief getCourseContent 
     *
     * @param int $courseRetrieverID
     * @param array $options
     *
     * @return array
     */
    public function getCourseContent(CoursesDemoCourseContentCourse $course, $options) {
    	$this->clearInternalCache();
    	$this->setContentMode('resources', $options);
    	$this->setOption('action', 'getResources');
    	$this->setOption('contentCourse', $course);
    	
    	return $this->getData();
    }

    /**
     * @brief getUsersByCourseId 
     *
     * TODO: need to be implemented
     * get enrollments
     *
     * @return 
     */
    public function getUsersByCourseId($courseId) {
        return array();
    }

    public function getCourseByCommonId($courseID, $options) {
        $courses = $this->getCourses($options);
        foreach($courses as $course) {
            if($course->getCommonId() == $courseID) {
                return $course;
            }
        }
        return false;
    }

    public function getFileForUrl($url, $fileName) {
    	$this->clearInternalCache();
        $this->setOption('action','downloadFile');
        $this->setOption('fileName', $fileName);

        $this->setBaseURL($url);
        $this->setSaveToFile(true);
        
        $response = $this->getResponse();
        return $response->getResponse();
    }

    protected function saveToFile() {
        if ($this->getOption('action')=='downloadFile') {
            return $this->getOption('fileName');
        }
        return false;
    }
    
    protected function init($args) {
        parent::init($args);
        if ($user = $this->getCurrentUser()) {
            if ($user instanceOf CoursesDemoUser) {
                $this->setOption("userID", $user->getID());
                $this->userID = $user->getID();
                if(isset($args['BASE_URL'])) {
                    $baseUrl = sprintf($args['BASE_URL'], $this->userID);
                    $this->setBaseURL($baseUrl);
                }
            }
        }
        if (isset($args['CONTENT_BASE_URL'])) {
        	$this->contentBaseURL = $args['CONTENT_BASE_URL'];
        }
    }
}
