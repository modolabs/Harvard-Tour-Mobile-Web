<?php
includePackage('Courses');

class CoursesWebModule extends WebModule {
    protected $id = 'courses'; 
    protected $controller;
    protected $courses;
    
    protected function linkforInfo($courseId, $description){
    	$links = array();
    	foreach(array('Roster', 'Course materials', 'Drop Class', 'Description') as $title){
    		$link['title'] = $title;
    		if($title == 'Roster'){
    			$link['url'] = $this->buildBreadcrumbURL('roster', array('id'=>$courseId), true);
    		}
    		if($title == 'Course materials'){
    			$link['url'] = '#';
    		}//waiting
    		if($title == 'Drop Class') {
    		    if ($this->controller->canRetrieve('registation')) {
    		        $link['url'] = $this->buildBreadcrumbURL('dropclass', array('id'=>$courseId), true);
    		    } else {
    		        continue;
    		    }
    		}//waiting
    		if($title == 'Description'){
    			$link['subtitle'] = $description;
    		}
    		$links[] = $link;
    		unset($link);
    	}
    	return $links;
    }
    
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
                           $this->buildBreadcrumbURL($content->getType(), $options, false);
            
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
        if($resource->getPublishedDate()){
	    	if($resource->getAuthor()){
	    		$link['subtitle'] = 'Updated '. $this->elapsedTime($resource->getPublishedDate()->format('U')) .' by '.$resource->getAuthor();
	    	}else{
	    		$link['subtitle'] = 'Updated '. $this->elapsedTime($resource->getPublishedDate()->format('U'));
	    	}
	    } else {
	    	$link['subtitle'] = $resource->getSubTitle();
	    } 
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
            $link['url'] = ($resource->getType() == 'link' || $resource->getType() == 'url') ? 
                           $resource->getFileurl() : 
                           $this->buildBreadcrumbURL($resource->getType(), $options, false);
            
        } elseif ($url = $resource->getUrl()) {
            $link['url'] = $url;
        }
        
        return $link;
    }
    
    public function linkForUpdate(CourseContent $content, $data){
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
    
    public function linkForCourse(CourseInterface $course, $options) {
        
        $options = array_merge($options, array(
            'courseID'  => $course->getID()
            )
        );
    
        $link = array(
            'title' => $course->getTitle()
        );

        if ($contentCourse = $course->getCourse('content')) {
            $page = 'updates';
            if ($lastUpdateContent = $contentCourse->getLastUpdate()) {
                $link['subtitle'] = $lastUpdateContent->getTitle() . '<br/>'. $this->elapsedTime($lastUpdateContent->getPublishedDate()->format('U'));
            } else {
                $link['subtitle'] = $this->getLocalizedString('NO_UPDATES');
            }
        } else {
            $page = 'info';
        }
        
        $link['url'] = $this->buildBreadcrumbURL($page, $options , false);
        return $link;
    }
    
	/**
	* assign term function
	* this function need improvment this term in some case is select area
	* @author saturn
	*
	*/
    public function assignTerm(){
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
        return $Term;
    }
    
    protected function getCourseFromArgs() {

        $courseID = $this->getArg('courseID');
        $term = $this->assignTerm();
        $options = $this->getCourseOptions();
        
        if ($course = $this->controller->getCourseByCommonID($courseID, $options)) {
            $this->assign('courseTitle', $course->getTitle());
            $courseTabs = array();
            if ($contentCourse = $course->getCourse('content')) {
                $courseTabs['updates'] = array(
                    'title'=>$this->getLocalizedString('COURSE_TAB_UPDATES'),
                    'url'=> $this->buildBreadcrumbURL('updates', $options, false)
                );

                $courseTabs['resources'] = array(
                    'title'=>$this->getLocalizedString('COURSE_TAB_RESOURCES'),
                    'url'=> $this->buildBreadcrumbURL('resources', $options, false)
                );
            }

            $courseTabs['info'] = array(
                'title'=>$this->getLocalizedString('COURSE_TAB_INFO'),
                'url'=> $this->buildBreadcrumbURL('info', $options, false)
            );
            
            $this->assign('courseTabs', $courseTabs);
        }
    
        return $course;
    }


    protected function getFeedTitle($feed) {
        return isset($this->feeds[$feed]['TITLE']) ? $this->feeds[$feed]['TITLE'] : '';
    }
    
    /* @TODO provide method to get bookmarked courses */
    protected function getBookmarkedCourses() {
        return array();
    }
    
    protected function initialize() {
    	$feeds = $this->loadFeedData();
    	
        if (isset($feeds['catalog'])) {
	        $catalogFeed = $this->getModuleSections('catalog');
	        $feeds['catalog'] = array_merge($feeds['catalog'], $catalogFeed);
        }
        
        $this->feeds = $feeds;
        $this->controller = CoursesDataModel::factory('CoursesDataModel', $this->feeds);
    }
    
    protected function getCourseOptions() {
        $courseID = $this->getArg('courseID');
        $term = $this->assignTerm();
        
        $options = array(
            'courseID'=>$courseID,
            'term'=>strval($term)
        );
        
        return $options;
    }
    
    protected function initializeForPage() {
        switch($this->page) {
        	case 'info':
        	    
        	    if (!$course = $this->getCourseFromArgs()) {
        	        $this->redirectTo('index');
        	    }
        	    $options = $this->getCourseOptions();
        	    
                $instructorList = array();
                $instructors = $course->getInstructors();
                
                foreach ($instructors as $instructor){
                	$value = $instructor->getFullName();
                	$link = Kurogo::moduleLinkForValue('people', $value, $this, $instructor);
                	if(!$link){
                		$link = array(
                				'title' => $value,
                		);
                	}
                	$instructorList[] = $link;
                }
                
                $this->assign('instructors',$instructorList);
                $links = array();

                if ($registrationCourse = $course->getCourse('registration')) {
                    if ($registrationCourse->canDrop()) {
                        $links[] = array(
                            'title'=> $this->getLocalizedString('DROP_COURSE'),
                            'url' => $this->buildBreadcrumbURL('dropclass', $options, true)
                        );
                    }
    		    }
    		    
    		    $this->assign('links', $links);
                
                
            	break;

        	case 'roster':
        	    if (!$course = $this->getCourseByArgs()) {
        	        $this->redirectTo('course');
        	    }

        		$students = $course->getStudents();
        		$links = array();
        		foreach ($students as $student){
        			$value = $student->getFullName();
        			$link = Kurogo::moduleLinkForValue('people', $value, $this, $student);
        			if(!$link){
        				$link = array(
                			'title' => $value,
                		);	
        			}
        			$links[] = $link;
        		}
        		$this->assign('links',$links);
        		break;
        		
        	case 'dropclass';
        	    if (!$course = $this->getCourseFromArgs()) {
        	        $this->redirectTo('course');
        	    }
        	    
        	    $options = $this->getCourseOptions();

        		$this->assign('dropTitle', $this->getLocalizedString('NOTIFICATION',$course->getTitle()));
        		
        		$links = array();
        		$links[] = array(
        			    'title'=>$this->getLocalizedString('DROP_CONFIRM'),
        			    'url'=>$this->buildBreadcrumbURL('dropclass', $options, false)
        		);
        		$links[] = array(
        		    'title'=>$this->getLocalizedString('DROP_CANCEL'),
        		    'url'=>$this->buildBreadcrumbURL('info', $options, false)
        		);
				$this->assign('links',$links);
        	    break;
        	    
            case 'catalog':
                if ($areas = $this->controller->getCatalogAreas()) {
                    $areasList = array();
                    foreach ($areas as $CourseArea) {
                        $areasList[] = array(
                            'title'=>$CourseArea->getTitle(),
                            'url'=>$this->buildBreadcrumbURL('area',array('area'=>$CourseArea->getCode()), false)
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
                $courses = $this->controller->getCourses('Catalog',array('area' => end($areas)));
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
            
            case 'updates':
                
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }
				
                $this->assign('title', $course->getTitle());

                if ($contentCourse = $course->getCourse('content')) {
                    $items = $contentCourse->getUpdates();
                    $contents = array();
                    foreach ($items as $item){
                        $contents[] = $this->linkForUpdate($item, array('courseID' => $courseID));
                    }
                    $this->assign('contents', $contents);
                }
                    
                

    /*
                $linkToInfoTab = $this->buildBreadcrumbURL('info',array('courseID'=> $courseID), false);
                $this->assign('linkToInfoTab',$linkToInfoTab);
                $linkToResourcesTab = $this->buildBreadcrumbURL('resources',array('courseID'=> $courseID), false);
                $this->assign('linkToResourcesTab',$linkToResourcesTab);
                */
                
                //$linkToInfoTab = $this->buildBreadcrumbURL('info',array('id'=> $courseID), false);
                //$this->assign('linkToInfoTab',$linkToInfoTab);
                             /*              
                $contentTypes = array();
                if ($contents = $this->course->getCourseContentById($courseID)) {

                    $options = array(
                        'id'      => $courseID,
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
            case 'resources':
        	    if (!$course = $this->getCourseByArgs()) {
        	        $this->redirectTo('course');
        	    }
				
                $this->assign('title', $course->getTitle());
                
                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('course');
                }
                

                $resources = array();
                $seeAllLinks = array();
                $items = $contentCourse->getResources();
                foreach ($items as $itemkey => $item){
                    $resources[] = $this->linkForResource($item, $options);
                }
                $this->assign('resources',$resources);
                
            	$linkToUpdateTab = $this->buildBreadcrumbURL('course', array('courseID'=> $courseID, 'type'=>'content'), false);
            	$this->assign('linkToUpdateTab',$linkToUpdateTab);
            	$linkByTopic = $this->buildBreadcrumbURL('resource', array('courseID'=> $courseID, 'type'=>'topic'), false);
            	$linkByDate = $this->buildBreadcrumbURL('resource', array('courseID'=> $courseID, 'type'=>'date'), false);
            	$this->assign('linkByTopic',$linkByTopic);
            	$this->assign('linkByDate',$linkByDate);
            	$linkToInfoTab = $this->buildBreadcrumbURL('info',array('courseID'=> $courseID), false);
                $this->assign('linkToInfoTab',$linkToInfoTab);
            	$this->assign('type', $type);
            	break;
            case 'resourceSeeAll':
            	$id = $this->getArg('id');
                $type = $this->getArg('type');
                $key = urldecode($this->getArg('key'));
                $options = array(
                	'courseID' => $id,
                );
                $this->controller->setType($type);
                $items = $this->controller->getResource($id);
                $resources = array();
                foreach ($items as $itemkey => $item){
                	foreach ($item as $resource){
                		if($key == $itemkey)
                		$resources[$itemkey][] = $this->linkForResource($resource,$options);
                	}
                }
                $this->assign('resources',$resources);
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
            case 'file':
                $contentID = $this->getArg('contentID');
                $courseID = $this->getArg('courseID');
                $term = $this->assignTerm();
                
                $options = array(
                    'term'=>strval($term)
                );
                
                if (!$course = $this->controller->getCourseByCommonID($courseID, $options)) {
                    $this->redirectTo('index');
                }
                
                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('course');
                }
                
//				$contents = $course->getResource($courseID);
                if (!$content = $contentCourse->getContentById($contentID)) {
                    throw new KurogoDataException($this->getLocalizedString('ERROR_CONTENT_NOT_FOUND'));
                }
                
                $options[] = array(
                		'url' => $this->controller->getFileUrl($content),
                		'title' => $content->getFileName(),
                		'subtitle' => 'FileSize:' . round($content->getFileSize()/1024,2) .'Kb',
                );
                if($content->getPublishedDate()){
		    		if($content->getAuthor()){
		    			$uploadDate = 'Updated '. $this->elapsedTime($content->getPublishedDate()->format('U')) .' by '.$content->getAuthor();
		    		}else{
		    			$uploadDate = 'Updated '. $this->elapsedTime($content->getPublishedDate()->format('U'));
		    		}
	    		} else {
	    			$uploadDate = $content->getSubTitle();
	    		}
	    		$this->assign('itemName',$content->getTitle());
	    		$this->assign('uploadDate',$uploadDate);
	    		$this->assign('description',$content->getDescription());
                // about the fileSize may i add function about clac filesize in CoursesDatamodel or in some file?
                $this->assign('options',$options);
                $url = $this->controller->getDownLoadTypeContent($content, $courseID);
                $this->assign('url',$url);
                //$this->outputFile($content);
                break;
                
            case 'index':
                $Term = $this->assignTerm();
                $courses = array();
                $options = array(
                    'term'=>$Term,
                    'types'=>array('content','registration')
                );
                
                $this->assign('hasPersonalizedCourses', $this->controller->canRetrieve('registration') || $this->controller->canRetrieve('content'));
                if ($this->isLoggedIn()) {
                    if ($items = $this->controller->getCourses($options)) {
                    	foreach ($items as $item) {
                            $course = $this->linkForCourse($item, array('term'=>strval($Term)));
                            $courses[] = $course;
                        }
                    }
                    $this->assign('courses', $courses);
                } else {
                    $this->assign('loginLink', $this->buildURLForModule('login','', $this->getArrayForRequest()));
                    $this->assign('loginText', $this->getLocalizedString('SIGN_IN_SITE', Kurogo::getSiteString('SITE_NAME')));
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
 
