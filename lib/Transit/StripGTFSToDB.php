<?php

//
// Configuration
//

class StripGTFSToDB {
    protected $config = array();
    protected $messages = '';

    public function getMessages() {
        return $this->messages;
    }
    
    public function getError() {
        return $this->error;
    }

    function addGTFS($feedIndex, $feedData) {
        $zipFile = DATA_DIR."/gtfs/gtfs-$feedIndex.zip";
        if (isset($feedData['zipfile'])) {
            $zipFile = $feedData['zipfile'];
        }
        
        // Optional route whitelist
        $routeFilter = array();
        if (isset($feedData['routes']) && is_array($feedData['routes'])) {
            $routeFilter = $feedData['routes'];
        }

        // Optional replacement fields
        // Live in arrays of type [FIELD]_replacement_keys and [FIELD]_replacement_vals
        // (e.g. agency_id_replacement_keys and agency_id_replacement_vals)
        $fieldRemaps = array();
        foreach ($feedData as $key => $replaceValues) {
            $pos = strpos($key, '_override_keys');
            if ($pos !== false && is_array($replaceValues)) {
                // is a remap keys config array
                $replaceField = substr($key, 0, $pos);
                $replacementsKey = "{$replaceField}_override_vals";
                if ($replaceField && isset($feedData[$replacementsKey])) {
                    // values config array is also set
                    $fieldRemap = array();
                    foreach ($replaceValues as $i => $replaceValue) {
                        if (isset($feedData[$replacementsKey][$i])) {
                            $fieldRemap[$replaceValue] = $feedData[$replacementsKey][$i];
                        }
                    }
                    
                    if ($fieldRemap) {
                        $fieldRemaps[$replaceField] = $fieldRemap;
                    }
                }
            }
        }

        // Optional regular expressions for replacement field
        // Live in strings of type [FIELD]_re_pattern and [FIELD]_re_replace
        // (e.g. agency_id_re_pattern and agency_id_re_replace)
        $fieldREs = array();
        foreach ($feedData as $patternKey => $pattern) {
            $pos = strpos($patternKey, '_re_pattern');
            if ($pos !== false && is_string($replaceValues)) {
                // is _re_pattern config string
                $replaceField = substr($patternKey, 0, $pos);
                $replaceKey = "{$replaceField}_re_replace";
                if ($replaceField && isset($feedData[$replaceKey])) {
                    // _re_replace config string is also set
                    $fieldREs[$replaceField] = array(
                        'pattern' => $pattern,
                        'replace' => $feedData[$replaceKey],
                    );
                }
            }
        }
        
        $this->config[$feedIndex] = array(
            'zip'        => $zipFile,
            'db'         => DATA_DIR."/gtfs/gtfs-$feedIndex.sqlite",
            'routes'     => $routeFilter,
            'fieldRemap' => $fieldRemaps,
            'fieldREs'   => $fieldREs,
        );
    }
    
    public function convert() {
        try {
            $tableMappings = $this->getTableMappings();
            
            foreach ($this->config as $databaseName => $agencyConfig) {
                $this->notice("Processing $databaseName...");
              
                $zip = new ZipArchive();
                $result = $zip->open($agencyConfig['zip']);
                if ($result !== true) {
                    throw new Exception("Failed to open {$agencyConfig['zip']} ($result)");
                }
              
                if (file_exists($agencyConfig['db'])) {
                    unlink($agencyConfig['db']); // remove old database
                }
              
                $db = new PDO('sqlite:'.$agencyConfig['db']);
                if (!$db) {
                    throw new Exception("Failed to open '{$agencyConfig['db']}': ".print_r($db->errorInfo(), true));
                }
                
                if ($db->exec('PRAGMA foreign_keys = ON') === FALSE) {
                    throw new Exception("Failed to turn on foreign key support: ".print_r($db->errorInfo(), true));
                }
                
                $filter = array();
                if (count($agencyConfig['routes'])) {
                    $filter['route_id'] = array_fill_keys($agencyConfig['routes'], 1);
                }
                
                if (!$db->beginTransaction()) {
                    throw new Exception("Failed to start transaction: ".print_r($db->errorInfo(), true));
                }
                
                foreach ($tableMappings as $tableName => $tableConfig) {
                    $this->notice("-- creating table $tableName");
                    
                    $fields = array();
                    foreach ($tableConfig['fields'] as $field => $type) {
                        $fields[] = "$field $type";
                    }
                    if ($tableConfig['constraint']) {
                        $fields[] = $tableConfig['constraint'];
                    }
                
                    if ($db->exec("CREATE TABLE $tableName (".implode(', ', $fields).")") === FALSE) {
                        $this->notice("-- f");
                        throw new Exception("Failed to create table '$tableName': ".print_r($db->errorInfo(), true));
                    }
                }
                
                foreach ($tableMappings as $tableName => $tableConfig) {
                    $this->notice("-- populating table $tableName");
                
                    $rows = $this->readCSVArray($zip, $tableConfig['file'], 
                      $agencyConfig['fieldRemap'], $agencyConfig['fieldREs'], 
                      $filter, $tableConfig['addToFilter']);
                    
                    $this->writeToDatabase($db, $tableName, $tableConfig, $rows);
                }
              
                if (!$db->commit()) {
                    throw new Exception("Failed to commit transaction: ".print_r($db->errorInfo(), true));
                }
            }
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->error = $e->getMessage();
            return false;
        }
        
        return true;
    }


