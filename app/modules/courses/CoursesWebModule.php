<?php
includePackage('Courses');
includePackage('DateTime');
class CoursesWebModule extends WebModule {
    protected $id = 'courses'; 
    protected $controller;
    protected $courses;
    protected $hasPersonalizedCourses = false;
    protected $selectedTerm;
    protected $detailFields = array();
    
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

        if($task->getPublishedDate()){
            if($task->getAuthor()){
                $subtitle[] = 'Updated '. $this->elapsedTime($task->getPublishedDate()->format('U')) .' by '.$task->getAuthor();
            }else{
                $subtitle[] = 'Updated '. $this->elapsedTime($task->getPublishedDate()->format('U'));
            }
        } else {
            $subtitle[] = $task->getSubTitle();
        }

        $options = $this->getCourseOptions();
        $options['taskID'] = $task->getID();
        $options['courseID'] = $course->getCommonID();
            
        $link['url'] = $this->buildBreadcrumbURL('task', $options, true);
        $link['subtitle'] = implode("<br />", $subtitle);
            
        return $link;
    }
    
    // returns a link for a particular resource
    public function linkForContent($resource, CourseContentCourse $course) {
    	$link = array(
            'title' => $resource->getTitle(),
            'subtitle' => $resource->getSubTitle(),
            'class' => "content content_" . $resource->getContentType(),
            'img'   => "/modules/courses/images/content_" . $resource->getContentType() . $this->imageExt
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
            'class' => "content content_" . $content->getContentType(),
            'img'   => "/modules/courses/images/content_" . $content->getContentType() . $this->imageExt
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
        
        $link['sortDate'] = $content->getPublishedDate() ? $content->getPublishedDate() : 0;

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

        $contentCourse = $course->getCourse('content');
        if ($contentCourse) {
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
                $link['class'] = 'content_'.$lastUpdateContent->getContentType();
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

    protected function linkForCatalogCourse(CourseInterface $course, $options = array()) {
        $options = array_merge($options, array(
            'courseID'  => $course->getID()
            )
        );
    
        $link = array(
            'title' => $course->getTitle()
        );

        $link['url'] = $this->buildBreadcrumbURL('info', $options , false);
        return $link;
    }
    
    protected function getContentLinks(CourseContent $content) {
        $links = array();
        switch ($content->getContentType()) {
            case 'link':
                $links[] = array(
                    'title'=>$content->getTitle(),
                    'subtitle'=>$content->getURL(),
                    'url'=>$content->getURL(),
                    'class'=>'external',
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
            case 'task':
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

        $this->controller->setCurrentTerm($Term);

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
        $tabsConfig = $this->getModuleSections('indextabs');
        foreach($tabsConfig as $page => $tab){
            if($tab['protected'] == 0){
                $courseTabs[$page] = array(
                    'title' => $tab['title'],
                    'url'   => $this->buildBreadcrumbURL($page, $options, false),
                );
            }
        }

        if ($this->hasPersonalizedCourses && $this->isLoggedIn()) {
            foreach($tabsConfig as $page => $tab){
                if($tab['protected'] == 1){
                    $courseTabs[$page] = array(
                        'title' => $tab['title'],
                        'url'   => $this->buildBreadcrumbURL($page, $options, false),
                    );
                }
            }
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
                $tabsConfig = $this->getModuleSections('coursetabs');
                foreach($tabsConfig as $page => $tab){
                    $courseTabs[$page] = array(
                        'title' => $tab['title'],
                        'url'   => $this->buildBreadcrumbURL($page, $options, false),
                    );
                }
            }
                        
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
            'area'      => $this->getBookmarkParam($aBookmark, 'area'),
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
        //load page detail configs
        $this->detailFields = $this->loadPageConfigFile('info', 'detailFields');
    }
    
    protected function getCourseOptions() {
        $courseID = $this->getArg('courseID');
        $area = $this->getArg('area');
        $term = $this->assignTerm();
        
        $options = array(
            'courseID' => $courseID,
            'term' => strval($term),
            'area' => $area
        );
        
        return $options;
    }
    
    protected function assignGroupLinks($groups, $defaultGroupOptions = array()){
        foreach ($groups as $groupIndex => $group) {
            $defaultGroupOptions['group'] = $groupIndex;
            $groupLinks[$groupIndex]['url'] = $this->buildBreadcrumbURL($this->page, $defaultGroupOptions, false);
            $groupLinks[$groupIndex]['title'] = $group['title'];
        }
        $tabCount = count($groups);
        $tabCountMap = array(
            1   => 'one',
            2   => 'two',
            3   => 'three',
            4   => 'four',
            5   => 'five',
        );
        $this->assign('tabCount', $tabCountMap[$tabCount]);
        $this->assign('groupLinks', $groupLinks);
    }

    protected function sortUpdatesByDate($updates){
        if(empty($updates)){
            return array();
        }
        uasort($updates, array($this, 'sortByDate'));
        return $updates;
    }

    private function sortByDate($updateA, $updateB){
        $updateA_time = $updateA['sortDate'] ? $updateA['sortDate']->format('U') : 0;
        $updateB_time = $updateB['sortDate'] ? $updateB['sortDate']->format('U') : 0;
        if($updateA_time == $updateB_time){
            return 0;
        }
        return ($updateA_time > $updateB_time) ? -1 : 1;
    }

    protected function formatCourseDetails(CombinedCourse $course) {
        //error_log(print_r($this->detailFields, true));
        
        $details = array();    
        
        foreach($this->detailFields as $key => $info) {
            $section = $this->formatCourseDetail($course, $info, $key);
            
            if (count($section)) {
                if (isset($info['section'])) {
                    if (!isset($details[$info['section']])) {
                        $details[$info['section']] = $section;
                    } else {
                        $details[$info['section']] = array_merge($details[$info['section']], $section);
                    }
                } else {
                    $details[$key] = $section;
                }
            }
        }
        //error_log(print_r($details, true));
        return $details;
    }
    
    protected function formatCourseDetail(CombinedCourse $course, $info, $key=0) {
        $section = array();
        $courseType = isset($info['courseType']) ? $info['courseType'] : null;
        
        if (count($info['attributes']) == 1) {
            $values = (array)$course->getField($info['attributes'][0], $courseType);
            if (count($values)) {
                $section[$key] = $this->formatDetail($this->formatValues($values, $info), $info, $course);
            }      
        } else {
            $valueGroups = array();
        
            foreach ($info['attributes'] as $attribute) {
                $values = $this->formatValues((array)$course->getField($attribute, $courseType), $info);
            
                if (count($values)) {
                    foreach ($values as $i => $value) {
                        $valueGroups[$i][] = $value;
                    }
                }
            }
          
            foreach ($valueGroups as $valueGroup) {
                $section[$key] = $this->formatDetail($valueGroup, $info, $course);
            }
        }
        
        return $section;
    }
    
    protected function formatDetail($values, $info, CombinedCourse $course) {
        if (isset($info['format'])) {
            $value = vsprintf($this->replaceFormat($info['format']), $values);
        } else {
            $delimiter = isset($info['delimiter']) ? $info['delimiter'] : ' ';
            $value = implode($delimiter, $values);
        }
    
        $detail = array(
            'label' => isset($info['label']) ? $info['label'] : '',
            'title' => $value
        );
    
        switch(isset($info['type']) ? $info['type'] : 'text') 
        {
            case 'email':
                $detail['title'] = str_replace('@', '@&shy;', $detail['title']);
                $detail['url'] = "mailto:$value";
                $detail['class'] = 'email';
                break;
        
            case 'phone':
                $detail['title'] = str_replace('-', '-&shy;', $detail['title']);
                
                if (strpos($value, '+1') !== 0) { 
                    $value = "+1$value"; 
                }
                $detail['url'] = PhoneFormatter::getPhoneURL($value);
                $detail['class'] = 'phone';
                break;
 
            // compatibility
            case 'map':
                $info['module'] = 'map';
                break;
        }

        if (isset($info['module'])) {
            $detail = array_merge($detail, Kurogo::moduleLinkForValue($info['module'], $value, $this, $person));
        }
        
        if (isset($info['urlfunc'])) {
            $urlFunction = create_function('$value,$course', $info['urlfunc']);
            $detail['url'] = $urlFunction($value, $course);
        }
    
        $detail['title'] = nl2br($detail['title']); 
        return $detail;
    }
    
    protected function formatValues($values, $info) {
        if (isset($info['parse'])) {
            $formatFunction = create_function('$value', $info['parse']);
            foreach ($values as &$value) {
                $value = $formatFunction($value);
            }
        }
        
        return $values;
    }
    
    protected function replaceFormat($format) {
        return str_replace(array('\n','\t'),array("\n","\t"), $format);
    }
    
    protected function initializeForPage() {
        switch($this->page) {
        	case 'info':
        	    
        	    if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
        	    }
                if (!$contentCourse = $course->getCourse('content')) {
                    if (!$contentCourse = $course->getCourse('catalog')) {
	                    $this->redirectTo('index');
	                }
                }
                if($description = $contentCourse->getDescription()){
                    $description = array(array('title'=>$description));
                    $this->assign('description', $description);
                }
        	    $options = $this->getCourseOptions();
        	    
        	    $courseDetails =  $this->formatCourseDetails($course);
        	    $this->assign('courseDetails', $courseDetails);
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
                	$link['class'] = 'people';
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

                $links[] = array(
                    'title' => 'Roster',
                    'url'   => $this->buildBreadcrumbURL('roster', $this->getCourseOptions()),
                );
                $links[] = array(
                    'title' => 'Course Materials',
                    'url'   => $this->buildBreadcrumbURL('index', array()),
                );
    		    
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
                        if($this->controller->getRetriever('content')->shouldDownloadFile()){
                            header('Content-type: ' . mime_type($file));
                            readfile($file);
                            die();
                        }
                        header('Location: '.$file);
                        die();
                    } else {
                        throw new KurogoException("Cannot download content of type " . $content->getContentType());
                    }
                }
                
                $this->setPageTitle($this->getLocalizedString('CONTENT_TYPE_TITLE_'.strtoupper($content->getContentType())));
                $this->assign('courseTitle', $course->getTitle());
                $this->assign('contentType', $content->getContentType());
                $this->assign('contentTitle', $content->getTitle());
                $this->assign('contentDescription', $content->getDescription());        	    
                if($content->getAuthor()){
                    $this->assign('contentAuthor', 'Posted by ' . $content->getAuthor());
                }
                if($content->getPublishedDate()){
                    $this->assign('contentPublished', $this->elapsedTime($content->getPublishedDate()->format('U')));
                }

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
                if($task->getPublishedDate()){
                    $this->assign('taskDate', 'Published: '.DateFormatter::formatDate($task->getPublishedDate(), DateFormatter::LONG_STYLE, DateFormatter::NO_STYLE));
                }
                if($task->getDueDate()){
                    $this->assign('taskDueDate', DateFormatter::formatDate($task->getDueDate(), DateFormatter::MEDIUM_STYLE, DateFormatter::NO_STYLE));
                }
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
                $term = $this->assignTerm();

                if ($areas = $this->controller->getCatalogAreas()) {
                    $areasList = array();
                    $areaOptions = array('term' => strval($term));
                    foreach ($areas as $CourseArea) {
                        $areasList[] = $this->linkForCatalogArea($CourseArea, $areaOptions);
                    }
                    $this->assign('areas', $areasList);
                }
                $this->assign('catalogHeader', $this->getOptionalModuleVar('catalogHeader','','catalog'));
                $this->assign('catalogFooter', $this->getOptionalModuleVar('catalogFooter','','catalog'));
                
                break;
                
            case 'area':
                $area = $this->getArg('area');
                $term = $this->assignTerm();
                $options = array('term' => $term);

                if (!$CourseArea = $this->controller->getCatalogArea($area)) {
                    $this->redirectTo('catalog', array());
                }

                $areas = $CourseArea->getAreas();
                
                $areasList = array();
                $areaOptions = array('term' => strval($term));
                foreach ($areas as $CourseArea) {
                    $areasList[] = $this->linkForCatalogArea($CourseArea, $areaOptions);
                }

                $courses = array();
                $options = array(
                    'term'=>$term,
                    'types'=>array('catalog'),
                    'area'=>$area
                );
                
                $courses = $this->controller->getCourses($options);
                $coursesList = array();
 
                foreach ($courses as $item) {
                    $course = $this->linkForCatalogCourse($item, array('term'=>strval($term), 'area' => $area));
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
                $contents = $this->sortUpdatesByDate($contents);
                $contents = array_slice($contents, 0, $this->getOptionalModuleVar('MAX_UPDATES', 10));
                $this->assign('contents', $contents);
                break;
            
            case 'alltasks':
                if (!$this->isLoggedIn()) {
                    $this->redirectTo('index');
                }
                $Term = $this->assignTerm();
                $this->assignIndexTabs(array('term'=>$Term->getID()));

                //@TODO make this configurable
                $groups = $this->getModuleSections('alltasks');
                $this->assignGroupLinks($groups);

                $group = $this->getArg('group', current(array_keys($groups)));
                $options = array(
                    'group'=>$group
                );

                $tasks = array();
                $courses = $this->controller->getCourses(array());
                foreach($courses as $course){
                    if ($contentCourse = $course->getCourse('content')) {
                        $groups = $contentCourse->getTasks($options);
                        foreach ($groups as $groupTitle => $items){
                            $groupItems = array();
                            foreach ($items as $item) {
                                $groupItems[] = $this->linkForTask($item, $contentCourse);
                            }
                            if($group == 'priority'){
                                $title = $this->getLocalizedString('CONTENT_PRIORITY_TITLE_'.strtoupper($groupTitle));
                            }else{
                                $title = $groupTitle;
                            }
                            $task = array(
                                'title' => $title,
                                'items' => $groupItems,
                            );

                            $tasks[] = $task;
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

                $groups = $this->getModuleSections('tasks');
                $this->assignGroupLinks($groups, $this->getCourseOptions());

                $group = $this->getArg('group', current(array_keys($groups)));
                $options = array(
                    'group'=>$group
                );

                $tasks = array();
                if ($contentCourse = $course->getCourse('content')) {
                    $groups = $contentCourse->getTasks($options);
                    foreach ($groups as $groupTitle => $items){
                        $groupItems = array();
                        foreach ($items as $item) {
                            $groupItems[] = $this->linkForTask($item, $contentCourse);
                        }
                        if($group == 'priority'){
                            $title = $this->getLocalizedString('CONTENT_PRIORITY_TITLE_'.strtoupper($groupTitle));
                        }else{
                            $title = $groupTitle;
                        }
                        $task = array(
                            'title' => $title,
                            'items' => $groupItems,
                        );

                        $tasks[] = $task;
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
                    if($group == 'type'){
                        $title = $this->getLocalizedString('CONTENT_TYPE_TITLE_'.strtoupper($groupTitle));
                    }else{
                        $title = $groupTitle;
                    }
                    $resource = array(
                        'title' => $title,
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
            case 'grades':
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }

                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('index');
                }

                $grades = $contentCourse->getGrades();
                $this->assignTerm();
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
                    $this->assign('courseListHeading', $this->getLocalizedString('COURSE_LIST_HEADING', $Term->getTitle(), count($courses)));
                    $this->assign('courses', $courses);
                } else {
                    $loginLink = array(
                        'title' => $this->getLocalizedString('SIGN_IN_SITE', Kurogo::getSiteString('SITE_NAME')),
                        'url'   => $this->buildURLForModule('login','', $this->getArrayForRequest()),
                    );
                    $this->assign('loginLink', array($loginLink));
                    $this->assign('loginText', $this->getLocalizedString('NOT_LOGGED_IN'));
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
                $this->assign('courseCatalogText', $this->getLocalizedString('COURSE_CATALOG_TEXT'));
                $this->assign('catalogItems', $catalogItems);
                break;
        }
    }
}
 
