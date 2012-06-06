<?php
includePackage('Courses');
includePackage('DateTime');
class CoursesWebModule extends WebModule {
    protected $id = 'courses';
    protected $controller;
    protected $selectedTerm;
    protected $detailFields = array();
    protected $showCourseNumber = true;
    protected $defaultModel = 'CoursesDataModel';
    protected $infoDetails = array();
    protected $Term;
    protected $originalPage;
    protected $tab;

    public function linkForTask($task, CourseContentCourse $course, $includeCourseName=true) {
    	$link = array(
            'title' =>$includeCourseName ? htmlentities($task->getTitle()) : $course->getTitle(),
    		'date' => $task->getDate() ? $task->getDate() : $task->getDueDate(),
            'img'   => "/modules/courses/images/content_" . $task->getContentClass() . $this->imageExt
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

        $link['url'] = $this->buildBreadcrumbURL('task', $options);
        $link['updated'] = implode("<br />", $subtitle);

        return $link;
    }

    // returns a link for a particular resource
    public function linkForContent(CourseContent $content, CourseContentCourse $course) {
    	$link = array(
            'title' => htmlentities($content->getTitle()),
            'subtitle' => $content->getSubTitle(),
            'type'  => $content->getContentType(),
            'class' => "content content_" . $content->getContentType(),
            'img'   => "/modules/courses/images/content_" . $content->getContentClass() . $this->imageExt
        );

        if ($content->getPublishedDate()){
	    	if ($content->getAuthor()) {
	    	    $updated = $this->getLocalizedString('CONTENTS_AUTHOR_PUBLISHED_STRING', $content->getAuthor(), $this->elapsedTime($content->getPublishedDate()->format('U')));
	    		//$updated = 'Updated '. $this->elapsedTime($content->getPublishedDate()->format('U')) .' by '.$content->getAuthor();
	    	} else {
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

        if (!$content instanceOf UnsupportedCourseContent) {
            $link['url'] = $this->buildBreadcrumbURL('content', $options);
        }

        return $link;
    }

    public function linkForFolder(CourseContent $content, CourseContentCourse $course) {
        $link = array(
            'title' => htmlentities($content->getTitle()),
            'type'  => $content->getContentType(),
            'class' => "content content_" . $content->getContentType(),
            'img'   => "/modules/courses/images/content_" . $content->getContentClass() . $this->imageExt
        );

        $options = $this->getCourseOptions();
        $options['contentID'] = $content->getID();
        $options['type'] = $content->getContentType();
        $options['tab'] = 'browse';
        $link['url'] = $this->buildAjaxBreadcrumbURL($this->page, $options, false);

        return $link;
    }

    public function linkForAnnouncement(AnnouncementCourseContent $announcement, CourseContentCourse $course, $includeCourseName=false){
        $contentID = $announcement->getID();
        $options = array(
            'courseID'  => $course->getCommonID(),
            'contentID' => $contentID,
            'type'      => $announcement->getContentType(),
        );
        $link = array(
            'title' => $includeCourseName ? $course->getTitle() : htmlentities($announcement->getTitle()),
            'type' => $announcement->getContentType(),
            'class' => "update update_" . $announcement->getContentType(),
            'img'   => "/modules/courses/images/content_" . $announcement->getContentClass() . $this->imageExt
        );
        foreach (array('courseID') as $field) {
            if (isset($data[$field])) {
                $options[$field] = $data[$field];
            }
        }
        $subtitle = array();
        
        if ($includeCourseName) {
            $subtitle[] = "<div class=\"announcementTitle\">". $announcement->getTitle() . "</div>";
        }
        
        $link['url'] = $this->buildBreadcrumbURL('content', $options);

        if ($this->pagetype == 'tablet') {
            $body = $announcement->getDescription();
            $maxLength = $this->getOptionalModuleVar('ANNOUNCEMENT_TABLET_MAX_LENGTH', 250);
            if (strlen($body) > $maxLength) {
                $body = substr($body, 0, $maxLength) . "...";
            } else {
                unset($link['url']);
            }
            
        
            $subtitle[] = "<div class=\"announcementBody\">" . $body . "</div>";
        }


        if ($announcement->getPublishedDate()){
            $published = $this->elapsedTime($announcement->getPublishedDate()->format('U'));
            if ($announcement->getAuthor()) {
                $published = $this->getLocalizedString('CONTENTS_AUTHOR_PUBLISHED_STRING', $announcement->getAuthor(), $published);
            } else {
                $published = $this->getLocalizedString('CONTENTS_PUBLISHED_STRING', $published);
            }
            $subtitle[] = "<div class=\"announcementPublished\">$published</div>";
        }

        $link['sortDate'] = $announcement->getPublishedDate() ? $announcement->getPublishedDate() : 0;
        $link['subtitle'] = implode("", $subtitle);
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
            'title' => $includeCourseName ? $course->getTitle() : htmlentities($content->getTitle()),
            'type' => $content->getContentType(),
            'class' => "update update_" . $content->getContentType(),
            'img'   => "/modules/courses/images/content_" . $content->getContentClass() . $this->imageExt
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
        if(!$content instanceOf UnsupportedCourseContent){
            $link['url'] = $this->buildBreadcrumbURL('content', $options);
        }
        return $link;
    }

    protected function linkForCatalogArea(CourseArea $area, $options=array()) {
        $options = array_merge($options,array(
            'area'=>$area->getCode(),
            'parent'=>$area->getParent(),
            )
        );
        $link = array(
            'title'=> $area->getTitle(),
            'url'=>$this->buildBreadcrumbURL('catalogarea', $options)
        );
        return $link;
    }

    protected function linkForCourse(CourseInterface $course, $options=array()) {

        $options = array_merge($options, array(
            'courseID'  => $course->getID()
            )
        );

        if ($this->showCourseNumber && $course->getField('courseNumber')) {
            $title = sprintf("(%s) %s", $course->getField('courseNumber'), $course->getTitle());
        } else {
            $title = $course->getTitle();
        }
        $link = array(
            'title' => $title
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
            $page = 'catalogcourse';
        }
        unset($options['course']);

        if ($this->pagetype == 'tablet') {
            $link['url'] = $this->buildAjaxBreadcrumbURL($page, $options);
        } else {
            $link['url'] = $this->buildBreadcrumbURL($page, $options);
        }
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

        $link['url'] = $this->buildBreadcrumbURL('catalogcourse', $options);
        return $link;
    }

    protected function linkForGrade(GradeAssignment $gradeAssignment) {
        $options = $this->getCourseOptions();
        $options['gradeID'] = $gradeAssignment->getId();

        $link = array();
        $link['title'] = $gradeAssignment->getTitle();

        $subtitle = array();
        if ($gradeScore = $gradeAssignment->getGrade()) {
            if($gradeScore->getExempt()){
                $subtitle[] = $this->getLocalizedString('GRADE_STATUS', $this->getLocalizedString('GRADE_STATUS_EXEMPT'));
            }else{
                if($gradeScore->getStatus() == GradeScore::SCORE_STATUS_GRADED){
                    $subtitle[] = $this->getLocalizedString('GRADE_STATUS', $this->getLocalizedString('GRADE_STATUS_GRADED'));
                }elseif($gradeScore->getStatus() == GradeScore::SCORE_STATUS_GRADED){
                    $subtitle[] = $this->getLocalizedString('GRADE_STATUS', $this->getLocalizedString('GRADE_STATUS_NEEDS_GRADING'));
                }
            }
        }

        if($gradeAssignment->getGrade()){
            $subtitle[] = $this->getLocalizedString('GRADE_OUT_OF_POSSIBLE', number_format($gradeAssignment->getGrade()->getScore()), number_format($gradeAssignment->getPossiblePoints()));
        }else{
            $subtitle[] = $this->getLocalizedString('GRADE_POSSIBLE', number_format($gradeAssignment->getPossiblePoints()));
        }

        $link['subtitle'] = implode('<br/>', $subtitle);

        $link['url'] = $this->buildBreadcrumbURL('grade', $options);

        return $link;
    }

    protected function pageLinkForValue($page, $value, $object) {

        $args = array_merge(
            $this->args,
            array('value'=>$value)
        );
        $link = array(
            'title'=>$value,
            'url'=>$this->buildBreadcrumbURL($page, $args)
        );

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
                        'url'=>$this->buildExternalURL($this->buildURL('download', $options)),
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
            case 'unsupported':
                break;
            default:
                throw new KurogoException("Unhandled content type " . $content->getContentType());
        }

        return $links;
    }

    protected function assignTerm(){
        $feedTerms = $this->controller->getAvailableTerms();

        $term = $this->getArg('term', CoursesDataModel::CURRENT_TERM);
        if (!$Term = $this->controller->getTerm($term)) {
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

        if ($courseID = $this->getArg('courseID')) {
            $options = $this->getCourseOptions();

            if ($course = $this->controller->getCourseByCommonID($courseID, $options)) {
                $this->assign('courseTitle', $course->getTitle());
                $this->assign('courseID', $course->getID());

                if ($section = $this->getArg('section')) {
                    if ($catalogCourse = $course->getCourse('catalog')) {
                        if ($class = $catalogCourse->getSection($section)) {
                            $this->assign('sectionNumber', $class->getSectionNumber());
                        }
                    }
                }
            }
            return $course;
        }
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
        //load showCourseNumber setting
        $this->showCourseNumber = $this->getOptionalModuleVar('SHOW_COURSENUMBER_IN_LIST', 1);
        $this->Term = $this->assignTerm();
        $this->assign('hasPersonalizedCourses', $this->controller->canRetrieve('registration') || $this->controller->canRetrieve('content'));
    }

    protected function getCourseOptions() {
        $courseID = $this->getArg('courseID');
        $area = $this->getArg('area');

        $options = array(
            'courseID' => $courseID,
            'term' => strval($this->Term)
        );

        if ($area) {
            $options['area'] = $area;
        }

        return $options;
    }

    protected function assignGroupLinks($tabPage, $groups, $defaultGroupOptions = array()){
        $page = $this->originalPage;
        foreach ($groups as $groupIndex => $group) {
            $defaultGroupOptions[$tabPage . 'Group'] = $groupIndex;
            $groupLinks[$groupIndex]['url'] = $this->buildAjaxBreadcrumbURL($page, $defaultGroupOptions, false);
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
        $this->assign('tabstripId', $tabPage.'-'.md5($this->buildURL($this->page, $this->args)));
    }

    protected function paginateArray($contents, $limit) {
        $totalItems = count($contents);
        $start = $this->getArg('start', 0);
        $previousURL = null;
        $nextURL = null;

        if ($totalItems > $limit) {
            $args = $this->args;
            $args['tab'] = $this->tab;
            if ($start > 0) {
                $args['start'] = $start - $limit;
                $previousURL = $this->buildAjaxBreadcrumbURL($this->originalPage, $args, false);
                $this->assign('previousURL', $previousURL);
                $this->assign('previousCount', $limit);
            }

            if (($totalItems - $start) > $limit) {
                $args['start'] = $start + $limit;
                $nextURL = $this->buildAjaxBreadcrumbURL($this->originalPage, $args, false);
                $num = $totalItems - $start - $limit;
                if($num > $limit) {
                    $num = $limit;
                }

                $this->assign('nextURL', $nextURL);
                $this->assign('nextCount', $num);
            }
        }

        $contents = array_slice($contents, $start, $limit);
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
        switch ($this->sortType) {
            case 'sortDate':
                $updateA_time = $contentA['sortDate'] ? $contentA['sortDate']->format('U') : 0;
                $updateB_time = $contentB['sortDate'] ? $contentB['sortDate']->format('U') : 0;
                if($updateA_time == $updateB_time){
                    return 0;
                }
                return ($updateA_time > $updateB_time) ? -1 : 1;
            default:
                if ($contentA->sortBy() == $contentB->sortBy()) {
                    return 0;
                }
                return ($contentA->sortBy() > $contentB->sortBy()) ? -1 : 1;
            break;
        }
    }

    // takes a config and process the info data
    protected function formatCourseDetails($options, $configName) {

        //load page detail configs
        $detailFields = $this->getModuleSections($configName);
        $sections = array();

        // the section=xxx value separates the fields into nav sections.
        foreach ($detailFields as $key=>$keyData) {
            if (!isset($keyData['section'])) {
                throw new KurogoConfigurationException("No section value found for field $key");
            }

            $section = $keyData['section'];
            unset($keyData['section']);

            //set the type - we need to handle list types appropriately
            $keyData['type'] = isset($keyData['type']) ? $keyData['type'] : 'text';

            switch ($keyData['type']) {
                case 'list':
                    $keyData['items'] = $key;
                    $sections[$section] = $keyData;
                    break;
                default:

                    //assign field key
                    $keyData['field'] = $key;

                    // any field that has the heading attribute can be used as the heading for that section
                    if (isset($keyData['heading'])) {
                        $sections[$section]['heading'] = $keyData['heading'];
                        unset($keyData['heading']);
                    }
                    $sections[$section]['type'] = 'fields';
                    $sections[$section]['fields'][$key] = $keyData;
                    break;
            }
        }

        $details = array();
        foreach ($sections as $section=>$sectionData) {
            if ($items = $this->formatCourseDetailSection($options, $sectionData)) {
                $details[$section] = array(
                    'heading'=>isset($sectionData['heading']) ? $sectionData['heading'] : '',
                    'items'=>$items,
                    'subTitleNewline'=>isset($sectionData['subTitleNewline']) ? $sectionData['subTitleNewline'] : 0
                );
            }
        }

        return $details;
    }

    protected function getInfoObject($options) {
        if (isset($options['course'])) {
            $Course = $options['course'];
            $courseType = isset($options['courseType']) ? $options['courseType'] : 'catalog';
            if (!$object = $Course->getCourse($courseType)) {
                return null;
            }
        } elseif (isset($options['section'])) {
            $object = $options['section'];
        } else {
            throw new KurogoException("No valid object type found. Check trace");
        }

        return $object;
    }

    protected function formatCourseDetailSection($options, $sectionData) {

        switch ($sectionData['type']) {
            case 'fields':
                $items = array();
                foreach ($sectionData['fields'] as $field=>$fieldData) {
                    if ($object = $this->getInfoObject(array_merge($fieldData, $options))) {

                        if (isset($fieldData['title'])) {
                            //static value
                            $item = $this->formatInfoDetail($fieldData['title'], $fieldData, $object);
                        } else {
                            $item = $this->formatDetailField($object, $field, $fieldData);
                        }

                        if ($item) {
                            $items[] = $item;
                        }
                    }
                }
                return $items;
                break;

            case 'list':
                $items = array();
                if ($object = $this->getInfoObject(array_merge($sectionData, $options))) {
                    $method = "get" . $sectionData['items'];
                    if (!is_callable(array($object, $method))) {
                        throw new KurogoDataException("Method $method does not exist on " . get_class($object));
                    }

                    $sectionItems = $object->$method();
                    foreach ($sectionItems as $sectionItem) {
                        if ($item = $this->formatSectionDetailField($object, $sectionItem, $sectionData)) {
                            $items[] = $item;
                        }
                    }
                }

                return $items;
                break;
        }
    }

    protected function formatSectionDetailField($object, $sectionItem, $sectionData) {

        if (!is_object($sectionItem)) {
            throw new KurogoDataException("Item passed is not an object");
        }

        foreach (array('title','subtitle','label') as $attrib) {
            if (isset($sectionData[$attrib.'field'])) {
                $method = "get" . $sectionData[$attrib.'field'];
                if (!is_callable(array($sectionItem, $method))) {
                    throw new KurogoDataException("Method $method does not exist on " . get_class($sectionItem));
                }
                $sectionData[$attrib] = $sectionItem->$method();
            }
        }

        if (isset($sectionData['params'])) {
            $params = array();
            foreach ($sectionData['params'] as $param) {
                $method = "get" . $param;
                if (!is_callable(array($sectionItem, $method))) {
                    throw new KurogoDataException("Method $method does not exist on " . get_class($sectionItem));
                }
                $params[$param] = $sectionItem->$method();
            }
            $sectionData['params'] = $params;
        }

        $value = isset($sectionData['title']) ? $sectionData['title'] : strval($sectionItem);
        $fieldData = $sectionData;
        $fieldData['type'] = isset($fieldData['valuetype']) ? $fieldData['valuetype'] : 'text';

        return $this->formatInfoDetail($value, $fieldData, $sectionItem);
    }


    protected function formatDetailField($object, $field, $fieldData) {

        $method = "get" . $field;
        if (!is_callable(array($object, $method))) {
            throw new KurogoDataException("Method $method does not exist on " . get_class($object));
        }

        $value = $object->$method();
        return $this->formatInfoDetail($value, $fieldData, $object);
    }

    protected function formatInfoDetail($value, $info, $object) {

        $detail = $info;

        if (is_array($value)) {
	    	if (isset($info['format'])) {
	            $value = vsprintf($this->replaceFormat($info['format']), $value);
	        } else {
	            $delimiter = isset($info['delimiter']) ? $info['delimiter'] : ' ';
	            $value = implode($delimiter, $value);
	        }
        } elseif (is_object($value)) {
            throw new KurogoDataException("Value is an object. This needs to be traced");
        }

        if (strlen($value) == 0) {
            return null;
        }

        $detail['title'] = $value;

        $type = isset($info['type']) ? $info['type'] : 'text';
        $detail['type'] = $type;
        switch($type) {
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
            case 'text':
                break;
            default:
                throw new KurogoException("Unhandled type $type");
                break;
        }

        if (isset($info['module'])) {
            $modValue = $value;
            if (isset($info['value'])) {
                $method = "get" . $info['value'];
                if (!is_callable(array($object, $method))) {
                    throw new KurogoDataException("Method $method does not exist on " . get_class($object));
                }
                $modValue = $object->$method();
            }
            $detail = array_merge(Kurogo::moduleLinkForValue($info['module'], $modValue, $this), $detail);

        } elseif (isset($info['page'])) {
            $pageValue = $value;
            if (isset($info['value'])) {
                $method = "get" . $info['value'];
                if (!is_callable(array($object, $method))) {
                    throw new KurogoDataException("Method $method does not exist on " . get_class($object));
                }
                $pageValue = $object->$method();
            }

            $detail = array_merge($this->pageLinkForValue($info['page'], $pageValue, $object), $detail);
        }

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
        $page = isset($options['page']) ? $options['page'] : $this->page;
        $section = $page == 'index' ? 'alltasks' : 'tasks';
        $taskGroups = $this->getModuleSections($section);

        $groupOptions = array('tab'=>'tasks', 'page'=>$page);
        if (isset($options['course'])) {
            $groupOptions = array_merge($groupOptions, $this->getCourseOptions());
        }

        if (!$this->getArg('ajaxgroup')) {
            $this->assignGroupLinks('tasks', $taskGroups, $groupOptions);
        }

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

    protected function getOptionsForBrowse($options){
        if ($contentID = $this->getArg('contentID', '')) {
            return array('contentID'=>$contentID);
        }
        return array();
    }

    protected function getOptionsForCourse(){
        $options = array(
            'term' => strval($this->Term)
        );
        return $options;
    }

    protected function getOptionsForResources($options) {
        $page = isset($options['page']) ? $options['page'] : $this->page;
        $groupsConfig = $this->getModuleSections('resources');

        $groupOptions = array('tab'=>'resources','page'=>$page);
        if (isset($options['course'])) {
            $groupOptions = array_merge($groupOptions, $this->getCourseOptions());
        }

        if (!$this->getArg('ajaxgroup')) {
            $this->assignGroupLinks('resources', $groupsConfig, $groupOptions);
        }

        $group = $this->getArg('resourcesGroup', key($groupsConfig));
        $key = $this->getArg('key', '');  //particular type
        $this->assign('resourcesGroup', $group);
        $maxItems = isset($groupsConfig[$group]['max_items']) ? $groupsConfig[$group]['max_items'] : 0;
        $options = array(
            'group'=>$group,
            'limit'=>$maxItems,
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

    protected function initializeGrades($options) {
        if (isset($options['course'])) {
            $course = $options['course'];
        } else {
            throw new KurogoConfigurationException("Aggregated grades not currently supported");
        }

        $contentCourse = $course->getCourse('content');
        $grades = $contentCourse->getGrades(array('user'=>true));

        $gradesLinks = array();
        foreach ($grades as $grade) {
            $gradesLinks[] = $this->linkForGrade($grade);
        }

        $gradesLinks = $this->paginateArray($gradesLinks, $this->getOptionalModuleVar('MAX_GRADES', 5));
        $this->assign('gradesLinks',$gradesLinks);
    }

    protected function initializeInfo($options) {

        $course = $options['course'];
        $infoDetails['info'] = $this->formatCourseDetails($options, 'course-info');
        $this->assign('infoDetails', $infoDetails);

        // @TODO ADD configurable links
        $links = array();
        if ($registrationCourse = $course->getCourse('registration')) {
            if ($registrationCourse->canDrop()) {
                $links[] = array(
                    'title'=> $this->getLocalizedString('DROP_COURSE'),
                    'url' => $this->buildBreadcrumbURL('dropclass', $options)
                );
            }
        }

        $this->assign('links', $links);
        return true;
        break;
    }

    protected function getOptionsForCourses() {
        $options = array(
            'term'=>$this->Term
        );

        return $options;
    }

    protected function getCourses($options, $grouped=false) {

        /** prevent this from being called more than once **/
        static $count=0;
        if ($count) {
            KurogoDebug::debug(func_get_Args(), true);
        }
        /** end debug **/


        $courseListings = $this->getModuleSections('courses');
        $courses = array();

        foreach ($courseListings as $id => $listingOptions) {
            if ($this->isLoggedIn()) {
                $courses[$id] = array('heading'=>$listingOptions['heading'], 'courses'=>$this->controller->getCourses($listingOptions));
            }
        }

        if (!$grouped) {
            $_courses = array();
            foreach ($courses as $c) {
                $_courses = array_merge($_courses, $c['courses']);
            }
            $courses = $_courses;
        }
        $count++; //debug
        return $courses;
    }

    protected function initializeAnnouncements($options) {
        $announcementsLinks = array();
        if (isset($options['course'])) {
            $showCourseTitle = false;
            $courses = array($options['course']);
        } else {
            $showCourseTitle = true;
            $courses = $this->getCourses($this->getOptionsForCourses());
        }

        foreach($courses as $course){
            if ($contentCourse = $course->getCourse('content')) {
                $options['course'] = $contentCourse;
                if ($items = $contentCourse->getAnnouncements($this->getOptionsForAnnouncements($options))) {
                    foreach ($items as $item) {
                        $announcementsLinks[] = $this->linkForAnnouncement($item, $contentCourse, $showCourseTitle);
                    }
                }
            }
        }
        $announcementsLinks = $this->sortCourseContent($announcementsLinks, 'sortDate');
        $announcementsLinks = $this->paginateArray($announcementsLinks, $this->getOptionalModuleVar('MAX_ANNOUNCEMENTS', 5));
        $this->assign('announcementsLinks', $announcementsLinks);
        return true;
    }

    protected function initializeUpdates($options) {
        $updatesLinks = array();

        if (isset($options['course'])) {
            $showCourseTitle = false;
            $courses = array($options['course']);
        } else {
            $showCourseTitle = true;
            $courses = $this->getCourses($this->getOptionsForCourses());
        }

        foreach($courses as $course){
            if ($contentCourse = $course->getCourse('content')) {
                $options['course'] = $contentCourse;
                if($items = $contentCourse->getUpdates($this->getOptionsForUpdates($options))) {
                    foreach ($items as $item){
                        $updatesLinks[] = $this->linkForUpdate($item, $contentCourse, $showCourseTitle);
                    }
                }
            }
        }
        $updatesLinks = $this->sortCourseContent($updatesLinks, 'sortDate');
        $updatesLinks = $this->paginateArray($updatesLinks, $this->getOptionalModuleVar('MAX_UPDATES', 5));
        $this->assign('updatesLinks', $updatesLinks);
        return true;
    }

    protected function initializeTasks($options) {

        if (isset($options['course'])) {
            $courses = array($options['course']);
        } else {
            $courses = $this->getCourses($this->getOptionsForCourses());
        }

        $tasks = array();

        foreach ($courses as $course) {
            if ($contentCourse = $course->getCourse('content')) {
                $tasksOptions = $this->getOptionsForTasks($options);
                $group = $tasksOptions['group'];
                $groups = $contentCourse->getTasks($tasksOptions);
                foreach ($groups as $groupTitle => $items){
                    if ($group == 'priority') {
                        $title = $this->getLocalizedString('CONTENT_PRIORITY_TITLE_'.strtoupper($groupTitle));
                    } else {
                        $title = $groupTitle;
                    }
                    $task = array(
                        'title' => $title,
                        'items' => $items,
                    );

                    if (isset($tasks[$title])) {
                        $tasks[$title]['items'] = array_merge($tasks[$title]['items'], $task['items']);
                    } else {
                        $tasks[$title]['items'] = $task['items'];
                    }
                }
            }
        }
        //Sort aggregated content
        $sortedTasks = array();
        foreach ($tasks as $title => $group) {
            $items = $this->sortCourseContent($group['items']);
            $tasksLinks = array();
            foreach ($items as $item) {
                $tasksLinks[] = $this->linkForTask($item, $item->getContentCourse());
            }
            $task = array(
                'title' => $title,
                'items' => $tasksLinks,
            );
            if (isset($sortedTasks[$title])) {
                $sortedTasks[$title] = array_merge($tasks[$title], $task);
            } else {
                $sortedTasks[$title] = $task;
            }
        }

        $this->assign('tasks', $sortedTasks);
    }

    protected function initializeResources($options) {

        if (isset($options['course'])) {
            $course = $options['course'];
        } else {
            throw new KurogoConfigurationException("Aggregated resources not currently supported");
        }

        $contentCourse = $course->getCourse('content');
        $resourcesLinks = array();
        $resourcesOptions = $this->getOptionsForResources($options);
        $groups = $contentCourse->getResources($resourcesOptions);
        $group = $resourcesOptions['group'];
        if ($group == "date") {
            $limit = 0;
            $pageSize = $resourcesOptions['limit'];
        } else {
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
                if ($index >= $limit && $limit != 0) {
                    break;
                }
                $groupItems[] = $this->linkForContent($item, $contentCourse);
                $index++;
            }
            if ($group == 'type') {
                $title = $this->getLocalizedString('CONTENT_TYPE_TITLE_'.strtoupper($groupTitle));
            } else {
                $title = $groupTitle;
            }
            $resource = array(
                'title' => $title,
                'items' => $groupItems,
                'count' => count($items),
            );
            if ($group != "date" && count($items) > $limit && $limit != 0) {
                $courseOptions = $this->getCourseOptions();
                $courseOptions['group'] = $group;
                $courseOptions['key'] = $groupTitle;
                $courseOptions['tab'] = 'resources';

                // currently a separate page
                $resource['url'] = $this->buildBreadcrumbURL("resourceSeeAll", $courseOptions);
            }
            $resourcesLinks[] = $resource;
        }
        if ($group == "date" && $pageSize && isset($resourcesLinks[0])) {
            $resource = $resourcesLinks[0];
            $limitedItems = $this->paginateArray($resource['items'], $pageSize);
            $resourcesLinks[0]['items'] = $limitedItems;
            $resourcesLinks[0]['count'] = count($limitedItems);
        }

        $this->assign('resourcesLinks', $resourcesLinks);
        $this->assign('courseResourcesGroup', $group);
    }

    protected function initializeBrowse($options){
        if (isset($options['course'])) {
            $course = $options['course'];
        } else {
            throw new KurogoConfigurationException("Aggregated resources not currently supported");
        }

        $contentCourse = $course->getCourse('content');
        $browseOptions = $this->getOptionsForBrowse($options);
        $browseContent = $contentCourse->getContentByParentId($browseOptions);

        $browseLinks = array();
        foreach ($browseContent as $content) {
            switch ($content->getContentType()) {
                case 'folder':
                    $browseLinks[] = $this->linkForFolder($content, $contentCourse);
                    break;
                default:
                    $browseLinks[] = $this->linkForContent($content, $contentCourse);
                    break;
            }
        }
        $this->assign('browseLinks', $browseLinks);
    }

    protected function initializeCourses() {

        if ($this->isLoggedIn()) {
            $courses = $this->getCourses($this->getOptionsForCourses(), true);
            $options = $this->getOptionsForCourse();

            $coursesListLinks = array();

            foreach ($courses as $id => $coursesTuple) {
                $coursesLinks = array();
                foreach ($coursesTuple['courses'] as $course) {
                    $courseLink = $this->linkForCourse($course, $options);
                    $coursesLinks[] = $courseLink;
                }

                $courseListHeading = str_replace("%t", $this->Term->getTitle(), $coursesTuple['heading']);
                $courseListHeading = str_replace("%n", count($coursesLinks), $courseListHeading);

                $coursesListLinks[] = array('courseListHeading' => $courseListHeading,
                                            'coursesLinks' => $coursesLinks);
            }
            $this->assign('coursesListLinks', $coursesListLinks);
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
                'url'   => $this->buildBreadcrumbURL('catalog', array('term'=>strval($this->Term)))
            );

            if ($bookmarks = $this->getBookmarksForTerm($this->Term)) {
                $catalogItems[] = array(
                    'title' => $this->getLocalizedString('BOOKMARKED_COURSES') . " (" . count($bookmarks) . ")",
                    'url'   => $this->buildBreadcrumbURL('bookmarks', array('term'=>strval($this->Term))),
                );
            }

            $courseCatalogText = str_replace("%t", $this->Term->getTitle(), $this->getLocalizedString('COURSE_CATALOG_TEXT'));
            $this->assign('courseCatalogText', $courseCatalogText);
            $this->assign('catalogItems', $catalogItems);
        }

        return true;
    }

    protected function showTab($tabID, $tabData) {
        if (self::argVal($tabData, 'protected', 0) && !$this->isLoggedIn()) {
            return false;
        }

        switch ($tabID) {
            case 'courses':
                if ($this->pagetype=='tablet' || !$this->isLoggedIn()) {
                    $this->initializeCourses();
                    return false;
                }

                break;
        }

        return true;
    }

    protected function initializeForPage() {
        $this->originalPage = $this->page;

        // Ajax loading and error strings
        $this->addInlineJavascript('var AJAX_CONTENT_LOADING = "<div class=\"loading\">'.
            $this->getLocalizedString('AJAX_CONTENT_LOADING').'</div>";');
        $this->addInlineJavascript('var AJAX_CONTENT_LOAD_FAILED = "<div class=\"nonfocal\">'.
            $this->getLocalizedString('AJAX_CONTENT_LOAD_FAILED').'</div>";');

        if ($this->pagetype == 'tablet') {
            $this->addOnOrientationChange('moduleHandleWindowResize();');
        }

        switch($this->page) {
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
                if ($content->getAuthor()) {
                    $this->assign('contentAuthor', 'Posted by ' . $content->getAuthor());
                }
                if ($content->getPublishedDate()) {
                    $this->assign('contentPublished', $this->elapsedTime($content->getPublishedDate()->format('U')));
                }

                if ($content->getContentType() == "page") {
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
                if ($task->getPublishedDate()) {
                    $this->assign('taskDate', 'Published: '.DateFormatter::formatDate($task->getPublishedDate(), DateFormatter::LONG_STYLE, DateFormatter::NO_STYLE));
                }
                if ($task instanceOf TaskCourseContent) {
                    if ($task->getDueDate()) {
                        $this->assign('taskDueDate', DateFormatter::formatDate($task->getDueDate(), DateFormatter::MEDIUM_STYLE, DateFormatter::NO_STYLE));
                    }
                    $this->assign('links', $task->getLinks());
                }

                break;

        	case 'roster':
        	    if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
        	    }

        		$students = $course->getStudents();
        		$links = array();
        		foreach ($students as $student) {
        			$value = $student->getFullName();
        			$link = Kurogo::moduleLinkForValue('people', $value, $this, $student);
        			if (!$link) {
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
        			    'url'=>$this->buildBreadcrumbURL('dropclass', $options)
        		);
        		$links[] = array(
        		    'title'=>$this->getLocalizedString('DROP_CANCEL'),
        		    'url'=>$this->buildBreadcrumbURL('info', $options)
        		);
				$this->assign('links',$links);
        	    break;

            case 'catalog':
                if ($areas = $this->controller->getCatalogAreas()) {
                    $areasList = array();
                    $areaOptions = array('term' => strval($this->Term));
                    foreach ($areas as $CourseArea) {
                        $areasList[] = $this->linkForCatalogArea($CourseArea, $areaOptions);
                    }
                    $this->assign('areas', $areasList);
                }

                if ($bookmarks = $this->getBookmarksForTerm($this->Term)) {
                    $bookmarksList[] = array(
                        'title' => $this->getLocalizedString('BOOKMARKED_COURSES') . " (" . count($bookmarks) . ")",
                        'url'   => $this->buildBreadcrumbURL('bookmarks', array('term' => strval($this->Term))),
                    );
                    $this->assign('bookmarksList', $bookmarksList);
                }

                $this->assign('catalogHeader', $this->getOptionalModuleVar('catalogHeader','','catalog'));
                $this->assign('catalogFooter', $this->getOptionalModuleVar('catalogFooter','','catalog'));
                $this->assign('placeholder', $this->getLocalizedString("CATALOG_SEARCH"));

                break;

            case 'catalogarea':
                $area = $this->getArg('area');
                $options = array('term' => $this->Term);
                if ($parent = $this->getArg('parent')) {
                    $options['parent'] = $parent;
                }

                if (!$CourseArea = $this->controller->getCatalogArea($area, $options)) {
                    $this->redirectTo('catalog', array());
                }
                $this->setBreadcrumbTitle($CourseArea->getCode());
                $this->setBreadcrumbLongTitle($CourseArea->getTitle());

                $areas = $CourseArea->getAreas();

                $areasList = array();
                $areaOptions = array('term' => strval($this->Term));
                foreach ($areas as $areaObj) {
                    $areasList[] = $this->linkForCatalogArea($areaObj, $areaOptions);
                }

                $courses = array();
                $searchOptions = $options = array(
                    'term'=>strval($this->Term),
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
                $this->assign('hiddenArgs', array('area' => $area, 'term' => strval($this->Term)));
                $this->assign('placeholder', $this->getLocalizedString("SEARCH_MODULE", $CourseArea->getTitle()));

                break;


            case 'catalogcourse':
            	$area = $this->getArg('area');
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }
                $this->setBreadcrumbTitle($course->getField('courseNumber'));
                $this->setBreadcrumbLongTitle($course->getTitle());

                // Bookmark
                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $cookieParams = array(
                    	'title' => $course->getTitle(),
                        'id' => $course->getID(),
                        'term'  => rawurlencode($this->Term->getID()),
                        'area'    => rawurlencode($area),
                        'courseNumber' => rawurlencode($course->getField('courseNumber'))
                    );

                    $cookieID = http_build_query($cookieParams);
                    $this->generateBookmarkOptions($cookieID);
                }

                $options = array(
                    'course'=> $course
                );

                $tabsConfig = $this->getModuleSections('catalogcoursetabs');
                $tabs = array();
                $tabTypes = array();
                $infoDetails = array();
                foreach ($tabsConfig as $tab => $tabData) {
                    $tabs[] = $tab;
                    if (!isset($tabData['type'])) {
                        $tabData['type'] = 'details';
                    }

                    $configName = $this->page . '-' . $tab;
                    $infoDetails[$tab] = $this->formatCourseDetails($options, $configName);
                    $tabTypes[$tab] = $tabData['type'];
                }

                $this->enableTabs($tabs);
                $this->assign('tabs',$tabs);
                $this->assign('tabTypes',$tabTypes);
                $this->assign('tabDetails', $infoDetails);
            	break;

            case 'catalogsection':
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }

                if (!$catalogCourse = $course->getCourse('catalog')) {
                    $this->redirectTo('info', $this->args);
                }

                $sectionNumber = $this->getArg('sectionNumber');

                if (!$section = $catalogCourse->getSection($sectionNumber)) {
                    $this->redirectTo('info', $this->args);
                }

                $options = array(
                    'section'=> $section
                );

                $tabsConfig = $this->getModuleSections('catalogsectiontabs');
                $tabs = array();
                $tabTypes = array();
                foreach ($tabsConfig as $tab => $tabData) {
                    $tabs[] = $tab;
                    if (!isset($tabData['type'])) {
                        $tabData['type'] = 'details';
                    }

                    $this->initializeForInfoTab($tab, array_merge($tabData, $options));
                    $tabTypes[$tab] = $tabData['type'];
                }

                $this->enableTabs($tabs);
                $this->assign('tabs',$tabs);
                $this->assign('tabTypes',$tabTypes);
                $this->assign('tabDetails', $this->infoDetails);
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

            case 'bookmarks':
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

                $options = array(
                    'course'=> $course,
                );

                $tabsConfig = $this->getModuleSections('coursetabs');
                $tabs = array();
                $javascripts = array();
                $args = $this->args;
                $args['ajax'] = true;
                $args['page'] = $this->page;
                $this->tab = $this->getArg('tab', key($tabsConfig));
                foreach ($tabsConfig as $tabID => $tabData) {
                    if ($this->showTab($tabID, $tabData)) {
                        if ($tabID == $this->tab) {
                            $method = "initialize" . $tabID;
                            if (!is_callable(array($this, $method))) {
                                throw new KurogoDataException("Method $this does not exist on " . get_class($this));
                            }
                            $this->originalPage = $tabID;
                            $this->$method(array('course' => $course, 'page' => $this->page));
                            $this->originalPage = $this->page;
                        } else {
                            $args['tab'] = $tabID;
                            $javascripts[$tabID] = "loadTab(tabId, '".$this->buildAjaxBreadcrumbURL($tabID, $args)."');";
                        }
                        $tabs[] = $tabID;
                    }
                }

                //@TODO enable javascript for loading content
                $this->enableTabs($tabs, null, $javascripts);
                $this->assign('tabs', $tabs);
                $this->assign('currentTab', $this->tab);
                break;

            case 'courses':
                $this->initializeCourses();
                break;

            case 'resources':
            case 'grades':
            case 'updates':
            case 'tasks':
            case 'announcements':
            case 'browse':
            case 'info':
                $options = array();
                $_args = $this->args;
                unset($this->args['ajax']);
                if ($page= $this->getArg('page')) {
                    $this->page = $page;
                    $options['page'] = $page;
                }
                if ($course = $this->getCourseFromArgs()) {
                    $options['course'] = $course;
                }
                $method = "initialize" . $this->originalPage;
                if (!is_callable(array($this, $method))) {
                    throw new KurogoDataException("Method $method does not exist on " . get_class($this));
                }

                $this->$method($options);
                $this->page = $this->originalPage;
                break;

            case 'index':
            case 'allCourses':
                $tabsConfig = $this->getModuleSections('indextabs');
                $options = array('page'=>$this->page);
                $tabs = array();
                $javascripts = array();
                $this->tab = $this->getArg('tab', key($tabsConfig));
                $args = $this->args;
                $args['ajax'] = true;
                $args['page'] = $this->page;
                foreach($tabsConfig as $tabID => $tabData){
                    if ($this->showTab($tabID, $tabData)) {
                        if ($tabID == $this->tab) {
                            $method = "initialize" . $tabID;
                            if (!is_callable(array($this, $method))) {
                                throw new KurogoDataException("Method $method does not exist on " . get_class($this));
                            }
                            $this->$method($options);
                        } else {
                            $javascripts[$tabID] = "loadTab(tabId, '".$this->buildAjaxBreadcrumbURL($tabID, $args)."');";
                        }
                        $tabs[] = $tabID;

                    }
                }

                if ($tabs) {
                    $this->enableTabs($tabs, null, $javascripts);
                    $this->assign('tabs', $tabs);
                    $this->assign('currentTab', $this->tab);
                }
                break;

            case 'search':
                $searchTerms = $this->getArg('filter', false);

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
            case 'grade':
                $gradeID = $this->getArg('gradeID');

                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }

                if (!$contentCourse = $course->getCourse('content')) {
                    $this->redirectTo('index');
                }

                if (!$gradeAssignment = $contentCourse->getGradeById($gradeID, array('user'=>true))) {
                    throw new KurogoDataException($this->getLocalizedString('ERROR_GRADE_NOT_FOUND'));
                }

                $gradeContent = array();

                $gradeContent['title'] = $gradeAssignment->getTitle();
                if($gradeAssignment->getDueDate()){
                    $gradeContent['dueDate'] = DateFormatter::formatDate($gradeAssignment->getDueDate(), DateFormatter::LONG_STYLE, DateFormatter::SHORT_STYLE);
                }
                if($gradeAssignment->getDateModified()){
                    $gradeContent['dateModified'] = DateFormatter::formatDate($gradeAssignment->getDateModified(), DateFormatter::LONG_STYLE, DateFormatter::SHORT_STYLE);
                }

                if($gradeScore = $gradeAssignment->getGrade()){
                    // Strict type checking in case score is 0.
                    if($gradeScore->getScore() !== null){

                        $gradeContent['grade'] = number_format($gradeScore->getScore());
                    }

                    if($gradeScore->getInstructorComment()){
                        $gradeContent['instructorComment'] = $gradeScore->getInstructorComment();
                    }

                    if($gradeScore->getStudentComment()){
                        $gradeContent['studentComment'] = $gradeScore->getStudentComment();
                    }
                }

                // Strict type checking in case possible points is 0.
                if($gradeAssignment->getPossiblePoints() !== null){
                    $gradeContent['possiblePoints'] = number_format($gradeAssignment->getPossiblePoints());
                }

                $this->assign('grade', $gradeContent);
                break;
        }
    }
}

