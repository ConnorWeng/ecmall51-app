<?php

class Mobile_goodsApp extends Mobile_frontendApp {
    function __construct() {
    }

    function index() {
        $store_id = $this->_make_sure_numeric('store_id', 0);
        $this->_index($store_id);
    }

    function _index($store_id) {
        $order_by = 'add_time DESC';
        $page_per = 25;
        $page = $this->_get_page($page_per);
        $conditions = $store_id == 0 ? '' : "store_id = {$store_id}";
        $goods_mod =& m('goods');
        $goods_list = $goods_mod->find(array(
            'fields' => 'goods_id, goods_name, default_image, price, store_id',
            'index_key' => false,
            'conditions' => $conditions,
            'order' => $order_by,
            'limit' => $page['limit']));
        echo ecm_json_encode($goods_list);
    }
}

?>