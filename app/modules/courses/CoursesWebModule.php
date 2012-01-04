<?php
includePackage('Courses');

class CoursesWebModule extends WebModule {
    protected $id = 'courses'; 
    protected $feed;
    protected $courses;
    
    protected function linkForContent($content, $data = array()) {
    
        $link = array(
            'title' => $content->getTitle(),
            'subtitle' => $content->getSubTitle()
        );
        
        if ($contentID = $content->getGUID()) {
            $type = $content->getType();
            
            $options = array(
                'contentID' => $contentID
            );
            
            foreach (array('section', 'type', 'courseID') as $field) {
                if (isset($data[$field])) {
                    $options[$field] = $data[$field];
                }
            }
            $link['url'] = ($content->getType() == 'link') ? 
                           $content->getFileurl() : 
                           $this->buildBreadcrumbURL($content->getType(), $options, true);
            
        } elseif ($url = $content->getUrl()) {
            $link['url'] = $url;
        }
        
        return $link;
    }
    
    public function getCourseFeed() {
        if ($feeds = $this->loadFeedData()) {
            if (isset($feeds['catalog'])) {
                $catalogFeed = $this->getModuleSections('catalog');
                $feeds['catalog'] = array_merge($feeds['catalog'], $catalogFeed);
            }
            return DataModel::factory('CoursesDataModel', $feeds);
        } else {
            throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED'));
        }
    }
    
    public function linkForCourse(Course $course) {
        $link = array(
            'title' => $course->getTitle(),
            'url'   => $this->buildBreadcrumbURL('course', array('id'=> $course->getCourseNumber()), true)
        );

        if ($lastUpdateContent = $this->feed->getLastUpdate($course->getRetrieverId('content'))) {
            $link['subtitle'] = $lastUpdateContent->getTitle() . '<br/>'. $this->elapsedTime($lastUpdateContent->getPublishedDate()->format('U'));
        }
        
        
        return $link;
    }
    
    function outputFile(MoodleDownLoadCourseContent $content) {
        $file = $content->getCacheFile();
        header('Content-type: '.mime_type($file));
        readfile($file);
        exit;
    }
    
    protected function initialize() {
    
        $this->feed = $this->getCourseFeed();
        
    }
    
