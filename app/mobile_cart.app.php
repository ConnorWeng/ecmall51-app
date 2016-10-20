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
            $item['default_image'] = $item['goods_image'];
            @$carts[$item['store_id']]['store_name'] = $item['store_name'];
            @$carts[$item['store_id']]['amount'] += $item['subtotal'];   //各店铺的总金额
            @$carts[$item['store_id']]['quantity'] += $item['quantity'];   //各店铺的总数量
            @$carts[$item['store_id']]['goods'][] = $item;
            @$carts[$item['store_id']]['store_id'] = $item['store_id'];
        }

        // 转成app需要的数组格式
        $mobileCarts = array();

        foreach ($carts as $_store_id => $cart) {
            $cart['kinds'] = count(array_keys($kinds[$_store_id]));
            array_push($mobileCarts, $cart);
        }

        echo ecm_json_encode($mobileCarts);
    }

    function add() {
        if (!IS_POST) {
            $this->_ajax_error(400, NOT_POST_ACTION, 'not a post action');
            return;
        }
        $spec_id = $this->_make_sure_numeric('spec_id', 0);
        $quantity = $this->_make_sure_numeric('quantity', 0);
        if ($spec_id === 0 || $quantity === 0) {
            $this->_ajax_error(400, PARAMS_ERROR, 'parameters error');
            return;
        }

        // 购物车中宝贝数量不能超过100
        $model_cart = & m('cart');
        $count_in_cart = $model_cart->getOne('select count(1) from ecm_cart where user_id = '.$this->visitor->get('user_id'));
        if ($count_in_cart > 100) {
            $this->_ajax_error(400, CART_IS_FULL, '购物车已满');
            return;
        }

        /* 店铺所在市场是否有代发 和  店铺本身提供配送。如果都没有，则报错！ */
        $mod_goodsspec = & m('goodsspec');
        $goodsspec = $mod_goodsspec->get($spec_id);

        $goods_id = $goodsspec ? $goodsspec['goods_id'] : 0;
        $mod_goods = & m('goods');
        $store = $mod_goods->find(array(
            'conditions' => 'g.goods_id = ' . $goods_id,
            'join' => 'belongs_to_store',
            'fields' => 's.store_id,s.mk_id',
                ));
        //查看店铺是否有运费模板
        $mod_delivery_template = & m('delivery_template');
        $delivery_templates = $mod_delivery_template->find(array(
            'conditions' => 'delivery_template.store_id =' . $store[$goods_id]['store_id'],
            'fields' => 'template_id'));

        //查看店铺所在市场是否有代发
        $mod_market = & m('market');
        $layer = $mod_market->get_layer($store[$goods_id]['mk_id']);
        if ($layer == 2) {
            $mk_id = $store[$goods_id]['mk_id']; //市场名
        }
        if ($layer == 3) {
            $market = $mod_market->get($store[$goods_id]['mk_id']); //楼层名，须得到parent_id市场名
            $mk_id = $market['parent_id'];
        }
        if (!in_array($layer, array(2, 3))) {
            $market_behalfs = null;
        } else {
            $market_behalfs = $mod_market->getRelatedData('belongs_to_behalf', $mk_id);
        }

        if (empty($delivery_templates) && empty($market_behalfs)) {
            $this->_ajax_error(400, GOODS_NO_LOGISTICS, '该商家尚未加入代发区，请将商家名反馈给我们，同时请您电话通知商家加入51免费代发区！');
            return;
        }

        /* 是否有商品 */
        $spec_model = & m('goodsspec');
        $spec_info = $spec_model->get(array(
            'fields' => 'g.store_id, g.goods_id, g.goods_name, g.spec_name_1, g.spec_name_2, g.default_image, gs.spec_1, gs.spec_2, gs.stock, gs.price',
            'conditions' => $spec_id,
            'join' => 'belongs_to_goods'));

        if (!$spec_info) {
            $this->_ajax_error(400, NO_SUCH_GOODS, '该宝贝已下架');
            /* 商品不存在 */
            return;
        }

        //价格少于5元，不能加入购物车
        if($spec_info['price'] < 5) {
            $this->_ajax_error(400, PRICE_LESS_5, '宝贝价格不能少于5元');
            return;
        }

        /* 如果是自己店铺的商品，则不能购买 */
        // if ($this->visitor->get('manage_store')) {
        //     if ($spec_info['store_id'] == $this->visitor->get('manage_store')) {
        //         $this->_ajax_error(400, CAN_NOT_BUY_YOURSELF, '不能购买自己店铺的宝贝');
        //         return;
        //     }
        // }

        /* 是否添加过 */
        $item_info = $model_cart->get("spec_id={$spec_id} AND session_id='" . SESS_ID . "'");
        if (!empty($item_info)) {
            $this->_ajax_error(400, GOODS_ALREADY_IN_CART, '该宝贝已在购物车中');
            return;
        }

        if ($quantity > $spec_info['stock']) {
            $this->_ajax_error(400, NO_ENOUGH_GOODS, '该宝贝库存不够了');
            return;
        }

        $spec_1 = $spec_info['spec_name_1'] ? $spec_info['spec_name_1'] . ':' . $spec_info['spec_1'] : $spec_info['spec_1'];
        $spec_2 = $spec_info['spec_name_2'] ? $spec_info['spec_name_2'] . ':' . $spec_info['spec_2'] : $spec_info['spec_2'];

        $specification = $spec_1 . ' ' . $spec_2;

        /* 将商品加入购物车 */
        $cart_item = array(
            'user_id' => $this->visitor->get('user_id'),
            'session_id' => SESS_ID,
            'store_id' => $spec_info['store_id'],
            'spec_id' => $spec_id,
            'goods_id' => $spec_info['goods_id'],
            'goods_name' => addslashes($spec_info['goods_name']),
            'specification' => addslashes(trim($specification)),
            'price' => $spec_info['price'],
            'quantity' => $quantity,
            'goods_image' => addslashes($spec_info['default_image']),
        );

        /* 添加并返回购物车统计即可 */
        $cart_model = & m('cart');
        $cart_model->add($cart_item);

        /* 更新被添加进购物车的次数 */
        $model_goodsstatistics = & m('goodsstatistics');
        $model_goodsstatistics->edit($spec_info['goods_id'], 'carts=carts+1');

        echo ecm_json_encode(array(
            'sucess' => true));
    }
}

?>