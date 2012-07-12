<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Locations');
class LocationsShellModule extends ShellModule {

    protected $id = 'locations';
    
    protected $feeds = array();
    protected $timezone;
    protected $feedGroups = array();
 
    public function getLocationFeed($groupID, $id) {
        if (!isset($this->feedGroups[$groupID])) {
            throw new KurogoDataException($this->getLocalizedString('ERROR_NO_LOCATION_FEED', $id));
        }
        //load feeds by group
        if (!isset($this->feeds[$id])) {
            throw new KurogoDataException($this->getLocalizedString('ERROR_NO_LOCATION_FEED', $id));
        }
        
        $feedData = $this->feeds[$id];
        $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'LocationsDataModel';
        
        return LocationsDataModel::factory($dataModel, $feedData);
    }

    public function getAllControllers() {
        $controllers = array();
        
        foreach($this->feedGroups as $groupID => $feedGroup) {
            $configName = 'feeds-'.$groupID;
            if ($this->feeds = $this->getModuleSections($configName)) {
                foreach ($this->feeds as $id => $feedData) {
                    if ($feed = $this->getLocationFeed($groupID, $id)) {
                        $controllers[] = $feed;
                    }
                }
            }
        }
        return $controllers;
    }

    protected function initializeForCommand() {
        $this->feedGroups = $this->getModuleSections('feedgroups');
        $this->timezone = Kurogo::siteTimezone();

        switch($this->command) {
            case 'fetchAllData':
                $this->preFetchAllData();
                return 0;
                
                break;
            default:
                $this->invalidCommand();
                
                break;
        }
    }
}