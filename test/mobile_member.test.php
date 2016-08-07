<?php

define('ROOT_PATH', dirname(__FILE__).'/..');

require(ROOT_PATH.'/test/fake/ecmall.php');
require(ROOT_PATH.'/test/fake/frontend.base.php');
require(ROOT_PATH.'/test/fake/uc.passport.php');
require(ROOT_PATH.'/test/fake/member.model.php');
require(ROOT_PATH.'/test/fake/access_token.model.php');

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
        $user_stub = $this->stub('UcPassportUser', 'auth', 1);
        $member_stub = $this->stub('MemberModel', 'get',
                                   array('user_name' => 'fakeuser',
                                         'add_time' => 1470580460));
        $access_token_stub = $this->stub('Access_tokenModel', 'add', true);

        $json = ajaxFunctionReturnJson(
            function ($mobile_member, ...$params) {
                $mobile_member->_login('fakeuser', 'fakepassword', ...$params);
            }, $this->mobile_member, $user_stub, $member_stub, $access_token_stub);
        $user_info = json_decode($json);

        $this->assertEquals(1, $user_info->user_id);
        $this->assertEquals('fakeuser', $user_info->user_name);
        $this->assertEquals(64, strlen($user_info->access_token));
    }

    function test_login_should_return_error_code() {
        $user_stub = $this->stub('UcPassportUser', 'auth', false);

        $json = ajaxFunctionReturnJson(
            function ($mobile_member, ...$params) {
                $mobile_member->_login('fakeuser', 'fakepassword', ...$params);
            }, $this->mobile_member, $user_stub, new MemberModel(), new Access_tokenModel());

        $this->assertEquals(510002, json_decode($json)->code);

        $user_stub = $this->stub('UcPassportUser', 'auth', 1);
        $member_stub = $this->stub('MemberModel', 'get',
                                   array('user_name' => 'fakeuser',
                                         'add_time' => 1470580460));
        $access_token_stub = $this->stub('Access_tokenModel', 'add', false);

        $json = ajaxFunctionReturnJson(
            function ($mobile_member, ...$params) {
                $mobile_member->_login('fakeuser', 'fakepassword', ...$params);
            }, $this->mobile_member, $user_stub, $member_stub, $access_token_stub);

        $this->assertEquals(510003, json_decode($json)->code);
    }

    function test_generate_access_token() {
        $token = $this->mobile_member->_generate_access_token();

        $this->assertEquals(64, strlen($token));

        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $this->mobile_member->_generate_access_token();
        }
        $end = microtime(true);
        $elapsed_time = $end - $start;

        $this->assertTrue($elapsed_time < 1);
    }

    function stub($class_name, ...$configs) {
        $stub = $this->getMockBuilder($class_name)->getMock();
        for ($i = 0; $i < count($configs); $i += 2) {
            $stub->method($configs[$i])->willReturn($configs[$i+1]);
        }
        return $stub;
    }
}

?>