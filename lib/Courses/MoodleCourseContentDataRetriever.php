<?php

class MoodleCourseContentDataRetriever extends URLDataRetriever implements CourseContentDataRetriever {

    protected $DEFAULT_PARSER_CLASS='MoodleCourseContentDataParser';
    
    protected $server;
    protected $secure = true;
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
            case 'getCourses':
                $parameters['wsfunction'] = 'core_enrol_get_users_courses';
                $postData['userid'] = $this->getOption('userID');
                break;
            
            case 'getCourseContent':
                $parameters['wsfunction'] = 'core_course_get_contents';
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
        
        return $parameters;
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
    
    public function getCourseById($courseID) {
        
    }
    
    public function getGrades($options) {
        
    }
    
    public function sortContents() {
        
    }
    
    public function getLastUpdate($courseID) {
        if ($courseContents = $this->getCourseContent($courseID)) {
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
                    foreach ($data as $value) {
                        if ($course = $this->parseCourse($value)) {
                            $items[] = $course;
                        }
                    }
                    break;
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
        $course = new Course();
        $course->setTitle($data['shortname']);
        $course->addRetrieverId('content', $data['id']);
        
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
                            } elseif (isset($module['contents'][0]['timemodified']) && $module['contents'][0]['timemodified']) {
                                $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timemodified']));
                                $contentType->setPublishedDate($datetime);
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

class MoodleDownLoadCourseContent extends DownLoadCourseContent {
    
}

class MoodleLinkCourseContent extends LinkCourseContent {
    
}

class MoodlePageCourseContent extends PageCourseContent {
    
}