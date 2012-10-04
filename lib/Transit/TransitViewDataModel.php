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
  * Transit View Data Model
  * @package Transit
  */

class TransitViewDataModel extends DataModel implements TransitDataModelInterface
{
    protected $config = array();
    protected $models = array();
    protected $globalIDSeparator = '__';
    
    protected $cacheClass = 'DataCache';
    protected $cacheFolder = 'Transit';
    
    protected $routesCache = null;
    protected $routeCache  = null;
    protected $stopCache   = null;
    
    protected $routesCacheLifetime = 300;
    protected $routeCacheLifetime  = 20;
    protected $stopCacheLifetime   = 20;
    
    const DEFAULT_TRANSIT_CACHE_GROUP = 'View';
    
    // Do not call parent!!!
    protected function init($config) {
        $this->config = new TransitConfig($config);
        $this->initArgs = $this->config->getGlobalArgs();

        $this->setDebugMode(Kurogo::getOptionalSiteVar('DEBUG_MODE', false));
        
        if (isset($this->initArgs['TRANSIT_GLOBAL_ID_SEPARATOR'])) {
            $this->globalIDSeparator = $this->initArgs['TRANSIT_GLOBAL_ID_SEPARATOR'];
        }
        
        foreach ($this->config->getModelIDs() as $modelID) {
            $model = array(
                'system' => $this->config->getSystem($modelID),
                'live'   => false,
                'static' => false,
            );
            
            if ($this->config->hasLiveModel($modelID)) {
                $class                   = $this->config->getLiveModelClass($modelID);
                $args                    = $this->config->getLiveModelArgs($modelID);
                $args['FIELD_OVERRIDES'] = $this->config->getLiveModelOverrides($modelID);
                
                $model['live'] = DataModel::factory($class, $args);
            }
            
            if ($this->config->hasStaticModel($modelID)) {
                $class                   = $this->config->getStaticModelClass($modelID);
                $args                    = $this->config->getStaticModelArgs($modelID);
                $args['FIELD_OVERRIDES'] = $this->config->getStaticModelOverrides($modelID);
                
                $model['static'] = DataModel::factory($class, $args);
            }
            
            $this->models[$modelID] = $model;
        }
        
        if (isset($this->initArgs['CACHE_CLASS'])) {
            $this->cacheClass = $this->initArgs['CACHE_CLASS'];
        }
        if (isset($this->initArgs['CACHE_FOLDER'])) {
            $this->cacheFolder = $this->initArgs['CACHE_FOLDER'];
        }
        
        if (isset($this->initArgs['CACHE_LIFETIME'])) {
            // default single value cache lifetime configuration
            $this->routesCacheLifetime = $this->initArgs['CACHE_LIFETIME'];
            $this->routeCacheLifetime = $this->initArgs['CACHE_LIFETIME'];
            $this->stopCacheLifetime = $this->initArgs['CACHE_LIFETIME'];
        }
        if (isset($this->initArgs['TRANSIT_VIEW_CACHE_TIMEOUT'])) {
            // legacy single value cache lifetime configuration
            $this->routesCacheLifetime = $this->initArgs['TRANSIT_VIEW_CACHE_TIMEOUT'];
            $this->routeCacheLifetime = $this->initArgs['TRANSIT_VIEW_CACHE_TIMEOUT'];
            $this->stopCacheLifetime = $this->initArgs['TRANSIT_VIEW_CACHE_TIMEOUT'];
        }
        if (isset($this->initArgs['CACHE_LIFETIME_ROUTES'])) {
            $this->routesCacheLifetime = $this->initArgs['CACHE_LIFETIME_ROUTES'];
        }
        if (isset($this->initArgs['CACHE_LIFETIME_ROUTE'])) {
            $this->routeCacheLifetime = $this->initArgs['CACHE_LIFETIME_ROUTE'];
        }
        if (isset($this->initArgs['CACHE_LIFETIME_STOP'])) {
            $this->stopCacheLifetime = $this->initArgs['CACHE_LIFETIME_STOP'];
        }
        
        if (defined('KUROGO_SHELL')) {
            TransitDataModel::updateCacheLifetimeForShell($this->routesCache);
            TransitDataModel::updateCacheLifetimeForShell($this->routeCache);
            TransitDataModel::updateCacheLifetimeForShell($this->stopCache);
        }
        
        $this->routesCache = DataCache::factory($this->cacheClass, array('CACHE_FOLDER' => $this->cacheFolder));
        $this->routesCache->setCacheGroup('View');
        $this->routesCache->setCacheLifetime($this->routesCacheLifetime);

        $this->routeCache = DataCache::factory($this->cacheClass, array('CACHE_FOLDER' => $this->cacheFolder));
        $this->routeCache->setCacheGroup('View');
        $this->routeCache->setCacheLifetime($this->routeCacheLifetime);

        $this->stopCache = DataCache::factory($this->cacheClass, array('CACHE_FOLDER' => $this->cacheFolder));
        $this->stopCache->setCacheGroup('View');
        $this->stopCache->setCacheLifetime($this->stopCacheLifetime);
    }
    
