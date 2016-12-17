<?php

class Mobile_frontendApp extends FrontendApp {
    private $_request_result = null;

    function __construct() {}

    function _ajax_error($http_code, $user_code, $message) {
        http_response_code($http_code);
        echo ecm_json_encode(array(
            'error' => true,
            'code' => $user_code,
            'message' => $message));
    }

    function _post($func) {
        if (IS_POST) {
            $func();
        } else {
            $this->_ajax_error(400, NOT_POST_ACTION, '请求错误');
            return;
        }
    }

    function _make_sure_string($param, $length_limit, $default) {
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

    function _get_url($url) {
        return $this->_request_result;
    }

    function set_request_result($result) {
        $this->_request_result = $result;
    }
}

?>