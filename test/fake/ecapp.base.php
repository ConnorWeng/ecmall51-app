<?php

// mobile app
define('NOT_POST_ACTION', 510001);
define('LOGIN_FAILED', 510002);
define('ADD_ACCESS_TOKEN_FAILED', 510003);
define('SUBMIT_ORDER_PARAMS_ERROR', 510004);
define('ORDER_GOODS_NOT_FOUND', 510005);
define('SUBMIT_ORDER_FAILED', 510006);
define('NOT_LOGIN', 510007);
define('ACCESS_TOKEN_ERROR', 510008);
define('PARAMS_ERROR', 510011);
define('TAOBAO_API_ERROR', 510035);

define('ORDER_SUBMITTED', 10);   // 针对货到付款而言，他的下一个状态是卖家已发货
define('ORDER_PENDING', 11);     // 等待买家付款
define('ORDER_ACCEPTED', 20);    // 买家已付款，等待卖家发货
define('ORDER_SHIPPED', 30);     // 卖家已发货
define('ORDER_FINISHED', 40);    // 交易成功
define('ORDER_CANCELED', 0);     // 交易已取消

class ECBaseApp {
    function __construct() {}

    function getNoreply() {
        return array(
            'user_id' => '1',
            'token' => 'faketoken');
    }
}

?>