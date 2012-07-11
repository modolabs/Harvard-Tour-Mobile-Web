<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class CourseRegistrationCourse extends Course {

    protected $enrolled;
    public function setEnrolled($enrolled) {
        $this->enrolled = $enrolled;
    }
    
    public function isEnrolled() {
        return $this->enrolled;
    }
    
    public function canDrop() {
        return $this->isEnrolled();
    }
    
}
