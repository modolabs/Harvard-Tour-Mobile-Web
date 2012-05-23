<?php

class MoodleCourseContentDataParser extends dataParser {
    protected $commonID_field='courseNumber';

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
            	case 'getUserProfiles':
                    foreach ($data as $value) {
                        if ($user = $this->parseUser($value)) {
                            $items[] = $user;
                        }
                    }
            		break;
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
                case 'getCourseContent':
                    $items = $this->parseCourseContent($data);
                    break;
                default:
                    throw new KurogoDataException("not defined the action:" . $action);
                    break;
            }
        } elseif ($action=='downloadFile') {
            return $this->response->getResponse();
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
        $course->setCommonIDField($this->commonID_field);
        $course->setCourseNumber($data['idnumber']);
        if (isset($data['summary'])) {
            $course->setDescription($data['summary']);
        }

        return $course;
    }

    protected function parseUser($data){
		$User = new MoodleCourseUser();
		$User->setId($data['id']);
		$User->setEmail($data['email']);
		$User->setRoles($data['roles']);
		$User->setFullName($data['fullname']);
		$User->setEnrolledCourses($data['enrolledcourses']);
		return $User;
    }

    public function init($args) {
        parent::init($args);
        if (isset($args['COMMON_ID_FIELD'])) {
            $this->commonID_field = $args['COMMON_ID_FIELD'];
        }
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
                                $contentType = new MoodleDownloadCourseContent();
                                break;

                            case 'url':
                                $contentType = new MoodleLinkCourseContent();
                                break;

                            case 'page':
                                $contentType = new MoodlePageCourseContent();
                                break;
                            case 'label':
                                break;
                            case 'forum':
                                break;

                            default:
                                throw new KurogoDataException("Don't know how to handle " . $module['modname']);
                                break;
                        }
                        if ($contentType) {
                            $contentType->setContentRetriever($this->getResponseRetriever());
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

                            if (isset($module['contents'][0]['timecreated']) && $module['contents'][0]['timecreated']) {
                                $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timecreated']));
                                $contentType->setPublishedDate($datetime);
                            }
                            if (isset($module['contents'][0]['timemodified']) && $module['contents'][0]['timemodified']) {
                                $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timemodified']));
                                $contentType->setPublishedDate($datetime);
                            }
                            if($module['modname'] == 'resource' || $module['modname'] == 'folder'){
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
                            		$contentType->setURL($module['contents'][0]['fileurl']);
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