    protected function initializeForPage() {
        switch($this->page) {
            case 'catalog':
                if ($areas = $this->feed->getCatalogAreas()) {
                    $areasList = array();
                    foreach ($areas as $CourseArea) {
                        $areasList[] = array(
                            'title'=>$CourseArea->getTitle(),
                            'url'=>$this->buildBreadcrumbURL('area',array('area'=>$CourseArea->getCode()), true)
                        );
                    }
                    $this->assign('areas', $areasList);
                }
                
                break;
                
            case 'area':
                $baseArea = '';
                if ($area = $this->getArg('area', '')) {
                    if ($CourseArea = $this->feed->getCatalogArea($area)) {
                        $baseArea = $area . '|';
                        $this->setPageTitles($CourseArea->getTitle());
                    } else {
                        $this->redirectTo('index', array());
                    }
                } else {
                    $this->redirectTo('index', array());
                }

                $areas = $this->feed->getCatalogAreas($area);
                
                $areasList = array();
                foreach ($areas as $CourseArea) {
                    $areasList[] = array(
                        'title'=>$CourseArea->getTitle(),
                        'url'=>$this->buildBreadcrumbURL('area',array('area'=>$baseArea . $CourseArea->getCode()), true)
                    );
                }

                $areas = explode("|", $area);
                $courses = $this->feed->getCatalogCourses(array('area' => end($areas)));
                $coursesList = array();
 
                foreach ($courses as $Course) {
                    $coursesList[] = array(
                        'title'=>$Course->getTitle(),
                        'subtitle'=>$Course->getCourseNumber(),
                        'url'=>$this->buildBreadcrumbURL('course',array('id'=> $Course->getCourseNumber(),'catalog'=>$Course->getCatalogNumber()), true)
                    );
                }
                
                $this->assign('description', $CourseArea->getDescription());
                $this->assign('areas', $areasList);
                $this->assign('courses', $coursesList);
                
                break;
            
            case 'course':
            	// get courseID by AreaCode
                $id = $this->feed->GetCourseId($this->getArg('id'), 'content');
                
                //$course = $this->feed->getCourseById($id);
                $contentTypes = array();
                if ($contents = $this->feed->getCourseContentById($id)) {
                $options = array(
                    'id'      => $id,
                );
                    $items = array_keys($contents['resource']);
                    
                    foreach ($items as $type) {
                        $options['type'] = $type;
                    
                        $contentType = array(
                            'title' => $this->getLocalizedString(strtoupper($type) .'_TITLE'),
                            'url'   => $this->buildBreadcrumbURL('contents', $options, true)
                        );
                        
                        $contentTypes[] = $contentType;
                    }
                }
                $this->assign('contentTypes', $contentTypes);
                break;
            case 'contents':
           // 	$section = $this->getArg('section');
                $id = $this->getArg('id');
                //$courseId = $this->getArg('courseId');
                $type = $this->getArg('type');
                
                
                $items = $this->feed->getCourseContentById($id);
                
                
                if (!isset($items['resource'][$type])) {
                    throw new KurogoConfigurationException('not found the content for type ' . $type);
                }
                
                $options = array(
             //   	'section'  => $section,
             		//'courseId' => $courseId,
                    'type'     => $type,
                    'courseID' => $id
                );
                    
                $contents = array();
                foreach ($items['resource'][$type] as $item) {
                    $content = $this->linkForContent($item, $options);
                    $contents[] = $content;
                }
                $this->setPageTitles($this->getLocalizedString(strtoupper($type) .'_TITLE'));
                $this->assign('contents', $contents);
                break;
            case 'page':
            	$contentID = $this->getArg('contentID', '');
            	$courseID = $this->getArg('courseID', '');
                if (!$contents = $this->feed->getCourseContentById($courseID, $contentID)) {
                    throw new KurogoConfigurationException('not found the course content');
                }
                
            	$content = $this->feed->getPageTypeContent($contents['resource']);
            	$this->assign('content', $content);
            	break;
            case 'download':
                //$section   = $this->getArg('section');
                $courseID  = $this->getArg('courseID');
                $type      = $this->getArg('type');
                $contentID = $this->getArg('contentID');
                
                //$feed = $this->getCourseFeed($section);

                if (!$contentType = $this->feed->getCourseContentById($courseID, $contentID)) {
                    throw new KurogoConfigurationException('not found the course content');
                }
                $contentType = $this->feed->getDownLoadTypeContent($contentType['resource'], $courseID);
                $this->outputFile($contentType);
                break;
            case 'index':
                $feedTerms = $this->feed->getAvailableTerms();
                $terms = array();
                foreach($feedTerms as $term) {
                    $terms[$term->getID()] = $term->getTitle();
                }
                                
                if (count($terms)>1) {
                    $this->assign('terms', $terms);
                } else {
                    $this->assign('termTitle', current($terms));
                }
                
                $courses = array();
                
                if ($items = $this->feed->getContentCourses()) {
                    foreach ($items as $item) {
                        $course = $this->linkForCourse($item);
                        $courses[] = $course;
                    }
                }
                $this->assign('courses', $courses);
                
                // do we have a catalog? 
                $catalogItems = array();
                if ($this->feed->canRetrieve('catalog')) {
                    $catalogItems[] = array(
                        'title' => 'Course Catalog',
                        'url'   => $this->buildBreadcrumbURL('catalog', array(), true),
                    );
                    $catalogItems[] = array(
                        'title' => 'Bookmarked Courses',
                        'url'   => '',
                    );
                }
                $this->assign('catalogItems', $catalogItems);
                break;
        }
    }
}
