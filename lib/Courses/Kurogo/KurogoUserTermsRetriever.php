<?php

class KurogoUserTermsRetriever extends URLDataRetriever implements TermsDataRetriever {
    protected $DEFAULT_PARSER_CLASS = "KurogoTermsParser";
    protected $termsAPI = '';

    public function init($args){
        parent::init($args);

        if (isset($args['BASE_URL']) && $args['BASE_URL']) {
            $this->termsAPI = rtrim($args['BASE_URL'], '/');
        }
    }

    public function getAvailableTerms(){
        $this->setBaseURL($this->termsAPI . '/userterms');
        $this->setOption('action', 'terms');
        return $this->getData($response);
    }

    public function getTerm($termCode){
        if($termCode == CoursesDataModel::CURRENT_TERM){
            return $this->getCurrentTerm();
        }else{
            $terms = $this->getAvailableTerms();
            foreach ($terms as $term) {
                if($termCode == $term->getID()){
                    return $term;
                }
            }
        }
        return null;
    }

    public function getCurrentTerm(){
        $this->setBaseURL($this->termsAPI . '/currentUserTerm');
        $this->setOption('action', 'currentTerm');
        return $this->getData($response);
    }
}
