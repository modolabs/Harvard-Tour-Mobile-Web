<?php 
/**
  * Transit Configuration Parser
  * @package Transit
  */

class TransitConfig {
  private $parsers = array();
  
  function __construct($feedConfig) {
    // Loads an array from an ini file
    // See config/feeds/transit.ini for more details on the structure of the ini

    foreach ($feedConfig as $id => $config) {
      $system = isset($config['system']) ? $config['system'] : $id;
    
      $liveParserClass = null;
      if (isset($config['live_class']) && $config['live_class']) {
        $liveParserClass = $config['live_class'];
      }
      unset($config['live_class']);

      $staticParserClass = null;
      if (isset($config['static_class']) && $config['static_class']) {
        $staticParserClass = $config['static_class'];
      }
      unset($config['static_class']);

      $this->addParser($id, $system, $liveParserClass, $staticParserClass);
      
      if (isset($config['route_whitelist']) && count($config['route_whitelist'])) {
        $this->setRouteWhitelist($id, $config['route_whitelist']);
      }
      unset($config['route_whitelist']);
      
      foreach ($config as $configKey => $configValue) {
        if ($configKey == 'system') { continue; }
      
        $parts = explode('_', $configKey);
        
        $type = $parts[0];
        $field = $parts[1];
        $keyOrVal = end($parts);
        
        // skip values so we don't add twice
        if ($keyOrVal == 'vals') { continue; }  
        
        if ($type != 'live' && $type != 'static') {
          error_log("Warning: unknown transit configuration type '$type'");
          continue;
        }
        
        $configValueKey = implode('_', array_slice($parts, 0, -1)).'_vals';
        if (!isset($config[$configValueKey])) {
          error_log("Warning: transit configuration file missing value '$configValueKey' for key '$configKey'");
          continue;
        }
        
        $fieldKeys = $configValue;
        $fieldValues = $config[$configValueKey];
        
        switch ($field) {
          case 'argument': 
            foreach ($fieldKeys as $i => $fieldKey) {
              $this->setArgument($id, $type, $fieldKey, $fieldValues[$i]);
            }
            break;
            
          case 'override':
            if (count($parts) == 5) {
              $object = $parts[2];
              $field = $parts[3];
              
              foreach ($fieldKeys as $i => $fieldKey) {
                $this->setFieldOverride($id, $type, $object, $field, $fieldKey, $fieldValues[$i]);
              }
            }
            break;
          
          default:
            error_log("Warning: unknown transit configuration key '$configKey'");
            break;
        }
      }
    }
  }
  
  public function addParser($id, $system, $liveParserClass=null, $staticParserClass=null) {
    if (isset($liveParserClass) || isset($staticParserClass)) {
      $this->parsers[$id] = array(
        'system' => $system,
      );
    }
    if (isset($liveParserClass) && $liveParserClass) {
      $this->parsers[$id]['live'] = array(
        'class'     => $liveParserClass,
        'arguments' => array(),
        'overrides' => array(),
      );
    }
    if (isset($staticParserClass) && $staticParserClass) {
      $this->parsers[$id]['static'] = array(
        'class'     => $staticParserClass,
        'arguments' => array(),
        'overrides' => array(),
      );
    }
  }
  
  private function setArgument($id, $type, $key, $value) {
    if (isset($this->parsers[$id], $this->parsers[$id][$type])) {
      $this->parsers[$id][$type]['arguments'][$key] = $value;
    }
  }
  
  private function setFieldOverride($id, $type, $object, $field, $key, $value) {
    if (isset($this->parsers[$id], $this->parsers[$id][$type])) {
      if (!isset($this->parsers[$id][$type]['overrides'][$object])) {
        $this->parsers[$id][$type]['overrides'][$object] = array();
      }
      if (!isset($this->parsers[$id][$type]['overrides'][$object][$field])) {
        $this->parsers[$id][$type]['overrides'][$object][$field] = array();
      }
      $this->parsers[$id][$type]['overrides'][$object][$field][$key] = $value;
    }
  }

  private function setRouteWhitelist($id, $routes) {
    if (isset($this->parsers[$id])) {
      $this->parsers[$id]['routes'] = $routes;
    }
  }
  
  //
  // Query
  //
  
  private function getParserValueForKey($id, $type, $key, $default) {
    if (isset($this->parsers[$id], 
              $this->parsers[$id][$type], 
              $this->parsers[$id][$type][$key])) {
              
      return $this->parsers[$id][$type][$key];
    } else {
      return $default;
    }    
  }
  
  public function getParserIDs() {
    return array_keys($this->parsers);
  }
  
  public function hasLiveParser($id) {
    return isset($this->parsers[$id], $this->parsers[$id]['live']);
  }
  public function hasStaticParser($id) {
    return isset($this->parsers[$id], $this->parsers[$id]['static']);
  }
  
  public function getSystem($id) {
    return isset($this->parsers[$id]) ? $this->parsers[$id]['system'] : $id;
  }
  
  public function getLiveParserClass($id) {
    return $this->getParserValueForKey($id, 'live', 'class', false);
  }
  public function getStaticParserClass($id) {
    return $this->getParserValueForKey($id, 'static', 'class', false);
  }
  
  public function getLiveParserRouteWhitelist($id) {
    return $this->getParserValueForKey($id, 'live', 'routes', array());
  }
  public function getStaticParserRouteWhitelist($id) {
    return $this->getParserValueForKey($id, 'static', 'routes', array());
  }
  
  public function getLiveParserArgs($id) {
    return $this->getParserValueForKey($id, 'live', 'arguments', array());
  }
  public function getStaticParserArgs($id) {
    return $this->getParserValueForKey($id, 'static', 'arguments', array());
  }
  
  public function getLiveParserOverrides($id) {
    return $this->getParserValueForKey($id, 'live', 'overrides', array());
  }
  public function getStaticParserOverrides($id) {
    return $this->getParserValueForKey($id, 'static', 'overrides', array());
  }
}
