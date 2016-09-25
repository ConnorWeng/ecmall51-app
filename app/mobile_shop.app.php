<?php

class Mobile_shopApp extends Mobile_frontendApp {
    function __construct() {
    }

    function index() {
        $mk_id = $this->_make_sure_numeric('mk_id', 0);
        $page = $this->_make_sure_numeric('page', 1);
        $this->_index($mk_id, $page);
    }

    // 根据市场、排序方式、页面大小、页面号进行店铺查询
    function _index($mk_id, $page) {
        $order_by = 'mk_id, floor, address';
        $page_per = 25;
        $page = $this->_get_page($page_per);
        $conditions = $mk_id == 0 ? '' : "mk_id = {$mk_id}";
        $shop_mod =& m('store');
        $shop_list = $shop_mod->find(array(
            'fields' => 'mk_id, store_id, floor, address, store_name, see_price, business_scope',
            'index_key' => false,
            'conditions' => $conditions,
            'order' => $order_by,
            'limit' => $page['limit']));
        echo ecm_json_encode($shop_list);
    }
}

?>