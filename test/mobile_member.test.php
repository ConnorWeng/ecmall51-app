<?php

require_once(ROOT_PATH.'/test/fake/ecmall.php');
require_once(ROOT_PATH.'/test/fake/frontend.base.php');
require_once(ROOT_PATH.'/test/fake/mobile_frontend.app.php');
require_once(ROOT_PATH.'/test/fake/uc.passport.php');
require_once(ROOT_PATH.'/test/fake/member.model.php');
require_once(ROOT_PATH.'/test/fake/access_token.model.php');

require_once(ROOT_PATH.'/app/mobile_member.app.php');

class Mobile_memberTest extends TestCase {

    private $mobile_member;

    function __construct() {
        $this->mobile_member = new Mobile_memberApp();
    }

    function test_ajax_error_should_return_error_code_and_message() {
        $json = ajax_method_return_json($this->mobile_member, '_ajax_error', 400, 510001, 'error!');
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

        $json = ajax_method_return_json($this->mobile_member, '_login',
                                     'fakeuser', 'fakepassword', $user_stub,
                                     $member_stub, $access_token_stub);
        $user_info = json_decode($json);

        $this->assertEquals(1, $user_info->user_id);
        $this->assertEquals('fakeuser', $user_info->user_name);
        $this->assertEquals(64, strlen($user_info->access_token));
    }

    function test_login_should_return_error_code() {
        $user_stub = $this->stub('UcPassportUser', 'auth', false);

        $json = ajax_method_return_json($this->mobile_member, '_login',
                                     'fakeuser', 'fakepassword', $user_stub,
                                     new MemberModel(), new Access_tokenModel());

        $this->assertEquals(510002, json_decode($json)->code);

        $user_stub = $this->stub('UcPassportUser', 'auth', 1);
        $member_stub = $this->stub('MemberModel', 'get',
                                   array('user_name' => 'fakeuser',
                                         'add_time' => 1470580460));
        $access_token_stub = $this->stub('Access_tokenModel', 'add', false);

        $json = ajax_method_return_json($this->mobile_member, '_login', 'fakeuser',
                                     'fakepassword', $user_stub, $member_stub,
                                     $access_token_stub);

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

}

?>