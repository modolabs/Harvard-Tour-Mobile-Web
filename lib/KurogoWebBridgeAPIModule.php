<?php

//
// This class is instantiated for modules with WebBridge support
// but which do not have an API.
//
class KurogoWebBridgeAPIModule extends APIModule {
    protected $id = '';
    protected $vmin = 1;
    protected $vmax = 1;
    
    // web bridge modules do not know their ids
    public function setID($id) {
        $this->id = $id;
        if (!$this->configModule) {
            $this->configModule = $this->id;
        }
    }
    
    protected function initializeForCommand() {
        switch ($this->command) {
            default:
                $this->invalidCommand();
                break;
        }
    }
}
