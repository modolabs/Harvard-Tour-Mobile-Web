<?php

interface Downloadable {
    // This function should return an array of
    // attachements, or it can return a reference
    // to itself ($this), the DownloadCourseContent
    // object, as the only element in an array.
    public function getFiles();
}