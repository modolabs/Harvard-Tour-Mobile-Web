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
		$User->setId(Kurogo::arrayVal($data, 'id'));
		$User->setEmail(Kurogo::arrayVal($data, 'email'));
		$User->setRoles(Kurogo::arrayVal($data, 'roles'));
		$User->setFullName(Kurogo::arrayVal($data, 'fullname'));
		$User->setEnrolledCourses(Kurogo::arrayVal($data, 'enrolledcourses'));
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
        $retriever = $this->getResponseRetriever();
        foreach ($data as $value) {

            $section = array(
                'id'=>$value['id'],
                'name'=>$value['name'],
            );
            
            if (isset($value['modules']) && $value['modules'] || isset($value['contents']) && $value['contents']) {
                $moduleValue = isset($value['modules'])?$value['modules']:$value['contents'];

                foreach ($moduleValue as $module) {

                    $content = null;

                    if ($module ['visible']) {
                        switch ($module['modname']) {
                            case 'resource':
                                $content = new FileCourseContent();
                                break;

                            case 'url':
                                $content = new LinkCourseContent();
                                break;

                            case 'page':
                                $content = new PageCourseContent();
                                break;
                                
                            case 'label':
                                continue 2;
                                
                            case 'forum':
                            	//@TODO handle
                                continue 2;
                                
                            case 'folder':
                            	//@TODO handle
                            	continue 2;

                            case 'assignment':
                            	//@TODO handle
                            	continue 2;

                            default:
                                throw new KurogoDataException("Don't know how to handle " . $module['modname']);
                                break;
                        }
                        
                        $content->setContentRetriever($retriever);
                        
                        $content->setTitle($module['name']);
                        $content->setID($module['id']);

                        if(isset($CourseId)  && $CourseId){
                            $content->setCourseID($CourseId);
                        }

                        if (isset($module['contents'][0]['timecreated']) && $module['contents'][0]['timecreated']) {
                            $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timecreated']));
                            $content->setPublishedDate($datetime);
                        }
                        
                        if (isset($module['contents'][0]['timemodified']) && $module['contents'][0]['timemodified']) {
                            $datetime = new DateTime(date('Y-n-j H:i:s', $module['contents'][0]['timemodified']));
                            $content->setPublishedDate($datetime);
                        }

                        if (isset($module['contents'][0]['author']) && $module['contents'][0]['author']) {
                            $content->setAuthor($module['contents'][0]['author']);
                        }

                        switch ($module['modname'])
                        {
                            case 'resource':
                                $attachment = new CourseContentAttachment();
                                
                                if (isset($module['contents'][0]['filename']) && $module['contents'][0]['filename']){
                                    $attachment->setFilename($module['contents'][0]['filename']);
                                }
                                
                                if (isset($module['contents'][0]['filesize']) && $module['contents'][0]['filesize']){
                                    $attachment->setFilesize($module['contents'][0]['filesize']);
                                }
                                
                                if (isset($module['contents'][0]['fileurl']) && $module['contents'][0]['fileurl']){
                                    $url = $module['contents'][0]['fileurl'] . "&token=" . $retriever->getToken();
                                    $attachment->setURL($url);
                                }
                                
                                if (isset($module['contents'][0]['sortorder']) && $module['contents'][0]['sortorder']){
                                //   $content->setSortorder($module['contents'][0]['sortorder']);
                                }
                                
                                $content->addAttachment($attachment);
                                
                                break;
                                
                            case 'url':
                                $content->setURL($module['contents'][0]['fileurl']);
                                break;
                                
                            case 'page':
                                if (isset($module['contents'][0]['fileurl']) && $module['contents'][0]['fileurl']){
                                    $content->setURL($module['contents'][0]['fileurl']);
                                }
                                
                                if (isset($module['contents'][0]['timemodified']) && $module['contents'][0]['timemodified']){
                                    $content->setTimemodified($module['contents'][0]['timemodified']);
                                }
                                
                                break;
                        }
                        
                        $content->setAttribute('section', $section);
                        $contentTypes[] = $content;
                    }
                }
            }
        }
        return $contentTypes;

    }
}
