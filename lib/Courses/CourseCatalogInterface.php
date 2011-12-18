<?php

interface CourseCatalogInterface extends CourseDataInterface {
    public function getCatalogSections($options);
}