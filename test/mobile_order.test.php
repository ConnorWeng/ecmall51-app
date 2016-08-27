<?php

require_once(ROOT_PATH.'/test/fake/frontend.base.php');
require_once(ROOT_PATH.'/test/fake/address.model.php');
require_once(ROOT_PATH.'/test/fake/order.model.php');
require_once(ROOT_PATH.'/test/fake/behalf.model.php');
require_once(ROOT_PATH.'/test/fake/member.model.php');
require_once(ROOT_PATH.'/test/fake/goodsstatistics.model.php');
require_once(ROOT_PATH.'/test/fake/store.model.php');
require_once(ROOT_PATH.'/test/fake/goodsspec.model.php');

require_once(ROOT_PATH.'/app/mobile_order.app.php');

class Mobile_orderTest extends TestCase {

    function test_validate_submit_order_params() {
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
        $mobile_order = new Mobile_orderApp($address_stub, $order_stub,
                                                  $goodsstatistics_stub,
                                                  $store_stub, $goodsspec_stub);

        $this->assertTrue(
            $mobile_order->_validate_submit_order_params(
                array(1001, 1002), array(1, 1), 1, 1, 1));
        $this->assertTrue(
            $mobile_order->_validate_submit_order_params(
                array('1001', '1002'), array('1', '1'), '1', '1', '1'));
        $this->assertFalse(
            $mobile_order->_validate_submit_order_params(
                array(1001, 1002), array(1), 1, 1, 1));
        $this->assertFalse(
            $mobile_order->_validate_submit_order_params(
                array(123, 456, 'abc'), array(1, 2, 3), 1, 1, 1));
        $this->assertFalse(
            $mobile_order->_validate_submit_order_params(
                array(1001, 1002), array(1, 1), 'abc', 1, 1));
        $this->assertFalse(
            $mobile_order->_validate_submit_order_params(
                array(1001, 1002), array(1, 1), 1, 'abc', 1));
        $this->assertFalse(
            $mobile_order->_validate_submit_order_params(
                array(1001, 1002), array(1, 1), 1, 1, 'abc'));
    }

}

?>