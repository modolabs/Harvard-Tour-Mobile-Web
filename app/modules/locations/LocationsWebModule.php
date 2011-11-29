<?php

class LocationsWebModule extends WebModule
{
    protected $id = 'locations';
    
    protected $feeds = array();
    
    
    public function getLocationFeed($id) {
        if (!isset($this->feeds[$id])) {
            throw new KurogoDataException('Unable to load data for location '. $id);
        }
        
        $feedData = $this->feeds[$id];
        $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'LocationsDataModel';
        
        return LocationsDataModel::factory($dataModel, $feedData);
    }
    
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
    } 
    
    protected function initializeForPage() {
        
        switch ($this->page) {
            
            case 'index':
                $locations = array();
                
                foreach ($this->feeds as $id => $feedData) {
                    $feed = $this->getLocationFeed($id);
                    print_r($feed);
                }
                print_r($this->feeds);
                exit;
                break;
        }
    }
}