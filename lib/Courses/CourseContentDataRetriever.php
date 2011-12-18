<?php

class CourseContentDataRetriever extends URLDataRetriever implements CourseContentInterface {

    protected $DEFAULT_PARSER_CLASS='MoodleDataParser';
    protected $server;
    protected $secure = true;
    protected $token;
    protected $userID;

    public function getCourses($options) {
        
    }
    public function getAvailableTerms() {
        
    }
    public function getCourseById($courseID) {
        
    }
    
    public function getGrades($options) {
        
    }
    
    public function getLastUpdate() {
        
    }
    
    public function getCourseContent($options) {
        
    }
    
    public function searchCourseContent($searchTerms, $options) {
        
    }
    
    protected function setUserID($userID) {
        $this->userID = $userID;
    }

    public function setToken($token) {
        $this->token = $token;
    }

    public function getCache() {
        return $this->cache;
    }
    
    protected function baseURL() {

        if ($this->getOption('action') == 'downLoadFile') {
            $this->baseURL = $this->getOption('fileUrl');
            
        } elseif ($this->getOption('action') == 'getPageContent') {
            $this->baseURL = $this->getOption('pageUrl');
        } else {
            $this->baseURL = sprintf("http%s://%s/webservice/rest/server.php",
                $this->secure ? 's' : '',
                $this->server
            );
        }
        
        return $this->baseURL;
    }
    
    protected function parameters() {
    
        $parameters = parent::parameters();

        $action = $this->getOption('action');
        $parameters['wstoken'] = $this->token;
        $parameters['wsfunction'] = '';
        $parameters['moodlewsrestformat'] = 'json';
        
        $postData = array();

        switch ($action) {
            case 'getCoursesForUser':
                $parameters['wsfunction'] = 'moodle_enrol_get_users_courses';
                $postData['userid'] = $this->getOption('userID') ? $this->getOption('userID') : $this->userID;
                break;
            
            //the api not work good (Access control exception)
            case 'getCourse':
                $parameters['wsfunction'] = 'core_course_get_courses';
                $postData['options']['ids'][] = $this->getOption('courseID');
                break;
                
            case 'getCourseContent':
                $parameters['wsfunction'] = 'core_course_get_contents';
                $postData['courseid'] = $this->getOption('courseID');
                break;
                
            //get enrolled users by course id.
            case 'getUsers':
                $parameters['wsfunction'] = 'core_enrol_get_enrolled_users';
                $courseID = $this->getOption('courseID');
                $postData['courseid'] = $courseID;
                break;
                
            case 'downLoadFile':
            case 'getPageContent':
                unset($parameters['wsfunction']);
                unset($parameters['moodlewsrestformat']);
                unset($parameters['wstoken']);
                $parameters['token'] = $this->token;
                break;
                
            default:
                $parameters['wsfunction'] = '';
                break;
        }

        if ($postData) {
            $this->setMethod('POST');
            $this->addHeader('Content-type', 'application/x-www-form-urlencoded');
            $this->setData(http_build_query($postData));
        }
        
        return $parameters;
    }
    
    public function retrieveResponse() {
        
        $action = $this->getOption('action');
        $response = parent::retrieveResponse();
        
        $response->setContext('action', $action);
        return $response;
    }
    
    protected function init($args) {
        parent::init($args);
        
        /*
        if (!isset($args['SERVER'])) {
            throw new KurogoConfigurationException("Moodle SERVER must be set");
        }
        $this->server = $args['SERVER'];

        if (isset($args['SECURE'])) {
            $this->secure = (bool) $args['SECURE'];
        }
        
        if ($this->authority instanceOf MoodleAuthentication) {
            if ($this->authority->isLoggedIn()) {
                
                $user = $this->getCurrentUser();
                $this->setUserID($user->getUserID());
                $this->setToken($user->getToken());
            }
        }
        
        if (($parser = $this->parser()) && $parser instanceOf MoodleDataParser) {
            $parser->setRetriever($this);
        }
        */
    }
}

class MoodleDataParser extends dataParser {
    protected $items;
    protected $retriever;
    
    public function clearInternalCache() {
        parent::clearInternalCache();
        $this->items = array();
    }
    
    public function setRetriever(MoodleDataRetriever $retriever) {
        $this->retriever = $retriever;
    }
    
    public function getRetriever() {
        return $this->retriever;
    }
    
    protected function getParseFunc() {
        $this->getRetriever()->setMethod('GET');
        $action = $this->response->getContext('action');
        
        $parseFunc = '';
        switch ($action) {
            case 'getCoursesForUser':
            case 'getCourse':
                $parseFunc = 'parseCourses';
                break;
            case 'getCourseContent':
                $parseFunc = 'parseCourseContent';
                break;
            case 'getUsers':
                $parseFunc = 'parseUsers';
                break;
            default:
                throw new KurogoDataException("not defined the action:" . $action);
                break;
        }
        
        return $parseFunc;
    }
    
    public function parseData($data) {
        $parseFunc = $this->getParseFunc();

        if ($data = json_decode($data, true)) {
            if (isset($data['exception'])) {
                throw new KurogoDataException($data['message']);
            }
            $this->items = $this->{$parseFunc}($data);
        }

        $this->setTotalItems(count($this->items));
        return $this->items;
    }

    protected function parseCourses($data) {
        $courses = array();
        
        foreach ($data as $value) {
            if (isset($value['visible']) && !$value['visible']) {
                continue;
            }
            $course = new MoodleLMSCourse();
            $course->setID($value['id']);
            $course->setShortName($value['shortname']);
            $course->setTitle($value['fullname']);
            $course->setCoureNumber($value['idnumber']);

            if (isset($value['summary'])) {
                $course->setDescription($value['summary']);
            }
            if (isset($value['enrolledusercount'])) {
                $course->setStudentCount($value['enrolledusercount']);
            }
            if (isset($value['startdate']) && $value['startdate']) {
                $course->setStartDate(new DateTime(date('Y-n-j H:i:s', $value['startdate'])));
            }
            //indicate the moodleDataRetriever
            $course->setRetriever($this->getRetriever());
            
            $courses[] = $course;
        }
        
        return $courses;
    }

    //parse the users data
    protected function parseUsers($data) {
    
        $users = array();
        foreach ($data as $item) {
            $user = new MoodleLMSCourseUser();
            $user->setId($item['id']);
            $user->setFullName($item['fullname']);
            $user->setCity($item['city']);
            $user->setCountry($item['country']);
            if (isset($item['roles'])) {
                $user->setRoles($item['roles']);
            }
            $users[] = $user;
        }
        
        return $users;
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
                                $contentType = new DownLoadMoodleLMSContentType();
                                break;
                                
                            case 'url':
                                $contentType = new LinkMoodleLMSContentType();
                                break;
                                
                            case 'page':
                                $contentType = new PageMoodleLMSContentType();
                                break;
                                
                            case 'assignment':
                                break;
                                
                            default:
                                break;
                        }
                        if ($contentType) {
                            $contentType->setID($module['id']);

                            $contentType->setTitle($module['name']);
                            if (isset($module['url'])) {
                                $contentType->setUrl($module['url']);
                            }
                            //add the contents to the LMSContentType properties.
                            if (isset($module['contents'])) {
                                $properties['contents'] = $module['contents'][0];
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
