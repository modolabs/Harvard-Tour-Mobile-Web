<?php

class CoursesDemoAuthentication extends AuthenticationAuthority {
    protected $userClass = 'CoursesDemoUser';
    protected $server;
    protected $secure;

    public function init($args) {
        parent::init($args);
        if (!isset($args['HOST'])) {
            throw new KurogoConfigurationException("Moodle HOST must be set");
        }
        $this->server = $args['HOST'];
        $args = is_array($args) ? $args : array();
        if (isset($args['SECURE'])) {
            $this->secure = (bool) $args['SECURE'];
        }
    }

    public function auth($login, $password, &$user) {
        $url = sprintf("http%s://%s/auth?%s",
            $this->secure ? 's' : '',
            $this->server,
            http_build_query(
                array(
                    'username' => $login,
                    'password' => $password
                )
            )
        );

        $result = file_get_contents($url);
        if ($data = @json_decode($result, true)) {
            if ($data['response']) {
                $response = $data['response'];
                $user = new $this->userClass($this);
                $user->setID($response['user_id']);
                $user->setUserID($response['username']);
                $user->setFirstName($response['first_name']);
                $user->setLastName($response['last_name']);
                $user->setEmail($response['email']);
                $this->cacheUserArray($user->getUserID(), $response);
                return AUTH_OK;
            } else {
                return AUTH_FAILED;
            }
        } else {
            return AUTH_FAILED;
        }
    }

    public function getUser($login) {
       if (empty($login)) {
            return new AnonymousUser();       
        }
        
        $filename = $this->cacheFile($login) ;
        $user = false;
        if (file_exists($filename)) {
            if ($response = unserialize(file_get_contents($filename))) {
                $user = new $this->userClass($this);
                $user->setID($response['user_id']);
                $user->setUserID($response['username']);
                $user->setFirstName($response['first_name']);
                $user->setLastName($response['last_name']);
                $user->setEmail($response['email']);
            }
        }

        return $user; 
    }

    protected function cacheUserArray($login, array $array) {
        $umask = umask(0077);
        $return = file_put_contents($this->cacheFile($login), serialize($array));
        umask($umask);
        return $return;
    }

    protected function cacheFile($login) {
        $cacheDir = CACHE_DIR . '/CoursesDemoUser' ;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0700, true);
        }
        return $cacheDir . "/" . md5($login);
    }

    public function getGroup($group) {
        return false;
    }
    
    public function validate(&$error) {
        return true;
    }
}

class CoursesDemoUser extends User {
    protected $id;

    public function setID($id) {
        $this->id = $id;
    }

    public function getID() {
        return $this->id;
    }
}
