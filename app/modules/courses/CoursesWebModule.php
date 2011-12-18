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
    
    public function getCourseFeed() {
    
        if ($feeds = $this->loadFeedData()) {
            return DataModel::factory('CoursesDataModel', $feeds);
        } else {
            throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED'));
        }
    }
    
    protected function initialize() {
    
        $this->feed = $this->getCourseFeed();
        
    }
    
    protected function initializeForPage() {
        switch($this->page) {
            case 'list':
                $id = $this->getArg('id', $this->getDefaultLevel());
                $courses = $controller->getCoursesBySection($id);

                // courses list and render to detail
                $courseList = array();
                foreach($courses as $course) {
                    $item = array();
                    $item['title'] = $course->getTitle();
                    $item['subtitle'] = $course->getID();
                    $item['url'] = $this->buildBreadcrumbURL('detail', array('id' => $course->getID()), true);
                    $courseList[] = $item;
                }
                $this->assign('items', $items);
                break;
            case 'search':
                break;
                
                
            case 'index':
                exit;
                $controller = $this->getController('areas');
                $areas = $controller->getAreas();
                                
                $areasList = array();
                foreach ($areas as $CourseArea) {
                    $areasList[] = array(
                        'title'=>$CourseArea->getTitle(),
                        'url'=>$this->buildBreadcrumbURL('area',array('area'=>$CourseArea->getCode()), true)
                    );
                }
                

                $this->assign('description', $this->getModuleVar('description','strings'));
                $this->assign('placeholder', $this->getLocalizedString('SEARCH_TEXT'));
                $this->assign('areas', $areasList);
                break;

            case 'area':
                $controller = $this->getController('areas');
            
                $baseArea = '';
                if ($area = $this->getArg('area', '')) {
                    if ($CourseArea = $controller->getArea($area)) {
                        $baseArea = $area . '|';
                        $this->setPageTitles($CourseArea->getTitle());
                    } else {
                        $this->redirectTo('index', array());
                    }
                } else {
                    $this->redirectTo('index', array());
                }

                $areas = $controller->getAreas($area);

                                
                $areasList = array();
                foreach ($areas as $CourseArea) {
                    $areasList[] = array(
                        'title'=>$CourseArea->getTitle(),
                        'url'=>$this->buildBreadcrumbURL('area',array('area'=>$baseArea . $CourseArea->getCode()), true)
                    );
                }


                $controller = $this->getController('courses');
                $areas = explode("|", $area);
                $courses = $controller->getCoursesByArea(end($areas));
                $coursesList = array();
                foreach ($courses as $Course) {
                    $coursesList[] = array(
                        'title'=>$Course->getTitle(),
                        'subtitle'=>$Course->getCourseNumber(),
                        'url'=>$this->buildBreadcrumbURL('detail',array('area'=>$baseArea . $CourseArea->getCode(),'catalog'=>$Course->getCatalogNumber()), true)
                    );
                }


                $this->assign('description', $CourseArea->getDescription());
                $this->assign('placeholder', $this->getLocalizedString('SEARCH_TEXT'));
                $this->assign('areas', $areasList);
                $this->assign('courses', $coursesList);
                break;
            case 'section':
                $area = $this->getArg('area');
                $catalog = $this->getArg('catalog');

                $section = $this->getArg('section');
                $controller = $this->getController('courses');

                if (!$course = $controller->getCourse($catalog)) {
                    throw new KurogoUserException($this->getLocalizedString('COURSES_NOT_FOUND',$catalog));
                }
                
                if (!$Section = $course->getSection($section)) {
                    throw new KurogoUserException($this->getLocalizedString('SECTION_NOT_FOUND',$section));
                }
                                
                $this->assign('courseTitle', $course->getTitle());
                $this->setPageTitles($course->getTitle());
                
                $this->assign('sectionDetails', $this->getSectionDetails($Section));
                
                break;                    
            case 'detail':
                $area = $this->getArg('area');
                $catalog = $this->getArg('catalog');
                $controller = $this->getController('courses');
                if (!$course = $controller->getCourse($catalog)) {
                    throw new KurogoUserException($this->getLocalizedString('COURSES_NOT_FOUND',$catalog));
                }
                
                $this->assign('courseTitle', $course->getTitle());
                $this->setPageTitles($course->getTitle());
                
                $this->assign('courseDetails', $this->getCourseDetails($course));
                
                $sectionList = array();
                $sections = $course->getSections();
                foreach ($sections as $section) {
                    $sectionList[] = array(
                        'title'=>$section->getSectionNumber(),
                        'subtitle'=>$section->getInstructor(),
                        'url'=>$this->buildBreadcrumbURL('section',array('area'=>$area,'catalog'=>$course->getCatalogNumber(),'section'=>$section->getSectionNumber()), true)
                    );
                }
                $this->assign('sectionList', $sectionList);
                                
                break;
            case 'detail-section':
                break;
        }
    }
}
