<?php

class LocationsWebModule extends WebModule
{
    protected $id = 'locations';
    
    protected $feeds = array();
    
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
    } 
    
    protected function initializeForPage() {
        
        switch ($this->page) {
            
            case 'index':
                $locations = array();
                
                
                print_r($this->feeds);
                exit;
                break;
        }
    }
}