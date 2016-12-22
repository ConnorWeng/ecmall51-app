<?php

class Mobile_frontendApp extends FrontendApp {
    function __construct() {
        parent::__construct();
        @header("Content-type: application/json");
    }

    function _init_visitor() {
        if (!empty($_REQUEST['access_token'])) {
            try {
                $this->visitor = env('visitor', new MobileVisitor($_REQUEST['access_token']));
            } catch (Exception $e) {
                $this->_ajax_error(400, ACCESS_TOKEN_ERROR, $e->getMessage());
                exit;
            }
        } else {
            $this->_ajax_error(400, NOT_LOGIN, '请先登录');
            exit;
        }
    }

    function _get_url($url) {
        return file_get_contents($url);
    }

    function _post_url($url, $data) {
        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)));
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    function _post($func) {
        if (IS_POST) {
            $func();
        } else {
            $this->_ajax_error(400, NOT_POST_ACTION, 'not a post action');
            return;
        }
    }

    function _ajax_error($http_code, $user_code, $message) {
        http_response_code($http_code);
        echo ecm_json_encode(array(
            'error' => true,
            'code' => $user_code,
            'message' => $message));
    }

    function _make_sure_numeric($param, $default) {
        $result = $this->_make_sure_numeric_impl($param, $default);
        if ($result === false) {
            exit;
        } else {
            return $result;
        }
    }

    function _make_sure_numeric_impl($param, $default) {
        if (isset($_REQUEST[$param])) {
            if (is_numeric($_REQUEST[$param]) && $_REQUEST[$param] >= 0) {
                return $_REQUEST[$param];
            } else {
                $this->_ajax_error(400, PARAMS_ERROR, 'parameters error');
                return false;
            }
        } else {
            return $default;
        }
    }

    function _make_sure_all_numeric($param, $default) {
        if (isset($_REQUEST[$param])) {
            $result = array();
            $parts = explode(',', $_REQUEST[$param]);
            foreach ($parts as $part) {
                if (is_numeric($part)) {
                    array_push($result, $part);
                } else {
                    $this->_ajax_error(400, PARAMS_ERROR, 'parameters error');
                    exit;
                }
            }
            return $result;
        } else {
            return $default;
        }
    }

    function _make_sure_string($param, $length_limit, $default) {
        if (defined('MOBILE_DEBUG')) {
            $param_info = print_r($_REQUEST[$param], true);
            Log::write('request string '.$param.': '.$param_info, Log::DEBUG);
        }
        if (isset($_REQUEST[$param])) {
            if (is_string($_REQUEST[$param]) && mb_strlen($_REQUEST[$param], 'utf-8') <= $length_limit) {
                return $_REQUEST[$param];
            } else {
                $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
                exit;
            }
        } else {
            return $default;
        }
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