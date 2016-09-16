<?php

class Mobile_frontendApp extends FrontendApp {
    function _init_visitor() {
        if (!empty($_REQUEST['access_token'])) {
            try {
                $this->visitor = env('visitor', new MobileVisitor($_REQUEST['access_token']));
            } catch (Exception $e) {
                $this->_ajax_error(400, ACCESS_TOKEN_ERROR, $e->getMessage());
                exit;
            }
        } else {
            $this->_ajax_error(400, NOT_LOGIN, 'please login first');
            exit;
        }
    }

    function _ajax_error($http_code, $user_code, $message) {
        http_response_code($http_code);
        echo ecm_json_encode(array(
            'error' => true,
            'code' => $user_code,
            'message' => $message));
    }
}

class MobileVisitor {
    var $_access_token;
    var $_user_info;

    function __construct($access_token) {
        $this->_access_token = db()->escape_string($access_token);
        $access_token_mod =& m('access_token');
        $this->_user_info = $access_token_mod->get(array(
            'conditions' => "access_token='{$this->_access_token}'",
            'join' => 'belongs_to_member'));
        if (empty($this->_user_info['user_id'])) {
            throw new RuntimeException('access token invalid');
        }
    }

    function get($key) {
        return $this->_user_info[$key];
    }
}

?>