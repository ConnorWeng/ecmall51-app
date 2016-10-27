<?php

define('ROOT_PATH', dirname(__FILE__));
include(ROOT_PATH . '/eccore/ecmall.php');
ecm_define(ROOT_PATH . '/data/config.inc.php');
import('log.lib');
import('alipay-sdk/AopSdk');

$params = http_build_query($_POST);
$c = new AopClient;
$sign_verified = @$c->rsaCheckV1($_POST, MOBILE_ALIPAY_PUBLIC_KEY);

if ($sign_verified) {
    $context_options = array (
        'http' => array (
            'method' => 'POST',
            'header'=> "Content-type: application/x-www-form-urlencoded\r\n".
            "Content-Length: ".strlen($params)."\r\n",
            'content' => $params));
    $context = stream_context_create($context_options);
    $result = file_get_contents('http://app.51zwd.com/ecmall51-app/index.php?app=alipay_notify&act=accept', false, $context);
    echo $result;
} else {
    Log::write(
        "fail to verify sign, post:{$params} ");
}

?>