    // Cache for getRoutes()
    protected function getCachedRoutesView() {
        $view = $this->routesCache->get('routes');
        return $view ? $view : array();
    }
    protected function cacheRoutesView($view) {
        $this->routesCache->set('routes', $view);
    }
    
    // Cache for getRouteInfo()
    protected function getCachedRouteView($globalID) {
        $view = $this->routeCache->get("route.$globalID");
        return $view ? $view : array();
    }
    protected function cacheRouteView($globalID, $view) {
        $this->routeCache->set("route.$globalID", $view);
    }
    
    // Cache for getStopInfo()
    protected function getCachedStopView($globalStopID) {
        $view = $this->stopCache->get("stop.$globalStopID");
        return $view ? $view : array();
    }
    protected function cacheStopView($globalStopID, $view) {
        $this->stopCache->set("stop.$globalStopID", $view);
    }
    
    public function refreshLiveServices() {
        foreach ($this->config->getModelIDs() as $modelID) {
            if ($this->config->hasLiveModel($modelID)) {            
                unset($this->models[$modelID]['live']);
                
                $class                   = $this->config->getLiveModelClass($modelID);
                $args                    = $this->config->getLiveModelArgs($modelID);
                $args['FIELD_OVERRIDES'] = $this->config->getLiveModelOverrides($modelID);
                
                $this->models[$modelID]['live'] = TransitDataModel::factory($class, $args);
            }
        }
    }
    
