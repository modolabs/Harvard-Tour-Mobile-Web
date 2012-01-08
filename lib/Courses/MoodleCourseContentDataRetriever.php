<?php

class MoodleCourseContentDataRetriever extends URLDataRetriever implements CourseContentDataRetriever {

    protected $DEFAULT_PARSER_CLASS='MoodleCourseContentDataParser';
    
    protected $server;
    protected $secure = true;
    protected $DEFAULT_CACHE_LIFETIME = 60; // 1 min
    protected $token;
    protected $userID;
        
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
    
    protected function getUserID() {
        return $this->userID;
    }

    protected function getToken() {
        return $this->token;
    }
    
    public function setOption($option, $value) {
        parent::setOption($option, $value);
        switch ($option)
        {
            case 'action':
                $this->setContext($option, $value);
                break;
        }
    }

    protected function initRequest() {

        $baseUrl = sprintf("http%s://%s/webservice/rest/server.php",
                $this->secure ? 's' : '',
                $this->server);
        $this->setBaseURL($baseUrl);
        
        $this->addParameter('wstoken', $this->getToken());
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
                $this->setCacheGroup($this->getOption('courseID'));
                $postData['courseid'] = $this->getOption('courseID');
                break;
            case 'getCourseResource':
                $this->addParameter('wsfunction', 'core_course_get_contents');
                $this->setCacheGroup($this->getOption('courseID'));
                $postData['courseid'] = $this->getOption('courseID');
                break;  
            case 'downLoadFile':
            case 'getPageContent':
                $this->setBaseURL($this->getOption('contentUrl'));
                $postData['token'] = $this->getToken();
                break;   	
            default:
                throw new KurogoDataException("Action $action not defined");
        }
        
