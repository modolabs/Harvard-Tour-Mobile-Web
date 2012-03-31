<?php

class CoursesXMLDataParser extends XMLDataParser {
    protected $trimWhiteSpace = true;

    protected function shouldStripTags($element) {
    }
    
    protected function shouldHandleStartElement($name) {
        return true;
    }

    protected function shouldHandleEndElement($name) {
        return true;
    }
    
    protected function elementMap() {
        return array(
            'TERM_CODE'=>'termCode',
            'TITLE'=>'title',
            'DESCRIPTION'=>'description',
            'COURSE_NUMBER'=>'courseNumber',
            'CATALOG_NUMBER'=>'catalogNumber',
            'SCHOOL'=>'area',
            'SCHOOL_CODE'=>'areaCode',
            'CLASS_NUMBER'=>'classNumber',
            'SECTION_NUMBER'=>'sectionNumber',
            'CREDITS'=>'credits',
            'CREDIT_LEVEL'=>'creditLevel',
            'DAYS'=>'days',
            'START_TIME'=>'startTime',
            'END_TIME'=>'endTime',
            'START_DATE'=>'startDate',
            'END_DATE'=>'endDate',
            'ENROLLMENT_CAP'=>'enrollmentLimit',
            'ENROLLMENT_CURRENT'=>'enrollment',
            'BUILDING'=>'building',
            'ROOM'=>'room',
            'INSTRUCTOR_NAME'=>'instructor',
            'INSTRUCTOR_ID'=>'instructorID',
            'REQUIREMENTS'=>'requirements'
        );
    }

    protected function handleStartElement($name, $attribs) {
        switch($name) {
            case 'COURSE':
                $this->elementStack[] = new CourseXMLObject();
                break;
            case 'SECTION':
                $this->elementStack[] = new CourseSectionXMLObject();
                break;
            case 'SCHEDULE':
                $this->elementStack[] = new CourseScheduleXMLObject();
                break;
            default:
                $this->elementStack[] = new XMLElement($name, $attribs);
        }
    }

    protected function handleEndElement($name, $element, $parent) {
        $elementMap = $this->elementMap();
        if (isset($elementMap[$name])) {
            $method = 'set' . $elementMap[$name];
            $parent->$method($element->value());
        }
        
        switch($name) {
            case 'SECTION':
                $parent->addSection($element);
                break;
            case 'COURSE':
                if ($courseID = $this->getOption('courseID')) {
                    if ($element->getCommonID() == $courseID) {
                        $this->items = $element;
                    }
                }elseif ($area = $this->getOption('area')) {
                    if ($element->getAreaCode() == $area) {
                        $this->items[] = $element;
                    }
                } else {
                    $this->items[] = $element;
                }                
                
                break;
            case 'SCHEDULE':
                $parent->addScheduleItem($element);
                break;
            default:
                break;
        }
    }
}

class CourseXMLObject extends CourseCatalogCourse {
    protected $commonID_field = 'courseNumber';
}

class CourseSectionXMLObject extends CourseSectionObject {
}

class CourseScheduleXMLObject extends CourseScheduleObject {
}
