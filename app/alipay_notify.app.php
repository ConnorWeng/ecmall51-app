<?php

class Alipay_notifyApp extends FrontendApp {
    private $_order_mod = null;

    function __construct($order_mod = null) {
        parent::__construct();
        $this->_order_mod = $order_mod;
        if ($this->_order_mod === null) {
            $this->_order_mod =& m('order');
        }
    }

    function accept() {
        import('alipay-sdk/AopSdk');
        $params = $_POST;
        $c = new AopClient;
        $sign_verified = @$c->rsaCheckV2($params, MOBILE_ALIPAY_PUBLIC_KEY);
        $order_sn = $_POST['out_trade_no'];
        $total_amount = $_POST['total_amount'];
        $seller_email = $_POST['seller_email'];
        $app_id = $_POST['app_id'];
        $trade_status = $_POST['trade_status'];
        $gmt_payment = $_POST['gmt_payment'];
        if ($sign_verified) {
            $this->_accept($order_sn, $total_amount, $seller_email,
                           $app_id, $trade_status, $gmt_payment);
        } else {
            Log::write(
                "fail to verify sign, order_sn:{$order_sn} ".
                       "total_amount:{$total_amount} ".
                       "seller_email:{$seller_email} ".
                       "app_id:{$app_id} ".
                       "trade_status:{$trade_status}");
            echo('fail to verify sign');
        }
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