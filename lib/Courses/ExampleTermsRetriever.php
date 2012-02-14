<?php

class ExampleTermsRetriever extends URLDataRetriever implements TermsDataRetriever 
{
    public function getAvailableTerms() {
        $term = new CourseTerm();
        $term->setTitle('Spring 2012');
        $term->setID('2012S');
        return array($term);
    }
    
    public function getTerm($termCode) {
        $terms = $this->getAvailableTerms();
        if ($termCode==CoursesDataModel::CURRENT_TERM) {
            return current($terms);
        }
        
        foreach ($terms as $term) {
            if ($term->getID() == $termCode) {
                return $term;
            }
        }
        
        KurogoDEbug::debug($termCode, true);
        
        return null;
    }
}