    //
    // Helper functions
    //
    
    function readCSVArray($zip, $file, $fieldRemap, $fieldREs, &$filter=array(), $addToFilter=array()) {
        $rows = array();
        $fieldNames = array();
          
        $this->notice("    -- processing $file");
      
        $index = $zip->locateName($file, ZIPARCHIVE::FL_NODIR);
        if ($index === false) {
            $this->notice("       -- could not find $file in archive, skipping");
            return $rows; // non-fatal
        }
        
        $info = $zip->statIndex($index);
        if ($info === false) {
            throw new Exception("Could not stat entry $index in archive");
        }
      
        $fp = $zip->getStream($info['name']);
        if (!$fp) {
            throw new Exception("Failed to open $file");
        }
        
        $updatedFilter = $filter;
        
        $fieldNames = fgetcsv($fp);
        while ($fpArray = fgetcsv($fp)) {
            $row = array();
            
            foreach ($fieldNames as $index => $fieldName) {
                $value = isset($fpArray[$index]) ? $fpArray[$index] : '';
                
                // Run regular expression matches and then field remaps, if set
                if (isset($fieldREs[$fieldName])) {
                    $value = preg_replace($fieldREs[$fieldName]['pattern'], $fieldREs[$fieldName]['replace'], $value);
                    //error_log('replaced "'.(isset($fpArray[$index]) ? $fpArray[$index] : '').'" with "'.$value.'"');
                }
                
                if (isset($fieldRemap[$fieldName], $fpArray[$index], $fieldRemap[$fieldName][$fpArray[$index]])) {
                    $value = $fieldRemap[$fieldName][$fpArray[$index]];
                }
                
                $row[$fieldName] = $value;
            }
            
            $rowIsValid = true;
            foreach ($filter as $rowKey => $validValues) {
                // no valid values means grab all rows
                if (count($validValues) && isset($row[$rowKey]) && !isset($validValues[$row[$rowKey]])) {
                    $rowIsValid = false;
                }
            }
            
            if ($rowIsValid) {
                $rows[] = $row;
        
                foreach ($addToFilter as $addKey) {
                    if (isset($row[$addKey]) && $row[$addKey] !== '') {
                        if (!isset($updatedFilter[$addKey])) {
                            $updatedFilter[$addKey] = array();
                        }
                        $updatedFilter[$addKey][$row[$addKey]] = 1;
                    }
                }
            }
        }
        
        fclose($fp);
        $this->notice("    -- processed $file");
        
        $filter = $updatedFilter;
        return $rows;
    }
    
    function writeToDatabase($db, $tableName, $tableConfig, $rows) {
        $params = array_fill(0, count($tableConfig['fields']), '?');
        $sql = "INSERT INTO $tableName VALUES (".implode(',',$params).')';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement '$sql' ".print_r($db->errorInfo(), true));
        }
      
        foreach ($rows as $row) {
            $values = array();
            
            foreach ($tableConfig['fields'] as $field => $type) {
                // TODO allow specification of optional fields
                if (isset($row[$field])) {
                    $value = $row[$field];
                    if (strpos($type, 'TEXT') !== 0 && $value === '') {
                        $value = '0';
                    }
                
                } else {
                    $value = NULL;
                }
              
                $values[] = $value;
            }
            if (!$stmt->execute($values)) {
                $info = $stmt->errorInfo();
                if ($info[0] == '23000') {
                    $this->notice("        -- skipping row '".implode(', ', $values)."': ".$info[2]);
                } else {
                    throw new Exception("failed to insert row '".implode(', ', $values)."' ".$info[2]);
                }
            }
        }
        
