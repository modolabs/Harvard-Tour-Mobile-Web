<?php

class MoodleCourseContentDataRetriever extends URLDataRetriever implements CourseContentDataRetriever {

    protected $DEFAULT_PARSER_CLASS='MoodleCourseContentDataParser';
    
    protected $server;
    protected $secure = true;
    protected $token;
    protected $userID;
    
    protected $sortType;
    
    protected function setUserID($userID) {
        $this->userID = $userID;
    }

    public function setToken($token) {
        $this->token = $token;
    }
    
    public function clearInternalCache() {
        $this->setMethod('GET');
        parent::clearInternalCache();
    }
    
    protected function cacheKey() {
        return null;
    }
    
    protected function initRequest() {
        $baseUrl = sprintf("http%s://%s/webservice/rest/server.php",
                $this->secure ? 's' : '',
                $this->server);
                
        $this->setBaseURL($baseUrl);
        
        $this->addParameter('wstoken', $this->token);
        $this->addParameter('wsfunction', '');
        $this->addParameter('moodlewsrestformat', 'json');
        
        $postData = array();
        $action = $this->getOption('action');
        switch ($action) {
            case 'getCourses':
                $this->addParameter('wsfunction', 'core_enrol_get_users_courses');
                $postData['userid'] = $this->getOption('userID');
                break;
            case 'getCourseContent':
                $this->addParameter('wsfunction', 'core_course_get_contents');
                $postData['courseid'] = $this->getOption('courseID');
                break;
            case 'getCourse':
                $this->addParameter('wsfunction', 'core_course_get_courses');
                $postData['options']['ids'][] = $this->getOption('courseID');
                break;
            case 'getCourseResource':
                $this->addParameter('wsfunction', 'core_course_get_contents');
                $postData['courseid'] = $this->getOption('courseID');
                break;     	
            default:
                throw new KurogoDataException("not defined the action:" . $action);
        }
        
        if ($postData) {
            $this->setMethod('POST');
            $this->addHeader('Content-type', 'application/x-www-form-urlencoded');
            $this->setData(http_build_query($postData));
        }
    }
    
    public function getCourses($options) {
        $this->clearInternalCache();
        $this->setOption('action', 'getCourses');
        if (isset($options['userID'])) {
            $this->setOption('userID', $options['userID']);
        } else {
            $this->setOption('userID', $this->userID);
        }

        $courses = array();
        if ($items = $this->getData()) {
            foreach ($items as $item) {
                $item->setRetriever('content', $this);
                $courses[] = $item;
            }
        }

        return $courses;
    }
    
    public function getAvailableTerms() {
        
    }
    public function getCourseResourceById($courseNumber){
    	$options = array();
        $courseRetrieverID = '';
        if ($courses = $this->getCourses($options)) {
            foreach ($courses as $course) {
                if ($course->getCourseNumber() == $courseNumber) {
                    $courseRetrieverID = $course->getRetrieverId('content');
                    break;
                }
            }
        }
        if ($courseRetrieverID) {
            $this->clearInternalCache();
            $this->setOption('action', 'getCourseResource');
            $this->setOption('courseID', $courseRetrieverID);
            if ($course = $this->getData()) {
                return current($course);
            }
        }
    }
    public function getCourseById($courseNumber) {
        $options = array();
        
        $courseRetrieverID = '';
        if ($courses = $this->getCourses($options)) {
            foreach ($courses as $course) {
                if ($course->getCourseNumber() == $courseNumber) {
                    $courseRetrieverID = $course->getRetrieverId('content');
                    break;
                }
            }
        }
        
        if ($courseRetrieverID) {
            $this->clearInternalCache();
            $this->setOption('action', 'getCourse');
            $this->setOption('courseID', $courseRetrieverID);
            if ($course = $this->getData()) {
                return current($course);
            }
        }
        return array();
    }
    
    public function getGrades($options) {
        
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
	
    protected function sortCourseContent($courseContents, $sort) {
        if (empty($courseContents)) {
            return array();
        }
        
		$this->sortType = $sort;
		
		uasort($courseContents, array($this, "sortByField"));
		
        return $courseContents;
    }
    
    public function getLastUpdate($courseID) {
        if ($courseContents = $this->getCourseContent($courseID)) {
            $courseContents = $this->sortCourseContent($courseContents, 'publishedDate');
            return current($courseContents);
        }
        return array();
    }
    
    public function getCourseContent($courseID) {
        $this->clearInternalCache();
        $this->setOption('action', 'getCourseContent');
        $this->setOption('courseID', $courseID);
        
        $contents = array();
        if ($items = $this->getData()) {
            foreach ($items as $item) {
                $item->setCourseID($courseID);
                $item->setContentRetriever($this);
                $contents[] = $item;
            }
        }
        return $contents;
    }
    
    public function searchCourseContent($searchTerms, $options) {
        
    }
    
    protected function init($args) {
    
        parent::init($args);
        
        if (!isset($args['SERVER'])) {
            throw new KurogoConfigurationException("Moodle SERVER must be set");
        }
        $this->server = $args['SERVER'];

        if (isset($args['SECURE'])) {
            $this->secure = (bool) $args['SECURE'];
        }
        
        if (isset($args['TOKEN'])) {
            $this->token = $args['TOKEN'];
        }
        
        if (isset($args['USERID'])) {
            $this->userID = $args['USERID'];
        }
    }
}

class MoodleCourseContentDataParser extends dataParser {
    
