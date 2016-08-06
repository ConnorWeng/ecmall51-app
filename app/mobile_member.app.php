<?php

class Mobile_memberApp extends FrontendApp {
    function __construct() {
        $this->Mobile_memberApp();
    }

    function Mobile_memberApp() {
        header("Content-type: application/json");
    }

    function login() {
        if(IS_POST) {
            $ms =& ms();
            $user_api = $ms->user;
            // FIXME: ensure security
            $this->_login($_POST['username'], $_POST['password'], $user_api);
        } else {
            $this->ajax_error(400, 510001, 'not a post action');
        }
    }

    function _login($username, $password, $user_api) {
        $user_id = $user_api->auth($username, $password);
        if (!$user_id) {
            $this->ajax_error(400, 510002, 'username or password not correct');
        } else {
            echo ecm_json_encode(array('user_id' => $user_id));
        }
    }

    function ajax_error($http_code, $user_code, $message) {
        http_response_code($http_code);
        echo ecm_json_encode(array(
            'error' => true,
            'code' => $user_code,
            'message' => $message));
    }

}

?>