<?php

define('ROOT_PATH', dirname(__FILE__).'/..');

require(ROOT_PATH.'/test/fake/ecmall.php');
require(ROOT_PATH.'/test/fake/frontend.base.php');
require(ROOT_PATH.'/test/fake/uc.passport.php');

require(ROOT_PATH.'/app/mobile_member.app.php');

function ajaxFunctionReturnJson($function, $param1 = null, $param2 = null) {
    ob_start();
    $function($param1, $param2);
    $json = ob_get_contents();
    ob_end_clean();
    return $json;
}

class Mobile_memberTest extends PHPUnit_Framework_TestCase {

    private $mobileMemberApp;

    function __construct() {
        $this->mobileMemberApp = new Mobile_memberApp();
    }

    function test_ajax_error_should_return_error_code_and_message() {
        $json = ajaxFunctionReturnJson(
            function ($mobileMemberApp) {
                $mobileMemberApp->ajax_error(400, 510001, 'error!');
            }, $this->mobileMemberApp);
        $error = json_decode($json);
        $this->assertEquals(true, $error->error);
        $this->assertEquals(510001, $error->code);
        $this->assertEquals('error!', $error->message);
    }

    function test_login_should_return_user_id() {
        $user_stub = $this->getMockBuilder('UcPassportUser')->getMock();
        $user_stub->method('auth')->willReturn(1);
        $json = ajaxFunctionReturnJson(
            function ($mobileMemberApp, $user_stub) {
                $mobileMemberApp->_login('fakeuser', 'fakepassword', $user_stub);
            }, $this->mobileMemberApp, $user_stub);
        $this->assertEquals(1, json_decode($json)->user_id);
    }

    function test_login_should_return_error_code() {
        $user_stub = $this->getMockBuilder('UcPassportUser')->getMock();
        $user_stub->method('auth')->willReturn(false);
        $json = ajaxFunctionReturnJson(
            function ($mobileMemberApp, $user_stub) {
                $mobileMemberApp->_login('fakeuser', 'fakepassword', $user_stub);
            }, $this->mobileMemberApp, $user_stub);
        $this->assertEquals(510002, json_decode($json)->code);
    }
}

?>