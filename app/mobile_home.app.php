<?php

class Mobile_homeApp extends MallbaseApp {
    function index() {
        $order_by = 'goods_id DESC';
        $goods_mod =& m('goods');
        $goods_list = $goods_mod->find(array(
            'conditions' => 'g.default_spec != 0 and g.description is not null and '.behalf_open_stores_condition(),
            'fields' => 'g.goods_id, g.goods_name, g.default_image, g.price, s.store_id, s.store_name, s.see_price, s.mk_name, s.address, s.business_scope',
            'join' => 'belongs_to_store_open',
            'index_key' => false,
            'order' => $order_by,
            'limit' => '5000, 20'));
        $this->assign('goods_list', $goods_list);
        $this->display('mobile_home.html');
    }
}

?>