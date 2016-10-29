<?php

require_once(ROOT_PATH.'/test/fake/config.inc.php');
require_once(ROOT_PATH.'/test/fake/frontend.base.php');
require_once(ROOT_PATH.'/test/fake/mobile_frontend.app.php');
require_once(ROOT_PATH.'/test/fake/order.model.php');

require_once(ROOT_PATH.'/app/alipay_notify.app.php');

class Alipay_notifyTest extends TestCase {
    private $alipay_notify;

    function __construct() {
        $order_stub = $this->stub('OrderModel',
                                  'get', array(
                                      'order_id' => '1',
                                      'order_amount' => '0.01',
                                      'status' => ORDER_PENDING),
                                  'edit', true);
        $this->alipay_notify = new Alipay_notifyApp($order_stub);
    }

    function test_accept_should_return_success() {
        import('time.lib');
        $result = ajax_method_return_json($this->alipay_notify, '_accept',
                                          '12345', '0.01', 'ep51@163.com',
                                          '99999', 'TRADE_SUCCESS',
                                          '2015-04-27 15:45:57');
        $this->assertEquals('success', $result);
    }
}

?>