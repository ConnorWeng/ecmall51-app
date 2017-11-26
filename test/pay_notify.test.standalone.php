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

require_once(ROOT_PATH.'/app/pay_notify.app.php');

class Log {
    const INFO = 'INFO';

    static function write() {

    }
}

class Pay_notifyTest extends TestCase {
    private $pay_notify;

    private $paylog_stub;
    private $my_money_stub;
    private $my_moneylog_stub;

    /**
     * @before
     */
    function setup() {
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
        $this->paylog_stub = $this->stub('PaylogModel',
                                   'get_info', array(),
                                   'add', true);
        $this->my_money_stub = $this->stub('My_moneyModel',
                                     'get', array(
                                         'money' => 10,
                                         'jifen' => 10,
                                         'money_dj' => 5,
                                         'user_name' => 'testuser'),
                                     'add', true,
                                     'edit', true);
        $this->my_moneylog_stub = $this->stub('My_moneylogModel',
                                        'get', array(),
                                        'add', true);
        $this->pay_notify = new Pay_notifyApp($order_stub, $this->paylog_stub,
                                                    $this->my_money_stub, $this->my_moneylog_stub);
    }

    function test_accept_should_return_success() {
        $result = ajax_method_return_json($this->pay_notify, '_accept',
                                          '54321', '12345', '0.01',
                                          '99999', 'TRADE_SUCCESS',
                                          '2015-04-27 15:45:57', '支付宝');
        $this->assertEquals('success', $result);
    }

    function test_top_up_should_return_true() {
        $this->paylog_stub->expects($this->once())->method('add')->with(
            $this->equalTo(array(
                'out_trade_no' => '99999',
                'total_fee' => '19.9',
                'createtime' => '1479577913',
                'endtime' => '1479577913',
                'trade_status' => 1,
                'type' => 0,
                'customer_id' => '12345',
                'customer_name' => 'testuser')));
        $this->my_money_stub->
                expects($this->exactly(2))->
                method('edit')->withConsecutive(
                    [$this->equalTo('user_id=12345'), array('money' => '29.9')],
                    [$this->equalTo('user_id=12345'), array('jifen' => '29.9')]);
        $this->my_moneylog_stub->
                expects($this->once())->
                method('add')->with(
                    $this->equalTo(array(
                        'user_id' => '12345',
                        'user_name' => 'testuser',
                        'buyer_name' => '支付宝',
                        'seller_id' => '12345',
                        'seller_name' => 'testuser',
                        'order_sn ' => '99999',
                        'add_time' => gmtime() - 8*60*60,
                        'admin_time' => gmtime() - 8*60*60,
                        'leixing' => 30,
                        'money_zs' => '19.9',
                        'money' => '19.9',
                        'log_text' => '支付宝手机端充值',
                        'caozuo' => 4,
                        's_and_z' => 1,
                        'moneyleft' => '34.9')));
        $this->assertTrue($this->pay_notify->_top_up('12345', 'testuser', '99999', '19.9', '1479577913', '支付宝'));
    }

    function test_payment() {
        $this->my_money_stub->
                expects($this->exactly(2))->
                method('edit')->
                withConsecutive(
                    ['user_id=12345', 'money = money -19.9'],
                    ['user_id=54321', 'money_dj = money_dj +19.9']);
        $this->pay_notify->_payment('12345', '54321', '19.9', '88888', '99999', '支付宝');
    }
}

?>