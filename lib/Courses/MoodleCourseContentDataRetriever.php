<?php

class MoodleCourseContentDataRetriever extends URLDataRetriever implements CourseContentDataRetriever {

    protected $DEFAULT_PARSER_CLASS='MoodleCourseContentDataParser';
    
    protected $server;
    protected $secure = true;
    protected $DEFAULT_CACHE_LIFETIME = 60; // 1 min
    protected $token;
    protected $userID;
    protected $sortType;
        
    protected function setUserID($userID) {
        $this->userID = $userID;
    }
    
    public function getCache() {
        return $this->cache;
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

    //CoursesDATAModel function getFileUrl need use token
    public function getToken() {
        return $this->token;
    }
    
    // the public function return protected function retrieveResponse() result
    // calling in coursesDataModel getDownLoadTypeContent
    public function retrieveResponse() {
        
        $action = $this->getOption('action');
        $response = parent::retrieveResponse();
        
        $response->setContext('action', $action);
        return $response;
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
        	case 'getUsersByCourseId':
                $this->addParameter('wsfunction', 'core_enrol_get_enrolled_users');
                $postData['courseid'] = $this->getOption('courseID');
                break;      		
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
    public function getCourseContent($courseRetrieverID,$type=''){
        if ($courseRetrieverID) {
            $this->clearInternalCache();
            $this->setOption('action', 'getCourseResource');
            $this->setOption('courseID', $courseRetrieverID);
            if ($course = $this->getData()) {
            	$courseContents = array();
            	switch ($type){
            		case 'topic':
            			foreach ($course as $courseContentObj){
            				$section = $courseContentObj->getProperty('section');
            				if(isset($section['name'])){
            					$courseContents[$section['name']][] = $courseContentObj;
            				}
            			}
            			//$sortCourseContents = array();
            			foreach ($courseContents as $topic => $courseContent){
	            			$courseContent = $this->sortCourseContent($courseContent, 'publishedDate');
	            			$sortCourseContents[$topic] = $courseContent;
            			}
            			return $sortCourseContents;
            			break;
            		case 'type':
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
            			break;
            		case 'date':
            			$courseContents[] = $this->sortCourseContent($course, 'publishedDate');
            			return $courseContents;
            			break;
            		default:
            			return $course;
            			break;
            	}
            	return $course;
            }
        }
        return '';
    }
    public function getContentById($contents, $contentId){
    	if($contents){
    		foreach ($contents as $content){
    			if($content->getId() == $contentId){
    				return $content;
    			}
    		}
    	}
    }
    public function getCourseById($courseID) {

        $courses = $this->getCourses();
        foreach ($courses as $course) {
            if ($course->getID()==$courseID) {
                return $course;
            }
        }
    }
    
    public function getUsersByCourseId($courseId){
    	$this->clearInternalCache();
    	$this->setOption('action', 'getUsersByCourseId');
    	$this->setOption('courseID', $courseId);
    	$courses = $this->getData();
    	return $courses;
    }
    public function getGrades($options) {
        
    }
    public function sortCourseContent($courseContents, $sort) {
        if (empty($courseContents)) {
            return array();
        }
        
		$this->sortType = $sort;
		
		uasort($courseContents, array($this, "sortByField"));
		
        return $courseContents;
    }
    
    // sort type for content
	private function sortByField($contentA, $contentB) {
		switch ($this->sortType){
			
			case 'publishedDate':
	            $contentA_time = $contentA->getPublishedDate() ? $contentA->getPublishedDate()->format('U') : 0;
	            $contentB_time = $contentB->getPublishedDate() ? $contentB->getPublishedDate()->format('U') : 0;
	            return $contentA_time < $contentB_time;		
	            		
			default:
	            $func = 'get' . $this->sortType;
	            if(function_exists($func)){
	            	return strcasecmp($contentA->$func(), $contentB->$func());
	            }else{
	            	throw new KurogoConfigurationException("Function not exist");
	            }
	            
            break;
		
		}

	}
	
    public function getUpdates($courseID, $options=array()) {
        $this->clearInternalCache();
        $this->setOption('action', 'getCourseContent');
        $this->setOption('courseID', $courseID);
        
        $contents = array();
        if ($items = $this->getData()) {
        	$items = $this->sortCourseContent($items,'publishedDate');
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
            	case 'getUsersByCourseId':
                    foreach ($data as $value) {
                        if ($user = $this->parseUser($value)) {
                            $items[] = $user;
                        }
                    }
            		break;
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
    
    protected function parseUser($data){
		$User = new CourseUser();
		$User->setId($data['id']);
		$User->setEmail($data['email']);
		$User->setRoles($data['roles']);
		$User->setFullName($data['fullname']);
		$User->setEnrolledCourses($data['enrolledcourses']);
		return $User;
    }
    protected function parseCourseContent($data) {
        $contentTypes = array();
        $CourseId = $this->getOption('courseID');
        foreach ($data as $value) {
            $properties = array();
            if (isset($value['modules']) && $value['modules'] || isset($value['contents']) && $value['contents']) {
                $moduleValue = isset($value['modules'])?$value['modules']:$value['contents'];
                foreach ($moduleValue as $module) {
                    $contentType = null;
                    if ($module ['visible'] && isset($module['modname']) && $module['modname']) {
                        switch ($module['modname']) {
                            case 'resource':
                            case 'folder':
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
                        	$unsetString = isset($value['modules'])? 'modules' : 'contents';
                        	unset($value[$unsetString]);
                        	$properties['section'] = $value;
                            if(isset($module['name'])  && $module['name']){
                        		$contentType->setTitle($module['name']);
                        	}
                            if(isset($module['id'])  && $module['id']){
                        		$contentType->setID($module['id']);
                        	}
                            if(isset($CourseId)  && $CourseId){
                            	
                        		$contentType->setCourseID($CourseId);
                        	}
                        	
                        	$contentType->setType($module['modname']);
                        	
                            if (isset($module['contents'][0]['timecreated']) && $module['contents'][0]['timecreated']) {
                                $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timecreated']));
                                $contentType->setPublishedDate($datetime);
                            } 
                            if (isset($module['contents'][0]['timemodified']) && $module['contents'][0]['timemodified']) {
                                $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timemodified']));
                                $contentType->setPublishedDate($datetime);
                            }
                            if($module['modname'] == 'resource' || $module['modname'] == 'folder'){
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
                            	if(isset($module['contents'][0]['fileurl']) && $module['contents'][0]['fileurl']){
                            		$contentType->setFileurl($module['contents'][0]['fileurl']);
                            	}
                            
                            }
                            
                            if($module['modname'] == 'page'){
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
	public function getStudents(){
		if ($retriever = $this->getRetriever()) {
			$users = $retriever->getUsersByCourseId($this->getID());
			$Student = array();
		    foreach ($users as $user){
            	$roles = $user->getRoles();
	            if($roles[0]['roleid'] == 5){ // if rileId eq 5 is Teacher in moodle
	            	$Student[] = $user;
	            }
            }
			return $Student;
		}
	}
	public function getInstructors(){
		if ($retriever = $this->getRetriever()) {
			$users = $retriever->getUsersByCourseId($this->getID());
			$instructorLish = array();
		    foreach ($users as $user){
            	$roles = $user->getRoles();
	            if($roles[0]['roleid'] == 3){ // if rileId eq 3 is Teacher in moodle
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
class CourseUser implements KurogoObject{
	protected $id;
	protected $fullname;
	protected $email;
	protected $roles;
	protected $enrolledCourses; 
	
	public function filterItem($filters){
		return $filters;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	public function getFullName() {
		return $this->fullname;
	}
	
	public function setFullName($fullname) {
		$this->fullname = $fullname;
	}
	public function getEmail() {
		return $this->email;
	}
	
	public function setEmail($email) {
		$this->email = $email;
	}
	public function getRoles() {
		return $this->roles;
	}
	
	public function setRoles($roles) {
		$this->roles = $roles;
	}
	public function getEnrolledCourses() {
		return $this->enrolledCourses;
	}
	
	public function setEnrolledCourses($enrolledCourses) {
		$this->enrolledCourses = $enrolledCourses;
	}
}