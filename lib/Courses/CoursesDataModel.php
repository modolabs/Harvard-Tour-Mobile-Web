<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('DataModel');

class CoursesDataModel extends DataModel {

    const CURRENT_TERM = 1;
    const ALL_TERMS = 2;
    const TERM_TYPE_CATALOG = 'catalog';
    const TERM_TYPE_USER = 'user';
    const TERM_TYPE_BOTH = 'both';
    const COURSE_TYPE_CONTENT      = "CourseContent";
    const COURSE_TYPE_CATALOG      = "CourseCatalog";
    const COURSE_TYPE_REGISTRATION = "CourseRegistration";
    protected $retrievers=array();
    protected $termsRetrievers=array();
    protected $catalogRetrieverKey;
    
    //returns an array of terms. 
    public function getAvailableTerms($type) {
    	if ($retriever = $this->getTermsRetriever($type)) {
    		return $retriever->getAvailableTerms();
    	} 

		return array();
    }
    
    public function getCurrentTerm($type) {    	
    	if ($retriever = $this->getTermsRetriever($type)) {
    		if (!$term = $retriever->getCurrentTerm()) {
    			throw new KurogoDataException("Unable to determine current term");
    		}
    	} else {
            $term = new CourseTermCurrent();
		}    	
    }

    public function getTerm($termCode, $type) {
    	if ($retriever = $this->getTermsRetriever($type)) {
    		return $retriever->getTerm($termCode);
		}    	
    }

    //returns a Course object (may call all 3 retrievers to get the data)
    public function getCourseByCommonID($courseID, $options) {
        $combinedCourse = new CombinedCourse();
        $ok = false;
        if (strlen($courseID)==0) {
            return false;
        }
        foreach ($this->retrievers as $retriever) {
            if ($course = $retriever->getCourseByCommonID($courseID, $options)) {
                $combinedCourse->addCourse($course);
                $ok = true;
            }
        }
        
        return $ok ? $combinedCourse : null;
    }
    
    public function canRetrieve($type) {
        if (isset($this->retrievers[$type]) && $this->retrievers[$type]) {
            return true;
        } else {
            return false;
        }
    }
    
    public function hasRetrieverType($type) {
    	$interface = $type . 'DataRetriever';
        foreach ($this->retrievers as $key => $retriever) {
            if ($retriever instanceof $interface) {
            	return true;
            }
        }
    }

    public function hasPersonalizedCourses(){
    	return $this->hasRetrieverType(self::COURSE_TYPE_CONTENT) || $this->hasRetrieverType(self::COURSE_TYPE_REGISTRATION);
    }

    public function getRetriever($type=null) {
        return isset($this->retrievers[$type]) ? $this->retrievers[$type] : null;
    }

    public function getCatalogRetriever($key=null) {
    	if ($key) {
    		if ($retriever = $this->getRetriever($key)) {
    			if ($this->getRetrieverType($retriever)==self::COURSE_TYPE_CATALOG) {
    				return $retriever;
    			}
    		}
    	} else {
			return $this->getRetriever($this->catalogRetrieverKey);
		}
    }
    
    public function getTermsRetriever($type) {
        return isset($this->termsRetrievers[$type]) ? $this->termsRetrievers[$type] : null;
    }

    public function getCatalogRetrieverKey() {
    	return $this->catalogRetrieverKey;
    }
    
    protected function getRetrieverType($retriever) {
    	$types = array(self::COURSE_TYPE_CONTENT, self::COURSE_TYPE_CATALOG, self::COURSE_TYPE_REGISTRATION);
    	foreach ($types as $type) {
    		$interface = $type . "DataRetriever";
    		if ($retriever instanceOf $interface) {
    			return $type;
    		}
    	}
    	
    	return null;
    }

