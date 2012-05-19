<?php

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