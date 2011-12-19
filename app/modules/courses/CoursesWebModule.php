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
            case 'index':
                
                $courses = array();
                
                if ($items = $this->feed->getContentCourses()) {
                    foreach ($items as $item) {
                        $course = $this->linkForCourse($item);
                        $courses[] = $course;
                    }
                }
                
                $this->assign('course', $courses);
                break;
        }
    }
}
