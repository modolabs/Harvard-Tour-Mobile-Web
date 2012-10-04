<?php

includePackage('Transit');

class TransitShellModule extends ShellModule
{
    protected $id = 'calendar';
    protected function initializeForCommand() {
        switch($this->command) {
            case 'fetchAllData':
                $view = DataModel::factory("TransitViewDataModel", $this->loadFeedData());
                $routesInfo = $view->getRoutes();
                
                return 0;
                
                break;
            default:
                $this->invalidCommand();
                break;
        }
    }
}