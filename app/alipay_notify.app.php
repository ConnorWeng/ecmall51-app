<?php

class Alipay_notifyApp extends Mobile_frontendApp {
    private $_order_mod = null;

    function __construct($order_mod = null) {
        $this->_order_mod = $order_mod;
        if ($this->_order_mod === null) {
            $this->_order_mod =& m('order');
        }
    }

    function accept() {
        $order_sn = $this->_make_sure_string('out_trade_no', 64, '');
        $total_amount = $this->_make_sure_numeric('total_amount', 0);
        $seller_email = $this->_make_sure_string('seller_email', 100, '');
        $app_id = $this->_make_sure_string('app_id', 32, '');
        $trade_status = $this->_make_sure_string('trade_status', 32, '');
        $gmt_payment = $this->_make_sure_string('gmt_payment', 32, '');
        $this->_accept($order_sn, $total_amount, $seller_email,
                       $app_id, $trade_status, $gmt_payment);
    }

    function _accept($order_sn, $total_amount, $seller_email,
                     $app_id, $trade_status, $gmt_payment) {
        $order_info = $this->_order_mod->get(array(
            'conditions' => "order_sn = '{$order_sn}'"));
        if ($order_info &&
            isset($order_info['order_id']) &&
            isset($order_info['order_amount']) &&
            $order_info['order_amount'] == $total_amount &&
            $seller_email == MOBILE_ALIPAY_EMAIL &&
            $app_id == MOBILE_ALIPAY_APP_ID &&
            $trade_status == 'TRADE_SUCCESS') {
            if ($order_info['status'] == ORDER_PENDING) {
                $order_edit_array = array(
                    'payment_name' => '支付宝手机端',
                    'payment_code' => 'alipay-mobile',
                    'pay_time' => @local_strtotime($gmt_payment),
                    'status' => ORDER_ACCEPTED);
                $this->_order_mod->edit($order_info['order_id'], $order_edit_array);
                Log::write("accept alipay notify, order_sn:{$order_sn} paid",
                           Log::INFO);
            } else {
                Log::write("accept alipay notify, order_sn:{$order_sn} not paid, ".
                           "status:{$order_info['status']}",
                           Log::INFO);
            }
            echo('success');
        } else {
            Log::write(
                "fail to verify notify params, order_sn:{$order_sn} ".
                       "total_amount:{$total_amount} ".
                       "seller_email:{$seller_email} ".
                       "app_id:{$app_id} ".
                       "trade_status:{$trade_status}");
            echo('fail to verify notify params');
        }
    }
}

?>