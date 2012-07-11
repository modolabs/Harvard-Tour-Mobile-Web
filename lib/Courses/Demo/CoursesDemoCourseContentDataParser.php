<?php

class CoursesDemoCourseContentDataParser extends JSONDataParser {

    public function parseData($data) {
    	$action = $this->getOption('action');
        $data = parent::parseData($data);
        if($data['error']) {
            return array();
        }else {
            $items = array();
            
            switch ($action) {
                case 'getCourses':
                case 'getCourse':
		            if (is_array($data['response']) && $data['count'] > 0) {
		                $this->setTotalItems($data['count']);
		                foreach($data['response'] as $item) {
		                    $items[] = $this->parseCourse($item);
		                }
		            }
                    
                    break;
                case 'getUpdates':
                    if (is_array($data['response']) && $data['count'] > 0) {
		                $this->setTotalItems($data['count']);
		                foreach($data['response'] as $item) {
		                    $items[] = $this->parseUpdatas($item);
		                }
		            }
                	break;
                case 'getTasks':
                    if (is_array($data['response']) && $data['count'] > 0) {
		                $this->setTotalItems($data['count']);
		                foreach($data['response'] as $item) {
		                    $items[] = $this->parseTask($item);
		                }
		            }
                	break;
                case 'getResources':
                    if (is_array($data['response']) && $data['count'] > 0) {
		                $this->setTotalItems($data['count']);
		                foreach($data['response'] as $item) {
		                    $items[] = $this->parseCourseContent($item);
		                }
		            }
                    break;
                case 'getAnnouncements':
                    if (is_array($data['response']) && $data['count'] > 0) {
		                $this->setTotalItems($data['count']);
		                foreach($data['response'] as $item) {
		                    $items[] = $this->parseAnnouncement($item);
		                }
		            }
                    break;
                default:
                    throw new KurogoDataException("not defined the action:" . $action);
                    break;
            }
            return $items;
        }
    }

    public function parseCourse($item) {
        $course = new CoursesDemoCourseContentCourse();
        $course->setID($item['course_id']);
        $course->setCourseNumber($item['course_number']);
        if (isset($item['term_code'])) {
        	$term = new CourseTerm();
        	$term->setID($item['term_code']);
        	$course->setTerm($term);
        }
        if (isset($item['instructors'])) {
            foreach ($item['instructors'] as $value) {
        		$instructor = new CourseUser();
        		//$instructor->setID($value['user_id']);
        		$instructor->setFirstName($value['first_name']);
        		$instructor->setLastName($value['last_name']);
        		//$instructor->setEmail($value['email']);
        		$course->addInstructor($instructor);
        	}
        }
        if (isset($item['schedules'])) {
            foreach ($item['schedules'] as $value) {
                $schedule = new CourseScheduleObject();
                $schedule->setDays($value['days']);
                $schedule->setStartTime($value['start_time']);
                $schedule->setEndTime($value['end_time']);
                $schedule->setStartDate($value['start_date']);
                $schedule->setEndDate($value['end_date']);
                $schedule->setBuilding($value['building']);
                $schedule->setRoom($value['locations']);
                $course->addScheduleItem($schedule);
            }
        }
        
        $course->setTitle($item['course_title']);
        $course->setDescription($item['course_description']);
        $course->setAttributes($item);
        $course->setRetriever($this->getResponseRetriever());

        return $course;
    }
    
    protected function parseAnnouncement($item) {
        $ann = new AnnouncementCourseContent();
        $ann->setID($item['content_id']);
        $ann->setTitle($item['title']);
        $ann->setAuthor($item['author']);
        $ann->setDescription($item['description']);
        $createdDate = new DateTime(date('Y-n-j H:i:s', $item['timecreated']), Kurogo::siteTimezone());
        $ann->setPublishedDate($createdDate);
        $ann->setAttribute('topic', $item['topic']);
        
        return $ann;
    }
    
