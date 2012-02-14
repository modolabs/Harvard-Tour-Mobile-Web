<?php
includePackage('Courses');

class CoursesWebModule extends WebModule {
    protected $id = 'courses'; 
    protected $controller;
    protected $courses;
    protected $hasPersonalizedCourses = false;
    
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
    
    // returns a link for a particular resource
    public function linkForContent($resource, CourseContentCourse $course) {
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

        $options = $this->getCourseOptions();
        $options['contentID'] = $resource->getID();
            
        $link['url'] = $this->buildBreadcrumbURL('content', $options, true);
            
        return $link;
    }
    
    public function linkForUpdate(CourseContent $content, CourseContentCourse $course, $includeCourseName=false) {

        $contentID = $content->getID();
        $options = array(
            'courseID'  => $course->getCommonID(),
            'contentID' => $contentID
        );
        $link = array(
            'title' => $includeCourseName ? $course->getTitle() : $content->getTitle(),
            'class' => "content_" . $content->getContentType()
        );
        foreach (array('courseID') as $field) {
            if (isset($data[$field])) {
                $options[$field] = $data[$field];
            }
        }
        $subtitle = array();
        if ($includeCourseName) {
            $subtitle[] = $content->getTitle();
        }

        if ($content->getSubtitle()) {
            $subtitle[] = $content->getSubTitle();
        }
        
        if ($content->getPublishedDate()){
            $published = 'Updated '. $this->elapsedTime($content->getPublishedDate()->format('U'));
            if ($content->getAuthor()) {
                $published .= ' by '.$content->getAuthor();
            }
            $subtitle[] = $published;
        }
        
        $link['subtitle'] = implode("<br />", $subtitle);
        $link['url'] = $this->buildBreadcrumbURL('content', $options, true);
        return $link;
    }
    
    protected function linkForCatalogArea(CourseArea $area, $options=array()) {
        $options = array_merge($options,array(
            'area'=>$area->getCode()
            )
        );
        $link = array(
            'title'=> $area->getTitle(),
            'url'=>$this->buildBreadcrumbURL('area',$options, true)
        );
        return $link;
    }
    
    protected function linkForCourse(CourseInterface $course, $options=array()) {
        
        $options = array_merge($options, array(
            'courseID'  => $course->getID()
            )
        );
    
        $link = array(
            'title' => $course->getTitle()
        );

        if ($contentCourse = $course->getCourse('content')) {
            $page = 'updates';
            $subtitle = array();
            if ($lastUpdateContent = $contentCourse->getLastUpdate()) {
                $subtitle[] = $lastUpdateContent->getTitle();
                if ($publishedDate = $lastUpdateContent->getPublishedDate()) {
                    $published = $this->elapsedTime($publishedDate->format('U'));
                    if ($lastUpdateContent->getAuthor()) {
                        $published = '<span class="author">'. $lastUpdateContent->getAuthor() .', ' . $published . "</span>";
                    }
                    $subtitle[] = $published;
                }
            } else {
                $subtitle[] = $this->getLocalizedString('NO_UPDATES');
            }
            
            $link['subtitle'] = implode("<br />", $subtitle);
        } else {
            $page = 'info';
        }
        
        $link['url'] = $this->buildBreadcrumbURL($page, $options , false);
        return $link;
    }
    