    public function getStopInfo($globalStopID) {
        $stopInfo = array();
        
        if (!$stopInfo = $this->getCachedStopView($globalStopID)) {
            list($system, $stopID) = $this->getRealID($globalStopID);
          
            foreach ($this->modelsForStop($system, $stopID) as $model) {
                $modelInfo = false;
                
                if ($model['live']) {
                    $modelInfo = $model['live']->getStopInfo($stopID);
                }
                
                if ($model['static']) {
                    $staticModelInfo = $model['static']->getStopInfo($stopID);
                }
                
                if (!$modelInfo) {
                    $modelInfo = $staticModelInfo;
                } else if (isset($staticModelInfo['routes'])) {
                    // if live model returns routes that are actually not in service
                    foreach (array_keys($modelInfo['routes']) as $routeID) {
                        if (!isset($staticModelInfo['routes'][$routeID])) {
                            unset($modelInfo['routes'][$routeID]);
                        }
                    }
            
                    foreach ($staticModelInfo['routes'] as $routeID => $routeInfo) {
                        if (!isset($modelInfo['routes'][$routeID]) ||
                            !isset($modelInfo['routes'][$routeID]['directions'])) {
                            $modelInfo['routes'][$routeID] = $routeInfo;
                        }
                        
                        // Use static route names if available
                        if (isset($routeInfo['name']) && $routeInfo['name']) {
                            $modelInfo['routes'][$routeID]['name'] = $routeInfo['name'];
                        }
                    }
                    
                    // Use static stop names if available
                    if (isset($staticModelInfo['name']) && $staticModelInfo['name']) {
                        $modelInfo['name'] = $staticModelInfo['name'];
                    }
                }
                
                if ($modelInfo) {
                    if (!count($stopInfo)) {
                        $stopInfo = $modelInfo;
                    } else {
                        foreach ($modelInfo['routes'] as $routeID => $stopRouteInfo) {
                            if (!isset($stopInfo['routes'][$routeID])) {
                                // add new route
                                $stopInfo['routes'][$routeID] = $stopRouteInfo;
                                continue;
                            }
                            
                            $directions = $stopInfo['routes'][$routeID]['directions'];
                            foreach ($stopRouteInfo['directions'] as $directionID => $directionInfo) {
                                if (!isset($directions[$directionID])) {
                                    // add new direction
                                    $directions[$directionID] = $directionInfo;
                                    
                                } else if (count($directionInfo['predictions'])) {
                                    // merge in new predictions for existing direction
                                    $directions[$directionID]['predictions'] = array_merge($directions[$directionID]['predictions'],
                                                                                           $directionInfo['predictions']);
                                                    
                                    $directions[$directionID]['predictions'] = array_unique($directions[$directionID]['predictions']);
                                    sort($directions[$directionID]['predictions']);
                                }
                            }
                            $stopInfo['routes'][$routeID]['directions'] = $directions;
                        }
                    }
                }
            }
            uasort($stopInfo['routes'], array('TransitDataModel', 'sortRoutes'));
            
            $this->remapStopInfo($system, $stopInfo);
            
            $this->cacheStopView($globalStopID, $stopInfo);
        }
        return $stopInfo;
    }
    
    protected function liveAndStaticMerge($live, $static) {
        if ($live && !$static) {
            return $live;
        }
        if (!$live && $static) {
            return $static;
        }
        
    }
    
    public function getMapImageForStop($globalStopID, $width, $height) {
        $image = false;
        list($system, $stopID) = $this->getRealID($globalStopID);
        $models = $this->modelsForStop($system, $stopID);
        $model = reset($models);
        
        if ($model['live']) {
            $image = $model['live']->getMapImageForStop($stopID, $width, $height);
        }
        
        if (!$image && $model['static']) {
            $image = $model['static']->getMapImageForStop($stopID, $width, $height);
        }
        
        return $image;
    }
  
    public function getMapImageForRoute($globalRouteID, $width, $height) {
        $image = false;
        list($system, $routeID) = $this->getRealID($globalRouteID);
        $model = $this->modelForRoute($system, $routeID);
        
        if ($model['live']) {
            $image = $model['live']->getMapImageForRoute($routeID, $width, $height);
        }
        
        if (!$image && $model['static']) {
            $image = $model['static']->getMapImageForRoute($routeID, $width, $height);
        }
        
        return $image;
    }
    