    protected function parseTask($data) {
    	if(empty($data)) {
    		return array();
    	}
    	
    	if ($course = $this->getOption('contentCourse')) {
    		if ($data['task_type']) {
	    		$task = new CalendarCourseContent();
	    		$startDate = new DateTime(date('Y-n-j H:i:s', $data['startDateTime']), Kurogo::siteTimezone());
	            $endDate = new DateTime(date('Y-n-j H:i:s', $data['endDateTime']), Kurogo::siteTimezone());
	            $task->setDate($startDate);
	            $task->setEndDate($endDate);
	    	} else {
	    		$task = new TaskCourseContent();
	    		$taskDate = new DateTime(date('Y-n-j H:i:s', $data['timecreated']), Kurogo::siteTimezone());
	    		$dueDate = new DateTime(date('Y-n-j H:i:s', $data['dueDateTime']), Kurogo::siteTimezone());
	    		$task->setDate($taskDate);
	    		$task->setDueDate($dueDate);
	    	}
	    	$task->setContentCourse($course);
	    	$task->setID($data['content_id']);
	    	$task->setTitle($data['title']);
	    	$task->setAuthor($data['author']);
	    	$task->setAuthorID($data['user_id']);
	    	$task->setDescription($data['description']);
	    	$task->setAttribute('priority', $data['priority']);

            foreach ($data['links'] as $link) {
                $title = $link['title'];
                $url = $link['url'];
                $bits = parse_url($url);
                if (!isset($bits['scheme'])) {
                    $url = rtrim($this->initArgs['SERVER_BASE_URL'], '/') . $url;
                }
                $task->addLink($title, $url);
            }	    	
	    	
	    	return $task;
    	}
    	
    	
    	return null;
    }
    
    protected function parseCourseContent($item) {
    	
    	switch ($item['typeName']) {
    		case 'resource':
    			$contentType = new FileCourseContent();
    			break;
    		case 'url':
    			$contentType = new LinkCourseContent();
    			break;
    		case 'page':
    			$contentType = new PageCourseContent();
    			break;
    	}
    	
    	$contentType->setContentRetriever($this->getResponseRetriever());
    	$contentType->setID($item['content_id']);
    	$contentType->setAttribute('topic', $item['topic']);
    	$contentType->setAttribute('priority', $item['priority']);
    	$contentType->setTitle($item['title']);
    	$contentType->setDescription($item['description']);
        if (isset($item['timecreated']) && $item['timecreated']) {
			$datetime = new DateTime(date('Y-n-j H:i:s', $item['timecreated']));
			$contentType->setPublishedDate($datetime);
		}
		if (isset($item['timemodified']) && $item['timemodified']) {
			$datetime = new DateTime(date('Y-n-j H:i:s', $item['timemodified']));
			$contentType->setPublishedDate($datetime);
		}
		
		$contentType->setAuthor($item['author']);
    	$contentType->setAuthorID($item['user_id']);
		if ($item['typeName'] == 'resource') {
            $attachment = new CourseContentAttachment();
            $attachment->setFilename($item['filename']);
            $attachment->setFilesize($item['filesize']);
            $attachment->setURL(rtrim($this->initArgs['SERVER_BASE_URL'], '/') . $item['fileurl']);
            $attachment->setDownloadMode(CourseContent::MODE_URL);

            $attachment->setParentContent($contentType);

            $contentType->addAttachment($attachment);
		}
    	
    	if ($item['typeName'] == 'page') {
    	    $contentType->setFilename($item['filename']);
    	    $contentType->setFileurl(rtrim($this->initArgs['SERVER_BASE_URL'], '/') . $item['fileurl']);
    	}
    	
        if($item['typeName'] == 'url') {
        	$contentType->setURL($item['fileurl']);
        }
        
    	return $contentType;
    }
    
    protected function parseUpdatas($item) {
        $contentTypes = array();
        if (isset($item['typeName'])) {
        	switch ($item['typeName']) {
        		case 'announcement':
        			return $this->parseAnnouncement($item);
        			break;
        		case 'task':
        			return $this->parseTask($item);
        			break;
        		default:
        			return $this->parseCourseContent($item);
        			break;
        	}
        }
    }
}
