<?php

class CoursesDemoTermsRetriever extends URLDataRetriever implements TermsDataRetriever  {
    protected $DEFAULT_PARSER_CLASS='CoursesDemoTermsDataParser';
    
    protected function cacheKey() {
        return '';
    }
    
    public function getAvailableTerms() {
        $availableTerms = $this->getData();
        return $availableTerms;
    }
    
    public function getTerm($termCode) {
        $terms = $this->getAvailableTerms();
        if ($termCode == CoursesDataModel::CURRENT_TERM) {
            foreach ($terms as $term) {
                if ($term->getStatus() == CoursesDataModel::CURRENT_TERM) {
                    return $term;
                }
            }
            return null;
        }
        
        foreach ($terms as $term) {
            if ($term->getID() == $termCode) {
                return $term;
            }
        }
        
        KurogoDebug::debug($termCode, true);
        return null;
    }
}

class CoursesDemoTermsDataParser extends DataParser {

    public function clearInternalCache() {
        parent::clearInternalCache();
    }
    
    public function parseData($data) {
        $items = array();
        if ($data = json_decode($data, true)) {
            if (isset($data['error']) && $data['error']) {
                throw new KurogoDataException($data['error']['message']);
            }
            
            if (isset($data['response'])) {
                foreach ($data['response'] as $value) {
                    $term = new CoursesDemoCourseTerm();
                    $term->setID($value['term_code']);
                    $term->setTitle($value['term_title']);
                    $term->setStartDate($value['start_date']);
                    $term->setEndDate($value['end_date']);
                    $term->setStatus($value['status']);
                    $items[] = $term;
                }
            }
        }
        
        $this->setTotalItems(count($items));
        return $items;
    }
}

class CoursesDemoCourseTerm extends CourseTerm {
    public $status;
    
    public function setStatus($status) {
        $this->status = $status;
    }
    
    public function getStatus() {
        return $this->status;
    }
}
