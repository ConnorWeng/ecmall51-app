<?php

class Mobile_homeApp extends MallbaseApp {
    function index() {
        $order_by = 'goods_id DESC';
        $goods_mod =& m('goods');
        $goods_list = $goods_mod->find(array(
            'conditions' => 'default_spec != 0 and description is not null',
            'fields' => 'goods_id, goods_name, default_image, price, store_id',
            'index_key' => false,
            'order' => $order_by,
            'limit' => '5000, 20'));
        $this->assign('goods_list', $goods_list);
        $this->display('mobile_home.html');
    }
}

?>