    protected function getContentLinks(CourseContent $content) {
        $links = array();
        switch ($content->getContentType()) {
            case 'link':
                $links[] = array(
                    'title'=>'Follow Link',
                    'subtitle'=>$content->getURL(),
                    'url'=>$content->getURL()
                );
                break;
            case 'file':
                $options = $this->getCourseOptions();
                $options['contentID'] = $content->getID();
                $links[] = array(
                    'title'=>'Download File',
                    'subtitle'=>$content->getFilename(),
                    'url'=>$this->buildBreadcrumbURL('download', $options, false)
                );
                break;
            
            default:
                KurogoDebug::debug($content, true);
        }
        
        return $links;
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
    
    protected function assignIndexTabs(){
        $courseTabs = array();
        $courseTabs['index'] = array(
            'title'=>$this->getLocalizedString('INDEX_TAB_COURSES'),
            'url'=> $this->buildBreadcrumbURL('index', array(), false)
        );

        if ($this->hasPersonalizedCourses && $this->isLoggedIn()) {
            $courseTabs['allupdates'] = array(
                'title'=>$this->getLocalizedString('INDEX_TAB_UPDATES'),
                'url'=> $this->buildBreadcrumbURL('allupdates', array(), false)
            );
            $courseTabs['alltasks'] = array(
                'title'=>$this->getLocalizedString('INDEX_TAB_TASKS'),
                'url'=> $this->buildBreadcrumbURL('alltasks', array(), false)
            );
        }
        $this->assign('courseTabs', $courseTabs);
    }

    protected function getCourseFromArgs() {

        $courseID = $this->getArg('courseID');
        $term = $this->assignTerm();
        $options = $this->getCourseOptions();
        
        if ($course = $this->controller->getCourseByCommonID($courseID, $options)) {
            $this->assign('courseTitle', $course->getTitle());
            $this->assign('courseID', $course->getID());
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
    	
        $this->feeds = $this->loadFeedData();
        $this->controller = CoursesDataModel::factory('CoursesDataModel', $this->feeds);
        $this->hasPersonalizedCourses =  $this->controller->canRetrieve('registration') || $this->controller->canRetrieve('content');
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
            	
            case 'content':
            case 'download':
                $contentID = $this->getArg('contentID');
                
        	    if (!$course = $this->getCourseFromArgs()) {
        	        $this->redirectTo('course');
        	    }
        	    				                
                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('course');
                }

                if (!$content = $contentCourse->getContentById($contentID)) {
                    throw new KurogoDataException($this->getLocalizedString('ERROR_CONTENT_NOT_FOUND'));
                }
                
                if ($this->page=='download') {
                    if ($content->getContentType()=='file') {
                        $file = $contentCourse->getFileForContent($contentID);
                        header('Content-type: ' . mime_type($file));
                        readfile($file);
                        die();
                    } else {
                        throw new KurogoException("Cannot download content of type " . $content->getContentType());
                    }
                }
                
                $this->assign('contentType', $content->getContentType());
                $this->assign('contentTitle', $content->getTitle());
                $this->assign('contentDescription', $content->getDescription());        	    

                $links = $this->getContentLinks($content);
                $this->assign('links', $links);
                
                break;
            
        	case 'roster':
        	    if (!$course = $this->getCourseFromArgs()) {
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
                $this->assign('catalogHeader', $this->getOptionalModuleVar('catalogHeader','','catalog'));
                $this->assign('catalogFooter', $this->getOptionalModuleVar('catalogFooter','','catalog'));
                
                break;
                
            case 'area':
                $baseArea = '';
                $area = $this->getArg('area');
                if (!$CourseArea = $this->controller->getCatalogArea($area)) {
                    $this->redirectTo('catalog', array());
                }

                $areas = $CourseArea->getAreas();
                
                $areasList = array();
                foreach ($areas as $CourseArea) {
                    $areasList[] = $this->linkForCatalogArea($CourseArea);
                }

                $Term = $this->assignTerm();
                $courses = array();
                $options = array(
                    'term'=>$Term,
                    'types'=>array('catalog'),
                    'area'=>$area
                );
                
                $courses = $this->controller->getCourses($options);
                $coursesList = array();
 
                foreach ($courses as $item) {
                    $course = $this->linkForCourse($item, array('term'=>strval($Term)));
                    $coursesList[] = $course;
                }

                $this->assign('areaTitle', $CourseArea->getTitle());                
                $this->assign('description', $CourseArea->getDescription());
                $this->assign('areas', $areasList);
                $this->assign('courses', $coursesList);
                
                break;
            
            case 'allupdates':
                $Term = $this->assignTerm();
                $this->assignIndexTabs();

                $contents = array();
                $courses = $this->controller->getCourses(array());
                foreach($courses as $course){
                    if ($contentCourse = $course->getCourse('content')) {
                        $items = $contentCourse->getUpdates();
                        foreach ($items as $item){
                            $contents[] = $this->linkForUpdate($item, $contentCourse, true);
                        }
                    }
                }
                $this->assign('contents', $contents);
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
                        $contents[] = $this->linkForUpdate($item, $contentCourse, false);
                    }
                    $this->assign('contents', $contents);
                }
                    
                break;

            case 'resources':
        	    if (!$course = $this->getCourseFromArgs()) {
        	        $this->redirectTo('course');
        	    }
        	    				                
                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('course');
                }
                                
                //@TODO make this configurable
                $groupLinks = array();
                $groups = array('topic','date','type');
                foreach ($groups as $group) {
                    $groupOptions = $this->getCourseOptions();
                    $groupOptions['group'] = $group;
                    $groupLinks[$group] = $this->buildBreadcrumbURL($this->page, $groupOptions, false);
                }
                $this->assign('groupLinks', $groupLinks);

                $group = $this->getArg('group', $groups[0]);
                $options = array(
                    'group'=>$group
                );
                
                $resources = array();
                $groups = $contentCourse->getResources($options);
                foreach ($groups as $groupTitle => $items){
                    $groupItems = array();
                    foreach ($items as $item) {
                        $groupItems[] = $this->linkForContent($item, $contentCourse);
                    }
                    
                    $resources[] = array(
                        'title'=>$groupTitle,
                        'items'=>$groupItems
                    );
                }

                $this->assign('resources',$resources);
                $this->assign('group', $group);
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
                		$resources[$itemkey][] = $this->linkForContent($resource,$options);
                	}
                }
                $this->assign('resources',$resources);
            	break;
            	
            case 'contents':
                KurogoDebug::debug($this, true);
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
        	    if (!$course = $this->getCourseFromArgs()) {
        	        $this->redirectTo('course');
        	    }
        	    				                
                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('course');
                }

                if (!$content = $contentCourse->getContentById($contentID)) {
                    throw new KurogoDataException($this->getLocalizedString('ERROR_CONTENT_NOT_FOUND'));
                }
                
                

                if ($this->getArg('download')) {
                    throw new KurogoException('Download of files is not complete');
                }                                
                                
                $options[] = array(
                		'url' => $this->buildBreadcrumbURL($this->page, array_merge($this->args, array('download'=>1)), true),
                		'title' => $content->getFileName(),
                		'subtitle' => 'FileSize: ' . number_format($content->getFileSize())
                );
                
                if ($content->getPublishedDate()){
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
	    		$this->assign('links', $options);
	    		$this->assign('description',$content->getDescription());                //$this->outputFile($content);
                break;
                
            case 'alltasks':
                $Term = $this->assignTerm();
                $this->assignIndexTabs();
                break;
                
            case 'index':
                $Term = $this->assignTerm();
                $courses = array();
                $options = array(
                    'term'=>$Term,
                    'types'=>array('content','registration')
                );

                
                // assign tabs
                $this->assignIndexTabs();
                $this->assign('hasPersonalizedCourses', $this->hasPersonalizedCourses);

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
 
