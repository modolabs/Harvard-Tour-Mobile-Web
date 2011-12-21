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
                    if ($CourseArea = $this->feed->getCatalogArea($area)) {
                        $baseArea = $area . '|';
                        $this->setPageTitles($CourseArea->getTitle());
                    } else {
                        $this->redirectTo('index', array());
                    }
                } else {
                    $this->redirectTo('index', array());
                }

                $areas = $this->feed->getCatalogAreas($area);
                
                $areasList = array();
                foreach ($areas as $CourseArea) {
                    $areasList[] = array(
                        'title'=>$CourseArea->getTitle(),
                        'url'=>$this->buildBreadcrumbURL('area',array('area'=>$baseArea . $CourseArea->getCode()), true)
                    );
                }

                $areas = explode("|", $area);
                $courses = $this->feed->getCatalogCourses(end($areas));
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
