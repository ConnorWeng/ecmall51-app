<?php

require_once(ROOT_PATH.'/test/fake/frontend.base.php');
require_once(ROOT_PATH.'/test/fake/mobile_frontend.app.php');
require_once(ROOT_PATH.'/test/fake/memberauth.model.php');

require_once(ROOT_PATH.'/app/mobile_taobao.app.php');

class Mobile_taobaoTest extends TestCase {

    private $mobile_taobao;

    function __construct() {
        $auth_stub = $this->stub('MemberauthModel', 'get', array('access_token' => '123456'));
        $this->mobile_taobao = new Mobile_taobaoApp($auth_stub, '123');
    }

    function test_upload_pictures_params_error() {
        $result = json_decode(ajax_method_return_json($this->mobile_taobao, 'upload_pictures'));
        $this->assertEquals(PARAMS_ERROR, $result->code);
    }

    function test_upload_pictures_return_new_urls() {
        $_REQUEST['img_urls'] = 'http://old.com/old.jpg,http://old.com/old.jpg';
        $_REQUEST['pcid'] = '12345';
        $this->mobile_taobao->set_request_result('{"newImgUrl": "http://new.com/new.jpg", "oldImgUrl": "http://old.com/old.jpg"}');

        $result = json_decode(ajax_method_return_json($this->mobile_taobao, 'upload_pictures'));
        $this->assertEquals(2, count($result));
        $this->assertEquals('http://new.com/new.jpg', $result[0]->newImgUrl);
        $this->assertEquals('http://new.com/new.jpg', $result[1]->newImgUrl);

        $this->mobile_taobao->set_request_result(null);
        unset($_REQUEST['pcid']);
        unset($_REQUEST['img_urls']);
    }

    function test_upload_pictures_taobao_api_error() {
        $_REQUEST['img_urls'] = 'http://old.com/old.jpg,http://old.com/old.jpg';
        $_REQUEST['pcid'] = '12345';
        $this->mobile_taobao->set_request_result('{"error": true}');

        $result = json_decode(ajax_method_return_json($this->mobile_taobao, 'upload_pictures'));
        $this->assertEquals(TAOBAO_API_ERROR, $result->code);

        $this->mobile_taobao->set_request_result(null);
        unset($_REQUEST['pcid']);
        unset($_REQUEST['img_urls']);
    }

}