    public function getRouteInfo($globalRouteID, $time=null) {
        $routeInfo = array();
        
        if ($time != null || !$routeInfo = $this->getCachedRouteView($globalRouteID)) {
            list($system, $routeID) = $this->getRealID($globalRouteID);
            $model = $this->modelForRoute($system, $routeID);
            
            if ($model['live']) {
                $routeInfo = $model['live']->getRouteInfo($routeID, $time);
                if (count($routeInfo)) {
                    $routeInfo['live'] = true;
                }
            }
            
            if ($model['static']) {
                $staticRouteInfo = $model['static']->getRouteInfo($routeID, $time);
                
                if (!count($routeInfo)) {
                    $routeInfo = $staticRouteInfo;
                
                } else if (count($staticRouteInfo)) {
                    if (strlen($staticRouteInfo['name'])) {
                        // static name is better
                        $routeInfo['name'] = $staticRouteInfo['name'];
                    }
                    if (strlen($staticRouteInfo['description'])) {
                        // static description is better
                        $routeInfo['description'] = $staticRouteInfo['description'];
                    }
                    if ($staticRouteInfo['frequency'] != 0) { // prefer static
                        $routeInfo['frequency'] = $staticRouteInfo['frequency'];
                    }
                    if (!count($routeInfo['directions'])) {
                        $routeInfo['directions'] = $staticRouteInfo['directions'];
                    
                    } else {
                        foreach ($routeInfo['directions'] as $directionID => $ignored1) {
                            foreach ($routeInfo['directions'][$directionID]['stops'] as $stopID => $ignored2) {
                                $staticStopID = $stopID;
                              
                                if (!isset($staticRouteInfo['directions'][$directionID]['stops'][$staticStopID])) {
                                    // NextBus sometimes has _ar suffixes on it.  Try stripping them
                                    $parts = explode('_', $stopID);
                                    if (isset($staticRouteInfo['directions'][$directionID]['stops'][$parts[0]])) {
                                        //error_log("Warning: static route does not have live stop id $stopID, using {$parts[0]}");
                                        $staticStopID = $parts[0];
                                    }
                                }
                                
                                if (isset($staticRouteInfo['directions'][$directionID]['stops'][$staticStopID])) {
                                    // Use static stop names if they exist
                                    if ($staticRouteInfo['directions'][$directionID]['stops'][$staticStopID]['name']) {
                                        $routeInfo['directions'][$directionID]['stops'][$stopID]['name'] = 
                                            $staticRouteInfo['directions'][$directionID]['stops'][$staticStopID]['name'];
                                    }
                                    
                                    // Prefer the static stop order
                                    $routeInfo['directions'][$directionID]['stops'][$stopID]['i'] = 
                                        $staticRouteInfo['directions'][$directionID]['stops'][$staticStopID]['i'];
                                    
                                    // Use static arrival time if available when live tracking is not available
                                    if (!$routeInfo['directions'][$directionID]['stops'][$stopID]['hasTiming'] && 
                                         $staticRouteInfo['directions'][$directionID]['stops'][$staticStopID]['hasTiming']) {
                                        $routeInfo['directions'][$directionID]['stops'][$stopID]['arrives'] = 
                                            $staticRouteInfo['directions'][$directionID]['stops'][$staticStopID]['arrives'];
                                        
                                        if (isset($staticRouteInfo['directions'][$directionID]['stops'][$staticStopID]['predictions'])) {
                                            $routeInfo['directions'][$directionID]['stops'][$stopID]['predictions'] = 
                                                $staticRouteInfo['directions'][$directionID]['stops'][$staticStopID]['predictions'];
                                        } else {
                                            unset($routeInfo['directions'][$directionID]['stops'][$stopID]['predictions']);
                                        }
                                    }
                                } else {
                                    Kurogo::log(LOG_WARNING, "static route info does not have live stop id $stopID", 'transit');
                                }
                            }
                        
                            uasort($routeInfo['directions'][$directionID]['stops'], array('TransitDataModel', 'sortStops'));
                        }
                    }
                }
            }
            if ($routeInfo['splitByHeadsign']) {
                // Headsigns are named so sort them
                uasort($routeInfo['directions'], array('TransitDataModel', 'sortDirections'));
            }
            
            $routeInfo['lastupdate'] = time();
            
            $this->remapRouteInfo($model['system'], $routeInfo);
      
            if ($time == null) {
                $this->cacheRouteView($globalRouteID, $routeInfo);
            }
        }
        
        return $routeInfo;    
    }
    
    public function getRoutePaths($globalRouteID) {
        $paths = array();
        
        list($system, $routeID) = $this->getRealID($globalRouteID);
        $model = $this->modelForRoute($system, $routeID);
        
        if ($model['live']) {
            $paths = $model['live']->getRoutePaths($routeID);
        } else if ($model['static']) {
            $paths = $model['static']->getRoutePaths($routeID);
        }
        
        return $paths;
    }
    
