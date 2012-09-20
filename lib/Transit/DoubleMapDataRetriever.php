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
    protected $doubleMapRouteRequestTimeout = 5;
    protected $doubleMapRouteCacheTimeout = 300;
    protected $doubleMapETARequestTimeout = 5;
    protected $doubleMapETACacheTimeout = 60;
    protected $doubleMapVehicleRequestTimeout = 3;
    protected $doubleMapVehicleCacheTimeout = 4;
    
    public function init($args) {
        if (isset($args['DAEMON_MODE'])) {
            $this->daemonMode = $args['DAEMON_MODE'];
        }
        
        if (isset($args['URL_HOST'])) {
            $this->urlHost = $args['URL_HOST'];
        }
        
        if (isset($args['DOUBLEMAP_ROUTE_REQUEST_TIMEOUT'])) {
            $this->doubleMapRouteRequestTimeout = $args['DOUBLEMAP_ROUTE_REQUEST_TIMEOUT'];
        }
        if (isset($args['DOUBLEMAP_ROUTE_CACHE_TIMEOUT'])) {
            $this->doubleMapRouteCacheTimeout = $args['DOUBLEMAP_ROUTE_CACHE_TIMEOUT'];
        }
        
        if (isset($args['DOUBLEMAP_ETA_REQUEST_TIMEOUT'])) {
            $this->doubleMapETARequestTimeout = $args['DOUBLEMAP_ETA_REQUEST_TIMEOUT'];
        }
        if (isset($args['DOUBLEMAP_ETA_CACHE_TIMEOUT'])) {
            $this->doubleMapETACacheTimeout = $args['DOUBLEMAP_ETA_CACHE_TIMEOUT'];
        }
        
        if (isset($args['DOUBLEMAP_VEHICLE_REQUEST_TIMEOUT'])) {
            $this->doubleMapVehicleRequestTimeout = $args['DOUBLEMAP_VEHICLE_REQUEST_TIMEOUT'];
        }
        if (isset($args['DOUBLEMAP_VEHICLE_REQUEST_TIMEOUT'])) {
            $this->doubleMapVehicleCacheTimeout = $args['DOUBLEMAP_VEHICLE_REQUEST_TIMEOUT'];
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
                $timeout = $this->doubleMapRouteRequestTimeout;
                $cacheLifetime = $this->doubleMapRouteCacheTimeout;
                break;
      
            case 'eta':
                $timeout = $this->doubleMapETARequestTimeout;
                $cacheLifetime = $this->doubleMapETACacheTimeout;
                break;

            case 'buses':
                $timeout = $this->doubleMapVehicleRequestTimeout;
                $cacheLifetime = $this->doubleMapVehicleCacheTimeout;
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
