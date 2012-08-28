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
  * DoubleMapDataRetriever
  * @package Transit
  */

class DoubleMapDataRetriever extends URLDataRetriever
{
    protected $daemonMode = false;
    protected $urlHost = '';
    protected $command = '';
    
    public function init($args) {
        if (isset($args['DAEMON_MODE'])) {
            $this->daemonMode = $args['DAEMON_MODE'];
        }
        
        if (isset($args['URL_HOST'])) {
            $this->urlHost = $args['URL_HOST'];
        }
        
        parent::init($args);
        
        $this->setCacheGroup('DoubleMap');
    }
    
    public function setCommand($command, $parameters=array()) {
        $this->command = $command;
        
        $timeout = 10;
        $cacheLifetime = 300;
        
        switch ($command) {
            case 'routes':
            case 'stops':
            case 'announcements':
                $timeout = Kurogo::getOptionalSiteVar('DOUBLEMAP_ROUTE_REQUEST_TIMEOUT', 5);
                $cacheLifetime = Kurogo::getOptionalSiteVar('DOUBLEMAP_ROUTE_CACHE_TIMEOUT', 300);
                break;
      
            case 'eta':
                $timeout = Kurogo::getOptionalSiteVar('DOUBLEMAP_ETA_REQUEST_TIMEOUT', 5);
                $cacheLifetime = Kurogo::getOptionalSiteVar('DOUBLEMAP_ETA_CACHE_TIMEOUT', 60);
                break;

            case 'buses':
                $timeout = Kurogo::getOptionalSiteVar('DOUBLEMAP_VEHICLE_REQUEST_TIMEOUT', 3);
                $cacheLifetime = Kurogo::getOptionalSiteVar('DOUBLEMAP_VEHICLE_CACHE_TIMEOUT', 4);
                break;
        }
        
        // daemons should load cached files aggressively to beat user page loads
        if ($this->daemonMode) {
            $cacheLifetime -= 900;
            if ($cacheLifetime < 1) { $cacheLifetime = 1; }
        }
        $this->setCacheLifeTime($cacheLifetime);
        $this->setTimeout($timeout);
        
        $this->setBaseURL("http://{$this->urlHost}.doublemap.com/map/v2/{$this->command}", false);
    }
    
    public function command() {
        return $this->command;
    }
    
    public function getURLHost() {
        return $this->urlHost;
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
