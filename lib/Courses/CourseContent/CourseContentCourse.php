<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class CourseContentCourse extends Course {
    abstract public function getLastUpdate();
    abstract public function getUpdates($options);
    abstract public function getTasks($options);
    abstract public function getAnnouncements($options);
    abstract public function getResources($options);
    abstract public function getGrades($options);
    abstract public function getInstructors();
    abstract public function getStudents();
    abstract public function getContentById($id, $options);
    abstract public function getTaskById($id, $options);
    abstract public function getFileForContent($id, $options);
    abstract public function isInstructor(CourseUser $user);
    abstract public function isStudent(CourseUser $user);
}
