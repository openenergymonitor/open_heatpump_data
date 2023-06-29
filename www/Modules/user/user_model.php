<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class User
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function emon_session_start()
    {
        $cookie_params = session_get_cookie_params();

        //name of cookie 
        session_name('HPMON_ORG_SESSID'); 
        //get subdir installation 
        $cookie_params['path'] = dirname($_SERVER['SCRIPT_NAME']);
        // Add a slash if the last character isn't already a slash
        if (substr($cookie_params['path'], -1) !== '/')
            $cookie_params['path'] .= '/';
        //not pass cookie to javascript 
        $cookie_params['httponly'] = true;
        $cookie_params['samesite'] = 'Strict';
        
        if (is_https()) {
            $cookie_params['secure'] = true;
        }
        
        if (PHP_VERSION_ID>=70300) {
            session_set_cookie_params($cookie_params);
        } else {
            session_set_cookie_params(
                $cookie_params['lifetime'],
                $cookie_params['path'],
                $cookie_params['domain'],
                $cookie_params['secure'],
                $cookie_params['httponly']
            );
        }
        
        session_start();
        
        $session = array();
        
        if (isset($_SESSION['admin'])) $session['admin'] = $_SESSION['admin']; else $session['admin'] = 0;
        if (isset($_SESSION['userid'])) $session['userid'] = $_SESSION['userid']; else $session['userid'] = 0;
        if (isset($_SESSION['username'])) $session['username'] = $_SESSION['username']; else $session['username'] = '';
        if (isset($_SESSION['email'])) $session['email'] = $_SESSION['email']; else $session['email'] = '';
        
        return $session;
    }

    public function login($username, $password, $with_emoncmsorg=false)
    {
        if (!$username || !$password) return array('success'=>false, 'message'=>_("Username or password empty"));

        if ($with_emoncmsorg) {
            return $this->login_using_emoncmsorg($username, $password);
        } else {
            return $this->login_local($username, $password);
        }
    }
    
    public function login_using_emoncmsorg($username, $password)
    {
        // Login using emoncms.org/user/auth.json
        if (!$result = http_request("POST", "https://emoncms.org/user/auth.json", array("username"=>$username,"password"=>$password))) {
            return array('success'=>false, 'message'=>_("Login error"));  
        }
        $result = json_decode($result);
        if (!isset($result->success) || $result->success!==true) return $result;

        $emoncmsorg_userid = (int) $result->userid;
        $emoncmsorg_apikey_read = $result->apikey_read;
        $emoncmsorg_apikey_write = $result->apikey_write;

        // Fetch email using emoncms.org/user/get.json
        $user_get = json_decode(file_get_contents("https://emoncms.org/user/get.json?apikey=".$emoncmsorg_apikey_write));
        $email = $user_get->email;

        // Check if emoncmsorg link exists
        $result = $this->mysqli->query("SELECT userid FROM emoncmsorg_link WHERE emoncmsorg_userid='$emoncmsorg_userid'");
        if ($result->num_rows==0) {
            $email = "";
            
            // Create new user fetch userid
            $stmt = $this->mysqli->prepare("INSERT INTO users (username, email) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $userid = (int) $stmt->insert_id;
            $stmt->close();

            // Create emoncmsorg link using prepared statement
            $stmt = $this->mysqli->prepare("INSERT INTO emoncmsorg_link (userid, emoncmsorg_userid, emoncmsorg_apikey_read, emoncmsorg_apikey_write) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $userid, $emoncmsorg_userid, $emoncmsorg_apikey_read, $emoncmsorg_apikey_write);
            $stmt->execute();
            $stmt->close();

        } else {
            $row = $result->fetch_object();
            $userid = (int) $row->userid;
        }

        // Check that the userid exists
        $result = $this->mysqli->query("SELECT * FROM users WHERE id='$userid'");
        if ($result->num_rows==0) {
            return array('success'=>false, 'message'=>_("User does not exist in users table"));
        }

        // Update fields in users table
        $stmt = $this->mysqli->prepare("UPDATE users SET username=?, email=? WHERE id=?");
        $stmt->bind_param("ssi", $username, $email, $userid);
        $stmt->execute();
        $stmt->close();

        // Update emoncmsorg link
        $stmt = $this->mysqli->prepare("UPDATE emoncmsorg_link SET emoncmsorg_apikey_read=?, emoncmsorg_apikey_write=? WHERE userid=?");
        $stmt->bind_param("ssi", $emoncmsorg_apikey_read, $emoncmsorg_apikey_write, $userid);
        $stmt->execute();

        session_regenerate_id();
        $_SESSION['userid'] = $userid;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;

        return array('success'=>true, 'message'=>_("Login successful"));
    }
    
    public function login_local($username, $password)
    {
        return array('success'=>false, 'message'=>_("Local login not implemented"));
    }
    
    public function logout()
    {
        session_unset();
        session_destroy();
    }

    public function get($userid) {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT id,username,email FROM users WHERE id='$userid'");
        if ($result->num_rows==0) {
            return false;
        } else {
            $row = $result->fetch_object();

            // check if emoncmsorg link exists
            $result = $this->mysqli->query("SELECT emoncmsorg_userid FROM emoncmsorg_link WHERE userid='$userid'");
            if ($result->num_rows>0) {
                $row->emoncmsorg_link = true;
            } else {
                $row->emoncmsorg_link = false;
            }

            return $row;
        }
    }
}