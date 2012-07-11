<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class MoodleCourseUser extends CourseUser {
	protected $roles;
	protected $enrolledCourses;

	public function getRoles() {
		return $this->roles;
	}

	public function setRoles($roles) {
		$this->roles = $roles;
	}
	public function getEnrolledCourses() {
		return $this->enrolledCourses;
	}

	public function setEnrolledCourses($enrolledCourses) {
		$this->enrolledCourses = $enrolledCourses;
	}
}
