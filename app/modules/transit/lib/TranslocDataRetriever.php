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
    protected $command = '';
    protected $translocRouteRequestTimeout = 5;
    protected $translocRouteCacheTimeout = 300;
    protected $translocUpdateRequestTimeout = 2;
    protected $translocUpdateCacheTimeout = 3;
    
    const BASE_URL = 'http://api.transloc.com/1.2/';
    
    public function init($args) {
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
        
        $requestTimeout = 10;
        $cacheLifetime = 300;
        
        switch ($command) {
            case 'agencies':
            case 'routes':
            case 'segments':
            case 'stops':
                $requestTimeout = $this->translocRouteRequestTimeout;
                $cacheLifetime = $this->translocRouteCacheTimeout;
                break;
      
            case 'arrival-estimates':
            case 'vehicles':
                $requestTimeout = $this->translocUpdateRequestTimeout;
                $cacheLifetime = $this->translocUpdateCacheTimeout;
                break;
        }
        
        // daemons should load cached files aggressively to beat user page loads
        if (defined('KUROGO_SHELL')) {
            TransitDataModel::updateCacheLifetimeForShell($cacheLifetime);
        }
        $this->setCacheLifeTime($cacheLifetime);
        $this->setTimeout($requestTimeout);
        
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