    public function getRouteVehicles($globalRouteID) {
        $vehicles = array();
        
        list($system, $routeID) = $this->getRealID($globalRouteID);
        $model = $this->modelForRoute($system, $routeID);
    
        if ($model['live']) {
            $vehicles = $model['live']->getRouteVehicles($routeID);
        } else if ($model['static']) {
            $vehicles = $model['static']->getRouteVehicles($routeID);
        }
        $vehicles = $this->remapVehicles($model['system'], $vehicles);
        
        return $vehicles;
    }
    
    public function getServiceInfoForRoute($globalRouteID) {
        $info = false;
        
        list($system, $routeID) = $this->getRealID($globalRouteID);
        $model = $this->modelForRoute($system, $routeID);
        
        if ($model['live']) {
            $info = $model['live']->getServiceInfoForRoute($routeID);
        }
        
        if (!$info && $model['static']) {
            $info = $model['static']->getServiceInfoForRoute($routeID);
        }
        
        return $info;
    }
    
    public function getRoutes($time=null) {
        $allRoutes = array();
        $cacheKey = 'allRoutes';
        
        if ($time != null || !$allRoutes = $this->getCachedRoutesView()) {
            foreach ($this->models as $model) {
                $routes = array();
                
                if ($model['live']) {
                    $routes = $this->remapRoutes($model['system'], $model['live']->getRoutes($time));
                }
                
                if ($model['static']) {
                    $staticRoutes = $this->remapRoutes($model['system'], $model['static']->getRoutes($time));
                    if (!count($routes)) {
                        $routes = $staticRoutes;
                    } else {
                        foreach ($routes as $routeID => $routeInfo) {
                          if (isset($staticRoutes[$routeID])) {
                              if (!$routeInfo['running']) {
                                  $routes[$routeID] = $staticRoutes[$routeID];
                              } else {
                                  // static name is better
                                  $routes[$routeID]['name'] = $staticRoutes[$routeID]['name'];
                                  $routes[$routeID]['description'] = $staticRoutes[$routeID]['description'];
                                  
                                  if ($staticRoutes[$routeID]['frequency'] != 0) {
                                      $routes[$routeID]['frequency'] = $staticRoutes[$routeID]['frequency'];
                                  }
                              }
                          }
                        }
                        // Pull in static routes with no live data
                        foreach ($staticRoutes as $routeID => $staticRouteInfo) {
                            if (!isset($routes[$routeID])) {
                                $routes[$routeID] = $staticRouteInfo;
                            }
                        }
                    }
                }
                $allRoutes += $routes;
            }
            uasort($routes, array('TransitDataModel', 'sortRoutes'));
            
            if ($time == null) {
                $this->cacheRoutesView($allRoutes);
            }
        }
        
        return $allRoutes;
    }
    
    // Private functions
    protected function remapStopInfo($system, &$stopInfo) {
        if (isset($stopInfo['routes'])) {
            $routes = array();
            foreach ($stopInfo['routes'] as $routeID => $routeInfo) {
                $routes[$this->getGlobalID($system, $routeID)] = $routeInfo;
            }
            $stopInfo['routes'] = $routes;
        }
    }
    
    protected function remapRouteInfo($system, &$routeInfo) {
        // remap stop ids for schedule mode structures
        foreach ($routeInfo['directions'] as $d => $directionInfo) {
            foreach ($directionInfo['segments'] as $i => $segmentInfo) {
                foreach ($segmentInfo['stops'] as $j => $stopInfo) {
                    $routeInfo['directions'][$d]['segments'][$i]['stops'][$j]['id'] = 
                        $this->getGlobalID($system, $stopInfo['id']);
                }
            }
            foreach ($directionInfo['stops'] as $i => $stopInfo) {
                $routeInfo['directions'][$d]['stops'][$i]['id'] = 
                    $this->getGlobalID($system, $stopInfo['id']);
            }
        }
    }
    
