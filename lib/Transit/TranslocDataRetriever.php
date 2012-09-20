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
    protected $translocRouteRequestTimeout = 5;
    protected $translocRouteCacheTimeout = 300;
    protected $translocUpdateRequestTimeout = 2;
    protected $translocUpdateCacheTimeout = 3;
    
    const BASE_URL = 'http://api.transloc.com/1.2/';
    
    public function init($args) {
        if (isset($args['DAEMON_MODE'])) {
            $this->daemonMode = $args['DAEMON_MODE'];
        }
        
        if (isset($args['TRANSLOC_ROUTE_REQUEST_TIMEOUT'])) {
            $this->translocRouteRequestTimeout = $args['TRANSLOC_ROUTE_REQUEST_TIMEOUT'];
        }
        
        if (isset($args['TRANSLOC_ROUTE_CACHE_TIMEOUT'])) {
            $this->translocRouteCacheTimeout = $args['TRANSLOC_ROUTE_CACHE_TIMEOUT'];
        }
        
        if (isset($args['TRANSLOC_UPDATE_REQUEST_TIMEOUT'])) {
            $this->translocUpdateRequestTimeout = $args['TRANSLOC_UPDATE_REQUEST_TIMEOUT'];
        }
        
        if (isset($args['TRANSLOC_UPDATE_CACHE_TIMEOUT'])) {
            $this->translocUpdateCacheTimeout = $args['TRANSLOC_UPDATE_CACHE_TIMEOUT'];
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
                $timeout = $this->translocRouteRequestTimeout;
                $cacheLifetime = $this->translocRouteCacheTimeout;
                break;
      
            case 'arrival-estimates':
            case 'vehicles':
                $timeout = $this->translocUpdateRequestTimeout;
                $cacheLifetime = $this->translocUpdateCacheTimeout;
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
