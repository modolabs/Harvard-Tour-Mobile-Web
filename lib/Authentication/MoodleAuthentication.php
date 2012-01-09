<?php

class MoodleAuthentication extends AuthenticationAuthority
{
    protected $userClass='MoodleUser';
    protected $server;
    protected $secure = true;
    protected $service;

    protected function tokenSessionVar() {
        return sprintf("%s_token", $this->index);
    }

    protected function cacheUserArray($login, array $array) {
        $umask = umask(0077);
        $return = file_put_contents($this->cacheFile($login), serialize($array));
        umask($umask);
        return $return;
    }

    protected function cacheFile($login) {
        $cacheDir = CACHE_DIR . '/MoodleUser' ;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0700, true);
        }
        return $cacheDir . "/" . md5($login);
    }
    
    public function auth($login, $password, &$user) {
        $url = sprintf("http%s://%s/login/token.php?%s", 
            $this->secure ? 's' : '',
            $this->server,
            http_build_query(array(
            'username'=>$login,
            'password'=>$password,
            'service'=>$this->service
            )));
            
        $result = file_get_contents($url);
        if ($data = @json_decode($result, true)) {
            if (isset($data['token'])) {
                $data['username'] = $login;
                $user = new $this->userClass($this);
                $user->setToken($data['token']);
                if ($info = $user->callWebService('moodle_webservice_get_siteinfo')) {
                    $data = array_merge($data, $info);
                }
            
                if ($user->setVars($data)) {
                    $this->cacheUserArray($user->getUserID(), $data);
                    return AUTH_OK;
                }
            }
        }

        return AUTH_FAILED;
    }

    protected function getUserFromArray(array $array) {
        $user = new $this->userClass($this);
        if ($user->setVars($array)) {
            return $user;
        }
        
        return false;
    }
    
    public function getUser($login) {

        if (empty($login)) {
            return new AnonymousUser();       
        }
        
        $filename = $this->cacheFile($login) ;
        $user = false;
        if (file_exists($filename)) {
            if ($array = unserialize(file_get_contents($filename))) {
                $user = $this->getUserFromArray($array);
            }
        }

        return $user;
    }
    
    public function getGroup($group) {
        return false;
    }
    
    public function validate(&$error) {
        return true;
    }
    
    public function init($args)
    {
        parent::init($args);
        if (!isset($args['HOST'])) {
            throw new KurogoConfigurationException("Moodle HOST must be set");
        }
        $this->server = $args['HOST'];

        if (!isset($args['SERVICE'])) {
            throw new KurogoConfigurationException("Moodle SERVICE must be set");
        }
        $this->service = $args['SERVICE'];

        if (isset($args['SECURE'])) {
            $this->secure = (bool) $args['SECURE'];
        }
    }
    
    public function getServerURL() {
        $url = sprintf("http%s://%s/webservice/rest/server.php",
            $this->secure ? 's' : '',
            $this->server
        );
        return $url;
    }        
}

class MoodleUser extends User
{
    protected $token;
    
    public function setVars($array) {
        if (!isset($array['username'], $array['userid'], $array['token'])) {
            return false;
        }
        $this->setUserID($array['userid']);
        $this->setToken($array['token']);
        $this->setFirstName($array['firstname']);
        $this->setLastName($array['lastname']);
        
        return true;
    }
    
    public function setToken($token) {
        $this->token = $token;
    }
    
    protected function getServerURL() {
        return $this->getAuthenticationAuthority()->getServerURL() ;
    }
    
    public function callWebService($function, $content='') {
        $context = stream_context_create(array('http'=>array(
            'method'=>'POST',
            'content'=>$content
        )));
        
        $url = $this->getServerURL() . '?' . http_build_query(array(
            'wstoken'=>$this->token,
            'wsfunction'=>$function,
            'moodlewsrestformat'=>'json'
        ));
        
        if ($result = file_get_contents($url, false, $context)) {
            $data = json_decode($result, true);
            return $data;
        }
    }

    
    
    public function getToken() {
        return $this->token;
    }
}