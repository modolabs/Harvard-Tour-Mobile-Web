<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

interface TransitDataModelInterface {
    public function getStopInfo($stopID);
    
    public function getMapImageForStop($stopID, $width, $height);
    
    public function getMapImageForRoute($routeID, $width, $height);
    
    public function getRouteInfo($routeID, $time=null);
    public function getRoutePaths($routeID);
    public function getRouteVehicles($routeID);
    
    public function getServiceInfoForRoute($routeID);
    
    public function getRoutes($time=null);
}
