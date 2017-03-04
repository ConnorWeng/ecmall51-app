<?php

class Mobile_homeApp extends MallbaseApp {
    function index() {
        $order_by = 'goods_id DESC';
        $goods_mod =& m('goods');
        $goods_list = $goods_mod->find(array(
            'conditions' => 'g.default_spec != 0 and s.state = 1 and g.description is not null',
            'fields' => 'g.goods_id, g.goods_name, g.default_image, g.price, s.store_id, s.store_name, s.see_price, s.mk_name, s.address, s.business_scope',
            'join' => 'belongs_to_store',
            'index_key' => false,
            'order' => $order_by,
            'limit' => '5000, 500'));
        $this->assign('goods_list', filter_behalf_open_stores($goods_list, 20));
        $this->display('mobile_home.html');
    }
}

?>