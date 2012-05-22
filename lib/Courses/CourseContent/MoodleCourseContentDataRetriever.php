<?php

Kurogo::includePackage('Courses','Moodle');
class MoodleCourseContentDataRetriever extends URLDataRetriever implements CourseContentDataRetriever {

    protected $DEFAULT_PARSER_CLASS='MoodleCourseContentDataParser';

    protected $server;
    protected $secure = true;
    protected $DEFAULT_CACHE_LIFETIME = 60; // 1 min
    protected $token;
    protected $userID;
    protected $sortType;
    protected $shouldDownloadFile = true;

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
        $this->setData(null);
        $this->setSaveToFile(false);
        parent::clearInternalCache();
    }

    protected function getUserID() {
        return $this->userID;
    }

    protected function setShouldDownloadFile($shouldDownloadFile){
        $this->shouldDownloadFile = $shouldDownloadFile;
    }

    public function shouldDownloadFile(){
        return $this->shouldDownloadFile;
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
            case 'downloadFile':
                $this->setBaseURL($this->getOption('contentUrl'));
                $this->setSaveToFile(true);
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
    public function getCourseContent($courseRetrieverID, $options=array()) {
        if ($courseRetrieverID) {
            $this->clearInternalCache();
            $this->setOption('action', 'getCourseContent');
            $this->setOption('courseID', $courseRetrieverID);
            $courseContents = array();

            if ($content = $this->getData()) {
            	$group = isset($options['group']) ? $options['group'] : null;
            	switch ($group) {
            		case 'topic':
            			foreach ($content as $courseContentObj){
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
            			break;

            		case 'type':
	            		foreach ($content as $item) {
	            		    $courseContents[$item->getContentType()][] = $item;
		            	}
            			break;
            		case 'date':
            			$courseContents[] = $this->sortCourseContent($content, 'publishedDate');
            			break;
            		default:
            			return $content;
            			break;
            	}
            }
        }
        return $courseContents;
    }

    public function getCourseByCommonID($commonID, $options) {
        $courses = $this->getCourses($options);
        foreach ($courses as $course) {
            if ($course->getCommonID()==$commonID) {
                return $course;
            }
        }

        return null;
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

    public function getFileForUrl($url, $fileName) {
    	$this->clearInternalCache();
        //append the token
        $this->setOption('action','downloadFile');
        $this->setOption('contentUrl', $url);
        $this->setOption('fileName', $fileName);

        $response = $this->getResponse();
        return $response->getResponse();
    }

    protected function saveToFile() {
        if ($this->getOption('action')=='downloadFile') {
            return $this->getOption('fileName');
        }
        return false;
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

    public function getAnnouncements($courseID, $options){
        return array();
        $this->clearInternalCache();
        $this->setOption('action', 'getForums');
        $this->setOption('courseID', $courseID);
        
        $forums = $this->getData();
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

        if (isset($args['DOWNLOAD_FILE'])) {
            $this->shouldDownloadFile = (bool) $args['DOWNLOAD_FILE'];
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

        if (isset($args['COMMON_ID'])) {
            $this->commonID_field = $args['COMMON_ID'];
        }
    }
}
