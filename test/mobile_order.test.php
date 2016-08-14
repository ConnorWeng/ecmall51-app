<?php

define('ROOT_PATH', dirname(__FILE__).'/..');

require(ROOT_PATH.'/test/fake/frontend.base.php');
require(ROOT_PATH.'/app/mobile_order.app.php');

function ajaxFunctionReturnJson($function, ...$params) {
    ob_start();
    $function(...$params);
    $json = ob_get_contents();
    ob_end_clean();
    return $json;
}

class Mobile_orderTest extends PHPUnit_Framework_TestCase {
    private $mobile_order;

    function __construct() {
        $this->mobile_order = new Mobile_orderApp();
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
}

?>