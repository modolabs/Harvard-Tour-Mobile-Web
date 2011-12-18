<?php

Kurogo::includePackage('Courses');

class CoursesAPIModule extends APIModule {
    protected static $defaultModel = 'CoursesDataModel';
    protected $id = 'courses';
    protected $vmin = 1;
    protected $vmax = 1;
    protected $feeds = array();

    protected function getSection($currentLevel, $parentLevel, $returnBoolean = false) {
        $config = '';
        if(!empty($parentLevel)) {
            $config .= $parentLevel . "/";
        }
        if($returnBoolean) {
            return (boolean) $this->getOptionalModuleSections($config . $currentLevel);
        }else {
            return $this->getOptionalModuleSections($config . $currentLevel);
        }
    }

    protected function getDefaultFeed() {
        return array_shift($this->feeds);
    }

    protected function getDefaultLevel() {
        $moduleConfig = $this->getModuleSections('module');
        return $moduleConfig['module']['starting_level'];
    }

    protected function getModel() {
        $controller = DataModel::factory(self::$defaultModel, $this->getDefaultFeed());

        return $controller;
    }

    public function  initializeForCommand() {
        $this->setResponseVersion(1);
        $this->feeds = $this->loadFeedData();
        switch ($this->command) {
            case 'sections':
                //get a list of courses for this section
                $currentLevel = $this->getArg('id', $this->getDefaultLevel());
                $parentLevel = $this->getArg('parent', '');;
                $controller = $this->getModel();
                $section = $this->getSection($currentLevel, $parentLevel);
                $items = array();
                foreach($section as $level => $listItem) {
                    $item = array();
                    $item['id'] = $level;
                    $item['title'] = $listItem['title'];
                    // return a field tell client if this section has child
                    // if yes, use sections command
                    // if no, use courses
                    $item['hasChild'] = $this->getSection($level, $currentLevel, true);
                    $items[] = $item;
                }
                $response = array(
                    'parent' => $currentLevel,
                    'items' => $items
                );
                $this->setResponse($response);
                break;
            case 'list':
                $sectionId = $this->getArg('id');
                $controller = $this->getModel();
                $listItems = $controller->getCoursesBySection($sectionId);
                $items = array();
                foreach($listItems as $course) {
                    $item = array();
                    $item['id'] = $course->getID();
                    $item['title'] = $course->getTitle();
                    $items[] = $item;
                }
                $response = array(
                    'id' => $sectionId,
                    'items' => $items
                );
                $this->setResponse($response);
                break;
            case 'detail':
                $id = $this->getArg('id');
                $controller = $this->getModel();
                $course = $controller->getCourse($id);
                // debug for now
                var_dump($course);
                $response = array(
                    'id' => $id,
                    'course' => $course
                );
                $this->setResponse($response);
                break;
                
            default:
                $this->invalidCommand();
                break;
        }
    }

}
