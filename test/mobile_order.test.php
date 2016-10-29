<?php

require_once(ROOT_PATH.'/test/fake/config.inc.php');
require_once(ROOT_PATH.'/test/fake/frontend.base.php');
require_once(ROOT_PATH.'/test/fake/mobile_frontend.app.php');
require_once(ROOT_PATH.'/test/fake/address.model.php');
require_once(ROOT_PATH.'/test/fake/order.model.php');
require_once(ROOT_PATH.'/test/fake/behalf.model.php');
require_once(ROOT_PATH.'/test/fake/member.model.php');
require_once(ROOT_PATH.'/test/fake/goodsstatistics.model.php');
require_once(ROOT_PATH.'/test/fake/store.model.php');
require_once(ROOT_PATH.'/test/fake/goodsspec.model.php');

require_once(ROOT_PATH.'/app/mobile_order.app.php');

class Mobile_orderTest extends TestCase {

    private $mobile_order;

    function __construct() {
        $address_stub = $this->stub('AddressModel', 'get', array(
            'region_id' => '1',
            'region_name' => '中国 上海 松江',
            'consignee' => '小明',
            'address' => '乐都路98号302',
            'phone_tel' => '12345678',
            'phone_mob' => '12345678901',
            'zipcode' => '201600'));
        $order_stub = $this->stub('OrderModel', 'get', array(
            'order_amount' => '99.9'));
        $goodsstatistics_stub = $this->stub('GoodsstatisticsModel', 'edit', true);
        $store_stub = $this->stub('StoreModel', 'get', array(
            'store_name' => 'trival_store_name',
            'im_qq' => '54321'));
        $goodsspec_stub = $this->stub('GoodsspecModel', 'find', array(
            array('store_id' => 1,
                  'goods_id' => 1,
                  'goods_name' => 'goods1',
                  'spec_id' => 1,
                  'specification' => '绿色 L',
                  'price' => 24,
                  'num' => 1,
                  'default_image' => 'http://www.example.com/some.jpg'),
            array('store_id' => 2,
                  'goods_id' => 2,
                  'goods_name' => 'goods2',
                  'spec_id' => 2,
                  'specification' => '绿色 S',
                  'price' => 22,
                  'num' => 2,
                  'default_image' => 'http://www.example.com/other.jpg')));
        $this->mobile_order = new Mobile_orderApp($address_stub, $order_stub,
                                            $goodsstatistics_stub,
                                            $store_stub, $goodsspec_stub);
    }

    function test_validate_submit_order_params() {
        $this->assertTrue(
            $this->mobile_order->_validate_submit_order_params(
                array(1001, 1002), array(1, 1), 1, 1, 1));
        $this->assertTrue(
            $this->mobile_order->_validate_submit_order_params(
                array('1001', '1002'), array('1', '1'), '1', '1', '1'));
        $this->assertFalse(
            $this->mobile_order->_validate_submit_order_params(
                array(1001, 1002), array(1), 1, 1, 1));
        $this->assertFalse(
            $this->mobile_order->_validate_submit_order_params(
                array(123, 456, 'abc'), array(1, 2, 3), 1, 1, 1));
        $this->assertFalse(
            $this->mobile_order->_validate_submit_order_params(
                array(1001, 1002), array(1, 1), 'abc', 1, 1));
        $this->assertFalse(
            $this->mobile_order->_validate_submit_order_params(
                array(1001, 1002), array(1, 1), 1, 'abc', 1));
        $this->assertFalse(
            $this->mobile_order->_validate_submit_order_params(
                array(1001, 1002), array(1, 1), 1, 1, 'abc'));
    }

    function test_build_alipay_order_info() {
        import('time.lib');
        $order_info = $this->mobile_order->_build_alipay_order_info('12345', '0.01', '1', '2016-07-29 16:55:53');
        $parts = explode('&sign=', $order_info);
        $this->assertEquals('app_id=99999&biz_content=%7B%22timeout_express%22%3A%2224h%22%2C%22product_code%22%3A%22QUICK_MSECURITY_PAY%22%2C%22total_amount%22%3A%220.01%22%2C%22subject%22%3A%221%22%2C%22out_trade_no%22%3A%2212345%22%7D&charset=utf-8&method=alipay.trade.app.pay&notify_url=http%3A%2F%2Fapp.51zwd.com%2Fecmall51-app%2Fgateway.php&sign_type=RSA&timestamp=2016-07-29+16%3A55%3A53&version=1.0', $parts[0]);
        $this->assertEquals('RpHXkvxnSA%2Byv%2BUpV7yBW85C8BkjslKDfOg5gKqXAdf1DyexIs5GbsWQXVyCkXcXs9t6jQwUUXR4wZEM1BbzOzZknrm9v0SdqA0%2B0jh5RXniuyryeQc9BgeOQItS4TKv2ws1%2Bcbo4%2BUsSPmfu2TPKZ9hxjEnVK1fPuPLH21%2B0LA%3D', $parts[1]);
    }

}

?>