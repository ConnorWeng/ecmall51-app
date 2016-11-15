<?php

define('IN_ECM', true);

require_once(ROOT_PATH.'/test/fake/config.inc.php');
require_once(ROOT_PATH.'/test/fake/frontend.base.php');
require_once(ROOT_PATH.'/test/fake/mobile_frontend.app.php');
require_once(ROOT_PATH.'/test/fake/order.model.php');
require_once(ROOT_PATH.'/test/fake/paylog.model.php');
require_once(ROOT_PATH.'/test/fake/my_money.model.php');
require_once(ROOT_PATH.'/test/fake/my_moneylog.model.php');
require_once(ROOT_PATH.'/test/fake/ecmall.php');
require_once(ROOT_PATH.'/includes/libraries/time.lib.php');

require_once(ROOT_PATH.'/app/alipay_notify.app.php');

class Log {
    const INFO = 'INFO';

    static function write() {

    }
}

class Alipay_notifyTest extends TestCase {
    private $alipay_notify;

    function __construct() {
        $order_stub = $this->stub('OrderModel',
                                  'get', array(
                                      'buyer_id' => '1',
                                      'buyer_name' => 'buyer_1',
                                      'seller_id' => '2',
                                      'seller_name' => 'seller_2',
                                      'order_id' => '1',
                                      'order_amount' => '0.01',
                                      'status' => ORDER_PENDING),
                                  'edit', true);
        $paylog_stub = $this->stub('PaylogModel',
                                   'get', array(),
                                   'add', true);
        $my_money_stub = $this->stub('My_moneyModel',
                                     'get', array(
                                         'money' => 10,
                                         'jifen' => 10,
                                         'money_dj' => 5,
                                         'user_name' => 'ep51'),
                                     'add', true,
                                     'edit', true);
        $my_moneylog_stub = $this->stub('My_moneylogModel',
                                        'get', array(),
                                        'add', true);
        $this->alipay_notify = new Alipay_notifyApp($order_stub, $paylog_stub,
                                                    $my_money_stub, $my_moneylog_stub);
    }

    function test_accept_should_return_success() {
        $result = ajax_method_return_json($this->alipay_notify, '_accept',
                                          '54321', '12345', '0.01', 'ep51@163.com',
                                          '99999', 'TRADE_SUCCESS',
                                          '2015-04-27 15:45:57');
        $this->assertEquals('success', $result);
    }
}

?>