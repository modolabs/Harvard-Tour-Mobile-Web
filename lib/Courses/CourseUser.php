<?php

class CourseUser implements KurogoObject {
	protected $id;
	protected $firstName;
	protected $lastName;
	protected $fullName;
	protected $email; 
    protected $attributes=array();
	
	public function filterItem($filters) {
		return $filters;
	}
	
	public function setUserID($id) {
	    $this->id = $id;
	}
	
	public function getUserID() {
	    return $this->id;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getFirstName() {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }
    
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }
	
    public function getFullName() {
        if (!empty($this->fullName)) {
            return $this->fullName;
        } elseif (!empty($this->firstName) || !empty($this->lastName)) {
            return trim(sprintf("%s %s", $this->firstName, $this->lastName));
        } else {
            return $this->getUserID();
        }
    }

	public function getEmail() {
		return $this->email;
	}
	
	public function setEmail($email) {
		$this->email = $email;
	}

    public function getAttributes() {
        return $this->attributes;
    }

    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
    }

    public function getAttribute($attrib) {
        return isset($this->attributes[$attrib]) ? $this->attributes[$attrib] : '';
    }
	
}
