<?php

define('ROOT_PATH', dirname(__FILE__).'/..');

require(ROOT_PATH.'/test/fake/ecmall.php');
require(ROOT_PATH.'/test/fake/frontend.base.php');
require(ROOT_PATH.'/test/fake/uc.passport.php');
require(ROOT_PATH.'/test/fake/member.model.php');

require(ROOT_PATH.'/app/mobile_member.app.php');

function ajaxFunctionReturnJson($function, ...$params) {
    ob_start();
    $function(...$params);
    $json = ob_get_contents();
    ob_end_clean();
    return $json;
}

class Mobile_memberTest extends PHPUnit_Framework_TestCase {

    private $mobile_member;

    function __construct() {
        $this->mobile_member = new Mobile_memberApp();
    }

    function test_ajax_error_should_return_error_code_and_message() {
        $json = ajaxFunctionReturnJson(
            function ($mobile_member) {
                $mobile_member->_ajax_error(400, 510001, 'error!');
            }, $this->mobile_member);
        $error = json_decode($json);
        $this->assertEquals(true, $error->error);
        $this->assertEquals(510001, $error->code);
        $this->assertEquals('error!', $error->message);
    }

    function test_login_should_return_user_info() {
        $user_stub = $this->getMockBuilder('UcPassportUser')->getMock();
        $user_stub->method('auth')->willReturn(1);
        $member_stub = $this->getMockBuilder('MemberModel')->getMock();
        $member_stub->method('get')->willReturn(array('user_name' => 'fakeuser'));
        $json = ajaxFunctionReturnJson(
            function ($mobile_member, ...$params) {
                $mobile_member->_login('fakeuser', 'fakepassword', ...$params);
            }, $this->mobile_member, $user_stub, $member_stub);
        $user_info = json_decode($json);
        $this->assertEquals(1, $user_info->user_id);
        $this->assertEquals('fakeuser', $user_info->user_name);
    }

    function test_login_should_return_error_code() {
        $user_stub = $this->getMockBuilder('UcPassportUser')->getMock();
        $user_stub->method('auth')->willReturn(false);
        $json = ajaxFunctionReturnJson(
            function ($mobile_member, ...$params) {
                $mobile_member->_login('fakeuser', 'fakepassword', ...$params);
            }, $this->mobile_member, $user_stub, new MemberModel());
        $this->assertEquals(510002, json_decode($json)->code);
    }
}

?>