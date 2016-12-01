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

    function check() {
        if(IS_POST) {
            $taobao_username = $this->_make_sure_string('taobao_username', 50, '');
            $id = $this->_make_sure_string('id', 255, '');
            $nick = $this->_make_sure_string('nick', 255, '');
            $avatar_url = $this->_make_sure_string('avatar_url', 255, '');
            $authorization_code = $this->_make_sure_string('authorization_code', 255, '');
            if (empty($taobao_username) ||
                empty($id) ||
                empty($nick) ||
                empty($avatar_url) ||
                empty($authorization_code)) {
                $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
                return ;
            }
            $this->_check($taobao_username, $id, $nick, $avatar_url, $authorization_code);
        } else {
            $this->_ajax_error(400, NOT_POST_ACTION, 'not a post action');
        }
    }

    function _check($taobao_username, $id, $nick, $avatar_url, $authorization_code) {
        $auth_mod =& m('memberauth');
        $auth_info = $auth_mod->get(array(
            'conditions' => "vendor = 0 and vendor_user_nick = '{$taobao_username}'"));
        if (empty($auth_info)) {
            $this->_ajax_error(400, CHECK_TAOBAO_FAILED, '系统中不存在该淘宝用户，请前往www.51zwd.com使用一次淘登录');
            return ;
        }

        $result = $auth_mod->edit($auth_info['user_id'], array(
            'avatar_url' => $avatar_url,
            'confusing_nick' => $nick,
            'confusing_id' => $id,
            'authorization_code' => $authorization_code));
        if (!$result) {
            $this->_ajax_error(500, DB_ERROR, '更新登录信息失败');
        }

        $ms =& ms();
        $user_api = $ms->user;
        $mod_user =& m('member');
        $mod_access_token =& m('access_token');
        $username = '51t_'.$taobao_username;
        $this->_login($username, FIXED_PASSWORD, $user_api, $mod_user, $mod_access_token);
    }
}

?>