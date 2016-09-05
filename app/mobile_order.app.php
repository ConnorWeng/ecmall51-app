<?php

class Mobile_orderApp extends FrontendApp {
    private $_address_mod = null;
    private $_order_mod = null;
    private $_goodsstatistics_mod = null;
    private $_store_mod = null;
    private $_goodsspec_mod = null;

    function __construct($address_mod = null, $order_mod = null,
                         $goodsstatistics_mod = null, $store_mod = null,
                         $goodsspec_mod = null) {
        @header("Content-type: application/json");
        parent::__construct();
        $this->_address_mod = $address_mod;
        if ($this->_address_mod === null) {
            $this->_address_mod =& m('address');
        }
        $this->_order_mod = $order_mod;
        if ($this->_order_mod === null) {
            $this->_order_mod =& m('order');
        }
        $this->_goodsstatistics_mod = $goodsstatistics_mod;
        if ($this->_goodsstatistics_mod === null) {
            $this->_goodsstatistics_mod =& m('goodsstatistics');
        }
        $this->_store_mod = $store_mod;
        if ($this->_store_mod === null) {
            $this->_store_mod =& m('store');
        }
        $this->_goodsspec_mod = $goodsspec_mod;
        if ($this->_goodsspec_mod === null) {
            $this->_goodsspec_mod =& m('goodsspec');
        }
    }

    function _init_visitor() {
        if (!empty($_REQUEST['access_token'])) {
            try {
                $this->visitor = env('visitor', new MobileVisitor($_REQUEST['access_token']));
            } catch (Exception $e) {
                $this->_ajax_error(400, ACCESS_TOKEN_ERROR, $e->getMessage());
                exit;
            }
        } else {
            $this->_ajax_error(400, NOT_LOGIN, 'please login first');
            exit;
        }
    }

    function get_alipay_order_info() {
        if (!IS_POST) {
            if (is_numeric($_GET['order_id'])) {
                $order_info = $this->_order_mod->get($_GET['order_id']);
                echo ecm_json_encode(array(
                    'order_info' => $this->_build_alipay_order_info(
                        $order_info['order_sn'],
                        $order_info['order_amount'],
                        '51zwd订单-'.$order_info['order_sn'],
                        local_date('Y-m-d H:i:s'))));
            } else {
                $this->_ajax_error(400, ORDER_ID_PARAM_ERROR, 'error in order id');
            }
        } else {
            $this->_ajax_error(400, NOT_GET_ACTION, 'not a get action');
            return;
        }
    }

    function submit_order() {
        if (!IS_POST) {
            $this->_ajax_error(400, NOT_POST_ACTION, 'not a post action');
            return;
        }
        $spec_ids = explode(',', $_POST['spec_ids']);
        $spec_nums = explode(',', $_POST['spec_nums']);
        $address_id = $_POST['address_id'];
        $behalf_id = $_POST['behalf_id'];
        $delivery_id = $_POST['delivery_id'];
        if ($this->_validate_submit_order_params($spec_ids, $spec_nums, $address_id, $behalf_id, $delivery_id)) {
            $postscript = $this->_escape_string($_POST['postscript']);
            $this->_submit_order($spec_ids, $spec_nums, $address_id, $behalf_id, $delivery_id, $postscript);
        } else {
            $this->_ajax_error(400, SUBMIT_ORDER_PRARMS_ERROR, 'error in order params');
        }
    }

    function _validate_submit_order_params($spec_ids, $spec_nums, $address_id, $behalf_id, $delivery_id) {
        if (count($spec_ids) != count($spec_nums) || count($spec_ids) < 1) {
            return false;
        }
        foreach ($spec_ids as $id) {
            if (!is_numeric($id)) {
                return false;
            }
        }
        foreach ($spec_nums as $num) {
            if (!is_numeric($num)) {
                return false;
            }
        }
        if (!is_numeric($address_id)) {
            return false;
        }
        if (!is_numeric($behalf_id)) {
            return false;
        }
        if (!is_numeric($delivery_id)) {
            return false;
        }
        return true;
    }

