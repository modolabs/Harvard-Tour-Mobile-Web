<?php

class ExampleTermsRetriever extends URLDataRetriever implements TermsDataRetriever 
{
    public function getAvailableTerms() {

        // How many terms to generate
        $numTerms = 3;
        $availableTerms = array();
        for($i = 0; $i < $numTerms; $i++){
            $term = new CourseTerm();
            $term->setTitle((($i % 2 == 0) ? 'Spring ' : 'Fall ') . strval(2012 + floor($i/2)));
            $term->setID(strval(2012+floor($i/2)) . (($i % 2 == 0) ? 'S' : 'F'));
            $availableTerms[] = $term;
        }
        return $availableTerms;
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
        
        KurogoDebug::debug($termCode, true);
        
        return null;
    }
}
