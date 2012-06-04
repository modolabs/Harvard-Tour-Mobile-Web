<?php

/**
  * GTFSDataRetriever
  * @package Transit
  */

class GTFSDataRetriever extends DatabaseDataRetriever
{
    public function init($args) {
        parent::init($args);
        
        $this->setCacheGroup('GTFS');
    }
}
