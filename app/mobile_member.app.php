<?php

class Mobile_memberApp extends Mobile_frontendApp {
    function __construct() {
    }

    function login() {
        if(IS_POST) {
            $ms =& ms();
            $user_api = $ms->user;
            $mod_user =& m('member');
            $mod_access_token =& m('access_token');
            // FIXME: ensure security
            $this->_login($_POST['username'], $_POST['password'], $user_api, $mod_user, $mod_access_token);
        } else {
            $this->_ajax_error(400, NOT_POST_ACTION, 'not a post action');
        }
    }

    function _login($username, $password, $user_api, $mod_user, $mod_access_token) {
        $user_id = $user_api->auth($username, $password);
        if (!$user_id) {
            $this->_ajax_error(400, LOGIN_FAILED, 'username or password not correct');
        } else {
            $user_info = $mod_user->get(array(
                'conditions'    => "member.user_id = '{$user_id}'",
                'join'          => 'has_access_token',                 //关联查找看看是否有店铺
                'fields'        => 'member.user_id, user_name, reg_time, '.
                                   'last_login, last_ip, add_time, access_token'));
            $access_token = $this->_generate_access_token();
            $data = array('user_id' => $user_id,
                          'access_token' => $access_token,
                          'add_time' => $user_info['add_time'],
                          'last_update' => time());
            if (empty($user_info['add_time'])) {
                $data['add_time'] = time();
            }
            $res = $mod_access_token->add($data, true);
            if ($res) {
                echo ecm_json_encode(array(
                    'user_id' => $user_id,
                    'user_name' => $user_info['user_name'],
                        'access_token' => $access_token));
            } else {
                $this->_ajax_error(500, ADD_ACCESS_TOKEN_FAILED, 'failed to add access token');
            }
        }
    }

    function _generate_access_token() {
        return md5(uniqid()).md5(uniqid());
    }
}

?>