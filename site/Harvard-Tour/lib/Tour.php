<?php

require_once('TourData.php');

class Tour {
  private $tourData = array();
  private $currentStop = null;

  function __construct() {
    $this->tourData = _getTourStops();
    //error_log(print_r(array_keys($this->tourData), true));
  }
  
}
