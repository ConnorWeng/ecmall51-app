<?php

$_SERVER['REQUEST_METHOD'] = 'POST';

require_once(ROOT_PATH.'/eccore/ecmall.php');

class EcmallTest extends TestCase {

    function test_import_alipay_sdk() {
        import('alipay-sdk/AopSdk');
        $this->assertNotNull(new AopClient());
    }

}

?>