<?php

$_SERVER['REQUEST_METHOD'] = 'GET';

require_once(ROOT_PATH.'/eccore/ecmall.php');
require_once(ROOT_PATH.'/test/fake/ecmall.php');
require_once(ROOT_PATH.'/test/fake/ecapp.base.php');
require_once(ROOT_PATH.'/test/fake/frontend.base.php');

require_once(ROOT_PATH.'/app/mobile_frontend.app.php');

class Mobile_frontendTest extends TestCase {
    private $mobile_frontend;

    function __construct() {
        $this->mobile_frontend = new Mobile_frontendApp();
    }

    function test_make_sure_numeric_given_negative_should_return_error_code() {
        $_REQUEST['negative'] = -1;
        $json = ajax_method_return_json($this->mobile_frontend, '_make_sure_numeric_impl', 'negative', 0);
        $error = json_decode($json);
        $this->assertEquals(PARAMS_ERROR, $error->code);
        unset($_REQUEST['negative']);
    }

    function test_make_sure_numeric_given_string_should_return_error_code() {
        $_REQUEST['string'] = 'string';
        $json = ajax_method_return_json($this->mobile_frontend, '_make_sure_numeric_impl', 'string', 0);
        $error = json_decode($json);
        $this->assertEquals(PARAMS_ERROR, $error->code);
        unset($_REQUEST['string']);
    }

    function test_make_sure_numeric_should_return_default_value() {
        $value = $this->mobile_frontend->_make_sure_numeric_impl('nonexists', 100);
        $this->assertEquals(100, $value);
    }

    function test_make_sure_numeric_should_return_exact_value() {
        $_REQUEST['intval'] = 10;
        $value = $this->mobile_frontend->_make_sure_numeric_impl('intval', 0);
        $this->assertEquals(10, $value);
        unset($_REQUEST['intval']);
    }
}

?>