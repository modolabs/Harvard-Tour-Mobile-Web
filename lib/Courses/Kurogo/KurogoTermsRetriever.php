<?php

class KurogoTermsRetriever extends URLDataRetriever implements TermsDataRetriever {
    protected $DEFAULT_PARSER_CLASS = "KurogoTermsParser";
    protected $termsAPI = '';

    public function init($args){
        parent::init($args);

        if (isset($args['BASE_URL']) && $args['BASE_URL']) {
            $this->termsAPI = rtrim($args['BASE_URL'], '/');
        }
    }

    public function getAvailableTerms(){
        $this->setBaseURL($this->termsAPI . '/terms');
        $this->setOption('action', 'terms');
        return $this->getData($response);
    }

    public function getTerm($termCode){
        $this->setBaseURL($this->termsAPI . '/currentTerm');
        $this->setOption('action', 'currentTerm');
        return $this->getData($response);
    }
}

class KurogoTermsParser extends DataParser {

    public function parseData($data){
        throw new KurogoException("Parse data not supported");
    }

    public function parseResponse(DataResponse $response){
        $this->setResponse($response);
        $data = $response->getResponse();

        if($result = json_decode($data,true)){
            if (isset($result['error']) && $result['error']) {
                $response->setCode($result['error']['code']);
                $response->setResponseError($result['error']['message']);
            }
            switch ($this->getOption('action')) {
                case 'terms':
                    if(isset($result['response']['total']) && $result['response']['total'] > 0){
                        $results = array();
                        foreach ($result['response']['terms'] as $item) {
                            $term = new CourseTerm();
                            $term->setID($item['id']);
                            $term->setTitle($item['title']);
                            $term->setStartDate($item['startDate']);
                            $term->setEndDate($item['endDate']);
                            $term->setAttributes($item['attributes']);
                            $results[] = $term;
                        }
                        $this->setTotalItems(count($results));
                        return $results;
                    }
                    break;

                case 'currentTerm':
                    if(isset($result['response']) && isset($result['response']['currentTerm'])){
                        $item = $result['response']['currentTerm'];
                        $term = new CourseTerm();
                        $term->setID($item['id']);
                        $term->setTitle($item['title']);
                        $term->setStartDate($item['startDate']);
                        $term->setEndDate($item['endDate']);
                        $term->setAttributes($item['attributes']);
                        $this->setTotalItems(1);
                        return $term;
                    }
                    break;
                
                default:
                    break;
            }
        }
    }
}