    protected function remapRoutes($system, $routes) {
        $mappedRoutes = array();
        
        foreach ($routes as $routeID => $routeInfo) {
            $mappedRoutes[$this->getGlobalID($system, $routeID)] = $routeInfo;
        }
        
        return $mappedRoutes;
    }
    
    protected function remapVehicles($system, $vehicles) {
        $mappedVehicles = array();
        
        foreach ($vehicles as $vehicleID => $vehicleInfo) {
            if (isset($vehicleInfo['routeID'])) {
                $vehicleInfo['routeID'] = $this->getGlobalID($system, $vehicleInfo['routeID']);
            }
            if (isset($vehicleInfo['nextStop'])) {
                $vehicleInfo['nextStop'] = $this->getGlobalID($system, $vehicleInfo['nextStop']);
            }
            $mappedVehicles[$this->getGlobalID($system, $vehicleID)] = $vehicleInfo;
        }
        
        return $mappedVehicles;
    }
  
    protected function modelForRoute($system, $routeID) {
        foreach ($this->models as $model) {
            if ($model['system'] != $system) { continue; }
          
            if ($model['live'] && $model['live']->hasRoute($routeID)) {
                return $model;
            }
            if ($model['static'] && $model['static']->hasRoute($routeID)) {
                return $model;
            }
        }
        return array('system' => $system, 'live' => false, 'static' => false);
    }
    
    protected function modelsForStop($system, $stopID) {
        $models = array();
      
        foreach ($this->models as $model) {
            if ($model['system'] != $system) { continue; }
        
            if (($model['live'] && $model['live']->hasStop($stopID)) ||
                ($model['static'] && $model['static']->hasStop($stopID))) {
                $models[] = $model;
            }
        }
        return $models;
    }
    
    protected function getGlobalID($system, $realID) {
        return $system.$this->globalIDSeparator.$realID;
    }
    
    protected function getRealID($globalID) {
        $parts = explode($this->globalIDSeparator, $globalID);
        if (count($parts) == 2) {
            return $parts;
        } else {
            throw new Exception("Invalid global view ID '$globalID'");
        }
    }
}

class TransitConfig
{
    protected $defaultArgs = array();
    protected $models = array();
    
    function __construct($feedConfigs) {
        if (isset($feedConfigs['defaults'])) {
            $this->defaultArgs = $feedConfigs['defaults'];
            unset($feedConfigs['defaults']);
        }
        
        foreach ($feedConfigs as $id => $config) {
            $system = isset($config['system']) ? $config['system'] : $id;
            
            // Figure out model classes
            $liveModelClass = null;
            if (isset($config['live_class']) && $config['live_class']) {
                $liveModelClass = $config['live_class'];
            }
            unset($config['live_class']);
      
            $staticModelClass = null;
            if (isset($config['static_class']) && $config['static_class']) {
                $staticModelClass = $config['static_class'];
            }
            unset($config['static_class']);
            
            // Add models
            if (isset($liveModelClass) || isset($staticModelClass)) {
                $this->models[$id] = array(
                    'system' => $system,
                );
            }
            if (isset($liveModelClass) && $liveModelClass) {
                $this->models[$id]['live'] = array(
                    'class'     => $liveModelClass,
                    'arguments' => $this->defaultArgs,
                    'overrides' => array(),
                );
            }
            if (isset($staticModelClass) && $staticModelClass) {
                $this->models[$id]['static'] = array(
                    'class'     => $staticModelClass,
                    'arguments' => $this->defaultArgs,
                    'overrides' => array(),
                );
            }
            
            // Read overrides and arguments
            foreach ($config as $configKey => $configValue) {
                $parts = explode('_', $configKey);
                
                if (count($parts) < 3) { continue; } // skip extra keys
                
                $model = $parts[0];
                $type = $parts[1];
                $keyOrVal = end($parts);
                
                if (!($model == 'live' || $model == 'static' || $model == 'all')) {
                    Kurogo::log(LOG_WARNING, "unknown transit configuration type '$type'", 'transit');
                    continue;
                }
                $models = ($model == 'all') ? array('live', 'static') : array($model);
                
                // skip values so we don't add twice
                if ($keyOrVal !== 'keys') { continue; }
                
                $configValueKey = implode('_', array_slice($parts, 0, -1)).'_vals';
                if (!isset($config[$configValueKey])) {
                    Kurogo::log(LOG_WARNING, "transit configuration file missing value '$configValueKey' for key '$configKey'", 'transit');
                    continue;
                }
                
                $fieldKeys = $configValue;
                $fieldValues = $config[$configValueKey];
                
                switch ($type) {
                    case 'argument': 
                        foreach ($fieldKeys as $i => $fieldKey) {
                            $this->setArgument($id, $models, $fieldKey, $fieldValues[$i]);
                        }
                        break;
                      
                    case 'override':
                        if (count($parts) == 5) {
                            $object = $parts[2];
                            $field = $parts[3];
                            
                            foreach ($fieldKeys as $i => $fieldKey) {
                                $this->setFieldOverride($id, $models, $object, $field, $fieldKey, $fieldValues[$i]);
                            }
                        }
                        break;
                    
                    default:
                        Kurogo::log(LOG_WARNING, "unknown transit configuration key '$configKey'", 'transit');
                        break;
                }
            }
        }
    }
    
