<?php

includePackage('Calendar');
class LocationsDataModel extends CalendarDataModel {
    protected $subtitle;
    protected $mapLocation;

    protected function init($args) {
        parent::init($args);
        $this->setSubtitle($args);
        $this->setMapLocation($args);
    }

    public function setSubtitle($args) {
        if(isset($args['SUBTITLE']) && strlen($args['SUBTITLE']) > 0) {
            $this->subtitle = $args['SUBTITLE'];
        }
    }

    public function getSubtitle() {
        return $this->subtitle;
    }

    public function setMapLocation($args) {
        if(isset($args['MAP_LOCATION']) && strlen($args['MAP_LOCATION']) > 0) {
            $this->mapLocation = $args['MAP_LOCATION'];
        }
    }

    public function getMapLocation() {
        return $this->mapLocation;
    }
}
