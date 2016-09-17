<?php

class Mobile_cartApp extends Mobile_frontendApp {
    function __construct() {
        parent::__construct();
    }

    function index() {
        $carts = array();

        /* 只有是自己购物车的项目才能购买 */
        $where_user_id = $this->visitor->get('user_id') ? "cart.user_id=" . $this->visitor->get('user_id') : '';
        $cart_model = & m('cart');
        $cart_items = $cart_model->find(array(
            'conditions' => $where_user_id,
            'fields' => 'this.*,store.store_name',
            'join' => 'belongs_to_store'));
        if (empty($cart_items)) {
            echo '{}';
            return;
        }
        $kinds = array();
        foreach ($cart_items as $item) {
            /* 小计 */
            $item['subtotal'] = $item['price'] * $item['quantity'];
            $kinds[$item['store_id']][$item['goods_id']] = 1;

            /* 以店铺ID为索引 */
            empty($item['goods_image']) && $item['goods_image'] = Conf::get('default_goods_image');
            @$carts[$item['store_id']]['store_name'] = $item['store_name'];
            @$carts[$item['store_id']]['amount'] += $item['subtotal'];   //各店铺的总金额
            @$carts[$item['store_id']]['quantity'] += $item['quantity'];   //各店铺的总数量
            @$carts[$item['store_id']]['goods'][] = $item;
        }

        foreach ($carts as $_store_id => $cart) {
            $carts[$_store_id]['kinds'] = count(array_keys($kinds[$_store_id]));  //各店铺的商品种类数
        }

        echo ecm_json_encode($carts);
    }
}

?>