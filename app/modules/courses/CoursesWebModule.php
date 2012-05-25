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
    protected $showCourseNumber = true;
    protected $defaultModel = 'CoursesDataModel';

    public function linkForTask($task, CourseContentCourse $course, $includeCourseName=true) {
    	$link = array(
            'title' =>$includeCourseName ? $task->getTitle() : $course->getTitle(),
    		'date' => $task->getDate() ? $task->getDate() : $task->getDueDate(),
            'img'   => "/modules/courses/images/content_" . $task->getContentType() . $this->imageExt
        );
        
        $subtitle = array();
        if ($includeCourseName) {
            $subtitle[] = $course->getTitle();
        }
        
        $type = $task->getContentType();
        if ($task->getContentType() == 'task' && $date = $task->getDueDate()) {
            $subtitle[] = $this->getLocalizedString('COURSE_TASK_DUE', DateFormatter::formatDate($date, DateFormatter::MEDIUM_STYLE, DateFormatter::NO_STYLE));
        } elseif ($date = $task->getDate()) {
            $subtitle[] = DateFormatter::formatDate($date, DateFormatter::LONG_STYLE, DateFormatter::LONG_STYLE);
        }

        $options = $this->getCourseOptions();
        $options['taskID'] = $task->getID();
        $options['courseID'] = $course->getCommonID();
        
        $link['url'] = $this->buildBreadcrumbURL('task', $options, true);
        $link['updated'] = implode("<br />", $subtitle);
        
        return $link;
    }

    // returns a link for a particular resource
    public function linkForContent(CourseContent $content, CourseContentCourse $course) {
        
    	$link = array(
            'title' => $content->getTitle(),
            'subtitle' => $content->getSubTitle(),
            'type'  => $content->getContentType(),
            'class' => "content content_" . $content->getContentType(),
            'img'   => "/modules/courses/images/content_" . $content->getContentType() . $this->imageExt
        );

        if($content->getPublishedDate()){
	    	if($content->getAuthor()){
	    	    $updated = $this->getLocalizedString('CONTENTS_AUTHOR_PUBLISHED_STRING', $content->getAuthor(), $this->elapsedTime($content->getPublishedDate()->format('U')));
	    		//$updated = 'Updated '. $this->elapsedTime($content->getPublishedDate()->format('U')) .' by '.$content->getAuthor();
	    	}else{
	    		//$updated = 'Updated '. $this->elapsedTime($content->getPublishedDate()->format('U'));
	    		$updated = $this->getLocalizedString('CONTENTS_PUBLISHED_STRING', $this->elapsedTime($content->getPublishedDate()->format('U')));
	    	}
	    	$link['subtitle'] = $link['updated'] = $updated;
	    } else {
	    	$link['subtitle'] = $content->getSubTitle();
	    }

        $options = $this->getCourseOptions();
        $options['contentID'] = $content->getID();
        $options['type'] = $content->getContentType();
        $link['url'] = $this->buildBreadcrumbURL('content', $options);

        return $link;
    }

    public function linkForAnnouncement(CourseContent $content, CourseContentCourse $course, $includeCourseName=false){
        $contentID = $content->getID();
        $options = array(
            'courseID'  => $course->getCommonID(),
            'contentID' => $contentID,
            'type'      => $content->getContentType(),
        );
        $link = array(
            'title' => $includeCourseName ? $course->getTitle() : $content->getTitle(),
            'type' => $content->getContentType(),
            'class' => "update update_" . $content->getContentType(),
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
            $published = $this->elapsedTime($content->getPublishedDate()->format('U'));
            if ($content->getAuthor()) {
                $published = $this->getLocalizedString('CONTENTS_AUTHOR_PUBLISHED_STRING', $content->getAuthor(), $published);
                //$published .= ' by '.$content->getAuthor();
            } else {
                $published = $this->getLocalizedString('CONTENTS_PUBLISHED_STRING', $published);
            }
            $subtitle[] = $published;
        }

        $link['sortDate'] = $content->getPublishedDate() ? $content->getPublishedDate() : 0;
        $link['subtitle'] = implode("<br />", $subtitle);
        $link['url'] = $this->buildBreadcrumbURL('content', $options);
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
            'type' => $content->getContentType(),
            'class' => "update update_" . $content->getContentType(),
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
            $published = $this->elapsedTime($content->getPublishedDate()->format('U'));
            if ($content->getAuthor()) {
                $published = $this->getLocalizedString('CONTENTS_AUTHOR_PUBLISHED_STRING', $content->getAuthor(), $published);
                //$published .= ' by '.$content->getAuthor();
            } else {
                $published = $this->getLocalizedString('CONTENTS_PUBLISHED_STRING', $published);
            }
            $subtitle[] = $published;
        }

        $link['sortDate'] = $content->getPublishedDate() ? $content->getPublishedDate() : 0;
        $link['subtitle'] = implode("<br />", $subtitle);
        $link['url'] = $this->buildBreadcrumbURL('content', $options);
        return $link;
    }

    protected function linkForCatalogArea(CourseArea $area, $options=array()) {
        $options = array_merge($options,array(
            'area'=>$area->getCode()
            )
        );
        $link = array(
            'title'=> $area->getTitle(),
            'url'=>$this->buildBreadcrumbURL('area',$options)
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
            $page = 'course';
            $subtitle = array();
            $options['course'] = $contentCourse;
            if ($lastAnnouncementContent = $contentCourse->getLastAnnouncement($this->getOptionsForAnnouncements($options))) {
                $subtitle[] = $lastAnnouncementContent->getTitle();
                if ($publishedDate = $lastAnnouncementContent->getPublishedDate()) {
                    $published = $this->elapsedTime($publishedDate->format('U'));
                    if ($lastAnnouncementContent->getAuthor()) {
                        //$published = '<span class="author">'. $lastAnnouncementContent->getAuthor() .', ' . $published . "</span>";
                        $published = $this->getLocalizedString('CONTENTS_AUTHOR_PUBLISHED_STRING', $lastAnnouncementContent->getAuthor(), $published);
                    } else {
                        $published = $this->getLocalizedString('CONTENTS_PUBLISHED_STRING', $published);
                    }
                    $subtitle[] = $published;
                }
                $link['type']  = $lastAnnouncementContent->getContentType();
                $link['img']   = "/modules/courses/images/content_" . $lastAnnouncementContent->getContentType() . $this->imageExt;
            } else {
                $subtitle[] = $this->getLocalizedString('NO_ANNOUNCEMENTS');
            }

            $link['subtitle'] = implode("<br />", $subtitle);
        } else {
            $page = 'info';
        }
        unset($options['course']);

        $link['url'] = $this->buildBreadcrumbURL($page, $options);
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

        if($this->showCourseNumber) {
        	$link['label'] = $course->getField('courseNumber');
        }

        $link['url'] = $this->buildBreadcrumbURL('info', $options);
        return $link;
    }

    protected function formatBytes($value) {
		//needs integer
		if (!preg_match('/^\d+$/', $value)) {
			return $value;
		}
		
		//less than 10,000 bytes return bytes
		if ($value < 10000) {
			return $value;
		//less than 1,000,000 bytes return KB
		} elseif ($value < 1000000) {
			return sprintf("%.2f KB", $value/1024);
		} elseif ($value < 1000000000) {
			return sprintf("%.2f MB", $value/(1048576));
		} elseif ($value < 1000000000000) {
			return sprintf("%.2f GB", $value/(1073741824));
		} else {
			return sprintf("%.2f TB", $value/(1099511627776));
		}
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
                $downloadMode = $content->getDownloadMode();
                if($downloadMode == $content::MODE_DOWNLOAD) {
                    $options = $this->getCourseOptions();
                    $options['contentID'] = $content->getID();
                    $title = 'Download File';
                    $subtitle = $content->getFileName();
                    if ($filesize = $content->getFileSize()) {
                        $subtitle .= " (" . $this->formatBytes($filesize) . ")";                    
                    }

                    $links[] = array(
                        'title'=>$title, 
                        'subtitle'=>$subtitle,
                        'url'=>$this->buildExternalURL($this->buildBreadcrumbURL('download', $options, false)),
                    );
                }elseif($downloadMode == $content::MODE_URL) {
                    $links[] = array(
                        'title'=>$content->getTitle(),
                        'subtitle'=>$content->getFilename(),
                        'url'=>$this->buildExternalURL($content->getFileurl()),
                        'class'=>'external',
                    );
                }
                break;
            case 'page':
                $viewMode = $content->getViewMode();
                if($viewMode == $content::MODE_URL) {
                    $links[] = array(
                        'title'=>$content->getTitle(),
                        'subtitle'=>$content->getFilename(),
                        'url'=>$content->getFileurl(),
                        'class'=>'external',
                    );
                }
                break;
            case 'announcement':
            case 'task':
                break;
            default:
                KurogoDebug::debug($content, true);
        }

        return $links;
    }

    protected function assignTerm(){
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

    protected function getCourseFromArgs() {

        $courseID = $this->getArg('courseID');
        $term = $this->assignTerm();
        $options = $this->getCourseOptions();

        if ($course = $this->controller->getCourseByCommonID($courseID, $options)) {
            $this->assign('courseTitle', $course->getTitle());
            $this->assign('courseID', $course->getID());
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
        $this->feeds = $this->loadFeedData();
        $this->controller = CoursesDataModel::factory($this->defaultModel, $this->feeds);
        $this->hasPersonalizedCourses =  $this->controller->canRetrieve('registration') || $this->controller->canRetrieve('content');
        $this->selectedTerm = $this->getArg('term', CoursesDataModel::CURRENT_TERM);
        //load showCourseNumber setting
        $this->showCourseNumber = $this->getOptionalModuleVar('SHOW_COURSENUMBER_IN_LIST', 1);
    }

    protected function getCourseOptions() {
        $courseID = $this->getArg('courseID');
        $area = $this->getArg('area');
        $term = $this->assignTerm();

        $options = array(
            'courseID' => $courseID,
            'term' => strval($term)
        );
        
        if ($area) {
            $options['area'] = $area;
        }

        return $options;
    }

    protected function assignGroupLinks($tabPage, $groups, $defaultGroupOptions = array()){
        foreach ($groups as $groupIndex => $group) {
            $defaultGroupOptions[$tabPage . 'Group'] = $groupIndex;
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
        $this->assign($tabPage.'TabCount', $tabCountMap[$tabCount]);
        $this->assign($tabPage.'GroupLinks', $groupLinks);
    }

    protected function paginateArray($contents, $limit, $localizedStem, $tab) {
        $localizedPrev = strtoupper($localizedStem) . '_PREV';
        $localizedNext = strtoupper($localizedStem) . '_NEXT';
        $totalItems = count($contents);
        $start = $this->getArg('start', 0);
        $previousURL = null;
        $nextURL = null;
        if ($totalItems > $limit) {
            $args = $this->args;
            $args['tab'] = strtolower($tab);
            if ($start > 0) {
                $args['start'] = $start - $limit;
                $previousURL = $this->buildBreadcrumbURL($this->page, $args, false);
            }

            if (($totalItems - $start) > $limit) {
                $args['start'] = $start + $limit;
                $nextURL = $this->buildBreadcrumbURL($this->page, $args, false);
            }
        }

        $contents = array_slice($contents, $start, $limit);

        if($previousURL) {
            $title = $this->getLocalizedString($localizedPrev, $limit);
            $link = array(
                'title' => $title,
                'url' => $previousURL,
            );
            array_unshift($contents, $link);
        }
        if($nextURL) {
            $num = $totalItems - $start - $limit;
            if($num > $limit) {
                $num = $limit;
            }
            $title = $this->getLocalizedString($localizedNext, $num);
            $link = array(
                'title' => $title,
                'url' => $nextURL,
            );
            array_push($contents, $link);
        }
        return $contents;
    }

    public function sortCourseContent($courseContents, $sort=null) {
        if (empty($courseContents)) {
            return array();
        }
        $this->sortType = $sort;
        uasort($courseContents, array($this, "sortByField"));
        return $courseContents;
    }

    // sort type for content
    private function sortByField($contentA, $contentB) {
        switch ($this->sortType){
            case 'sortDate':
                $updateA_time = $contentA['sortDate'] ? $contentA['sortDate']->format('U') : 0;
                $updateB_time = $contentB['sortDate'] ? $contentB['sortDate']->format('U') : 0;
                if($updateA_time == $updateB_time){
                    return 0;
                }
                return ($updateA_time > $updateB_time) ? -1 : 1;
            default:
                if($contentA->sortBy() == $contentB->sortBy()){
                    return 0;
                }
                return ($contentA->sortBy() > $contentB->sortBy()) ? -1 : 1;
            break;
        }
    }

    protected function formatCourseDetails(CombinedCourse $course, $configName) {
        //error_log(print_r($this->detailFields, true));
        //load page detail configs
        $detailFields = $this->getModuleSections($configName);
        $details = array();

        foreach($detailFields as $key => $info) {
            $details[$key] = $this->formatCourseDetail($course, $info, $key);
        }
        //error_log(print_r($details, true));
        return $details;
    }

    protected function formatCourseDetail(CombinedCourse $course, $info, $key=0) {
        $section = array();
        $courseType = isset($info['courseType']) ? $info['courseType'] : null;

        if(!$course->checkInStandardAttributes($key)) {
        	//try to set attribute in attributes list.
	        $course->setAttribute($key, $courseType);
        }
        $values = (array)$course->getField($key, $courseType);
        if (count($values)) {
            $section[$key] = $this->formatInfoDetail($this->formatValues($values, $info), $info, $course);
        }

        return $section;
    }

    protected function formatInfoDetail($values, $info, CombinedCourse $course) {
    	if(isset($values[0]) && is_object($values[0])) {
	        $detail = array(
	            'title' => null,
	        	'head'  => isset($info['title']) ? $info['title'] : null
	        );
    	}else{
	    	if (isset($info['format'])) {
	            $value = vsprintf($this->replaceFormat($info['format']), $values);
	        } else {
	            $delimiter = isset($info['delimiter']) ? $info['delimiter'] : ' ';
	            $value = implode($delimiter, $values);
	        }

	        $detail = array(
	            'label' => isset($info['label']) ? $info['label'] : null,
	            'title' => $value,
	        	'head'  => isset($info['title']) ? $info['title'] : null,
	        );
    	}

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

            // new type list, will return a list of values
            case 'list':
                foreach ($values as $key=>$instructor){
                	//TODO: can set title grabbing methd by config file
                	$value[] = $instructor->getFullName();
                }
                break;
        }

        if (isset($info['module'])) {
            if(is_array($value)) {
        		foreach($value as $eachValue) {
		            $detail['list'][] = array_merge($detail, Kurogo::moduleLinkForValue($info['module'], $eachValue, $this, $course));
        		}
        	}else{
	            $detail = array_merge($detail, Kurogo::moduleLinkForValue($info['module'], $value, $this, $course));
        	}
        } elseif (isset($info['page'])) {
            $options = array_merge($this->getCourseOptions(), array('value'=>$value));
            if(is_array($value)) {
                foreach ($value as $eachValue) {
                    $options['value'] = $eachValue;
		            $detail['list'][] = array_merge($detail, array(
		                'title'=>$eachValue,
		                'url'=>$this->buildBreadcrumbURL($info['page'], $options, true)
		            ));
                }
        	} else{
        	    $detail = array_merge($detail, array(
                    'title'=>$value,
                    'url'=>$this->buildBreadcrumbURL($info['page'], $options, true)
                ));
        	}

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

    protected function getOptionsForTasks($options) {
        $section = $this->page == 'index' ? 'alltasks' : 'tasks';
        $taskGroups = $this->getModuleSections($section);

        $groupOptions = array('tab'=>'tasks');
        if ($this->page == 'course') {
            $groupOptions = array_merge($groupOptions, $this->getCourseOptions());
        }

        $this->assignGroupLinks('tasks', $taskGroups, $groupOptions);

        $group = $this->getArg('tasksGroup', key($taskGroups));
        $this->assign('tasksGroup', $group);

        $options = array(
            'group'=>$group
        );

        return $options;
    }

    protected function getOptionsForAnnouncements($options){
        return array();
    }

    protected function getOptionsForUpdates($options) {
        return array();
    }

    protected function getOptionsForCourse($options){
        return array();
    }

    protected function getOptionsForCourses($options){
        return $options;
    }

    protected function getOptionsForResources($options) {
        $groupsConfig = $this->getModuleSections('resources');

        $groupOptions = array('tab'=>'resources');
        if ($this->page == 'course') {
            $groupOptions = array_merge($groupOptions, $this->getCourseOptions());
        }

        $this->assignGroupLinks('resources', $groupsConfig, $groupOptions);

        $group = $this->getArg('resourcesGroup', key($groupsConfig));
        $key = $this->getArg('key', '');  //particular type
        $this->assign('resourcesGroup', $group);
        $options = array(
            'group'=>$group,
            'limit'=>$groupsConfig[$group]['max_items'],
            'key'  =>$key
        );

        return $options;
    }
    
    protected function getBookmarksForTerm(CourseTerm $Term) {
        $_bookmarks =  $this->getBookmarks();
        $bookmarks = array();
        foreach ($_bookmarks as $aBookmark) {
            if ($this->getBookmarkParam($aBookmark, 'term')==$Term->getID()) {
                $bookmarks[] = $aBookmark;
            }
        }
        return $bookmarks;
    }

    protected function initializeForIndexTab($tab, $options) {

        switch ($tab)
        {
            case 'index':
                $Term = $this->assignTerm();
                $coursesLinks = array();
                $this->assign('hasPersonalizedCourses', $this->hasPersonalizedCourses);
                $courses = $options['courses'];

                if ($this->isLoggedIn()) {
                    $options = $this->getOptionsForCourse($options);
                    foreach ($courses as $course) {
                        $courseLink = $this->linkForCourse($course, $options);
                        $coursesLinks[] = $courseLink;
                    }
                    $this->assign('courseListHeading', $this->getLocalizedString('COURSE_LIST_HEADING', $Term->getTitle(), count($coursesLinks)));
                    $this->assign('coursesLinks', $coursesLinks);
                } else {
                    $loginLink = array(
                        'title' => $this->getLocalizedString('SIGN_IN_SITE', Kurogo::getSiteString('SITE_NAME')),
                        'url'   => $this->buildURLForModule('login','', $this->getArrayForRequest()),
                    );
                    $this->assign('loginLink', array($loginLink));
                    $this->assign('loginText', $this->getLocalizedString('NOT_LOGGED_IN'));
                }

                if ($this->controller->canRetrieve('catalog')) {
                    $catalogItems = array();

                    $catalogItems[] = array(
                        'title' => $this->getFeedTitle('catalog'),
                        'url'   => $this->buildBreadcrumbURL('catalog', array('term'=>strval($Term)))
                    );

                    if ($bookmarks = $this->getBookmarksForTerm($Term)) {
                        $catalogItems[] = array(
                            'title' => $this->getLocalizedString('BOOKMARKED_COURSES') . " (" . count($bookmarks) . ")",
                            'url'   => $this->buildBreadcrumbURL('bookmarks', array('term'=>strval($Term))),
                        );
                    }

                    $this->assign('courseCatalogText', $this->getLocalizedString('COURSE_CATALOG_TEXT', $Term->getTitle()));
                    $this->assign('catalogItems', $catalogItems);
                }

                return true;
                break;

            case 'announcements':
                $announcementsLinks = array();
                $courses = $options['courses'];
                foreach($courses as $course){
                    if ($contentCourse = $course->getCourse('content')) {
                        $options['course'] = $contentCourse;
                        if($items = $contentCourse->getAnnouncements($this->getOptionsForAnnouncements($options))) {
                            foreach ($items as $item){
                                $announcementsLinks[] = $this->linkForAnnouncement($item, $contentCourse, true);
                            }
                        }
                    }
                }
                $announcementsLinks = $this->sortCourseContent($announcementsLinks, 'sortDate');
                $announcementsLinks = $this->paginateArray($announcementsLinks, $this->getOptionalModuleVar('MAX_ANNOUNCEMENTS', 5), 'ANNOUNCEMENT', 'announcements');
                $this->assign('announcementsLinks', $announcementsLinks);
                return true;
                break;

            case 'updates':
                $updatesLinks = array();
                $courses = $options['courses'];
                foreach($courses as $course){
                    if ($contentCourse = $course->getCourse('content')) {
                        $options['course'] = $contentCourse;
                        if($items = $contentCourse->getUpdates($this->getOptionsForUpdates($options))) {
                            foreach ($items as $item){
                                $updatesLinks[] = $this->linkForUpdate($item, $contentCourse, true);
                            }
                        }
                    }
                }
                $updatesLinks = $this->sortCourseContent($updatesLinks, 'sortDate');
                $updatesLinks = $this->paginateArray($updatesLinks, $this->getOptionalModuleVar('MAX_UPDATES', 5), 'UPDATE', 'updates');
                $this->assign('updatesLinks', $updatesLinks);
                return true;
                break;

            case 'tasks':
                $tasks = array();
                $courses = $options['courses'];
                foreach($courses as $course){
                    if ($contentCourse = $course->getCourse('content')) {
                        $options['course'] = $contentCourse;
                        $tasksOptions = $this->getOptionsForTasks($options);
                        $group = $tasksOptions['group'];
                        $groups = $contentCourse->getTasks($tasksOptions);
                        foreach ($groups as $groupTitle => $items){
                            if($group == 'priority'){
                                $title = $this->getLocalizedString('CONTENT_PRIORITY_TITLE_'.strtoupper($groupTitle));
                            }else{
                                $title = $groupTitle;
                            }
                            $task = array(
                                'title' => $title,
                                'items' => $items,
                            );

                            if(isset($tasks[$title])){
                                $tasks[$title]['items'] = array_merge($tasks[$title]['items'], $task['items']);
                            }else{
                                $tasks[$title]['items'] = $task['items'];
                            }
                        }
                    }
                }
                //Sort aggregated content
                $sortedTasks = array();
                foreach($tasks as $title => $group){
                    $items = $this->sortCourseContent($group['items']);
                    $tasksLinks = array();
                    foreach($items as $item){
                        $tasksLinks[] = $this->linkForTask($item, $item->getContentCourse());
                    }
                    $task = array(
                        'title' => $title,
                        'items' => $tasksLinks,
                    );
                    if(isset($sortedTasks[$title])){
                        $sortedTasks[$title] = array_merge($tasks[$title], $task);
                    }else{
                        $sortedTasks[$title] = $task;
                    }
                }

                $this->assign('tasks', $sortedTasks);
                return true;
                break;
            }
    }

    protected function initializeForCourseTab($tab, $options) {
        $course = $options['course'];
        $contentCourse = $options['contentCourse'];

        switch ($tab)
        {
            case 'announcements':
                $announcementsOptions = $this->getOptionsForAnnouncements($options);
                $announcements = $contentCourse->getAnnouncements($announcementsOptions);
                $announcementsLinks = array();
                foreach ($announcements as $announcement) {
                    $announcementsLinks[] = $this->linkForAnnouncement($announcement, $contentCourse);
                }
                $announcementsLinks = $this->sortCourseContent($announcementsLinks, 'sortDate');
                $announcementsLinks = $this->paginateArray($announcementsLinks, $this->getOptionalModuleVar('MAX_ANNOUNCEMENTS', 10), 'ANNOUNCEMENT', 'announcements');
                $this->assign('announcementsLinks', $announcementsLinks);
                return true;
                break;
            case 'updates':
                $updatesOptions = $this->getOptionsForUpdates($options);
                $items = $contentCourse->getUpdates($updatesOptions);
                $updatesLinks = array();
                foreach ($items as $item){
                    $updatesLinks[] = $this->linkForUpdate($item, $contentCourse, false);
                }
                
                $updatesLinks = $this->paginateArray($updatesLinks, $this->getOptionalModuleVar('MAX_UPDATES', 10), 'UPDATE', 'updates');
                $this->assign('updatesLinks', $updatesLinks);
                return true;
                break;

            case 'resources':
                $resourcesLinks = array();
                $resourcesOptions = $this->getOptionsForResources($options);
                $groups = $contentCourse->getResources($resourcesOptions);
                $group = $resourcesOptions['group'];
                if($group == "date") {
                    $limit = 0;
                    $pageSize = $resourcesOptions['limit'];
                }else {
                    $limit = $resourcesOptions['limit'];
                }
                $key = $resourcesOptions['key'];
                $seeAllLinks = array();

                foreach ($groups as $groupTitle => $items){
                    //@Todo when particular type,it wil show the data about the type
                    if ($key) {
                        if ($key !== $groupTitle) {
                            continue;
                        } else {
                            $limit = 0;
                        }
                    }
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
                    if($group != "date" && count($items) > $limit && $limit != 0){
                        $courseOptions = $this->getCourseOptions();
                        $courseOptions['group'] = $group;
                        $courseOptions['key'] = $groupTitle;
                        $courseOptions['tab'] = 'resources';
                        $resource['url'] = $this->buildBreadcrumbURL("resourceSeeAll", $courseOptions, false);
                    }
                    $resourcesLinks[] = $resource;
                }
                if($group == "date" && $pageSize && isset($resourcesLinks[0])) {
                    $resource = $resourcesLinks[0];
                    $limitedItems = $this->paginateArray($resource['items'], $pageSize, "RESOURCES_DATE", $tab);
                    $resourcesLinks[0]['items'] = $limitedItems;
                    $resourcesLinks[0]['count'] = count($limitedItems);
                }

                $this->assign('resourcesLinks',$resourcesLinks);
                $this->assign('courseResourcesGroup', $group);
                return true;
                break;

            case 'tasks':
                $tasksOptions = $this->getOptionsForTasks($options);

                $tasks = array();
                $groups = $contentCourse->getTasks($tasksOptions);
                $group = $tasksOptions['group'];
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
                $this->assign('tasks', $tasks);
                return true;
                break;

            case 'info':
                $options = $this->getCourseOptions();

		        //load tab page detail configs
		        $this->detailFields = $this->getModuleSections($tab . '-detail');

                $courseDetails =  $this->formatCourseDetails($course, 'course-info');
				$this->assign('courseDetails', $courseDetails);

                $links = array();
                if ($registrationCourse = $course->getCourse('registration')) {
                    if ($registrationCourse->canDrop()) {
                        $links[] = array(
                            'title'=> $this->getLocalizedString('DROP_COURSE'),
                            'url' => $this->buildBreadcrumbURL('dropclass', $options)
                        );
                    }
                }

                // @TODO ADD configurable links
                $this->assign('links', $links);
                return true;
                break;

            case 'grades':
                $grades = $contentCourse->getGrades(array('user'=>true));
                return true;
                break;
        }
    }

    protected function initializeForInfoTab($tab, $options) {
    	$course = $options['course'];
        switch ($tab)
        {
            case 'index':
                $courseDetails =  $this->formatCourseDetails($course, 'info-index');
                $this->assign('courseDetails', $courseDetails);
                return true;
                break;

            case 'staff':
            	$instructorList = array();
                $staff =  $this->formatCourseDetails($course, 'info-staff');
                $this->assign('staff', $staff);
                return true;
                break;
        }
    }

    protected function initializeForPage() {
        switch($this->page) {
            case 'loadFile':
                $file = $this->getArg('file');
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
                    //we are downloading a file that the server retrieves
                    if ($content->getContentType()=='file') {
                        if ($file = $content->getContentFile()) {
                            if ($mime = $content->getContentMimeType()) {
                                header('Content-type: ' . $mime);
                            }
                            if ($size = $content->getFilesize()) {
                                header('Content-length: ' . sprintf("%d", $size));
                            }
                            
                            if ($filename = $content->getFilename()) {
                                header('Content-Disposition: inline; filename="'. $filename . '"');
                            }
                            readfile($file);
                            die();
                        } else {
                            throw new KurogoException("Unable to download requested file");
                        }
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

                if($content->getContentType() == "page") {
                    if($content->getViewMode() == $content::MODE_PAGE) {
                        $contentDataUrl = $contentCourse->getFileForContent($content->getID());
                        $contentData = file_get_contents($contentDataUrl);
                        $this->assign("contentData", $contentData);
                    }
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
                    throw new KurogoDataException($this->getLocalizedString('ERROR_CONTENT_NOT_FOUND'));
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
                
                if ($bookmarks = $this->getBookmarksForTerm($term)) {
                    $bookmarksList[] = array(
                        'title' => $this->getLocalizedString('BOOKMARKED_COURSES') . " (" . count($bookmarks) . ")",
                        'url'   => $this->buildBreadcrumbURL('bookmarks', array('term'=>strval($term))),
                    );
                    $this->assign('bookmarksList', $bookmarksList);
                }
                
                $this->assign('catalogHeader', $this->getOptionalModuleVar('catalogHeader','','catalog'));
                $this->assign('catalogFooter', $this->getOptionalModuleVar('catalogFooter','','catalog'));
                $this->assign('placeholder', $this->getLocalizedString("CATALOG_SEARCH"));

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
                $searchOptions = $options = array(
                    'term'=>strval($term),
                    'area'=>$area
                );
                
                $searchOptions['type'] = 'catalog';

                $courses = $this->controller->getCourses($searchOptions);
                $coursesList = array();

                foreach ($courses as $item) {
                    $course = $this->linkForCatalogCourse($item, $options);
                    $coursesList[] = $course;
                }

                $this->assign('areaTitle', $CourseArea->getTitle());
                $this->assign('description', $CourseArea->getDescription());
                $this->assign('areas', $areasList);
                $this->assign('courses', $coursesList);
                $this->assign('hiddenArgs', array('area' => $area, 'term' => strval($term)));
                $this->assign('placeholder', $this->getLocalizedString("SEARCH_MODULE", $CourseArea->getTitle()));

                break;


            case 'info':
            	$area = $this->getArg('area');
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }
                $Term = $this->assignTerm();

                // Bookmark
                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $cookieParams = array(
                    	'title' => $course->getTitle(),
                        'id' => $course->getID(),
                        'term'  => rawurlencode($Term->getID()),
                        'area'    => rawurlencode($area),
                        'courseNumber' => rawurlencode($course->getField('courseNumber'))
                    );

                    $cookieID = http_build_query($cookieParams);
                    $this->generateBookmarkOptions($cookieID);
                }

                $options = array(
                    'course'=> $course
                );

                $tabsConfig = $this->getModuleSections('infotabs');
                $tabs = array();
                foreach($tabsConfig as $tab => $tabData){
                    $tabs[] = $tab;
                    $this->initializeForInfoTab($tab, $options);
                }

                $this->enableTabs($tabs);
                $this->assign('tabs',$tabs);
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
                $this->assign('key', ucfirst($key));
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
                		'url' => $this->buildBreadcrumbURL($this->page, array_merge($this->args, array('download'=>1))),
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
                $Term = $this->assignTerm();
                $bookmarks = array();
                if($bookmarks = $this->getBookmarksForTerm($Term)) {
                    foreach ($bookmarks as $aBookmark) {
                        if ($aBookmark) {
                            // prevent counting empty string
                            $bookmark = array(
                                'title' => $this->getTitleForBookmark($aBookmark),
                                'url' => $this->detailURLForBookmark($aBookmark),
                            );

                            if ($this->showCourseNumber) {
                                $bookmark['label'] = $this->getBookmarkParam($aBookmark, 'courseNumber');
                            }

                            $bookmarks[] = $bookmark;
                        }
                    }
                    $this->assign('navItems', $bookmarks);
                }
                $this->assign('hasBookmarks', $this->hasBookmarks());
                break;
            case 'course':
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }

                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('index');
                }
                $Term = $this->assignTerm();

                $options = array(
                    'term'=>$Term,
                    'course'=> $course,
                    'contentCourse'=> $contentCourse
                );


                $tabsConfig = $this->getModuleSections('coursetabs');
                $tabs = array();
                foreach($tabsConfig as $tab => $tabData){
                    if ($this->initializeForCourseTab($tab, $options)) {
                        $tabs[] = $tab;
                    }
                }

                $this->enableTabs($tabs);
                $this->assign('tabs',$tabs);
                break;

            case 'index':
                $Term = $this->assignTerm();
                $options = array(
                    'term'=>$Term,
                    'types'=>array('content','registration'),
                    'courses'=>array()
                );

                if ($this->isLoggedIn()) {
                    $options['courses'] = $this->controller->getCourses($this->getOptionsForCourses($options));
                }

                $tabsConfig = $this->getModuleSections('indextabs');
                $tabs = array();
                foreach($tabsConfig as $tab => $tabData){
                    if(!$tabData['protected'] || $this->isLoggedIn()) {
                        if ($this->initializeForIndexTab($tab, $options)) {
                            $tabs[] = $tab;
                        }
                    }
                }

                $this->enableTabs($tabs);
                $this->assign('tabs', $tabs);
                break;
            case 'search':
                $searchTerms = $this->getArg('filter', false);
                $term = $this->assignTerm();

                $options = array(
                    'term' => $term,
                    'types' => array('catalog')
                );
                if($area = $this->getArg('area')) {
                    $options['area'] = $area;
                }

                $courses = $this->controller->search($searchTerms, $options);
                $coursesList = array();

                $options['term'] = strval($options['term']);
                foreach ($courses as $item) {
                	if(!$item->checkInStandardAttributes('areaCode', 'catalog')) {
			        	//try to set attribute in attributes list.
				        $item->setAttribute('areaCode', 'catalog');
			        }
			        $options['area'] = $item->getField('areaCode', 'catalog');
                    $course = $this->linkForCatalogCourse($item, $options);
                    $coursesList[] = $course;
                }
                $this->assign('results', $coursesList);
                if ($coursesList) {
                    $this->assign('resultCount', count($coursesList));
                }
                $this->assign('hiddenArgs', array('area' => $area, 'term' => strval($term)));
                $this->assign('searchTerms', $searchTerms);
                $this->assign('searchHeader', $this->getOptionalModuleVar('searchHeader','','catalog'));
                break;
        }
    }
}

