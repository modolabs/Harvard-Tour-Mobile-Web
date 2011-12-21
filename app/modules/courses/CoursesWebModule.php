<?php
/**
 * CoursesWebModule 
 * 
 * @uses WebModule
 * @package 
 * @version $id$
 * @copyright 2011
 * @author Jeffery You <jianfeng.you@symbio.com> 
 */

includePackage('Courses');

class CoursesWebModule extends WebModule {
    protected $id = 'courses'; 
    protected $feed;
    protected $courses;
    
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
        );
        if ($lastUpdateContent = $this->feed->getLastUpdate($course->getRetrieverId('content'))) {
            $link['subtitle'] = $lastUpdateContent->getTitle() . '<br/>'. $this->elapsedTime($lastUpdateContent->getPublishedDate()->format('U'));
        }
        
        return $link;
    }
    
    protected function initialize() {
    
        //$this->courses = $this->getModuleSections('courses');
        $this->feed = $this->getCourseFeed();
        
    }
    
    protected function initializeForPage() {
        switch($this->page) {
            case 'catalog':
                if ($areas = $this->feed->getCatalogAreas()) {
                    
                }
                break;
            case 'index':
                $courses = array();
                
                if ($items = $this->feed->getContentCourses()) {
                    foreach ($items as $item) {
                        $course = $this->linkForCourse($item);
                        $courses[] = $course;
                    }
                }
                
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
                $this->assign('course', $courses);
                $this->assign('catalogItems', $catalogItems);
                break;
        }
    }
}
