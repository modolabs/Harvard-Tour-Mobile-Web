<?php
includePackage('Courses');

class CoursesWebModule extends WebModule {
    protected $id = 'courses'; 
    protected $controller;
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
    
    public function linkForResource($resource, $data = array()){
    	 $link = array(
            'title' => $resource->getTitle(),
            'subtitle' => $resource->getSubTitle()
        );
            
        if ($contentID = $resource->getGUID()) {
            $type = $resource->getType();
            
            $options = array(
                'contentID' => $contentID
            );
            
            foreach (array('courseID') as $field) {
                if (isset($data[$field])) {
                    $options[$field] = $data[$field];
                }
            }
            $link['url'] = ($resource->getType() == 'link') ? 
                           $resource->getFileurl() : 
                           $this->buildBreadcrumbURL($resource->getType(), $options, true);
            
        } elseif ($url = $resource->getUrl()) {
            $link['url'] = $url;
        }
        
        return $link;
    }
    public function linkForUpdates($content,$data){
    	$type = array('page' => 'Page',
    				  'link' => 'Link',
    				  'file' => 'Download',
    				  'url' => 'Link',
    				  );
    	if ($contentID = $content->getGUID()) {
	    	$options = array(
	                'contentID' => $contentID
	        );
	    	$link = array(
	    			'title' => $type[$content->getType()].': '.$content->getTitle(),
	    	);
    	    foreach (array('courseID') as $field) {
                if (isset($data[$field])) {
                    $options[$field] = $data[$field];
                }
            }
	    	if($content->getPublishedDate()){
	    		if($content->getAuthor()){
	    			$link['subtitle'] = 'Updated '. $this->elapsedTime($content->getPublishedDate()->format('U')) .' by '.$content->getAuthor();
	    		}else{
	    			$link['subtitle'] = 'Updated '. $this->elapsedTime($content->getPublishedDate()->format('U'));
	    		}
	    	} else {
	    		$link['subtitle'] = $content->getSubTitle();
	    	}
	    	$link['url'] = ($content->getType() == 'url') ? 
	        $content->getFileurl() : 
	        $this->buildBreadcrumbURL($content->getType(), $options, true);
    	} elseif ($url = $content->getUrl()) {
            $link['url'] = $url;
        }
        return $link;
    
    }
    public function linkForCourse(Course $course, $type) {
        $link = array(
            'title' => $course->getTitle(),
            'url'   => $this->buildBreadcrumbURL('course', array('type'=>$type, 'id'=> $course->getID()), true)
        );

        switch ($type)
        {
            case 'content':
                if ($lastUpdateContent = $course->getLastUpdate()) {
                    $link['subtitle'] = $lastUpdateContent->getTitle() . '<br/>'. $this->elapsedTime($lastUpdateContent->getPublishedDate()->format('U'));
                } else {
                    $link['subtitle'] = $this->getLocalizedString('NO_UPDATES');
                }
                break;
        }
        
        return $link;
    }
    
    function outputFile(DownLoadCourseContent $content) {
        $file = $content->getCacheFile();
        header('Content-type: '.mime_type($file));
        readfile($file);
        exit;
    }
    
    protected function getFeedTitle($feed) {
        return isset($this->feeds[$feed]['TITLE']) ? $this->feeds[$feed]['TITLE'] : '';
    }
    
    /* @TODO provide method to get bookmarked courses */
    protected function getBookmarkedCourses() {
        return array();
    }
    
    protected function initialize() {
    
        $this->feeds = $this->loadFeedData();
        $this->controller = CoursesDataModel::factory('CoursesDataModel', $this->feeds);
    }
    
