<?php

class TransitShellModule extends ShellModule
{
    protected $id = 'transit';
    
    protected function initializeForCommand() {
        switch ($this->command) {
            case 'fetchAllData':
                $view = DataModel::factory('TransitViewDataModel', $this->loadFeedData());
                $routesInfo = $view->getRoutes();
                return 0;
                
            default:
                $this->invalidCommand();
                break;
        }
    }
}
