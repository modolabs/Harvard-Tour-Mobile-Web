<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
    protected $originalPage;
    protected $tab;

    /**
     * Creates a list item link for Tasks
     * @param  CourseContent       $task                   The task to link to
     * @param  CourseContentCourse $course                 The Course the task belongs to
     * @param  boolean             $includeCourseName=true Whether to include the Course name in the subtitle
     * @return array
     */
    public function linkForTask(CalendarCourseContent $task, CourseContentCourse $course, $includeCourseName=true) {
    	$link = array(
            'title' =>$includeCourseName ? Sanitizer::sanitizeHTML($task->getTitle()) : $course->getTitle(),
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

    /**
     * Creates a list item link for Content
     * @param  CourseContent       $content The content to link to
     * @param  CourseContentCourse $course  The Course the content belongs to
     * @return array
     */
    public function linkForContent(CourseContent $content, CourseContentCourse $course) {
    	$link = array(
            'title' => Sanitizer::sanitizeHTML($content->getTitle()),
            'subtitle' => $content->getSubTitle(),
            'type'  => $content->getContentType(),
            'class' => "content content_" . $content->getContentType(),
            'img'   => "/modules/courses/images/content_" . $content->getContentClass() . $this->imageExt
        );

        // Display published date and author
        if ($content->getPublishedDate()){
	    	if ($content->getAuthor()) {
	    	    $updated = $this->getLocalizedString('CONTENTS_AUTHOR_PUBLISHED_STRING', $content->getAuthor(), $this->elapsedTime($content->getPublishedDate()->format('U')));
	    	} else {
	    		$updated = $this->getLocalizedString('CONTENTS_PUBLISHED_STRING', $this->elapsedTime($content->getPublishedDate()->format('U')));
	    	}
	    	$link['subtitle'] = $link['updated'] = $updated;
	    } else {
            $link['subtitle'] = $content->getSubTitle();
	    }

        $options = $this->getCourseOptions();
        $options['contentID'] = $content->getID();
        $options['courseID'] = $course->getCommonID();
        $options['type'] = $content->getContentType();
        $link['sortDate'] = $content->getPublishedDate() ? $content->getPublishedDate() : 0;
        $link['url'] = $this->buildBreadcrumbURL('content', $options);

        return $link;
    }

    /**
     * Creates a list item link for Folders
     * @param  CourseContent       $content The folder to link to
     * @param  CourseContentCourse $course  The Course the folder belongs to
     * @return array
     */
    public function linkForFolder(FolderCourseContent $content, CourseContentCourse $course) {
        $link = array(
            'title' => Sanitizer::sanitizeHTML($content->getTitle()),
            'type'  => $content->getContentType(),
            'class' => "content content_" . $content->getContentType(),
            'img'   => "/modules/courses/images/content_" . $content->getContentClass() . $this->imageExt
        );

        $options = $this->getCourseOptions();
        $options['contentID'] = $content->getID();
        $options['type'] = $content->getContentType();
        $options['tab'] = 'browse';

        $link['subtitle'] = '<div class="folder-subtitle"><img src="/common/images/blank.png" style="position: absolute;visibility:hidden;" onload="loadFolderCount(this, ' . "'" . $this->buildAjaxBreadcrumbURL('folderCount', $options) . "'" .');"/></div>';

        $link['url'] = $this->buildBreadcrumbURL('fullbrowse', $options, true);

        return $link;
    }

    /**
     * Creates a list item link for Announcements
     * @param  AnnouncementCourseContent $announcement            The announcement to link to
     * @param  CourseContentCourse       $course                  The Course the announcement belongs to
     * @param  boolean                   $includeCourseName=false Whether to include the Course title in the subtitle or not
     * @return array
     */
    public function linkForAnnouncement(AnnouncementCourseContent $announcement, CourseContentCourse $course, $includeCourseName=false){
        $contentID = $announcement->getID();
        $options = $this->getCourseOptions();
        $options['contentID'] = $contentID;
        $options['courseID'] = $course->getCommonID();
        $options['type'] = $announcement->getContentType();

        $link = array(
            'title' => $includeCourseName ? $course->getTitle() : Sanitizer::sanitizeHTML($announcement->getTitle()),
        );
        foreach (array('courseID') as $field) {
            if (isset($data[$field])) {
                $options[$field] = $data[$field];
            }
        }

        if ($includeCourseName) {
            $link['announcementTitle'] = $announcement->getTitle();
        }

        $link['url'] = $this->buildBreadcrumbURL('content', $options);

        if ($this->pagetype == 'tablet') {
            $body = $announcement->getDescription();
            $maxLength = $this->getOptionalModuleVar('ANNOUNCEMENT_TABLET_MAX_LENGTH', 500);
            $retriever = $announcement->getContentRetriever();

            $body = Sanitizer::sanitizeAndTruncateHTML($body, $truncated,
                $this->getOptionalModuleVar('ANNOUNCEMENT_TABLET_MAX_LENGTH', 500),
                $this->getOptionalModuleVar('ANNOUNCEMENT_TABLET_MAX_LENGTH_MARGIN', 50),
                $this->getOptionalModuleVar('ANNOUNCEMENT_TABLET_MIN_LINE_LENGTH', 50),
                'inline|block|link|media|list|table', // allowed tags list for user-entered html
                $retriever ? $retriever->getEncoding() : 'utf-8');

            if ($truncated) {
                // remove links (outer nav <a> will confuse browser)
                $body = Sanitizer::sanitizeHTML($body, 'inline|block|media|list|table');
            } else {
                // didn't truncate html -- displaying entire announcement
                unset($link['url']);
            }

            $link['body'] = $body;
        }


        if ($announcement->getPublishedDate()){
            $published = $this->elapsedTime($announcement->getPublishedDate()->format('U'));
            if ($announcement->getAuthor()) {
                $published = $this->getLocalizedString('CONTENTS_AUTHOR_PUBLISHED_STRING', $announcement->getAuthor(), $published);
            } else {
                $published = $this->getLocalizedString('CONTENTS_PUBLISHED_STRING', $published);
            }
            $link['published'] = $published;
        }

        $link['sortDate'] = $announcement->getPublishedDate() ? $announcement->getPublishedDate() : 0;
        return $link;
    }

    /**
     * Creates a list item link for Updates
     * @param  CourseContent       $content                 The updates to link to
     * @param  CourseContentCourse $course                  The Course the update belongs to
     * @param  boolean             $includeCourseName=false Whether to include the Course title in the subtitle or not
     * @return array
     */
    public function linkForUpdate(CourseContent $content, CourseContentCourse $course, $includeCourseName=false) {

        $contentID = $content->getID();
        $options = $this->getCourseOptions();
        $options['contentID'] = $contentID;
        $options['courseID'] = $course->getCommonID();
        $options['type'] = $content->getContentType();

        $link = array(
            'title' => $includeCourseName ? $course->getTitle() : Sanitizer::sanitizeHTML($content->getTitle()),
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

    /**
     * Create a list item link to a Catalog Area
     * @param  CourseArea $area            The area to link to
     * @param  array      $options=array() Any options needed to create the link
     * @return array
     */
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
        if ($this->getOptionalModuleVar('SHOW_AREA_LABELS', true) && $area->showCode()) {
        	$link['label'] = $area->getCode();
        }

        return $link;
    }

    protected function linkForAttachment(CourseContentAttachment $attachment, CourseContent $content) {

        $link = array(
            'title'=>$attachment->getTitle() ? $attachment->getTitle() : 'Download File',
            'img'   => "/modules/courses/images/content_" . $attachment->getContentClass() . $this->imageExt,
        );

        $subtitle = $attachment->getFileName();
        if ($filesize = $attachment->getFileSize()) {
            $subtitle .= " (" . $this->formatBytes($filesize) . ")";
        }

        switch ($attachment->getDownloadMode())
        {
            case $content::MODE_DOWNLOAD:
                $options = $this->getCourseOptions();
                $options['contentID'] = $content->getID();

                if ($fileID = $attachment->getID()){
                    $options['fileID'] = $fileID;
                }

                $link['url'] = $this->buildDownloadURL($this->buildURL('download', $options));
                break;
            case $content::MODE_URL:
                $link['url'] = $this->buildDownloadURL($attachment->getURL());
                $link['class'] = 'external';
                $link['linkTarget'] = '_blank';
                break;
            default:
                break;
        }

        $link['subtitle'] = $subtitle;

        return $link;

    }

    /**
     * Return the title of the tab for a particular page.
     * @param  string $page The page the tab is on
     * @param  string $tab  The tab to get the title for
     * @return string
     */
    protected function getTitleForTab($page, $tab) {
        // TODO: Finish this
    }

    protected function linkForGradebookEntry(RegistrationGradebookEntry $entry, $options = array()){
        $item = array(
            'title' => $entry->getTitle(),
            'subtitle' => $entry->getSubtitle(),
        );

        $grades = array();
        foreach ($entry->getGrades() as $gradeObj) {
            $grade = array(
                'title' => $gradeObj->getTitle(),
                'date' => $gradeObj->getDate(),
                'score' => $gradeObj->getScore(),
                'type'  => $gradeObj->getType(),
            );
            $grades[] = $grade;
        }
        $item['grades'] = $grades;
        return $item;
    }

    /**
     * Create a list item link for Courses
     * @param  CourseInterface $course          The course to link to
     * @param  array           $options=array() Any options needed to create the link
     * @return array
     */
    protected function linkForCourse(CourseInterface $course, $options=array()) {

        $options = array_merge($options, array(
                'courseID'  => $course->getID(),
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

        $contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT);

        $page = 'course';
        if ($contentCourse) {
            $subtitle = array();
            $options['course'] = $contentCourse;

            // TODO: Change this to be anything but tablet
            if ($this->pagetype !='tablet') {
                // If we can get the last update display some info about it in the subtitle
                if ($lastUpdateContent = $contentCourse->getLastUpdate()) {
                    $subtitle[] = $lastUpdateContent->getTitle();
                    if ($publishedDate = $lastUpdateContent->getPublishedDate()) {
                        $published = $this->elapsedTime($publishedDate->format('U'));
                        if ($lastUpdateContent->getAuthor()) {
                            $published = $this->getLocalizedString('CONTENTS_AUTHOR_PUBLISHED_STRING', $lastUpdateContent->getAuthor(), $published);
                        } else {
                            $published = $this->getLocalizedString('CONTENTS_PUBLISHED_STRING', $published);
                        }
                        $subtitle[] = $published;
                    }
                    $link['type']  = $lastUpdateContent->getContentType();
                    $link['img']   = "/modules/courses/images/content_" . $lastUpdateContent->getContentType() . $this->imageExt;
                }
                $link['subtitle'] = implode("<br />", $subtitle);
            }

        }
        unset($options['course']);

        // Set variables for use with AJAX
        if ($this->pagetype == 'tablet') {
            $link['url'] = $this->buildAjaxBreadcrumbURL($page, $options);
            $link['updateIconsURL'] = $this->buildAjaxBreadcrumbURL('courseUpdateIcons', $options);

        } else {
            $link['url'] = $this->buildBreadcrumbURL($page, $options);
        }
        return $link;
    }

    /**
     * Create a list item link for a Catalog Course
     * @param  CourseInterface $course  The course to link to
     * @param  array           $options Any options needed to create the link
     * @return array
     */
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

    /**
     * Create a list item link for a Grade
     * @param  GradeAssignment $gradeAssignment The assignment to link to
     * @return array
     */
    protected function linkForGrade(GradeAssignment $gradeAssignment) {
        $options = $this->getCourseOptions();
        $options['gradeID'] = $gradeAssignment->getId();

        $link = array();
        $link['title'] = $gradeAssignment->getTitle();

        $subtitle = array();
        // If a score is available
        if ($gradeScore = $gradeAssignment->getGrade()) {
            // If the score has been graded display the grade, otherwise display the status
            if($gradeScore->getStatus() == GradeScore::SCORE_STATUS_GRADED){
                $grade = $gradeScore->getScore();
                $possiblePoints = $gradeAssignment->getPossiblePoints();
                if(is_numeric($grade) && ($possiblePoints != 0)){
                    $percent = ($grade / $possiblePoints)*100;
                    $subtitle = $this->getLocalizedString('GRADE_SUBTITLE_GRADED', $percent, number_format($gradeAssignment->getGrade()->getScore(), 2), number_format($gradeAssignment->getPossiblePoints(),2));
                }else{
                    $subtitle = $this->getLocalizedString('GRADE_SUBTITLE', number_format($gradeAssignment->getGrade()->getScore(), 2));
                }
            }else{
                $subtitle = $this->getLocalizedString('GRADE_SUBTITLE', $this->getLocalizedString($gradeScore->getStatus()));
            }
        }else{
            $subtitle = $this->getLocalizedString('GRADE_SUBTITLE', $this->getLocalizedString('SCORE_STATUS_NO_GRADE'));
        }

        $link['subtitle'] = $subtitle;

        $link['url'] = $this->buildBreadcrumbURL('grade', $options);

        return $link;
    }

    /**
     * Create a link to a given page setting value in the parameters
     * @param  string $page   The page to link to
     * @param  string $value  The value to assign
     * @param  mixed  $object Not used
     * @return array
     */
    protected function pageLinkForValue($page, $value, $object) {

        $args = $this->args;
        switch ($page)
        {
            case 'coursesection':
            case 'catalogsection':
                $args['sectionNumber'] = $value;
                break;

            default:
                $args['value'] = $value;
                break;
        }

        $link = array(
            'title'=>$value,
            'url'=>$this->buildBreadcrumbURL($page, $args)
        );

        return $link;
    }

    /**
     * Formats an integer into bytes, kilobytes, etc
     * @param  int $value The integer to format
     * @return string     The formatted string
     */
    protected function formatBytes($value) {
		//needs integer
		if (!preg_match('/^\d+$/', $value)) {
			return $value;
		}

		//less than 1024 bytes return bytes
		if ($value < 1024) {
			return sprintf("%d B", $value);
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

    /**
     * Returns an array of links to the content itself,
     * or files associated with the content.
     * @param  CourseContent $content The content being linked to or used for linking
     * @return array
     */
    protected function getContentLinks(CourseContent $content) {
        $links = array();
        switch ($content->getContentType()) {
            // Create a link to the content
            case 'link':
                $links[] = array(
                    'title'=>$content->getTitle(),
                    'subtitle'=>$content->getURL(),
                    'url'=>$this->buildExternalURL($content->getURL()),
                    'class'=>'external',
                    'linkTarget'=>'_blank'
                );
                break;
            /**
             * If the mode is download:
             *    Iterate through all the files associated with the content. These may
             *    instances of DownloadCourseContent or DownloadFileAttachment. Create
             *    links to the content/files for downloading.
             * If the mode is url:
             *    Create an external link to the content/files
             */
            case 'file':
                if($url = $content->getURL()){
                    $links[] = array(
                        'title'=>$this->getLocalizedString('VIEW_IN_BROWSER'),
                        'subtitle'=>$content->getURL(),
                        'url'=>$this->buildExternalURL($content->getURL()),
                        'class'=>'external',
                        'linkTarget'=>'_blank'
                    );
                }
//                $downloadMode = $content->getDownloadMode();
                if ($attachments = $content->getAttachments()) {
                    foreach ($attachments as $attachment) {
                        $links[] = $this->linkForAttachment($attachment, $content);
                    }
                }
                break;
            // Create an external link to the content
            case 'page':
                $viewMode = $content->getViewMode();
                if($viewMode == $content::MODE_URL) {
                    $links[] = array(
                        'title'=>$content->getTitle(),
                        'subtitle'=>$content->getFilename(),
                        'url'=>$content->getFileurl(),
                        'class'=>'external',
                        'linkTarget'=>'_blank'
                    );
                }
                break;
            // These types of content do not have links
            case 'announcement':
            case 'task':
            case 'blog':
            case 'youtube':
            case 'unsupported':
                break;
            default:
                throw new KurogoException("Unhandled content type " . $content->getContentType());
        }

        return $links;
    }

    protected function assignTerms($_terms, $selectedTerm) {
    	$terms = array();
    	if ($selectedTerm == CoursesDataModel::CURRENT_TERM) {
    	    if ($Term = $this->controller->getCurrentTerm()) {
    	        $selectedTerm = $Term->getID();
    	    }
    	}
    	
        foreach($_terms as $term) {
            $terms[] = array(
                'value'     => $term->getID(),
                'title'     => $term->getTitle(),
                'selected'  => ($term->getID() == $selectedTerm)
            );
            if ($term->getID() == $selectedTerm) {
                $this->assign('termTitle', $term->getTitle());
            }
        }

        if (count($terms)>1) {
            $this->assign('terms', $terms);
        }
    }
    
    /**
     * Gets the course from the request args.
     * Sets the courseTitle, courseID, and sectionNumber if available
     * @return Course
     */
    protected function getCourseFromArgs() {

        if ($courseID = $this->getArg('courseID')) {
            $options = $this->getCourseOptions();

            if ($course = $this->controller->getCourseByCommonID($courseID, $options)) {
                $this->assign('courseTitle', $course->getTitle());
                $this->assign('courseID', $course->getID());

                if ($section = $this->getArg('section')) {
                    if ($catalogCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CATALOG)) {
                        if ($class = $catalogCourse->getSection($section)) {
                            $this->assign('sectionNumber', $class->getSectionNumber());
                        }
                    }
                }
            }
            return $course;
        }
    }

    /**
     * Gets the title of the feed IDed by $feed
     * @param  string $feed The feed to lookup
     * @return string
     */
    protected function getFeedTitle($feed) {
        return isset($this->feeds[$feed]['TITLE']) ? $this->feeds[$feed]['TITLE'] : '';
    }

    /**
     * Returns the URL for a saved bookmark.
     * @param  string $aBookmark  The bookmark to link to
     * @return string
     */
    protected function detailURLForBookmark($aBookmark) {
        return $this->buildBreadcrumbURL('catalogcourse', array(
            'courseID'  => $this->getBookmarkParam($aBookmark, 'id'),
            'term'      => $this->getBookmarkParam($aBookmark, 'term'),
            'area'      => $this->getBookmarkParam($aBookmark, 'area'),
        ));
    }

    /**
     * Gets the title of a bookmark.
     * @param  string $aBookmark The bookmark to get the title of
     * @return string
     */
    protected function getTitleForBookmark($aBookmark) {
        return $this->getBookmarkParam($aBookmark, 'title');
    }

    /**
     * Retrieves a parameter from the bookmark string.
     * @param  string $aBookmark The bookmark to get the parameter from
     * @param  string $param     A paramter (title, id, term, area, etc) to get from the bookmark
     * @return string
     */
    protected function getBookmarkParam($aBookmark, $param){
        parse_str($aBookmark, $params);
        if(isset($params[$param])){
            return $params[$param];
        }
        return null;
    }

    /**
     * Initializes the module. Gets the feed data, creates the controller,
     * and assigns the term.
     */
    protected function initialize() {
        if(!$this->feeds = $this->loadFeedData()){
            throw new KurogoConfigurationException("Feeds configuration cannot be empty.");
        }
        $this->controller = CoursesDataModel::factory($this->defaultModel, $this->feeds);
        //load showCourseNumber setting
        $this->showCourseNumber = $this->getOptionalModuleVar('SHOW_COURSENUMBER_IN_LIST', 1);
        $this->assign('hasPersonalizedCourses', $this->controller->hasPersonalizedCourses());
    }

    /**
     * Gets an array of options based on the current page arguements.
     * Used to pass options from one page to another.
     * @return array
     */
    protected function getCourseOptions() {
        $courseID = $this->getArg('courseID');
        $area = $this->getArg('area');
        $term = $this->getArg('term');

        $options = array(
            'courseID'  => $courseID,
        );
        
        if ($term) {
            $options['term'] = $term;
        }

        if ($area) {
            $options['area'] = $area;
        }

        return $options;
    }

    /**
     * Assigns the group links for the groups in a tabstrip on a page.
     * @param  string $tabPage             The tab
     * @param  array  $groups              The array of groups to link to
     * @param  array  $defaultGroupOptions An array of options to include in the page link
     */
    protected function assignGroupLinks($tabPage, $groups, $defaultGroupOptions = array()){
        if ($this->pagetype == 'basic' || $this->pagetype == 'touch') {
            $defaultGroupOptions = array_merge($defaultGroupOptions, $this->args);
        }
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
        $this->assign('tabCount', $tabCount);
        $this->assign($tabPage.'TabCount', $tabCountMap[$tabCount]);
        $this->assign($tabPage.'GroupLinks', $groupLinks);
        $this->assign('tabstripId', $tabPage.'-'.md5($this->buildURL($this->page, $this->args)));
    }

    /**
     * Paginates an array of items.
     * @param  array  $contents The array of items to paginate
     * @param  int    $limit    The maximum number of items per page
     * @return array
     */
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

    /**
     * Sorts an array of content by the specified method, or by the content's sortBy() function.
     * @param  array  $courseContents The array of content being sorted
     * @param  string $sort=null      The way of sorting. May be a key used in sortByField.
     * @return array
     */
    public function sortCourseContent($courseContents, $sort=null) {
        if (empty($courseContents)) {
            return array();
        }
        $this->sortType = $sort;
        uasort($courseContents, array($this, "sortByField"));
        return $courseContents;
    }

    /**
     * Callback function used for sorting in sortCourseContent. Returns 0, -1, or 1.
     * @param  mixed  $contentA One object being compared.
     * @param  mixed  $contentB The second object being compared
     * @return int
     */
    private function sortByField($contentA, $contentB) {
        switch ($this->sortType) {
            case 'sortDate':
                $updateA_time = $contentA['sortDate'] ? $contentA['sortDate']->format('U') : 0;
                $updateB_time = $contentB['sortDate'] ? $contentB['sortDate']->format('U') : 0;
                if($updateA_time == $updateB_time){
                    return 0;
                }
                return ($updateA_time > $updateB_time) ? -1 : 1;
            case 'title':
                $titleA = $contentA['title'] ? $contentA['title'] : '';
                $titleB = $contentB['title'] ? $contentB['title'] : '';
                if($titleA == $titleB){
                    return 0;
                }
                return ($titleA > $titleB) ? 1 : -1;
            default:
                if ($contentA->sortBy() == $contentB->sortBy()) {
                    return 0;
                }
                return ($contentA->sortBy() > $contentB->sortBy()) ? -1 : 1;
            break;
        }
    }

    // takes a config and process the info data
    /**
     * Takes a config and processes the info
     * @param  array  $options    An array of options to be passed to formatCourseDetailSection
     * @param  string $configName The name of the config to load
     * @return array
     */
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

    /**
     * Returns the course or section object from the options array.
     * @param  array  $options
     * @return mixed
     */
    protected function getInfoObject($options) {
        if (isset($options['course'])) {
            $Course = $options['course'];
            $courseType = isset($options['courseType']) ? $options['courseType'] : CoursesDataModel::COURSE_TYPE_CATALOG;
            if (!$object = $Course->getCoursebyType($courseType)) {
                return null;
            }
        } elseif (isset($options['section'])) {
            $object = $options['section'];
        } else {
            throw new KurogoException("No valid object type found. Check trace");
        }

        return $object;
    }

    /**
     * Returns the items details
     * @param  array  $options
     * @param  array  $sectionData Data for a particular section
     * @return array
     */
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

    /**
     * Formats a particular detail field
     * @param  mxied  $object      A course or section object
     * @param  mixed  $sectionItem
     * @param  array $sectionData
     * @return array
     */
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

    /**
     * Retrieves and formats a detail field.
     * @param  mixed  $object    The object to get the field value from
     * @param  string $field     The field to retrieve
     * @param  array  $fieldData
     * @return array
     */
    protected function formatDetailField($object, $field, $fieldData) {

        $method = "get" . $field;
        if (!is_callable(array($object, $method))) {
            throw new KurogoDataException("Method $method does not exist on " . get_class($object));
        }

        $value = $object->$method();
        return $this->formatInfoDetail($value, $fieldData, $object);
    }

    /**
     * Formats a value based on it's type and other info
     * @param  mixed  $value  The value to format
     * @param  array  $info   Information about the value/field
     * @param  mixed  $object The object the value came from
     * @return array
     */
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

    /**
     * Format the value with a callback function if it's set
     * @param  array  $values The value to format
     * @param  array  $info   Array holding the callback function
     * @return array
     */
    protected function formatValues($values, $info) {
        if (isset($info['parse'])) {
            $formatFunction = create_function('$value', $info['parse']);
            foreach ($values as &$value) {
                $value = $formatFunction($value);
            }
        }

        return $values;
    }

    /**
     * Replaces newline and tab characters with actual newlines and tabs
     * @param  mixed  $format The string or array to replace newlines/tabs in
     * @return mixed
     */
    protected function replaceFormat($format) {
        return str_replace(array('\n','\t'),array("\n","\t"), $format);
    }

    protected function getGroupOptionsForTasks($options) {
        $page = isset($options['page']) ? $options['page'] : $this->page;
        $groupOptions = array('tab'=>'tasks', 'page'=>$page);
        if (isset($options['course'])) {
            $groupOptions = array_merge($groupOptions, $this->getCourseOptions());
        }
        return $groupOptions;
    }
    
    /**
     * Return options relevant to retrieving tasks
     * @param  array  $options
     * @return array
     */
    protected function getOptionsForTasks($options) {
        $page = isset($options['page']) ? $options['page'] : $this->page;
        $section = $page == 'index' ? 'alltasks' : 'tasks';
        $taskGroups = $this->getModuleSections($section);
        $groupOptions = $this->getGroupOptionsForTasks($options);

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

    /**
     * Return options relevant to retrieving announcements
     * @param  array $options
     * @return array
     */
    protected function getOptionsForAnnouncements($options){
        return array();
    }

    /**
     * Return options relevant to retrieving updates
     * @param  array $options
     * @return array
     */
    protected function getOptionsForUpdates($options) {
        return array();
    }

    /**
     * Return options relevant to retrieving all content
     * for the browse view. Sets the contentID option if
     * it is available.
     * @param  array $options
     * @return array
     */
    protected function getOptionsForBrowse($options){
        if ($contentID = $this->getArg('contentID', '')) {
            return array('contentID'=>$contentID);
        }
        return array();
    }

    /**
     * Return options relevant to retrieving a course.
     * @return array
     */
    protected function getOptionsForCourse(){
        return array();
    }

    protected function getGroupOptionsForResources($options) {
        $page = isset($options['page']) ? $options['page'] : $this->page;

        $groupOptions = array('tab'=>'resources','page'=>$page);
        if (isset($options['course'])) {
            $groupOptions = array_merge($groupOptions, $this->getCourseOptions());
        }
    
    	return $groupOptions;
    } 

    /**
     * Return options relevant to retrieving resources
     * @param  array $options
     * @return array
     */
    protected function getOptionsForResources($options) {
        $page = isset($options['page']) ? $options['page'] : $this->page;
        $groupsConfig = $this->getModuleSections('resources');

        $groupOptions = $this->getGroupOptionsForResources($options);

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

    /**
     * Gets all bookmarks for a given term
     * @param  CourseTerm $Term The term to get bookmarks for
     * @return array
     */
    protected function getBookmarksForTerm($term) {
        $_bookmarks =  $this->getBookmarks();
        $bookmarks = array();
        foreach ($_bookmarks as $aBookmark) {
            if ($this->getBookmarkParam($aBookmark, 'term')==$term) {
                $bookmarks[] = $aBookmark;
            }
        }
        return $bookmarks;
    }

    /**
     * Initializes the grades tab, assigns grades data.
     * @param  array  $options
     */
    protected function initializeGrades($options) {
        if (isset($options['course'])) {
            $course = $options['course'];
        } else {
            throw new KurogoConfigurationException("Aggregated grades not currently supported");
        }

        $contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT);
        $gradesLinks = array();
        if($grades = $contentCourse->getGrades(array('user'=>true))){
            foreach ($grades as $grade) {
                $gradesLinks[] = $this->linkForGrade($grade);
            }
            $gradesLinks = $this->paginateArray($gradesLinks, $this->getOptionalModuleVar('MAX_GRADES', 10));
        }
        $this->assign('gradesLinks',$gradesLinks);
    }

    protected function initializeGradebook($options){
        $this->assign('hasCourses', true);
        $options = $this->getOptionsForGradebook();
        $grades = $this->getGradesbookEntries($options);

        $gradesListLinks = array();
        $hasGrades = false;
        foreach ($grades as $id => $gradesTuple) {
            $gradesLinks = array();
            foreach ($gradesTuple['grades'] as $grade) {
                $hasGrades = true;
                $gradeLink = $this->linkForGradebookEntry($grade, $options);
                $gradesLinks[] = $gradeLink;
            }

            $gradeListHeading = $gradesTuple['heading'];
            $gradeListHeading = str_replace("%n", count($gradesLinks), $gradeListHeading);
            //$gradeListHeading = str_replace("%t", $TermTitle, $gradeListHeading);

            $gradesListLinks[] = array('gradeListHeading' => $gradeListHeading,
                                        'gradesLinks' => $gradesLinks);
        }

        if(!$hasGrades){
            $this->assign('noGradesText', $this->getLocalizedString('NO_GRADES'));
        }
        $this->assign('hasGrades', $hasGrades);
        $this->assign('gradesListLinks', $gradesListLinks);
        return true;
    }

    /**
     * Initializes the info tab, formats and assigns info details
     * @param  array  $options
     * @return boolean
     */
    protected function initializeInfo($options) {

        $course = $options['course'];
        $infoDetails['info'] = $this->formatCourseDetails($options, 'course-info');
        $this->assign('infoDetails', $infoDetails);

        // @TODO ADD configurable links
        $links = array();
        $this->assign('links', $links);
        return true;
        break;
    }

    protected function getOptionsForGradebook(){
        $options = array();
        if ($term = $this->getArg('term')) {
            $options['term'] = $term;
        }
        return $options;
    }

    /**
     * Return options relevant to retrieving courses.
     * Sets the term option to the term
     * @return array
     */
    protected function getOptionsForCourses() {
        $options = array();
        if ($term = $this->getArg('term')) {
            $options['term'] = $term;
        } else {
            $options['term'] = CoursesDataModel::CURRENT_TERM;
        }
        return $options;
    }

    protected function getGradesbookEntries($options){
        $gradeListings = $this->getModuleSections('grades');
        $grades = array();

        foreach ($gradeListings as $id => $listingOptions) {
            $listingOptions = array_merge($options, $listingOptions);
            if ($this->isLoggedIn()) {
                if ($listGrades = $this->controller->getGradesbookEntries($listingOptions)) {
                    $grades[$id] = array(
                        'heading'=>$listingOptions['heading'], 
                        'grades'=>$listGrades
                    );
                }
            }
        }
        return $grades;
    }

    protected function getTerms() {
        $courseListings = $this->getModuleSections('courses');
        $terms = array();
        foreach ($courseListings as $listingData) {
            $types = isset($listingData['types']) ? $listingData['types'] : array();
            foreach ($types as $type) {
                $_terms = $this->controller->getAvailableTerms($type);
                foreach ($_terms as $term) {
                    $terms[$term->getSort()] = $term;
                }
            }
        }

        ksort($terms);        
        return array_values($terms);
        
    }
    
    /**
     * Returns an array of courses
     * @param  array   $options
     * @param  boolean $grouped=false Whether the courses should be grouped by listing or not
     * @return array
     */
    protected function getCourses($options, $grouped=false) {

		$hasCourses = false;

        $courseListings = $this->getModuleSections('courses');
        $courses = array();

        foreach ($courseListings as $id => $listingOptions) {
            $listingOptions = array_merge($options, $listingOptions);
            if ($this->isLoggedIn()) {
            	if ($listCourses = $this->controller->getCourses($listingOptions)) {
            		$hasCourses = true;
            	}
                $courses[$id] = array(
                	'heading'=>$listingOptions['heading'], 
                	'courses'=>$listCourses
                );
            }
        }

        if (!$grouped) {
            $_courses = array();
            foreach ($courses as $c) {
                $_courses = array_merge($_courses, $c['courses']);
            }
            $courses = $_courses;
        }
        $this->assign('hasCourses', $hasCourses);
        return $courses;
    }

    /**
     * Initialize announcements, either for aggregated view or single view.
     * Assigns announcements with pagination
     * @param  array  $options
     * @return boolean
     */
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
            if ($contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT)) {
                $options['course'] = $contentCourse;
                if ($items = $contentCourse->getAnnouncements($this->getOptionsForAnnouncements($options))) {
                    foreach ($items as $item) {
                        $announcementsLinks[] = $this->linkForAnnouncement($item, $contentCourse, $showCourseTitle);
                    }
                }
            }
        }
        $announcementsLinks = $this->sortCourseContent($announcementsLinks, 'sortDate');
        $announcementsLinks = $this->paginateArray($announcementsLinks, $this->getOptionalModuleVar('MAX_ANNOUNCEMENTS', 10));
        $this->assign('announcementsLinks', $announcementsLinks);
        return true;
    }

    /**
     * Initialize updates, either for aggregated view or single view.
     * Assigns updates with pagination
     * @param  array  $options
     * @return boolean
     */
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
            if ($contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT)) {
                $options['course'] = $contentCourse;
                if($items = $contentCourse->getUpdates($this->getOptionsForUpdates($options))) {
                    foreach ($items as $item){
                        switch ($item->getContentType()) {
                            case 'announcement':
                                $updatesLinks[] = $this->linkForAnnouncement($item, $contentCourse, $showCourseTitle);
                                break;
                            default:
                                $updatesLinks[] = $this->linkForContent($item, $contentCourse, $showCourseTitle);
                                break;
                        }
                        
                    }
                }
            }
        }
        $updatesLinks = $this->sortCourseContent($updatesLinks, 'sortDate');
        $updatesLinks = $this->paginateArray($updatesLinks, $this->getOptionalModuleVar('MAX_UPDATES', 10));
        $this->assign('updatesLinks', $updatesLinks);
        return true;
    }

    /**
     * Initializes tasks, either for aggregated view or single view.
     * Sorts tasks and assigns data
     * @param  [type] $options [description]
     * @return [type]          [description]
     */
    protected function initializeTasks($options) {

        if (isset($options['course'])) {
            $courses = array($options['course']);
        } else {
            $courses = $this->getCourses($this->getOptionsForCourses());
        }

        $tasks = array();

        foreach ($courses as $course) {
            if ($contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT)) {
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

    /**
     * Initializes resources, assigns group data.
     * Assigns resources with pagination.
     * @param  array  $options
     */
    protected function initializeResources($options) {

        if (isset($options['course'])) {
            $course = $options['course'];
        } else {
            throw new KurogoConfigurationException("Aggregated resources not currently supported");
        }

        $contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT);;
        $resourcesLinks = array();
        $resourcesOptions = $this->getOptionsForResources($options);
        $group = $resourcesOptions['group'];
        if($group == 'browse'){
            if (isset($options['course'])) {
                $course = $options['course'];
            } else {
                throw new KurogoConfigurationException("Aggregated resources not currently supported");
            }

            $contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT);
            $browseOptions = $this->getOptionsForBrowse($options);
            $browseContent = $contentCourse->getContentByParentId($browseOptions);

            $browseLinks = array();
            foreach ($browseContent as $content) {
                switch ($content->getContentType()) {
                    case 'folder':
                        $browseLinks[] = $this->linkForFolder($content, $contentCourse);
                        break;
                    case 'task':
                        $browseLinks[] = $this->linkForTask($content, $contentCourse);
                        break;
                    default:
                        $browseLinks[] = $this->linkForContent($content, $contentCourse);
                        break;
                }
            }
            $this->assign('resourcesLinks', array(array('items'=>$browseLinks)));
        }else{
            $groups = $contentCourse->getResources($resourcesOptions);
            if ($group == "date") {
                $limit = 0;
                $pageSize = $resourcesOptions['limit'];
            } else {
                $limit = $resourcesOptions['limit'];
            }
            $key = $resourcesOptions['key'];
            $seeAllLinks = array();
            $otherFiles = array();

            foreach ($groups as $groupTitle => $items){
                $items = $this->sortCourseContent($items);
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

                $title = $this->getOptionalLocalizedString('CONTENT_CLASS_TITLE_'.strtoupper($groupTitle), $groupTitle);
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

                if($resource['title'] == $this->getLocalizedString('CONTENT_CLASS_TITLE_FILE')){
                    $otherFiles = $resource;
                }else{
                    $resourcesLinks[] = $resource;
                }
            }
            // Sort groups alphabetically by group title
            $resourcesLinks = $this->sortCourseContent($resourcesLinks, 'title');
            if($otherFiles){
                $resourcesLinks[] = $otherFiles;
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
    }

    /**
     * Initialize the browse view.
     * @param  array  $options
     */
    protected function initializeBrowse($options){
        if (isset($options['course'])) {
            $course = $options['course'];
        } else {
            throw new KurogoConfigurationException("Aggregated resources not currently supported");
        }

        $contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT);
        $browseOptions = $this->getOptionsForBrowse($options);
        $browseContent = $contentCourse->getContentByParentId($browseOptions);

        $browseLinks = array();
        foreach ($browseContent as $content) {
            switch ($content->getContentType()) {
                case 'folder':
                    $browseLinks[] = $this->linkForFolder($content, $contentCourse);
                    break;
                case 'task':
                    $browseLinks[] = $this->linkForTask($content, $contentCourse);
                    break;
                default:
                    $browseLinks[] = $this->linkForContent($content, $contentCourse);
                    break;
            }
        }
        $this->assign('browseLinks', $browseLinks);
    }

    /**
     * Get the tablet heading text for viewing all classes
     * @param  array  $options
     * @return string
     */
    protected function getTabletViewAllHeadingText($options) {
        return $this->getLocalizedString('COURSES_VIEW_ALL_CLASSES_TEXT');
    }

    /**
     * Get the tablet link text for viewing all classes
     * @param  array  $options
     * @return string
     */
    protected function getTabletViewAllLinkText($options) {
        return $this->getTabletViewAllHeadingText($options);
    }

    /**
     * Initialize courses tab. Create courses links,
     * and catalog link if it exists.
     * @return boolean
     */
    protected function initializeCourses() {

        if ($this->isLoggedIn()) {
            $courses = $this->getCourses($this->getOptionsForCourses(), true);

            $coursesListLinks = array();
            $hasCourses = false;
            foreach ($courses as $id => $coursesTuple) {
                $coursesLinks = array();
                foreach ($coursesTuple['courses'] as $course) {
                    $options = $course->getCourseOptions();
                    $hasCourses = true;
                    $courseLink = $this->linkForCourse($course, $options);
                    $coursesLinks[] = $courseLink;
                }

                $courseListHeading = $coursesTuple['heading'];
                $courseListHeading = str_replace("%n", count($coursesLinks), $courseListHeading);
                //$courseListHeading = str_replace("%t", $TermTitle, $courseListHeading);

                $coursesListLinks[] = array('courseListHeading' => $courseListHeading,
                                            'coursesLinks' => $coursesLinks);
            }
            if(!$hasCourses){
                $this->assign('noCoursesText', $this->getLocalizedString('NO_COURSES'));
            }
            $this->assign('hasCourses', $hasCourses);
            $this->assign('coursesListLinks', $coursesListLinks);
            if ($this->pagetype == 'tablet') {
                $options['courses'] = $courses;
                $this->assign('viewAllCoursesHeading', $this->getTabletViewAllHeadingText($options));
                $this->assign('viewAllCoursesLink', $this->getTabletViewAllLinkText($options));
            }
        } else {
            $loginLink = array(
                'title' => $this->getLocalizedString('SIGN_IN_SITE', Kurogo::getSiteString('SITE_NAME')),
                'url'   => $this->buildURLForModule('login','', $this->getArrayForRequest()),
            );
            $this->assign('loginLink', array($loginLink));
            $this->assign('loginText', $this->getLocalizedString('NOT_LOGGED_IN'));
        }

        if ($catalogRetrieverKey = $this->controller->getCatalogRetrieverKey()) {
            $catalogItems = array();
            
            if ($this->getOptionalModuleVar('EXPAND_CATALOG_TERMS', false)) {
	            $courseCatalogText = $this->getLocalizedString('COURSE_CATALOG_TEXT');
            	$terms = $this->controller->getAvailableTerms($catalogRetrieverKey);
            	foreach ($terms as $term) {
					$catalogItems[] = array(
						'title' => $term->getTitle(),
						'url'   => $this->buildBreadcrumbURL('catalog', array('feed'=>$catalogRetrieverKey,'term'=>strval($term)))
					);
            	}
            } else {
                $term = $this->controller->getCurrentTerm($catalogRetrieverKey);
	            $courseCatalogText = $this->getLocalizedString('COURSE_CATALOG_TEXT');
				$catalogItems[] = array(
					'title' => $this->getFeedTitle($catalogRetrieverKey),
					'url'   => $this->buildBreadcrumbURL('catalog', array('feed'=>$catalogRetrieverKey,'term'=>$term))
				);
	
				if ($bookmarks = $this->getBookmarksForTerm($term)) {
					$catalogItems[] = array(
						'title' => $this->getLocalizedString('BOOKMARKED_COURSES') . " (" . count($bookmarks) . ")",
						'url'   => $this->buildBreadcrumbURL('bookmarks', array('term'=>$term))
					);
				}
			}

            $this->assign('courseCatalogText', $courseCatalogText);
            $this->assign('catalogItems', $catalogItems);
        }

        return true;
    }

    /**
     * Whether to show a particular tab or not.
     * Will not show if tab is protected and user is not logged in.
     * @param  string $tabID   The tab ID to check
     * @param  array  $tabData The tab's Data
     * @return boolean
     */
    protected function showTab($tabID, $tabData, $options) {
        
        $showTabDefaults = array(
            'course' => array(
                'updates' => array(
                    'CourseContent',
                ),
                'announcements' => array(
                    'CourseContent',
                ),
                'resources' => array(
                    'CourseContent',
                ),
                'tasks' => array(
                    'CourseContent',
                ),
                'info' => array(
                    'CourseContent',
                    'CourseRegistration',
                    'CourseCatalog',
                ),
                'grades' => array(
                    'CourseContent',
                ),
            ),
        );

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

        # If we have the combined course object
        if(isset($options['course'])){
            $course = $options['course'];
            # Check if tab has overridden display conditions
            if(isset($tabData['showIfCourseExists'])){
                # An override was provided
                foreach ($tabData['showIfCourseExists'] as $courseType) {
                    if($course->getCoursebyType($courseType)){
                        # Combined course has CourseType, show it
                        return true;
                    }
                }
                # Combined course did not have CourseType, don't show it
                return false;
            }else{
                # Use defaults
                # Assume we should show the tab
                $hasCourseType = true;
                if(isset($showTabDefaults[$this->page]) && isset($showTabDefaults[$this->page][$tabID])){
                    foreach ($showTabDefaults[$this->page][$tabID] as $courseType) {
                        # Some limit exists, assume we should not show the tab unless
                        # the combined course has the CourseType
                        $hasCourseType = false;
                        if($course->getCoursebyType($courseType)){
                            return true;
                        }
                    }
                }
                return $hasCourseType;
            }
        }
        return true;
    }

    /**
     * Initialize the requested page. Set template variables.
     */
    protected function initializeForPage() {
        $this->originalPage = $this->page;
		$this->setAutoPhoneNumberDetection(false);

        if ($this->pagetype == 'tablet') {
            $this->addOnOrientationChange('moduleHandleWindowResize();');
        }

        switch($this->page) {
            case 'content':
            case 'download':
                if(!$this->isLoggedIn()){
                    $this->redirectTo('index');
                }
                $contentID = $this->getArg('contentID');

        	    if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
        	    }

                if (!$contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT)) {
                    $this->redirectTo('index');
                }

                $options['type'] = $this->getArg('type');
                if (!$content = $contentCourse->getContentById($contentID, $options)) {
                    throw new KurogoDataException($this->getLocalizedString('ERROR_CONTENT_NOT_FOUND'));
                }

                if ($this->page=='download') {
                    //we are downloading a file that the server retrieves
                    if ($content->getContentType()=='file' || $content->getContentType()=='task') {
                        $fileID = $this->getArg('fileID', null);
                        if ($attachment = $content->getAttachment($fileID)) {
                            if(!$fileURL = $attachment->getContentFile()){
                                throw new KurogoException("Unable to download requested file");
                            }
                            if ($mime = $attachment->getMimeType()) {
                                header('Content-type: ' . $mime);
                            }
                            if ($size = $attachment->getFilesize()) {
                                header('Content-length: ' . sprintf("%d", $size));
                            }

                            if ($filename = $attachment->getFilename()) {
                                header('Content-Disposition: inline; filename="'. $filename . '"');
                            }
                            readfile($fileURL);
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
                    $this->assign('contentAuthor', $this->getLocalizedString('POSTED_BY_AUTHOR', $content->getAuthor()));
                }
                if ($content->getPublishedDate()) {
                    $this->assign('contentPublished', $this->elapsedTime($content->getPublishedDate()->format('U')));
                }

                if($gradeID = $content->getAttribute('gradebookColumnId')){
                    if ($gradeAssignment = $contentCourse->getGradeById($gradeID, array('user'=>true))) {
                        $gradeLink = $this->linkForGrade($gradeAssignment);
                        $this->assign('gradeLink', array($gradeLink));
                        $this->assign('gradeLinkHeading', $this->getLocalizedString('GRADE_LINK_HEADING'));
                    }
                }

                if ($content->getContentType() == "page") {
                    if($content->getViewMode() == $content::MODE_PAGE) {
                        $contentData = $content->getContent();
                        $this->assign("contentData", $contentData);
                    }
                }

                $links = $this->getContentLinks($content);
                $this->assign('links', $links);
                break;

            case 'task':
                if(!$this->isLoggedIn()){
                    $this->redirectTo('index');
                }
                $taskID = $this->getArg('taskID');

        	    if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
        	    }

                if (!$contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT)) {
                    $this->redirectTo('index');
                }

                if (!$task = $contentCourse->getTaskById($taskID)) {
                    throw new KurogoDataException($this->getLocalizedString('ERROR_CONTENT_NOT_FOUND'));
                }

                $attachments = $task->getAttachments();
                $attachmentLinks = array();
                foreach ($attachments as $attachment) {
                    $attachmentLinks[] = $this->linkForAttachment($attachment, $task);
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
                    $links = $task->getLinks();
                    $links = array_merge($links, $attachmentLinks);
                    $this->assign('links', $links);
                }
                break;

            case 'catalog':
            	$feed = $this->getArg('feed', $this->controller->getCatalogRetrieverKey());
            	if (!$retriever = $this->controller->getCatalogRetriever($feed)) {
            		$this->redirectTo('index');
            	}

            	if (!$term = $this->getArg('term')) {
                    if ($Term = $this->controller->getCurrentTerm($feed)) {
                        $term = strval($Term);
                    }
            	}

                if ($areas = $retriever->getCatalogAreas(array('term' => $term))) {
                    $areasList = array();
                    $areaOptions = array('term' => $term);
                    foreach ($areas as $CourseArea) {
                        $areasList[] = $this->linkForCatalogArea($CourseArea, $areaOptions);
                    }
                    $this->assign('areas', $areasList);
                }

                if ($bookmarks = $this->getBookmarksForTerm($term)) {
                    $bookmarksList[] = array(
                        'title' => $this->getLocalizedString('COURSES_BOOKMARK_ITEM_TITLE', count($bookmarks)),
                        'url'   => $this->buildBreadcrumbURL('bookmarks', array('term' => $term))
                    );
                    $this->assign('bookmarksList', $bookmarksList);
                }

                $this->assign('showTermSelector', !$this->getOptionalModuleVar('EXPAND_CATALOG_TERMS', false));
                $this->assign('catalogHeader', $this->getOptionalModuleVar('catalogHeader','','catalog'));
                $this->assign('catalogFooter', $this->getOptionalModuleVar('catalogFooter','','catalog'));
                $this->assign('hiddenArgs', array('term' => $term));
                $this->assign('placeholder', $this->getLocalizedString("CATALOG_SEARCH"));
                $terms = $this->controller->getAvailableTerms($feed);
                $this->assignTerms($terms, $term);
                break;

            case 'catalogarea':
            	$feed = $this->getArg('feed', $this->controller->getCatalogRetrieverKey());
                $area = $this->getArg('area');
            	$term = $this->getArg('term', CoursesDataModel::CURRENT_TERM);
                $options = array('term' => $term);
                if ($parent = $this->getArg('parent')) {
                    $options['parent'] = $parent;
                }
            	if (!$retriever = $this->controller->getCatalogRetriever($feed)) {
            		$this->redirectTo('index');
            	}

                if (!$CourseArea = $retriever->getCatalogArea($area, $options)) {
                    $this->redirectTo('catalog', array());
                }
                $this->setBreadcrumbTitle($CourseArea->getCode());
                $this->setBreadcrumbLongTitle($CourseArea->getTitle());

                $areas = $CourseArea->getAreas();

                $areasList = array();
                $areaOptions = array('term' => $term);
                foreach ($areas as $areaObj) {
                    $areasList[] = $this->linkForCatalogArea($areaObj, $areaOptions);
                }

                $courses = array();
                $searchOptions = $options = array(
                    'term'=>$term,
                    'area'=>$area
                );

                $searchOptions['type'] = $feed;

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
                $this->assign('hiddenArgs', array('area' => $area, 'term' => $term));
                $this->assign('placeholder', $this->getLocalizedString("SEARCH_MODULE", $CourseArea->getTitle()));

                break;


            case 'catalogcourse':
            	$area = $this->getArg('area');
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }
            	$term = $this->getArg('term', CoursesDataModel::CURRENT_TERM);

                $this->setBreadcrumbTitle($course->getField('courseNumber'));
                $this->setBreadcrumbLongTitle($course->getTitle());

                // Bookmark
                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $cookieParams = array(
                    	'title' => $course->getTitle(),
                        'id' => $course->getID(),
                        'term'  => $term,
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
                $this->assign('showTermTitle', $course->showTerm());
            	break;

            case 'catalogsection':
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('catalogcourse', $this->args);
                }

                if (!$catalogCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CATALOG)) {
                    $this->redirectTo('catalogcourse', $this->args);
                }

                $sectionNumber = $this->getArg('sectionNumber');

                if (!$section = $catalogCourse->getSection($sectionNumber)) {
                    $this->redirectTo('catalogcourse', $this->args);
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

                    $configName = $this->page . '-' . $tab;
                    $infoDetails[$tab] = $this->formatCourseDetails($options, $configName);
                    $tabTypes[$tab] = $tabData['type'];
                }

                $this->enableTabs($tabs);
                $this->assign('tabs',$tabs);
                $this->assign('tabTypes',$tabTypes);
                $this->assign('tabDetails', $infoDetails);
                break;

            case 'coursesection':
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('course', $this->args);
                }
 
                if (!$registrationCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_REGISTRATION)) {
                    $this->redirectTo('course', $this->args);
                }
 
                $sectionNumber = $this->getArg('sectionNumber');
 
                if (!$section = $registrationCourse->getSection($sectionNumber)) {
                    $this->redirectTo('course', $this->args);
                }
 
                $options = array(
                    'section'=> $section
                );
 
                $tabsConfig = $this->getModuleSections('coursesectiontabs');
                $tabs = array();
                $tabTypes = array();
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

            case 'resourceSeeAll':
                if(!$this->isLoggedIn()){
                    $this->redirectTo('index');
                }
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }

                if (!$contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT)) {
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

                $resources = $this->paginateArray($resources, 10);
                $this->assign('key', $this->getOptionalLocalizedString('CONTENT_CLASS_TITLE_'.strtoupper($key), $key));
                $this->assign('resources',$resources);
            	break;

            case 'page':
                if(!$this->isLoggedIn()){
                    $this->redirectTo('index');
                }
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
            	$term = $this->getArg('term', CoursesDataModel::CURRENT_TERM);
                $bookmarkLinks = array();
                if($bookmarks = $this->getBookmarksForTerm($term)) {
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

                            $bookmarkLinks[] = $bookmark;
                        }
                    }
                    $this->assign('navItems', $bookmarkLinks);
                }
                $this->assign('bookmarkItemTitle', $this->getLocalizedString('COURSES_BOOKMARK_ITEM_TITLE', count($bookmarks)));
                $this->assign('hasBookmarks', $this->hasBookmarks());
                break;

            case 'course':
                if(!$this->isLoggedIn()){
                    $this->redirectTo('index');
                }
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
                $this->tab = $this->getCurrentTab(array_keys($tabsConfig));
                foreach ($tabsConfig as $tabID => $tabData) {
                    if ($this->showTab($tabID, $tabData, $options)) {
                        if ($tabID == $this->tab) {
                            $method = "initialize" . $tabID;
                            if (!is_callable(array($this, $method))) {
                                throw new KurogoDataException("Method $this does not exist on " . get_class($this));
                            }
                            $parentPage = $this->originalPage;
                            if ($this->pagetype != 'basic' && $this->pagetype != 'touch') {
                                $this->originalPage = $tabID;
                            }
                            $this->$method(array('course' => $course, 'page' => $this->page));
                            $this->originalPage = $parentPage;
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
                $this->assign('showTermTitle', $course->showTerm());
                break;

            case 'courses':
                if(!$this->isLoggedIn()){
                    $this->redirectTo('index');
                }
                $this->initializeCourses();
                break;

            case 'resources':
            case 'grades':
            case 'updates':
            case 'tasks':
            case 'announcements':
            case 'gradebook':
            case 'browse':
            case 'info':
                if(!$this->isLoggedIn()){
                    $this->redirectTo('index');
                }
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
                if ($this->page == 'index' && $this->pagetype == 'tablet') {
                    // always load courses list on tablet -- everything else is ajaxed in
                    $this->tab = 'Courses';
                } else {
                    $this->tab = $this->getCurrentTab(array_keys($tabsConfig));;
                }
                $args = $this->args;
                $args['ajax'] = true;
                $args['page'] = $this->page;
                foreach($tabsConfig as $tabID => $tabData){
                    if ($this->showTab($tabID, $tabData, $options)) {
                        if ($tabID == $this->tab) {
                            $method = "initialize" . $tabID;
                            if (!is_callable(array($this, $method))) {
                                throw new KurogoDataException("Method $method does not exist on " . get_class($this));
                            }
                            $parentPage = $this->originalPage;
                            if ($this->pagetype != 'basic' && $this->pagetype != 'touch') {
                                $this->originalPage = $tabID;
                            }
                            $this->$method($options);
                            $this->originalPage = $parentPage;
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

                if ($this->page == 'index' && $this->pagetype == 'tablet') {
                    $selectedCourseCookie = "module_{$this->configModule}_listselect";
                    $this->assign('selectedCourseCookie', $selectedCourseCookie);

                    $courseIdPrefix = 'course_';
                    $this->assign('courseIdPrefix', $courseIdPrefix);

                    $coursesAllId = $courseIdPrefix.'all';
                    $this->assign('coursesAllId', $coursesAllId);

                    if (isset($_COOKIE[$selectedCourseCookie])) {
                        $cookieCourseId = $_COOKIE[$selectedCourseCookie];

                        if ($cookieCourseId == $coursesAllId) {
                            // selected by default
                        } else {
                            $coursesListLinks = $this->getTemplateVars('coursesListLinks');
                            if (is_array($coursesListLinks)) {
                                $courseLinkCount = 0;
                                $foundCourseId = false;
                                foreach ($coursesListLinks as $courseList) {
                                    foreach ($courseList['coursesLinks'] as $courseLink) {
                                        $courseId = $courseIdPrefix.$courseLinkCount;
                                        if ($cookieCourseId === $courseId) {
                                            $this->addOnLoad(
                                                "updateTabletDetail('{$courseId}', '{$courseLink['url']}', '{$selectedCourseCookie}', '".COOKIE_PATH."');");
                                            $foundCourseId = true;
                                            break;
                                        }
                                        $courseLinkCount++;
                                    }
                                    if ($foundCourseId) { break; }
                                }
                            }
                        }
                    }
                }
                
                if ($terms = $this->getTerms()) {
                	$term = $this->getArg('term', CoursesDataModel::CURRENT_TERM);
					$this->assignTerms($terms, $term);
				}
                break;

            case 'search':
                $searchTerms = $this->getArg('filter', false);
            	$term = $this->getArg('term', null);

                $options = array(
                    'term' => $term,
                    'types' => array('catalog')
                );
                if($area = $this->getArg('area')) {
                    $options['area'] = $area;
                }

                $courses = $this->controller->search($searchTerms, $options);
                $coursesList = array();

                foreach ($courses as $item) {
                	if(!$item->checkInStandardAttributes('areaCode')) {
			        	//try to set attribute in attributes list.
				        $item->setAttribute('areaCode', CoursesDataModel::COURSE_TYPE_CATALOG);
			        }
			        $options['area'] = $item->getField('areaCode', CoursesDataModel::COURSE_TYPE_CATALOG);
                    $course = $this->linkForCatalogCourse($item, $options);
                    $coursesList[] = $course;
                }
                $this->assign('results', $coursesList);
                if ($coursesList) {
                    $this->assign('resultCount', count($coursesList));
                }
                $this->assign('hiddenArgs', array('area' => $area, 'term' => $term));
                $this->assign('searchTerms', $searchTerms);
                $this->assign('searchHeader', $this->getOptionalModuleVar('searchHeader','','catalog'));
                break;
            case 'grade':
                if(!$this->isLoggedIn()){
                    $this->redirectTo('index');
                }
                $gradeID = $this->getArg('gradeID');

                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }

                if (!$contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT)) {
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


                if ($gradeScore = $gradeAssignment->getGrade()) {
                    if($gradeScore->getStatus() == GradeScore::SCORE_STATUS_GRADED){
                        $grade = number_format($gradeAssignment->getGrade()->getScore(), 2);
                    }else{
                        $grade = $this->getLocalizedString($gradeScore->getStatus());
                    }

                    if($gradeScore->getStudentComment()){
                        $gradeContent['studentComment'] = $gradeScore->getStudentComment();
                    }
                }else{
                    $grade = $this->getLocalizedString('SCORE_STATUS_NO_GRADE');
                }

                $gradeContent['grade'] = $grade;

                // Strict type checking in case possible points is 0.
                if($gradeAssignment->getPossiblePoints() !== null){
                    $gradeContent['possiblePoints'] = $gradeAssignment->getPossiblePoints();
                }

                if(is_numeric($grade) && ($gradeContent['possiblePoints'] != 0)){
                    $percent = ($gradeContent['grade'] / $gradeContent['possiblePoints'])*100;
                    $gradeContent['percent'] = $this->getLocalizedString('GRADE_PERCENT', $percent);
                }

                $this->assign('grade', $gradeContent);
                break;
            case 'folderCount':
                try {
                    $course = $this->getCourseFromArgs();
                    $contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT);
                    if($contentID = $this->getArg('contentID')){
                        $options['contentID'] = $contentID;
                        $childContent = $contentCourse->getContentByParentId($options);
                        $count = count($childContent);
                        if($count == 1){
                            $subtitle = $this->getLocalizedString('FOLDER_SUBTITLE_COUNT_SINGULAR', $count);
                        }else{
                            $subtitle = $this->getLocalizedString('FOLDER_SUBTITLE_COUNT_PLURAL', $count);
                        }
                        $this->assign('folderCount', $subtitle);
                    }
                } catch (Exception $e) {
                    _404();
                }
                break;
            case 'courseUpdateIcons':
                try {
                    $course = $this->getCourseFromArgs();
                    $contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT);

                    $courseTabs = $this->getModuleSections('coursetabs');
                    $courseUpdateIcons = array();
                    foreach($courseTabs as $tab=>$data) {
                        if (in_array($tab, array('announcements', 'resources', 'tasks'))){
                            $method = 'get'.ucfirst($tab);
                            $optionsMethod = 'getOptionsFor'.ucfirst($tab);
                            $options = $this->$optionsMethod(array('course' => $course, 'page' => $this->page));
                            $content = $contentCourse->$method($options);
                            switch ($tab) {
                                case 'resources':
                                    $count = 0;
                                    foreach ($content as $courseContent) {
                                        $count += count($courseContent);
                                    }
                                    break;
                                case 'tasks':
                                    $count = 0;
                                    foreach ($content as $courseContent) {
                                        $count += count($courseContent);
                                    }
                                    break;
                                default:
                                    $count = count($content);
                                    break;
                            }
                            $courseUpdateIcons[] = sprintf('<span class="updateitem"><img src="/modules/courses/images/updates_%s.png" height="16" width="16" valign="middle" alt="%s" title="%2$s" /> %d</span>', $tab, $this->getTitleForTab($tab, 'course'), $count);
                        }
                    }
                    $courseUpdateIcons = implode("", $courseUpdateIcons);
                    $this->assign('courseUpdateIcons', $courseUpdateIcons);
                } catch (Exception $e) {
                    _404();
                }

                break;
            case 'fullbrowse':
                if(!$this->isLoggedIn()){
                    $this->redirectTo('index');
                }
                if (!$course = $this->getCourseFromArgs()) {
                    $this->redirectTo('index');
                }

                if (!$contentCourse = $course->getCoursebyType(CoursesDataModel::COURSE_TYPE_CONTENT)) {
                    $this->redirectTo('index');
                }

                $browseOptions = $this->getOptionsForBrowse(array());
                $browseContent = $contentCourse->getContentByParentId($browseOptions);

                $browseHeader = array();
                $folderName = "";
                if(isset($browseOptions['contentID'])){
                    $currentContent = $contentCourse->getContentById($browseOptions['contentID']);
                    $parentID = $currentContent->getParentID();
                    if($parentID){
                        $parentContent = $contentCourse->getContentById($parentID);
                        $browseHeader = $this->linkForFolder($parentContent, $contentCourse,true);
                    }else{
                        $options = $this->getCourseOptions();
                        $browseHeader['url'] = $this->buildBreadcrumbURL($this->page, $options);
                        $browseHeader['title'] = $this->getLocalizedString('ROOT_LEVEL_TITLE');
                    }
                    $folderName = $currentContent->getTitle();
                    $this->setBreadcrumbTitle($folderName);
                }
                $this->assign('folderName', $folderName);
                $this->assign('browseHeader', $browseHeader);

                $browseLinks = array();
                $folderLinks = array();
                foreach ($browseContent as $content) {
                    switch ($content->getContentType()) {
                        case 'folder':
                            $folderLinks[] = $this->linkForFolder($content, $contentCourse);
                            break;
                        case 'task':
                            $browseLinks[] = $this->linkForTask($content, $contentCourse);
                            break;
                        default:
                            $browseLinks[] = $this->linkForContent($content, $contentCourse);
                            break;
                    }
                }
                $this->assign('browseLinks', $browseLinks);
                $this->assign('folderLinks', $folderLinks);

                break;
        }
    }
    public function nativeWebTemplateAssets() {
        return array(
        	'/common/images/blank.png',
            '/modules/courses/images/content_announcement.png',
            '/modules/courses/images/content_assessment.png',
            '/modules/courses/images/content_assignment.png',
            '/modules/courses/images/content_blog.png',
            '/modules/courses/images/content_bloglink.png',
            '/modules/courses/images/content_calendar.png',
            '/modules/courses/images/content_file_audio.png',
            '/modules/courses/images/content_file_doc.png',
            '/modules/courses/images/content_file_img.png',
            '/modules/courses/images/content_file_pdf.png',
            '/modules/courses/images/content_file_ppt.png',
            '/modules/courses/images/content_file_txt.png',
            '/modules/courses/images/content_file_video.png',
            '/modules/courses/images/content_file_xls.png',
            '/modules/courses/images/content_file_zip.png',
            '/modules/courses/images/content_file.png',
            '/modules/courses/images/content_folder.png',
            '/modules/courses/images/content_forum.png',
            '/modules/courses/images/content_journal.png',
            '/modules/courses/images/content_lesson.png',
            '/modules/courses/images/content_lessonplan.png',
            '/modules/courses/images/content_link.png',
            '/modules/courses/images/content_multi.png',
            '/modules/courses/images/content_page.png',
            '/modules/courses/images/content_task.png',
            '/modules/courses/images/content_toollink.png',
        );
    }

}