    protected function initializeForPage() {
        switch($this->page) {
            case 'catalog':
                if ($areas = $this->controller->getCatalogAreas()) {
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
                    if ($CourseArea = $this->controller->getCatalogArea($area)) {
                        $baseArea = $area . '|';
                        $this->setPageTitles($CourseArea->getTitle());
                    } else {
                        $this->redirectTo('index', array());
                    }
                } else {
                    $this->redirectTo('index', array());
                }

                $areas = $this->controller->getCatalogAreas($area);
                
                $areasList = array();
                foreach ($areas as $CourseArea) {
                    $areasList[] = array(
                        'title'=>$CourseArea->getTitle(),
                        'url'=>$this->buildBreadcrumbURL('area',array('area'=>$baseArea . $CourseArea->getCode()), true)
                    );
                }

                $areas = explode("|", $area);
                $courses = $this->controller->getCatalogCourses(array('area' => end($areas)));
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
            
                $id = $this->getArg('id');
                $type = $this->getArg('type');
                
                if (!$course = $this->controller->getCourse($type, $id)) {
                    $this->redirectTo('index');
                }
				
                $items = $this->controller->getLastUpdate($id);
                foreach ($items as $item){
                	$contents[] = $this->linkForUpdates($item, array('courseID' => $id));
                }
                $this->assign('contents', $contents);
                
                $linkToResourcesTab = $this->buildBreadcrumbURL('resource',array('id'=> $id,'type'=>'topic'), true);
                $this->assign('linkToResourcesTab',$linkToResourcesTab);
                             /*              
                $contentTypes = array();
                if ($contents = $this->course->getCourseContentById($id)) {

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
                */
                break;
            case 'resource':
            	$id = $this->getArg('id');
                $type = $this->getArg('type');
                $options = array(
                	'courseID' => $id,
                );
                $this->controller->setType($type);
                $items = $this->controller->getResource($id);
                $resources = array();
                foreach ($items as $key => $item){
                	foreach ($item as $resource){
                		$resources[$key][] = $this->linkForResource($resource,$options);
                	}
                }
                var_dump($resources);
                exit;
            	$linkToUpdateTab = $this->buildBreadcrumbURL('course', array('id'=> $id, 'type'=>'content'), true);
            	$this->assign('linkToUpdateTab',$linkToUpdateTab);
            	break;
            case 'contents':
            // 	$section = $this->getArg('section');
                $id = $this->getArg('id');
                //$courseId = $this->getArg('courseId');
                $type = $this->getArg('type');
                
                
                $items = $this->controller->getCourseContentById($id);
                
                
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
            	$contents = $this->controller->getResource($courseID);
                if (!$content = $this->controller->getContentById($contents,$contentID)) {
                    throw new KurogoConfigurationException('not found the course content');
                }
            	$content = $this->controller->getPageTypeContent($content);
            	$this->assign('content', $content);
            	break;
            case 'download':
                //$section   = $this->getArg('section');
                $courseID  = $this->getArg('courseID');
                $type      = $this->getArg('type');
                $contentID = $this->getArg('contentID');
                
                //$feed = $this->getCourseFeed($section);

                if (!$contentType = $this->controller->getCourseContentById($courseID, $contentID)) {
                    throw new KurogoConfigurationException('not found the course content');
                }
                $contentType = $this->controller->getDownLoadTypeContent($contentType['resource'], $courseID);
                $this->outputFile($contentType);
                break;
            case 'index':
                $feedTerms = $this->controller->getAvailableTerms();
                $terms = array();
                
                foreach($feedTerms as $term) {
                    $terms[$term->getID()] = $term->getTitle();
                }

                $term = $this->getArg('term', CoursesDataModel::CURRENT_TERM);
                if (!$Term = $this->controller->getTerm($term)) {
                    $Term = $this->controller->getCurrentTerm();
                }
                                
                if (count($terms)>1) {
                    $this->assign('terms', $terms);
                } else {
                    $this->assign('termTitle', current($terms));
                }
                
                $courses = array();
                $options = array(
                    'term'=>$Term
                );
                $this->assign('hasPersonalizedCourses', $this->controller->canRetrieve('registration') || $this->controller->canRetrieve('content'));
                if ($this->isLoggedIn()) {                
                    if ($items = $this->controller->getCourses('content', $options)) {
                        foreach ($items as $item) {
                            $course = $this->linkForCourse($item, 'content');
                            $courses[] = $course;
                        }
                    }
                    $this->assign('courses', $courses);
                }
                
                // do we have a catalog?  catelog just demo and XML file copy from LMS //delete this line after look
                $catalogItems = array();
                if ($this->controller->canRetrieve('catalog')) {
                    $catalogItems[] = array(
                        'title' => $this->getFeedTitle('catalog'),
                        'url'   => $this->buildBreadcrumbURL('catalog', array(), true),
                    );
                    
                    if ($bookmarks = $this->getBookmarkedCourses()) {
                        $catalogItems[] = array(
                            'title' => $this->getLocalizedString('BOOKMARKED_COURSES') . "(" . count($bookmarks) . ")",
                            'url'   => '',
                        );
                    }
                }
                $this->assign('catalogItems', $catalogItems);
                break;
        }
    }
}
 