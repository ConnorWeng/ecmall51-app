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
            'limit' => '1000, 20'));
        $this->assign('goods_list', $goods_list);
        $this->display('mobile_home.html');
    }

    function mobile_shop() {
        $user_id = $_GET['user_id'];
        $goods_id = $_GET['goods_id'];
        $title = $_GET['title'];
        $price = $_GET['price'];
        $pic_url = $_GET['pic_url'];
        $mobileshop_mod =& m('mobileshop');
        $settings = $mobileshop_mod->get_info($user_id);
        if ($settings) {
            $this->assign('settings', $settings);
            $price = number_format(floatval($price) + floatval($settings['profit']), 2, '.', '');
        }
        $this->assign('goods_id', $goods_id);
        $this->assign('title', $title);
        $this->assign('price', $price);
        $this->assign('pic_url', $pic_url);
        $this->display('mobile_shop.html');
    }

    function mobile_good_detail() {
        $goods_id = $_GET['goods_id'];
        $goods_mod =& m('goods');
        $good = $goods_mod->get_info($goods_id);
        $this->assign('description', $good['description']);
        $this->display('mobile_good_detail.html');
    }
}

?>