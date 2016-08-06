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
            $mod_user =& m('member');
            // FIXME: ensure security
            $this->_login($_POST['username'], $_POST['password'], $user_api, $mod_user);
        } else {
            $this->ajax_error(400, 510001, 'not a post action');
        }
    }

    function _login($username, $password, $user_api, $mod_user) {
        $user_id = $user_api->auth($username, $password);
        if (!$user_id) {
            $this->ajax_error(400, 510002, 'username or password not correct');
        } else {
            $user_info = $mod_user->get(array(
                'conditions'    => "user_id = '{$user_id}'",
                'join'          => 'has_store',                 //关联查找看看是否有店铺
                'fields'        => 'user_id, user_name, reg_time, last_login, last_ip, store_id, behalf_goods_taker as taker_id'));
            echo ecm_json_encode(array('user_id' => $user_id, 'user_name' => $user_info['user_name']));
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