    function _submit_order($spec_ids, $spec_nums, $address_id, $behalf_id, $delivery_id, $postscript) {
        $goods = $this->_get_order_goods($spec_ids, $spec_nums);
        $merge_goods_info = $this->_get_goods_info($goods);
        if ($merge_goods_info === false) {
            $this->_ajax_error(500, ORDER_GOODS_NOT_FOUND, 'goods not found');
            return;
        }

        $address_info = $this->_address_mod->get($address_id);
        $region_id = $address_info['region_id'];
        $region_name = $address_info['region_name'];
        $receiver_name = $address_info['consignee'];
        $receiver_address = $address_info['address'];
        $receiver_phone = $address_info['phone_tel'];
        $receiver_mobile = $address_info['phone_mob'];
        $receiver_zip = $address_info['zipcode'];

        $goods_info = array(
            'items'     =>  array(),    //商品列表
            'quantity'  =>  0,          //商品总量
            'amount'    =>  0,          //商品总价
            'store_id'  =>  0,          //所属店铺
            'store_name'=>  '',         //店铺名称
            'type'      =>  null,       //商品类型
            'otype'     =>  'behalf',   //订单类型
            'allow_coupon'  => false,   //是否允许使用优惠券
            'rec_ids' => array(),
            'behalf_fee' => 0);
        $store_ids = array();
        foreach ($merge_goods_info as $key=>$value) {
            if(!empty($value['items'])) {
                foreach ($value['items'] as $goods_id=>$goods_value) {
                    $goods_info['items'][$goods_value['spec_id']] = $goods_value;
                }
            }
            $goods_info['quantity'] = intval($goods_info['quantity']) + $value['quantity'];
            $goods_info['amount'] = floatval($goods_info['amount']) + $value['amount'];//2015-06-05 by tanaiquan,intval($goods_info['amount'])变为floatval($goods_info['amount'])
            $goods_info['store_name'] = $goods_info['store_name']." ".$value['store_name'];
            $goods_info['type'] = $value['type'];
            $goods_info['behalf_fee'] = floatval($goods_info['behalf_fee']) + floatval($value['behalf_fee']);
            $store_ids[] = $key;
        }
        $goods_type =& gt($goods_info['type']);
        $order_type =& ot($goods_info['otype']);

        $check_result = $order_type->_check_behalf_blacklist($behalf_id, $store_ids);
        if ($check_result !== true) {
            $this->_ajax_error(400, STORE_EXISTS_IN_BLACKLIST, 'block because of blacklist');
            return;
        }

        $data = array(
            'address_options' => $address_id,
            'consignee' => $receiver_name,
            'address' => $receiver_address,
            'phone_tel' => $receiver_phone,
            'phone_mob' => $receiver_mobile,
            'region_name' => $region_name,
            'zipcode' => $receiver_zip,
            'region_id' => $region_id,
            'behalf' => $behalf_id,
            'shipping_choice' => '2',
            'delivery' => $delivery_id,
            'postscript' => $postscript);
        $order_id = $order_type->submit_merge_order(array(
            'goods_info'    =>  $goods_info, //商品信息（包括列表，总价，总量，所属店铺，类型）,可靠的!
            'post'          =>  $data));    //用户填写的订单信息

        if (!$order_id) {
            $this->_ajax_error(500, SUBMIT_ORDER_FAILED, 'submit order failed');
            return;
        }

        /* 减去商品库存 */
        $this->_order_mod->change_stock('-', $order_id);
        $order_info = $this->_order_mod->get($order_id);

        $goods_ids = array();
        foreach ($goods_info['items'] as $goods)
        {
            $goods_ids[] = $goods['goods_id'];
            //更新销售量
            $this->_goodsstatistics_mod->edit($goods['goods_id'], "sales=sales+{$goods['quantity']}");
        }
        $this->_goodsstatistics_mod->edit($goods_ids, 'orders=orders+1');

        // TODO: 更新vendor订单状态

        // 构建支付宝订单信息
        // $order_info['order_sn'], $order_info['order_amount']是string
        // $order_id是int
        $alipay_order_info = $this->_build_alipay_order_info($order_info['order_sn'], $order_info['order_amount'], '51zwd订单-'.$order_info['order_sn'], local_date('Y-m-d H:i:s'));

        echo ecm_json_encode(array(
            'order_id' => $order_id,
            'order_sn' => $order_info['order_sn'],
            'order_info' => $alipay_order_info,
            'total_amount' => $order_info['order_amount']));
    }

    function _build_alipay_order_info($order_sn, $total_amount, $subject, $timestamp) {
        import('alipay-sdk/AopSdk');
        // 需按照key排序
        $keyVals = array(
            'app_id' => MOBILE_ALIPAY_APP_ID,
            'biz_content' => ecm_json_encode(array(
                'timeout_express' => '24h',
                'product_code' => 'QUICK_MSECURITY_PAY',
                'total_amount' => $total_amount,
                'subject' => $subject,
                'out_trade_no' => $order_sn)),
            'charset' => 'utf-8',
            'method' => 'alipay.trade.app.pay',
            'sign_type' => 'RSA',
            'timestamp' => $timestamp,
            'version' => '1.0');
        $params = http_build_query($keyVals);
        $c = new AopClient;
        $c->rsaPrivateKeyFilePath = MOBILE_ALIPAY_PRIVATE_KEY_PATH;
        $sign = $c->rsaSign($keyVals);
        $order_info = $params.'&sign='.urlencode($sign);
        return $order_info;
    }

