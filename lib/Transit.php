<?php

$config = ConfigFile::factory('transit', 'site');
Kurogo::siteConfig()->addConfig($config);

interface TransitDataModelInterface {
    public function getStopInfo($stopID);
    
    public function getMapImageForStop($stopID, $width, $height);
    
    public function getMapImageForRoute($routeID, $width, $height);
    
    public function getRouteInfo($routeID, $time);
    public function getRoutePaths($routeID);
    public function getRouteVehicles($routeID);
    
    public function getServiceInfoForRoute($routeID);
    
    public function getRoutes($time);
}
