<?php
includePackage('Courses');
includePackage('DateTime');
class CoursesWebModule extends WebModule {
    protected $id = 'courses'; 
    protected $controller;
    protected $courses;
    protected $hasPersonalizedCourses = false;
    protected $selectedTerm;
    
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

    public function linkForTask($task, CourseContentCourse $course, $includeCourseName=false) {
    	$link = array(
            'title' =>$includeCourseName ? $course->getTitle() : $task->getTitle()
        );
        
        $subtitle = array();
        if ($includeCourseName) {
            $subtitle[] = $task->getTitle();
        }

        if($date = $task->getDate()) {
            $subtitle[] = DateFormatter::formatDate($date, DateFormatter::MEDIUM_STYLE, DateFormatter::NO_STYLE);
	    } else {
	    	$subtitle[] = $task->getSubTitle();
	    } 

        $options = $this->getCourseOptions();
        $options['taskID'] = $task->getID();
            
        $link['url'] = $this->buildBreadcrumbURL('task', $options, true);
        $link['subtitle'] = implode("<br />", $subtitle);
            
        return $link;
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
            'contentID' => $contentID,
            'type'      => $content->getContentType(),
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
            case 'announcement':
                break;
            default:
                KurogoDebug::debug($content, true);
        }
        
        return $links;
    }
    
    public function assignTerm(){
        $feedTerms = $this->controller->getAvailableTerms();

        if (!$Term = $this->controller->getTerm($this->selectedTerm)) {
            $Term = $this->controller->getCurrentTerm();
        }

        $terms = array();
        foreach($feedTerms as $term) {
            $terms[] = array(
                'value'     => $term->getID(),
                'title'     => $term->getTitle(),
                'selected'  => ($Term->getID() == $term->getID()),
            );
        }

        if (count($terms)>1) {
            $this->assign('sections', $terms);
        }
        $this->assign('termTitle', $Term->getTitle());
        return $Term;
    }
    
    protected function assignIndexTabs($options = array()){
        $courseTabs = array();
        $courseTabs['index'] = array(
            'title'=>$this->getLocalizedString('INDEX_TAB_COURSES'),
            'url'=> $this->buildBreadcrumbURL('index', $options, false)
        );

        if ($this->hasPersonalizedCourses && $this->isLoggedIn()) {
            $courseTabs['allupdates'] = array(
                'title'=>$this->getLocalizedString('INDEX_TAB_UPDATES'),
                'url'=> $this->buildBreadcrumbURL('allupdates', $options, false)
            );
            $courseTabs['alltasks'] = array(
                'title'=>$this->getLocalizedString('INDEX_TAB_TASKS'),
                'url'=> $this->buildBreadcrumbURL('alltasks', $options, false)
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

                $courseTabs['tasks'] = array(
                    'title'=>$this->getLocalizedString('COURSE_TAB_TASKS'),
                    'url'=> $this->buildBreadcrumbURL('tasks', $options, false)
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

    protected function detailURLForBookmark($aBookmark) {
        return $this->buildBreadcrumbURL('info', array(
            'courseID'  => $this->getBookmarkParam($aBookmark, 'id'),
            'term'      => $this->getBookmarkParam($aBookmark, 'term'),
        ));
    }

    protected function getTitleForBookmark($aBookmark) {
        return $this->getBookmarkParam($aBookmark, 'title');
    }

    protected function getBookmarkParam($aBookmark, $param){
        parse_str($aBookmark, $params);
        if(isset($params[$param])){
            return $params[$param];
        }
        return null;
    }

    protected function initialize() {
        $this->assign('loggedIn', $this->isLoggedIn());
        $this->feeds = $this->loadFeedData();
        $this->controller = CoursesDataModel::factory('CoursesDataModel', $this->feeds);
        $this->hasPersonalizedCourses =  $this->controller->canRetrieve('registration') || $this->controller->canRetrieve('content');
        $this->selectedTerm = $this->getArg('term', CoursesDataModel::CURRENT_TERM);
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
    
    protected function assignGroupLinks($groups, $defaultGroupOptions = array()){
        foreach ($groups as $groupIndex => $group) {
            $defaultGroupOptions['group'] = $groupIndex;
            $groupLinks[$groupIndex]['url'] = $this->buildBreadcrumbURL($this->page, $defaultGroupOptions, false);
            $groupLinks[$groupIndex]['title'] = $group['title'];
        }
        $this->assign('groupLinks', $groupLinks);
    }

    protected function initializeForPage() {
        switch($this->page) {
        	case 'info':
        	    
        	    if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
        	    }
        	    $options = $this->getCourseOptions();
        	    
                // Bookmark
                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $cookieParams = array(
                        'title' => $course->getTitle(),
                        'term'  => rawurlencode($options['term']),
                        'id'    => rawurlencode($options['courseID']),
                    );

                    $cookieID = http_build_query($cookieParams);
                    $this->generateBookmarkOptions($cookieID);
                }

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
                    $this->redirectTo('index');
        	    }
        	    				                
                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('index');
                }

                $options['type'] = $this->getArg('type');
                if (!$content = $contentCourse->getContentById($contentID, $options)) {
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
            case 'task':
                $taskID = $this->getArg('taskID');
                
        	    if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
        	    }
        	    				                
                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('index');
                }

                if (!$task = $contentCourse->getTaskById($taskID)) {
                    throw new KurogoDataException($this->getLocalizedString('ERROR_TASK_NOT_FOUND'));
                }
                                
                $this->assign('taskTitle', $task->getTitle());
                $this->assign('taskDescription', $task->getDescription());        	    
                $this->assign('taskDate', DateFormatter::formatDate($task->getDate(), DateFormatter::MEDIUM_STYLE, DateFormatter::NO_STYLE));
                $this->assign('taskDueDate', DateFormatter::formatDate($task->getDueDate(), DateFormatter::MEDIUM_STYLE, DateFormatter::NO_STYLE));
                $this->assign('links', $task->getLinks());

                break;
            
        	case 'roster':
        	    if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
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
                    $this->redirectTo('index');
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
                if (!$this->isLoggedIn()) {
                    $this->redirectTo('index');
                }
                $Term = $this->assignTerm();
                $this->assignIndexTabs(array('term'=>$Term->getID()));

                $contents = array();
                $courses = $this->controller->getCourses(array());
                foreach($courses as $course){
                    if ($contentCourse = $course->getCourse('content')) {
                        if($items = $contentCourse->getUpdates()){
                            foreach ($items as $item){
                                $contents[] = $this->linkForUpdate($item, $contentCourse, true);
                            }
                        }
                    }
                }
                $this->assign('contents', $contents);
                break;
            
            case 'alltasks':
                if (!$this->isLoggedIn()) {
                    $this->redirectTo('index');
                }
                $Term = $this->assignTerm();
                $this->assignIndexTabs(array('term'=>$Term->getID()));

                //@TODO make this configurable
                $groups = array('date','priority','course');
                $this->assignGroupLinks($groups);

                $group = $this->getArg('group', $groups[0]);
                $tasks = array();
                $courses = $this->controller->getCourses(array());
                foreach($courses as $course){
                    if ($contentCourse = $course->getCourse('content')) {
                        $items = $contentCourse->getTasks();
                        foreach ($items as $item){
                            $tasks[] = $this->linkForTask($item, $contentCourse, true);
                        }
                    }
                }
                $this->assign('tasks', $tasks);
                $this->assign('group', $group);
                break;

            case 'tasks':

                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }

                $this->assignTerm();

                //@TODO make this configurable
                $groups = array('date','priority','course');
                $this->assignGroupLinks($groups, $this->getCourseOptions());

                $group = $this->getArg('group', $groups[0]);

                $tasks = array();
                if ($contentCourse = $course->getCourse('content')) {
                    $items = $contentCourse->getTasks();
                    foreach ($items as $item){
                        $tasks[] = $this->linkForTask($item, $contentCourse);
                    }
                }
                $this->assign('tasks', $tasks);
                $this->assign('group', $group);
                break;
                
            case 'updates':
                
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }
				
                $this->assignTerm();

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
                    $this->redirectTo('index');
        	    }

                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('index');
                }

                $this->assignTerm();

                $groupsConfig = $this->getModuleSections('resources');
                $availableGroups = array_keys($groupsConfig);
                $this->assignGroupLinks($groupsConfig, $this->getCourseOptions());

                $group = $this->getArg('group', $availableGroups[0]);
                $options = array(
                    'group'=>$group
                );
                
                $resources = array();
                $groups = $contentCourse->getResources($options);
                $limit = $groupsConfig[$group]['max_items'];
                $seeAllLinks = array();

                foreach ($groups as $groupTitle => $items){
                    $hasMoreItems = false;
                    $index = 0;
                    $groupItems = array();
                    foreach ($items as $item) {
                        if($index >= $limit && $limit != 0){
                            break;
                        }
                        $groupItems[] = $this->linkForContent($item, $contentCourse);
                        $index++;
                    }
                    $resource = array(
                        'title' => $groupTitle,
                        'items' => $groupItems,
                        'count' => count($items),
                    );
                    if(count($items) > $limit && $limit != 0){
                        $courseOptions = $this->getCourseOptions();
                        $courseOptions['group'] = $group;
                        $courseOptions['key'] = $groupTitle;
                        $resource['url'] = $this->buildBreadcrumbURL('resourceSeeAll', $courseOptions);
                    }
                    $resources[] = $resource;
                }

                $this->assign('resources',$resources);
                $this->assign('group', $group);
            	break;
            	
            case 'resourceSeeAll':
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }

                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('index');
                }
                $key = $this->getArg('key');
                $group = $this->getArg('group');
                $groups = $contentCourse->getResources(array('group'=>$group));
                $items = $groups[$key];

                $resources = array();
                foreach ($items as $item){
                    $resources[] = $this->linkForContent($item, $contentCourse);
                }
                $this->assign('key', $key);
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
                    $this->redirectTo('index');
        	    }
        	    				                
                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('index');
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

            case 'bookmarks':
                $bookmarks = array();
                if($this->hasBookmarks()){
                    foreach ($this->getBookmarks() as $aBookmark) {
                        if ($aBookmark) {
                            // prevent counting empty string
                            $bookmarks[] = array(
                                'title' => $this->getTitleForBookmark($aBookmark),
                                'url' => $this->detailURLForBookmark($aBookmark),
                            );
                        }
                    }
                    $this->assign('navItems', $bookmarks);
                }
                $this->assign('hasBookmarks', $this->hasBookmarks());
                break;
                
            case 'index':
                $Term = $this->assignTerm();
                $courses = array();
                $options = array(
                    'term'=>$Term,
                    'types'=>array('content','registration')
                );

                
                // assign tabs
                $this->assignIndexTabs(array('term'=>$Term->getID()));
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
                        'url'   => $this->buildBreadcrumbURL('catalog', array(), false),
                    );
                    
                    if ($bookmarks = $this->getBookmarks()) {
                        $catalogItems[] = array(
                            'title' => $this->getLocalizedString('BOOKMARKED_COURSES') . " (" . count($bookmarks) . ")",
                            'url'   => $this->buildBreadcrumbURL('bookmarks', array(), true),
                        );
                    }
                }
                $this->assign('catalogItems', $catalogItems);
                break;
        }
    }
}
 