    public function search($searchTerms, $options) {
        $courses = array();
        if ($retriever = $this->getCatalogRetriever()) {
            $retrieverCourses = $retriever->searchCourses($searchTerms, $options);
            foreach ($retrieverCourses as $course) {
                if (!isset($courses[$course->getCommonID()])) {
                    $courses[$course->getCommonID()] = new CombinedCourse();
                }

                $combinedCourse = $courses[$course->getCommonID()];
                $combinedCourse->addCourse($course);
            }
        }
        return $courses;
    }

    public function getCourses($options) {
        
        $courses = array();

        if (isset($options['type'])) {
            $types = array($options['type']);
        } elseif (isset($options['types'])) {
            $types = $options['types'];
        } else {
            $types = array_keys($this->retrievers);
        }
        
        foreach ($types as $type) {
            if ($this->canRetrieve($type)) {
                $retrieverCourses = $this->retrievers[$type]->getCourses($options);
                foreach ($retrieverCourses as $course) {
                    if (!isset($courses[$course->getCommonID()])) {
                        $courses[$course->getCommonID()] = new CombinedCourse();
                    }
                    
                    $combinedCourse = $courses[$course->getCommonID()];
                    $combinedCourse->addCourse($course);
                }
            }
        }
                
        return $courses;
    }

    public function getGradesbookEntries($options){
        $grades = array();
        if($term = Kurogo::arrayVal($options, 'term')){
            if (isset($options['type'])) {
                $types = array($options['type']);
            } elseif (isset($options['types'])) {
                $types = $options['types'];
            } else {
                $types = array_keys($this->retrievers);
            }
            
            foreach ($types as $type) {
                if ($this->canRetrieve($type)) {
                    $retrieverGrades = $this->retrievers[$type]->getGrades($term);
                    $grades = array_merge($grades, $retrieverGrades);
                }
            }
        }
        return $grades;
    }
    
    public function setCoursesRetriever($key, DataRetriever $retriever) {
    	switch ($this->getRetrieverType($retriever))
    	{
    		case self::COURSE_TYPE_CATALOG:
    			if ($this->catalogRetrieverKey) {
    				throw new KurogoConfigurationException("Only 1 catalog retriever permitted ($this->catalogRetrieverKey defined, trying to add $key)");
    			}
    			$this->catalogRetrieverKey = $key;

    		case self::COURSE_TYPE_CONTENT:
    		case self::COURSE_TYPE_REGISTRATION:
		        $this->retrievers[$key] = $retriever;
		        break;
    	}
    }

    public function setTermsRetriever($type, TermsDataRetriever $retriever) {
    	switch ($type)
    	{
    		case self::TERM_TYPE_CATALOG:
    		case self::TERM_TYPE_USER:
		        $this->termsRetrievers[$type] = $retriever;
		        break;
    		case self::TERM_TYPE_BOTH:
    			$this->setTermsRetriever(self::TERM_TYPE_CATALOG, $retriever);
    			$this->setTermsRetriever(self::TERM_TYPE_USER, $retriever);
    			break;
    		default:
    			throw new KurogoConfigurationException("Invalid term type $type");
		}
    }
    
    protected function init($args) {
        $this->initArgs = $args;

        foreach ($args as $key => $section) {
            if(!is_array($section)){
                throw new KurogoConfigurationException("Feeds configuration section '$key' must be an array.");
            }

            if(Kurogo::arrayVal($args[$key], 'ENABLED', true)){
                $section['CACHE_FOLDER'] = isset($section['CACHE_FOLDER']) ? $section['CACHE_FOLDER'] : get_class($this);
                $retriever = DataRetriever::factory($section['RETRIEVER_CLASS'], $section);
                
                if ($retriever instanceOf TermsDataRetriever) {
                	if (isset($args['TERM_TYPE'])) {
	                	$this->setTermsRetriever($args['TERM_TYPE'], $retriever);
                	} else {
	                	$this->setTermsRetriever(self::TERM_TYPE_BOTH, $retriever);
                	}
                } else {
					$this->setCoursesRetriever($key, $retriever);
				}
            }
        }
    }
}