    public function clearInternalCache() {
        parent::clearInternalCache();
    }
    
    public function parseData($data) {
        $action = $this->getOption('action');
        
        $items = array();
        if ($data = json_decode($data, true)) {
            if (isset($data['exception'])) {
                throw new KurogoDataException($data['message']);
            }
            
            switch ($action) {
                case 'getCourses':
                case 'getCourse':
                    foreach ($data as $value) {
                        if ($course = $this->parseCourse($value)) {
                            $items[] = $course;
                        }
                    }
                    break;
                case 'getCourseResource':
                case 'getCourseContent':
                    $items = $this->parseCourseContent($data);
                    break;
                default:
                    throw new KurogoDataException("not defined the action:" . $action);
                    break;
            }
        }
        
        $this->setTotalItems(count($items));
        return $items;
    }

    protected function parseCourse($data) {
    
        if (isset($value['visible']) && !$value['visible']) {
            continue;
        }

        $course = new MoodleCourseContentCourse();
        $course->setTitle($data['shortname']);
        $course->setFullTitle($data['fullname']);
        $course->addRetrieverId('content', $data['id']);
        $course->setCourseNumber($data['idnumber']);
        if (isset($data['summary'])) {
            $course->setDescription($data['summary']);
        }
        
        return $course;
    }
    
    protected function parseCourseContent($data) {
        $contentTypes = array();
        
        foreach ($data as $value) {
            $properties = array();
            
            if (isset($value['modules']) && $value['modules']) {
                $moduleValue = $value['modules'];
                unset($value['modules']);
                $properties['section'] = $value;
                
                foreach ($moduleValue as $module) {
                    $contentType = null;
                    if ($module['visible'] && isset($module['modname']) && $module['modname']) {
                        switch ($module['modname']) {
                            case 'resource':
                                $contentType = new DownLoadCourseContent();
                                break;
                                
                            case 'url':
                                $contentType = new LinkCourseContent();
                                break;
                                
                            case 'page':
                                $contentType = new PageCourseContent();
                                break;
                                
                            default:
                                break;
                        }
                        if ($contentType) {
                            $contentType->setTitle($module['name']);
                            if (isset($module['contents'][0]['timecreated']) && $module['contents'][0]['timecreated']) {
                                $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timecreated']));
                                $contentType->setPublishedDate($datetime);
                            } 
                            if (isset($module['contents'][0]['timemodified']) && $module['contents'][0]['timemodified']) {
                                $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timemodified']));
                                $contentType->setPublishedDate($datetime);
                            }
                            if($contentType instanceof DownLoadCourseContent){
	                            if(isset($module['contents'][0]['type']) && $module['contents'][0]['type']){
	                            	$contentType->setType($module['contents'][0]['type']);
	                            }
	                            if(isset($module['contents'][0]['url']) && $module['contents'][0]['url']){
	                            	$contentType->setUrl($module['contents'][0]['url']);
	                            }
	                            
	                            if(isset($module['contents'][0]['filename']) && $module['contents'][0]['filename']){
	                            	$contentType->setFilename($module['contents'][0]['filename']);
	                            }
	                            if(isset($module['contents'][0]['filepath']) && $module['contents'][0]['filepath']){
	                            	$contentType->setFilepath($module['contents'][0]['filepath']);
	                            }
	                            if(isset($module['contents'][0]['filesize']) && $module['contents'][0]['filesize']){
	                            	$contentType->setFilesize($module['contents'][0]['filesize']);
	                            }
	                            if(isset($module['contents'][0]['fileurl']) && $module['contents'][0]['fileurl']){
	                            	$contentType->setFileurl($module['contents'][0]['fileurl']);
	                            }
	                            if(isset($module['contents'][0]['timecreated']) && $module['contents'][0]['timecreated']){
	                            	$contentType->setTimecreated($module['contents'][0]['timecreated']);
	                            }
	                            if(isset($module['contents'][0]['timemodified']) && $module['contents'][0]['timemodified']){
	                            	$contentType->setTimemodified($module['contents'][0]['timemodified']);
	                            }
	                            if(isset($module['contents'][0]['sortorder']) && $module['contents'][0]['sortorder']){
	                            	$contentType->setSortorder($module['contents'][0]['sortorder']);
	                            }
	                            if(isset($module['contents'][0]['userid']) && $module['contents'][0]['userid']){
	                            	$contentType->setUserid($module['contents'][0]['userid']);
	                            }
	                            if(isset($module['contents'][0]['author']) && $module['contents'][0]['author']){
	                            	$contentType->setAuthor($module['contents'][0]['author']);
	                            }
	                            if(isset($module['contents'][0]['license']) && $module['contents'][0]['license']){
	                            	$contentType->setLicense($module['contents'][0]['license']);
	                            }
                            }
                            $contentTypes[] = $contentType;
                        }
                    }
                }
            }
        }
        return $contentTypes;
    }
}

class MoodleCourseContentCourse extends CourseContentCourse {
    protected $fullTitle;
    
    public function setFullTitle($title) {
        $this->fullTitle = $title;
    }
    
    public function getFullTitle() {
        return $this->fullTitle;
    }
}

class MoodleDownLoadCourseContent extends DownLoadCourseContent {
   				
}
class MoodleLinkCourseContent extends LinkCourseContent {
    
}

class MoodlePageCourseContent extends PageCourseContent {
    
}