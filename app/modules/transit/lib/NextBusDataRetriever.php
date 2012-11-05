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
  * NextBusDataRetriever
  * @package Transit
  */

class NextBusDataRetriever extends URLDataRetriever
{
    protected $nextBusRouteCacheLifetime = 86400;
    protected $nextBusPredictionCacheLifetime = 20;
    protected $nextBusVehicleCacheLifetime = 10;
    protected $nextBusRequestTimeout = 10;
    
    const DEFAULT_BASE_URL = 'http://webservices.nextbus.com/service/publicXMLFeed';
    
    public function init($args) {
        if (!isset($args['BASE_URL'])) {
            $args['BASE_URL'] = self::DEFAULT_BASE_URL;
        }
        
        if (isset($args['NEXTBUS_ROUTE_CACHE_LIFETIME'])) {
            $this->nextBusRouteCacheLifetime = $args['NEXTBUS_ROUTE_CACHE_LIFETIME'];
        }
        
        if (isset($args['NEXTBUS_PREDICTION_CACHE_LIFETIME'])) {
            $this->nextBusPredictionCacheLifetime = $args['NEXTBUS_PREDICTION_CACHE_LIFETIME'];
        }
        
        if (isset($args['NEXTBUS_VEHICLE_CACHE_LIFETIME'])) {
            $this->nextBusVehicleCacheLifetime = $args['NEXTBUS_VEHICLE_CACHE_LIFETIME'];
        }
        
        if (isset($args['NEXTBUS_VEHICLE_REQUEST_TIMEOUT'])) {
            $this->nextBusRequestTimeout = $args['NEXTBUS_VEHICLE_REQUEST_TIMEOUT'];
        }
        
        parent::init($args);
        
        $this->setCacheGroup('NextBus');
        $this->setTimeout($this->nextBusRequestTimeout);
    }
    
    protected function url() {
        $url = $this->baseURL();
        $parameters = $this->parameters();
        
        $stopParameters = array();
        if (isset($parameters['stops'])) {
            foreach ($parameters['stops'] as $stopArg) {
                $stopParameters[] = http_build_query(array('stops' => $stopArg));
            }
            unset($parameters['stops']);
        }
        
        if (count($parameters) > 0 || count($stopParameters) > 0) {
            $url .= strpos($this->baseURL, '?') !== false ? '&' : '?';
            if (count($parameters) > 0) {
                $url .= http_build_query($parameters);
            }
            if (count($parameters) > 0 && count($stopParameters) > 0) {
                $url .= '&';
            }
            if (count($stopParameters) > 0) {
                $url .= implode('&', $stopParameters);
            }
        }
        
        return $url;
    }
    
    public function addFilter($var, $value) {
        parent::addFilter($var, $value);
        if ($var == 'command') {
            $this->updateForCommand($value);
        }
    }
    
    public function setFilters($filters) {
        parent::setFilters($filters);
        if (isset($filters['command'])) {
            $this->updateForCommand($filters['command']);
        }
    }

    protected function updateForCommand($command) {
        $cacheLifetime = $this->cacheLifetime();
        $requestTimeout = $this->nextBusRequestTimeout;
        switch ($command) {
            case 'routeList':
            case 'routeConfig':
                $cacheLifetime = $this->nextBusRouteCacheLifetime;
                break;
                
            case 'predictions':
            case 'predictionsForMultiStops':
                $cacheLifetime = $this->nextBusPredictionCacheLifetime;
                break;
                
            case 'vehicleLocations':
                $cacheLifetime = $this->nextBusVehicleCacheLifetime;
                break;
        }
        // daemons should load cached files aggressively to beat user page loads
        if (defined('KUROGO_SHELL')) {
            TransitDataModel::updateCacheLifetimeForShell($cacheLifetime);
        }
        $this->setCacheLifeTime($cacheLifetime);
        $this->setTimeout($requestTimeout);
    }
    
    protected function cacheKey() {
        if (!($url = $this->url())) {
            throw new KurogoDataException("URL could not be determined");
        }

        if (!($parameters = $this->parameters())) {
            throw new KurogoDataException("Command could not be determined");
        }
        
        $key = (isset($parameters['command']) ? $parameters['command'] : 'url').
            (isset($parameters['a']) ? '_'.$parameters['a'] : '').'_'.md5($url);

        if ($data = $this->data()) {
            $key .= "_" . md5($data);
        }
        return $key;
    }
    
    public function getDataAndAge(&$age, &$response=null) {
        $data = parent::getData($response);
        
        $age = null;
        $modified = $this->cache->getModified($this->cacheKey());
        if (!is_null($modified)) {
            $age = time() - $modified;    
        }
        
        return $data;
    }
}