    function _get_order_goods($spec_ids, $spec_nums) {
        $result = array();
        for ($i = 0; $i < count($spec_ids); $i++) {
            $spec_id = $spec_ids[$i];
            $num = $spec_nums[$i];
            $goods = $this->_goodsspec_mod->find(array(
                'conditions' => "spec_id={$spec_id}",
                'fields' => 'gs.spec_id,gs.goods_id,gs.spec_1,gs.spec_2,gs.color_rgb,gs.price,gs.stock,gs.sku,gs.spec_vid_1,gs.spec_vid_2,g.store_id,g.type,g.goods_name,g.description,g.cate_id,g.cate_name,g.brand,g.spec_qty,g.spec_name_1,g.spec_name_2,g.if_show,g.closed,g.close_reason,g.add_time,g.last_update,g.default_spec,g.default_image,g.searchcode,g.recommended,g.cate_id_1,g.cate_id_2,g.cate_id_3,g.cate_id_4,g.service_shipa,g.tags,g.sort_order,g.good_http,g.moods,g.cids,g.realpic,g.spec_pid_1,g.spec_pid_2,g.delivery_template_id,g.delivery_weight',
                'join' => 'belongs_to_goods',
                'index_key' => ''));
            if ($goods) {
                $goods[0]['num'] = $num;
                $goods[0]['specification'] = $goods[0]['spec_1'].' '.$goods[0]['spec_2'];
                $result[] = $goods[0];
            }
        }
        return $result;
    }

    function _get_goods_info($goods) {
        $return = array();
        $store_ids = $this->_get_store_ids($goods);
        foreach ($store_ids as $store_id) {
            $levy_behalf_fee = belong_behalfarea($store_id);
            $data = array(
                'items' => array(),
                'quantity'  =>  0,          //商品总量
                'amount'    =>  0,          //商品总价
                'store_id'  =>  0,          //所属店铺
                'store_name'=>  '',         //店铺名称
                'type'      =>  null,       //商品类型
                'otype'     =>  'normal',   //订单类型
                'allow_coupon'  => true,    //是否允许使用优惠券
                'rec_ids' => array(),
                'behalf_fee' => 0);
            $store_info = $this->_store_mod->get($store_id);
            $items = $this->_get_items($goods, $store_id);
            $data['items'] = $items;
            $data['quantity'] += $this->_get_quantity($items);
            $data['amount'] += $this->_get_amount($items);
            $data['store_id'] = $store_id;
            $data['store_name'] = $store_info['store_name'];
            $data['store_im_qq'] = $store_info['im_qq'];
            $data['type'] = 'material';
            $data['otype'] = 'behalf';
            $data['behalf_fee'] = $levy_behalf_fee === false ? $data['quantity'] * floatval(BEHALF_GOODS_SERVICE_FEE) : 0;
            $return[$store_id] = $data;
        }
        return $return;
    }

    function _get_store_ids($goods) {
        $return = array();
        foreach ($goods as $good){
            if (array_search($good['store_id'], $return) === false) {
                $return[] = $good['store_id'];
            }
        }
        return $return;
    }

    function _get_items($goods, $store_id) {
        $levy_behalf_fee = belong_behalfarea($store_id);
        $items = array();
        foreach ($goods as $good) {
            if ($good['store_id'] == $store_id) {
                $items[] = array(
                    'user_id' => $this->visitor->get('user_id'),
                    'session_id' => SESS_ID,
                    'store_id' => $store_id,
                    'goods_id' => $good['goods_id'],
                    'goods_name' => $good['goods_name'],
                    'spec_id' => $good['spec_id'],
                    'specification' => $good['specification'],
                    'price' => $good['price'],
                    'quantity' => $good['num'],
                    'goods_image' => $good['default_image'],
                    'subtotal' => $good['num'] * $good['price'],
                    'behalf_fee' => $levy_behalf_fee === false ? @$goods['num'] * floatval(BEHALF_GOODS_SERVICE_FEE) : 0);
            }
        }
        return $items;
    }

    function _get_quantity($items) {
        $quantity = 0;
        foreach ($items as $item) {
            $quantity += $item['quantity'];
        }
        return $quantity;
    }

    function _get_amount($items) {
        $amount = 0;
        foreach ($items as $item) {
            $amount += $item['quantity'] * $item['price'];
        }
        return $amount;
    }

    function _ajax_error($http_code, $user_code, $message) {
        http_response_code($http_code);
        echo ecm_json_encode(array(
            'error' => true,
            'code' => $user_code,
            'message' => $message));
    }

    function _escape_string($unescaped_string) {
        return empty($unescaped_string) ? '' : db()->escape_string($unescaped_string);
    }
}

class MobileVisitor {
    var $_access_token;
    var $_user_info;

    function __construct($access_token) {
        $this->_access_token = db()->escape_string($access_token);
        $access_token_mod =& m('access_token');
        $this->_user_info = $access_token_mod->get(array(
            'conditions' => "access_token='{$this->_access_token}'",
            'join' => 'belongs_to_member'));
        if (empty($this->_user_info['user_id'])) {
            throw new RuntimeException('access token invalid');
        }
    }

    function get($key) {
        return $this->_user_info[$key];
    }
}

?>