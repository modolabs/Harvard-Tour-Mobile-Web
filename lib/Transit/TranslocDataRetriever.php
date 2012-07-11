<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * TranslocDataRetriever
  * @package Transit
  */

class TranslocDataRetriever extends URLDataRetriever
{
    protected $daemonMode = false;
    protected $command = '';
    
    const BASE_URL = 'http://api.transloc.com/1.2/';
    
    public function init($args) {
        if (isset($args['DAEMON_MODE'])) {
            $this->daemonMode = $args['DAEMON_MODE'];
        }
        
        parent::init($args);
        
        $this->setCacheGroup('Transloc');
    }
    
    public function setCommand($command, $parameters=array()) {
        $this->command = $command;
        
        $timeout = 10;
        $cacheLifetime = 300;
        
        switch ($command) {
            case 'agencies':
            case 'routes':
            case 'segments':
            case 'stops':
                $timeout = Kurogo::getOptionalSiteVar('TRANSLOC_ROUTE_REQUEST_TIMEOUT', 5);
                $cacheLifetime = Kurogo::getOptionalSiteVar('TRANSLOC_ROUTE_CACHE_TIMEOUT', 300);
                break;
      
            case 'arrival-estimates':
            case 'vehicles':
                $timeout = Kurogo::getOptionalSiteVar('TRANSLOC_UPDATE_REQUEST_TIMEOUT', 2);
                $cacheLifetime = Kurogo::getOptionalSiteVar('TRANSLOC_UPDATE_CACHE_TIMEOUT', 3);
                break;
        }
        
        // daemons should load cached files aggressively to beat user page loads
        if ($this->daemonMode) {
            $cacheLifetime -= 900;
            if ($cacheLifetime < 1) { $cacheLifetime = 1; }
        }
        $this->setCacheLifeTime($cacheLifetime);
        $this->setTimeout($timeout);
        
        $this->setBaseURL(rtrim(self::BASE_URL, '/')."/{$this->command}.json", false);
    }
    
    public function command() {
        return $this->command;
    }

    protected function cacheKey() {
        if (!$url = $this->url()) {
            throw new KurogoDataException("URL could not be determined");
        }
        if (!$command = $this->command()) {
            throw new KurogoDataException("Command could not be determined");
        }
        
        $key = $command.'_'.md5($url);

        if ($data = $this->data()) {
            $key .= "_" . md5($data);
        }
        return $key;
    }
}
