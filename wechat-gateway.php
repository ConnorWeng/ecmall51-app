<?php

define('ROOT_PATH', dirname(__FILE__));
include(ROOT_PATH . '/eccore/ecmall.php');
ecm_define(ROOT_PATH . '/data/config.inc.php');
import('log.lib');
import('wechat.lib');

function log_wechat_error($message) {
    Log::write("wechat pay {$message}: ".print_r($GLOBALS['HTTP_RAW_POST_DATA'], true));
}

$wechat_pay = new WechatPay(MOBILE_WECHAT_APP_ID, MOBILE_WECHAT_MCH_ID, MOBILE_WECHAT_NOTIFY_URL, MOBILE_WECHAT_KEY);
$data = $wechat_pay->getNotifyData();
if ($data) {
    $sign = $wechat_pay->MakeSign($data);
    if ($sign == $data['sign']) {
        if ($data['return_code'] == 'SUCCESS') {
            $params = http_build_query($data);
            $context_options = array (
                'http' => array (
                    'method' => 'POST',
                    'header'=> "Content-type: application/x-www-form-urlencoded\r\n".
                    "Content-Length: ".strlen($params)."\r\n",
                    'content' => $params));
            $context = stream_context_create($context_options);
            $result = file_get_contents('http://app.51zwd.com/ecmall51-app/index.php?app=pay_notify&act=accept_wechat', false, $context);
            if ($result == 'success') {
                $wechat_pay->replyNotify();
            } else {
                log_wechat_error('handle order error');
            }
        } else {
            log_wechat_error('return code is FAIL');
        }
    } else {
        log_wechat_error('sign error');
    }
} else {
    log_wechat_error('data error');
}