    protected function setArgument($id, $models, $key, $value) {
        foreach ($models as $model) {
            if (isset($this->models[$id], $this->models[$id][$model])) {
                $this->models[$id][$model]['arguments'][$key] = $value;
            }
        }
    }
    
    protected function setFieldOverride($id, $models, $object, $field, $key, $value) {
        foreach ($models as $model) {
            if (isset($this->models[$id], $this->models[$id][$model])) {
                if (!isset($this->models[$id][$model]['overrides'][$object])) {
                    $this->models[$id][$model]['overrides'][$object] = array();
                }
                if (!isset($this->models[$id][$model]['overrides'][$object][$field])) {
                    $this->models[$id][$model]['overrides'][$object][$field] = array();
                }
                $this->models[$id][$model]['overrides'][$object][$field][$key] = $value;
            }
        }
    }
    
    //
    // Query
    //
    
    protected function getModelValueForKey($id, $type, $key, $default) {
        if (isset($this->models[$id], 
                  $this->models[$id][$type], 
                  $this->models[$id][$type][$key])) {
                  
            return $this->models[$id][$type][$key];
        } else {
            return $default;
        }    
    }
    
    //
    // Public functions
    //
    
    public function getModelIDs() {
        return array_keys($this->models);
    }
    
    public function hasLiveModel($id) {
        return isset($this->models[$id], $this->models[$id]['live']);
    }
    public function hasStaticModel($id) {
        return isset($this->models[$id], $this->models[$id]['static']);
    }
    
    public function getSystem($id) {
        return isset($this->models[$id]) ? $this->models[$id]['system'] : $id;
    }
    public function getGlobalArgs() {
        return $this->defaultArgs;
    }
    
    public function getLiveModelClass($id) {
        return $this->getModelValueForKey($id, 'live', 'class', false);
    }
    public function getStaticModelClass($id) {
        return $this->getModelValueForKey($id, 'static', 'class', false);
    }
    
    public function getLiveModelArgs($id) {
        return $this->getModelValueForKey($id, 'live', 'arguments', array());
    }
    public function getStaticModelArgs($id) {
        return $this->getModelValueForKey($id, 'static', 'arguments', array());
    }
    
    public function getLiveModelOverrides($id) {
        return $this->getModelValueForKey($id, 'live', 'overrides', array());
    }
    public function getStaticModelOverrides($id) {
        return $this->getModelValueForKey($id, 'static', 'overrides', array());
    }
}
