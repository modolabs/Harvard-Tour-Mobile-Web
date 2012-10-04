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
    protected $urlHost = '';
    protected $command = '';
    
    protected $doubleMapRouteRequestTimeout = 5;
    protected $doubleMapRouteCacheLifetime = 300;
    
    protected $doubleMapETARequestTimeout = 5;
    protected $doubleMapETACacheLifetime = 60;
    
    protected $doubleMapVehicleRequestTimeout = 3;
    protected $doubleMapVehicleCacheLifetime = 4;
    
    public function init($args) {
        if (isset($args['URL_HOST'])) {
            $this->urlHost = $args['URL_HOST'];
        }
        
        if (isset($args['DOUBLEMAP_ROUTE_REQUEST_TIMEOUT'])) {
            $this->doubleMapRouteRequestTimeout = $args['DOUBLEMAP_ROUTE_REQUEST_TIMEOUT'];
        }
        if (isset($args['DOUBLEMAP_ROUTE_CACHE_LIFETIME'])) {
            $this->doubleMapRouteCacheLifetime = $args['DOUBLEMAP_ROUTE_CACHE_LIFETIME'];
        }
        
        if (isset($args['DOUBLEMAP_ETA_REQUEST_TIMEOUT'])) {
            $this->doubleMapETARequestTimeout = $args['DOUBLEMAP_ETA_REQUEST_TIMEOUT'];
        }
        if (isset($args['DOUBLEMAP_ETA_CACHE_LIFETIME'])) {
            $this->doubleMapETACacheLifetime = $args['DOUBLEMAP_ETA_CACHE_LIFETIME'];
        }
        
        if (isset($args['DOUBLEMAP_VEHICLE_REQUEST_TIMEOUT'])) {
            $this->doubleMapVehicleRequestTimeout = $args['DOUBLEMAP_VEHICLE_REQUEST_TIMEOUT'];
        }
        if (isset($args['DOUBLEMAP_VEHICLE_CACHE_LIFETIME'])) {
            $this->doubleMapVehicleCacheLifetime = $args['DOUBLEMAP_VEHICLE_CACHE_LIFETIME'];
        }
        
        parent::init($args);
        
        $this->setCacheGroup('DoubleMap');
    }
    
    public function setCommand($command, $parameters=array()) {
        $this->command = $command;
        
        $requestTimeout = 10;
        $cacheLifetime = 300;
        
        switch ($command) {
            case 'routes':
            case 'stops':
            case 'announcements':
                $requestTimeout = $this->doubleMapRouteRequestTimeout;
                $cacheLifetime = $this->doubleMapRouteCacheLifetime;
                break;
      
            case 'eta':
                $requestTimeout = $this->doubleMapETARequestTimeout;
                $cacheLifetime = $this->doubleMapETACacheLifetime;
                break;

            case 'buses':
                $requestTimeout = $this->doubleMapVehicleRequestTimeout;
                $cacheLifetime = $this->doubleMapVehicleCacheLifetime;
                break;
        }
        
        // daemons should load cached files aggressively to beat user page loads
        if (defined('KUROGO_SHELL')) {
            TransitDataModel::updateCacheLifetimeForShell($cacheLifetime);
        }
        $this->setCacheLifeTime($cacheLifetime);
        $this->setTimeout($requestTimeout);
        
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
