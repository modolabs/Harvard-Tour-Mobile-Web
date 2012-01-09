<?php

includePackage('DataModel');

class CoursesDataModel extends DataModel {

    const CURRENT_TERM = 1;
    const ALL_TERMS = 2;
    
    protected $retrievers;
    
    public function getDownLoadTypeContent(MoodleDownLoadCourseContent $content, $courseID) {
        $this->retriever = $this->retrievers['content'];
    	$cache = $this->getRetriever()->getCache();
        $cacheKey = md5($content->getFileUrl()) . '.' .$content->getFileType();
        $cacheGroup = isset($this->initArgs['INDEX']) ? $this->initArgs['INDEX'] : '';
        $cacheGroup .= '-' . $courseID;
        $cache->setCacheGroup($cacheGroup);
        $cache->setSerialize(false);
        
        $fileFullPath = $cache->getFullPath($cacheKey);
        if (!$data = $cache->get($cacheKey)) {
            $this->clearInternalCache();
            $this->setOption('action', 'downLoadFile');
            $this->setOption('contentUrl', $content->getFileUrl());
            $cache->setCacheLifetime(500);
            if ($response = $this->getRetriever()->retrieveResponse()) {
                if (!$response instanceOf DataResponse) {
                    throw new KurogoDataException("Response must be instance of DataResponse");
                }
                if ($cache->set($cacheKey, $response->getResponse())) {
                    $content->setCacheFile($fileFullPath);
                }
            }
        } else {
            $content->setCacheFile($fileFullPath);
        }
        return $content;
    }
    
    public function getPageTypeContent(MoodlePageCourseContent $content) {
        $this->retriever = $this->retrievers['content'];
        if ($pageUrl = $content->getFileurl()) {
            $this->setOption('action', 'getPageContent');
            $this->setOption('contentUrl', $content->getFileurl());
            
            $this->retriever->setParser(new DOMDataParser());
            $content = '';
            if ( ($dom = $this->getData()) && ($dom instanceOf DOMDocument)) {
                if ($element = $dom->getElementsByTagName('body')->item(0)) {
                    $content = $dom->saveXML($element);
                    $content = preg_replace("#</?body.*?".">#", "", $content);
                } else {
                    $content = $this->getResponse();
                }
            }
            
            return $content;
        }
        return '';
    }
    
    //returns an array of terms. 
    public function getAvailableTerms() {
        return array(self::getCurrentTerm());
    }
    
    public function getCurrentTerm() {
        $term = new CourseTermCurrent();
        return $term;
    }
    
    public function getTerm($termCode) {
        if ($termCode==self::CURRENT_TERM) {
            return self::getCurrentTerm();
        } else {
            /** @TODO retrieve term values */
            return null;
        }
    }

    public function search($searchTerms, $options) {
        /* what are we searching? */
    }
    
    public function getContentById($content){
    	if(isset($content)){
    		
    	}
    }
    //returns a Course object (may call all 3 retrievers to get the data)
    public function getCourse($type, $courseID) {
        if ($this->canRetrieve($type)) {
            return $this->retrievers[$type]->getCourseById($courseID);
        }
    }
    
    //gets grades for this user for the term (both registration and content)
    public function getGrades(CourseTerm $term) {
        
    }
    
    //most recent activity from course
    public function getLastUpdate($courseID) {
        if ($this->canRetrieve('content')) {
            return $this->retrievers['content']->getUpdates($courseID);
        }
        
        return array();
    }
    
    public function canRetrieve($type) {
        if (isset($this->retrievers[$type]) && $this->retrievers[$type]) {
            return true;
        } else {
            return false;
        }
    }
    //use the CourseCatalogDataRetriever to get the courses
    /* options:
     *'area'=> a area code
     *'courseNumber' => a course number
     */
    public function getCourses($type, $options=array()) {

        if ($this->canRetrieve($type)) {
            return $this->retrievers[$type]->getCourses($options);
        }
        
        return array();
    }
    
    //get the catalog areas
    public function getCatalogAreas($area = null) {
        if ($this->canRetrieve('catalog')) {
            return $this->retrievers['catalog']->getCatalogAreas($area);
        }
    }
    
    //get a catalog area
    public function getCatalogArea($area) {
        $areas = explode("|", $area);
        
        if ($areaCode = array_shift($areas)) {
            $area = null;
            if ($items = $this->getCatalogAreas()) {
                foreach ($items as $item) {
                    if ($areaCode == $item->getID()) {
                        $area = $item;
                    }
                }
            }
            while ($area && $areaCode = array_shift($areas)) {
                $area = $area->getArea($areaCode);
            }
        }
        
        return $area;
    }

    public function setCoursesRetriever($type, DataRetriever $retriever) {
        $interface = 'Course' . ucfirst($type) . 'DataRetriever';
        if ($retriever instanceOf $interface) {
            $this->retrievers[$type] = $retriever;
        } else {
            throw new KurogoException("Data Retriever " . get_class($retriever) . " must conform to $interface");
        }
    }
    
    protected function init($args) {
        $this->initArgs = $args;
        if (isset($args['catalog'])) {
            $arg = $args['catalog'];
            $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
            $catalogRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
            $this->setCoursesRetriever('catalog', $catalogRetriever);
        }
        
        if (isset($args['registation'])) {
            $arg = $args['registation'];
            $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
            $registationRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
            $this->setCoursesRetriever('registation', $registationRetriever);
        }
        
        if (isset($args['content'])) {
            $arg = $args['content'];
            $arg['CACHE_FOLDER'] = isset($arg['CACHE_FOLDER']) ? $arg['CACHE_FOLDER'] : get_class($this);
            $contentRetriever = DataRetriever::factory($arg['RETRIEVER_CLASS'], $arg);
            $this->setCoursesRetriever('content', $contentRetriever);
        }
    }
}