        $this->notice("    -- added ".count($rows)." rows to $tableName");
    }
    
    function getTableMappings() {
        return array(
            'agency' => array(
                'file' => 'agency.txt',
                'fields' => array(
                    'agency_id'       => 'TEXT',
                    'agency_name'     => 'TEXT',
                    'agency_url'      => 'TEXT',
                    'agency_timezone' => 'TEXT',
                    'agency_lang'     => 'TEXT',
                    'agency_phone'    => 'TEXT',
                ),
                'constraint'  => '',
                'addToFilter' => array(),
            ),
            'routes' => array(
                'file' => 'routes.txt',
                'fields' => array(
                    'route_id'         => 'TEXT NOT NULL PRIMARY KEY',
                    'agency_id'        => 'TEXT',
                    'route_short_name' => 'TEXT',
                    'route_long_name'  => 'TEXT',
                    'route_desc'       => 'TEXT',
                    'route_type'       => 'TEXT',
                    'route_color'      => 'TEXT',
                ),
                'constraint'  => '',
                'addToFilter' => array('agency_id', 'route_id'),
            ),
            'trips' => array(
                'file' => 'trips.txt',
                'fields' => array(
                    'route_id'      => 'TEXT NOT NULL REFERENCES routes(route_id) DEFERRABLE INITIALLY DEFERRED',
                    'service_id'    => 'TEXT NOT NULL',
                    'trip_id'       => 'TEXT NOT NULL PRIMARY KEY',
                    'trip_headsign' => 'TEXT',
                    'direction_id'  => 'INTEGER',
                ),
                'constraint'  => '',
                'addToFilter' => array('trip_id', 'service_id'),
            ),
            'calendar' => array(
                'file' => 'calendar.txt',
                'fields' => array(
                    'service_id' => 'TEXT NOT NULL',
                    'monday'     => 'INTEGER',
                    'tuesday'    => 'INTEGER',
                    'wednesday'  => 'INTEGER',
                    'thursday'   => 'INTEGER',
                    'friday'     => 'INTEGER',
                    'saturday'   => 'INTEGER',
                    'sunday'     => 'INTEGER',
                    'start_date' => 'INTEGER NOT NULL',
                    'end_date'   => 'INTEGER NOT NULL',
                ),
                'constraint'  => '',
                'addToFilter' => array(),
            ),
            'calendar_dates' => array(
                'file' => 'calendar_dates.txt',
                'fields' => array(
                    'service_id'     => 'TEXT NOT NULL',
                    'date'           => 'INTEGER NOT NULL',
                    'exception_type' => 'INTEGER',
                ),
                'constraint'  => '',
                'addToFilter' => array(),
            ),
            'frequencies' => array(
                'file' => 'frequencies.txt',
                'fields' => array(
                    'trip_id'      => 'TEXT NOT NULL REFERENCES trips(trip_id) DEFERRABLE INITIALLY DEFERRED',
                    'start_time'   => 'TEXT NOT NULL',
                    'end_time'     => 'TEXT NOT NULL',
                    'headway_secs' => 'INTEGER',
                ),
                'constraint'  => '',
                'addToFilter' => array(),
            ),
            'stop_times' => array(
                'file' => 'stop_times.txt',
                'fields' => array(
                    'trip_id'        => 'TEXT NOT NULL REFERENCES trips(trip_id) DEFERRABLE INITIALLY DEFERRED',
                    'arrival_time'   => 'TEXT NOT NULL',
                    'departure_time' => 'TEXT NOT NULL',
                    'stop_id'        => 'TEXT NOT NULL REFERENCES stops(stop_id) DEFERRABLE INITIALLY DEFERRED',
                    'stop_sequence'  => 'INTEGER NOT NULL',
                    'pickup_type'    => 'INTEGER',
                    'drop_off_type'  => 'INTEGER',
                ),
                'constraint'  => 'UNIQUE (trip_id, stop_sequence)',
                'addToFilter' => array('stop_id'),
            ),
            'stops' => array(
                'file' => 'stops.txt',
                'fields' => array(
                    'stop_id'   => 'TEXT NOT NULL PRIMARY KEY',
                    'stop_code' => 'TEXT',
                    'stop_name' => 'TEXT',
                    'stop_desc' => 'TEXT',
                    'stop_lat'  => 'REAL',
                    'stop_lon'  => 'REAL',
                ),
                'constraint'  => 'UNIQUE (stop_id)',
                'addToFilter' => array(),
            ),
        );
    }
    
    protected function notice($message) {
        error_log($message);
        $this->messages .= "$message<br>";
    }
}