        if ($postData) {
            $this->setMethod('POST');
            $this->addHeader('Content-type', 'application/x-www-form-urlencoded');
            $this->setData(http_build_query($postData));
        }
    }
    
    public function getCourses($options = array()) {
        $this->clearInternalCache();

        if (!$this->getToken() || !$this->getUserID()) {
            return array();
        }
        
        $this->setOption('action', 'getCourses');
        $this->setOption('userID', $this->getUserID());

        $courses = $this->getData();

        return $courses;
    }
    
    public function getAvailableTerms() {
        
    }
    public function getCourseContentById($courseRetrieverID,$contentId=''){
        if ($courseRetrieverID) {
            $this->clearInternalCache();
            $this->setOption('action', 'getCourseResource');
            $this->setOption('courseID', $courseRetrieverID);
            if ($course = $this->getData()) {
            	$courseContents = array();
            	if(empty($contentId) && !$contentId){
	            	foreach ($course as $courseContentObj){
	            	    if($courseContentObj instanceof DownLoadCourseContent){
	            	    	$courseContentObj->setType('download');
	            			$courseContents['downLoad'][] = $courseContentObj;
	            		}
	            	    if($courseContentObj instanceof LinkCourseContent){
	            	    	$courseContentObj->setType('link');
	            			$courseContents['link'][] = $courseContentObj;
	            		}
	            	    if($courseContentObj instanceof PageCourseContent){
	            	    	$courseContentObj->setType('page');
	            			$courseContents['page'][] = $courseContentObj;
	            		}
	            	}
            	}else{
            		foreach ($course as $courseContentObj){
            			if($courseContentObj->getId() == $contentId){
            				$courseContents = $courseContentObj;
            			}
            		}
            	}
            	return $courseContents;
            }
        }
        return '';
    }
    public function getCourseById($courseID) {

        $courses = $this->getCourses();
        foreach ($courses as $course) {
            if ($course->getID()==$courseID) {
                return $course;
            }
        }
    }
    
    public function getGrades($options) {
        
    }

    public function getUpdates($courseID, $options=array()) {
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

    public function getTasks($courseID, $options=array()) {
        return array();
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
        
        if ($user = $this->getCurrentUser()) {
            if ($user instanceOf MoodleUser) {
                $this->setUserID($user->getUserID());
                $this->setToken($user->getToken());
            } else {
                // not a moodle user. Should we do something?
            }
        } else {
            // no user at all
        }

        if (isset($args['TOKEN'])) {
            $this->setToken($args['TOKEN']);
        }
        
        if (isset($args['USERID'])) {
            $this->setUserID($args['USERID']);
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
        $course->setRetriever($this->getResponseRetriever());
        $course->setID($data['id']);
        $course->setTitle($data['shortname']);
        $course->setCourseNumber($data['idnumber']);
        if (isset($data['summary'])) {
            $course->setDescription($data['summary']);
        }
        
        return $course;
    }
    
    protected function parseCourseContent($data) {
        $contentTypes = array();
        $CourseId = $this->getOption('courseID');
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
                                $contentType = new MoodleDownLoadCourseContent();
                                break;
                                
                            case 'url':
                                $contentType = new MoodleLinkCourseContent();
                                break;
                                
                            case 'page':
                                $contentType = new MoodlePageCourseContent();
                                break;
                                
                            default:
                                break;
                        }
                        if ($contentType) {
                            if(isset($module['name'])  && $module['name']){
                        		$contentType->setTitle($module['name']);
                        	}
                            if(isset($module['id'])  && $module['id']){
                        		$contentType->setID($module['id']);
                        	}
                            if(isset($CourseId)  && $CourseId){
                            	
                        		$contentType->setCourseID($CourseId);
                        	}
                            if (isset($module['contents'][0]['timecreated']) && $module['contents'][0]['timecreated']) {
                                $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timecreated']));
                                $contentType->setPublishedDate($datetime);
                            } 
                            if (isset($module['contents'][0]['timemodified']) && $module['contents'][0]['timemodified']) {
                                $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timemodified']));
                                $contentType->setPublishedDate($datetime);
                            }
                            if($module['modname'] == 'resource'){
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
                            
                            if($module['modname'] == 'url'){
                            	if(isset($module['contents'][0]['type']) && $module['contents'][0]['type']){
                            		$contentType->setType($module['contents'][0]['type']);
                            	}
                            	if(isset($module['contents'][0]['fileurl']) && $module['contents'][0]['fileurl']){
                            		$contentType->setFileurl($module['contents'][0]['fileurl']);
                            	}
                            
                            }
                            
                            if($module['modname'] == 'page'){
                                if(isset($module['contents'][0]['type']) && $module['contents'][0]['type']){
                            		$contentType->setType($module['contents'][0]['type']);
                            	}
                                if(isset($module['contents'][0]['filename']) && $module['contents'][0]['filename']){
                            		$contentType->setFilename($module['contents'][0]['filename']);
                            	}
                                if(isset($module['contents'][0]['fileurl']) && $module['contents'][0]['fileurl']){
                            		$contentType->setFileurl($module['contents'][0]['fileurl']);
                            	}
                                if(isset($module['contents'][0]['timemodified']) && $module['contents'][0]['timemodified']){
                            		$contentType->setTimemodified($module['contents'][0]['timemodified']);
                            	}
                            }
                            $contentType->setProperties($properties);
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
	


    public function getUpdates($options=array()) {
        if ($retriever = $this->getRetriever()) {
            return $retriever->getUpdates($this->getID(), $options);
        }           
    }

    public function getTasks($options=array()) {
    }

    public function getResources($options=array()) {
    }
    
    public function getGrades($options=array()) {
    }

}

class MoodleDownLoadCourseContent extends DownLoadCourseContent {
    public function getFileType() {
        $ext = '';
            if (!is_null($this->getFilename()) && $this->getFilename()) {
                $filebits = explode('.', $this->getFilename());
                $ext = strtolower(array_pop($filebits));
            }
        
        return $ext;
    }
    public function getSubTitle() {
    
        $subTitle = '';
        if ($value = $this->getProperty('section')) {
            $subTitle = isset($value['name']) ? strip_tags($value['name']) : '';
        }
        
        return $subTitle;
    }
   				
}
class MoodleLinkCourseContent extends LinkCourseContent {
    public function getSubTitle() {
    
        $subTitle = '';
        if ($value = $this->getProperty('section')) {
            $subTitle = isset($value['name']) ? strip_tags($value['name']) : '';
        }
        
        return $subTitle;
    }
}

class MoodlePageCourseContent extends PageCourseContent {
    public function getSubTitle() {
    
        $subTitle = '';
        if ($value = $this->getProperty('section')) {
            $subTitle = isset($value['name']) ? strip_tags($value['name']) : '';
        }
        
        return $subTitle;
    }
}