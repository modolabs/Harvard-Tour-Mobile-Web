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
    protected $daemonMode = false;
    
    const DEFAULT_BASE_URL = 'http://webservices.nextbus.com/service/publicXMLFeed';
    
    public function init($args) {
        if (!isset($args['CACHE_CLASS'])) {
            $args['CACHE_CLASS'] = 'NextBusDataCache';
        }
        
        if (!isset($args['BASE_URL'])) {
            $args['BASE_URL'] = self::DEFAULT_BASE_URL;
        }
        
        if (isset($args['DAEMON_MODE'])) {
            $this->daemonMode = $args['DAEMON_MODE'];
        }
        
        parent::init($args);
        
        $this->setCacheGroup('NextBus');
        $this->setTimeout(10);
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
        switch ($command) {
            case 'routeList':
            case 'routeConfig':
                $cacheLifetime = Kurogo::getOptionalSiteVar('NEXTBUS_ROUTE_CACHE_TIMEOUT', 86400);
                break;
                
            case 'predictions':
            case 'predictionsForMultiStops':
                $cacheLifetime = Kurogo::getOptionalSiteVar('NEXTBUS_PREDICTION_CACHE_TIMEOUT', 20);
                break;
                
            case 'vehicleLocations':
                $cacheLifetime = Kurogo::getOptionalSiteVar('NEXTBUS_VEHICLE_CACHE_TIMEOUT', 10);
                break;
        }
        if ($this->daemonMode) {
            $cacheLifetime -= 900;
            if ($cacheLifetime < 1) { $cacheLifetime = 1; }
        }
        $this->setCacheLifeTime($cacheLifetime);
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
        $age = $this->cache->getAge($this->cacheKey());
        
        return $data;
    }
}

class NextBusDataCache extends DataCache
{
    public function getAge($cacheKey) {
        return $this->getDiskAge($cacheKey);
    }
}
