<?php

class Mobile_orderApp extends Mobile_frontendApp {
    private $_address_mod = null;
    private $_order_mod = null;
    private $_goodsstatistics_mod = null;
    private $_store_mod = null;
    private $_goodsspec_mod = null;

    function __construct($address_mod = null, $order_mod = null,
                         $goodsstatistics_mod = null, $store_mod = null,
                         $goodsspec_mod = null) {
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

    function index() {
        if (!IS_POST) {
            $order_by = 'add_time DESC';
            $page_per = 25;
            $page = $this->_get_page($page_per);
            $user_id = $this->visitor->get('user_id');
            $orders = $this->_order_mod->findAll(array(
                'include' => array('has_ordergoods'),
                'index_key' => false,
                'conditions' => "buyer_id = {$user_id}",
                'order' => $order_by,
                'limit' => $page['limit'],
                'fields' => 'order_id, order_sn, order_amount, status'));
            echo ecm_json_encode($orders);
        } else {
            $this->_ajax_error(400, NOT_GET_ACTION, 'not a get action');
            return;
        }
    }

    function apply_refund() {
        if (IS_POST) {
            $order_id = $this->_make_sure_numeric('order_id', -1);
            $refund_amount = $this->_make_sure_numeric('refund_amount', -1);
            $refund_reason = $this->_escape_string($_POST['refund_reason']);
            $refund_intro = $this->_escape_string($_POST['refund_intro']);
            if ($order_id === -1 || $refund_amount === -1 || empty($refund_reason)) {
                $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
                return ;
            }
            $this->_apply_refund($order_id, $refund_amount, $refund_reason, $refund_intro);
        } else {
            $this->_ajax_error(400, NOT_POST_ACTION, 'not a post action');
            return;
        }
    }

    function _apply_refund($order_id, $refund_amount, $refund_reason, $refund_intro) {
        $order_info = $this->_order_mod->get("order_id = {$order_id} and buyer_id = ".$this->visitor->get('user_id'));
        if (empty($order_info)) {
            $this->_ajax_error(400, ORDER_NOT_EXISTS, '订单不存在');
            return ;
        }
        $model_orderrefund =& m('orderrefund');
        $refund_result = $model_orderrefund->find(array(
            'conditions' => 'order_id='.$order_info['order_id'].' AND receiver_id='.$order_info['bh_id'].''));
        if (!empty($refund_result)) {
            if (count($refund_result) > 1) {
                $this->_ajax_error(400, APPLY_ILLEAGE, '该订单申请已经超过两次，如有疑问请直接联系代发解决!');
                return;
            }
            $exist_refund = current($refund_result);
            //status 0:申请，1：已同意，2：已拒绝  closed 0:未关闭 1：已关闭
            if ($exist_refund['status'] != 2 && $exist_refund['closed'] != 1) {
                $this->_ajax_error(400, APPLY_ILLEAGE, '非法申请！如已申请过，请前往网页端查看申请进度。');
                return;
            }
        }
        if ($order_info['status'] != ORDER_ACCEPTED && $refund_amount > $order_info['goods_amount']) {
            $this->_ajax_error(400, HACK_ATTEMPTED, '发现恶意行为');
            return ;
        }
        $goods_ids = '';
        $data = array(
            'order_id' => $order_info['order_id'],
            'order_sn' => $order_info['order_sn'],
            'sender_id' => $this->visitor->get('user_id'),
            'sender_name' => $this->visitor->get('user_name'),
            'receiver_id' => $order_info['bh_id'],
            'refund_reason' => $refund_reason,
            'refund_intro' => $refund_intro,
            'goods_ids' => $goods_ids,
            'goods_ids_flag' => $goods_ids ? 1 : 0,
            'apply_amount' => $refund_amount,
            'invoice_no' => null,
            'dl_id' => 0,
            'dl_name' => null,
            'dl_code' => null,
            'refund_amount' => 0,
            'create_time' => gmtime(),
            'pay_time' => 0,
            'status' => 0,
            'closed' => 0,
            //1:代表申请退款退货 2：代表代发申请补邮
            'type' => 1);
        $model_orderrefund=& m('orderrefund');
        $affect_id = $model_orderrefund->add($data);
        if (empty($affect_id) || $model_orderrefund->has_error()) {
            $this->_ajax_error(500, 'DB_ERROR', $model_orderrefund->get_error());
            return;
        }

        echo ecm_json_encode(array(
            'success' => true));
    }

    function confirm_order() {
        if (IS_POST) {
            $order_id = $this->_make_sure_numeric('order_id', -1);
            if ($order_id === -1) {
                $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
                return ;
            }
            $this->_confirm_order($order_id);
        } else {
            $this->_ajax_error(400, NOT_POST_ACTION, 'not a post action');
            return;
        }
    }

    function _confirm_order($order_id) {
        $order_info = $this->_order_mod->get("order_id = {$order_id} and buyer_id = ".$this->visitor->get('user_id')." and status = ".ORDER_SHIPPED);
        if (empty($order_info)) {
            $this->_ajax_error(400, ORDER_NOT_EXISTS, '订单不存在');
            return ;
        }
        // start transaction
        $db_transaction_begin = db()->query('START TRANSACTION');
        if ($db_transaction_begin === false) {
            $this->_ajax_error(500, START_TRANSACTION_FAILED, '开启事务失败');
            return ;
        }
        $this->_order_mod->edit($order_id, array('status' => ORDER_FINISHED, 'finished_time' => gmtime()));
        if ($this->_order_mod->has_error()) {
            db()->query('ROLLBACK');
            $this->_ajax_error(500, DB_ERROR, $this->_order_mod->get_error());
            return;
        }
        // 记录订单操作日志
        $order_log =& m('orderlog');
        $order_log->add(array(
            'order_id'  => $order_id,
            'operator'  => addslashes($this->visitor->get('user_name')),
            'order_status' => order_status($order_info['status']),
            'changed_status' => order_status(ORDER_FINISHED),
            'remark'    => Lang::get('buyer_confirm'),
            'log_time'  => gmtime()));

        /*商付通v2.2.1  更新商付通定单状态 确认收货 开始*/
        $my_money_mod =& m('my_money');
        $my_moneylog_mod =& m('my_moneylog');
        $my_moneylog_row=$my_moneylog_mod->getrow("select * from ".DB_PREFIX."my_moneylog where order_id='$order_id' and s_and_z=2 and caozuo=20");
        //$money=$my_moneylog_row['money'];//定单价格
        $money = $order_info['order_amount'];
        $sell_user_id=$my_moneylog_row['seller_id'];//卖家ID
        if($my_moneylog_row['order_id']==$order_id) {
            $buy_user_id = $this->visitor->get('user_id');
            $sell_money_row=$my_money_mod->getrow("select * from ".DB_PREFIX."my_money where user_id='$sell_user_id'");
            $buy_money_row=$my_money_mod->getrow("select * from ".DB_PREFIX."my_money where user_id='$buy_user_id'");
            $buy_money = $buy_money_row['money'];  //买家资金
            $sell_money=$sell_money_row['money'];//卖家的资金
            $sell_money_dj=$sell_money_row['money_dj'];//卖家的冻结资金
            $new_money = $sell_money+$money;
            $new_money_dj = $sell_money_dj-$money;
            $new_buy_money = $buy_money;
            //更新数据
            $new_money_array=array(
                'money' => $new_money,
                'money_dj' => $new_money_dj);
            $new_buy_money_array = array(
                'money' => $new_buy_money);
            if($new_money_dj > 0 ) {
                $my_money_mod->edit('user_id='.$sell_user_id, $new_money_array);
            }

            //        $my_money_mod->edit('user_id='.$this->visitor->get('user_id'),$new_buy_money_array);
            //更新商付通log为 定单已完成
            $my_moneylog_mod->edit('order_id='.$order_id , array('caozuo'=>40));
        }

        db()->query('COMMIT');
        echo ecm_json_encode(array(
            'success' => true));
    }

    function get_order_info() {
        $order_id = $this->_make_sure_numeric('order_id', -1);
        if ($order_id === -1) {
            $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
            return ;
        }

        $model_order =& m('order');
        $order_info = $model_order->get(array(
            'fields'        => "*, order.add_time as order_add_time",
            'conditions'    => "order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id')));
        if (!$order_info) {
            $this->_ajax_error(400, ORDER_NOT_EXISTS, '该订单不存在');
        }
        $order_type =& ot($order_info['extension']);
        $order_detail = $order_type->get_order_detail($order_id, $order_info);

        $spec_ids = array();
        foreach ($order_detail['data']['goods_list'] as $key => $goods)
        {
            empty($goods['goods_image']) && $order_detail['data']['goods_list'][$key]['goods_image'] = Conf::get('default_goods_image');
            $spec_ids[] = $goods['spec_id'];
        }

        /* 查出最新的相应的货号 */
        $model_spec =& m('goodsspec');
        $spec_info = $model_spec->find(array(
            'conditions'    => $spec_ids,
            'fields'        => 'sku'));
        ////商家编码
        $model_goodsattr =& m('goodsattr');
        foreach ($order_detail['data']['goods_list'] as $key => $goods) {
            $order_detail['data']['goods_list'][$key]['sku'] = @$spec_info[$goods['spec_id']]['sku']; // goods被删引起这里报错，所以加上@
            if(!$order_detail['data']['goods_list'][$key]['sku']) {
                $order_detail['data']['goods_list'][$key]['sku'] = getHuoHao($goods['goods_name']);
                if(!$order_detail['data']['goods_list'][$key]['sku']) {
                    $goods_AttrModel = &m('goodsattr');
                    $attrs = $goods_AttrModel->get(array(
                        'conditions' => "goods_id = ".$goods['goods_id']." AND attr_id = 13021751"));
                    $order_detail['data']['goods_list'][$key]['sku'] = $attrs['attr_value'];
                }
            }
            $goods_seller_bm = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$goods['goods_id']} AND attr_id=1");
            $order_detail['data']['goods_list'][$key]['goods_seller_bm'] = $goods_seller_bm;
        }

        //tiq
        /*store,goods infos*/
        $data = $stores = array();
        $goods_model = & m('goods');
        $store_model = & m('store');
        foreach ($order_detail['data']['goods_list'] as $key => $goods) {
            if(!empty($goods['goods_id'])) {
                $result = $goods_model->get(array(
                    'fields'=>'store_id',
                    'conditions'=>'goods_id='.$goods['goods_id']));
                if (!$result['store_id']) { // goods被删，所以goods表里查不到store_id了，只能直接从goods对象里拿store_id
                    $result = array('store_id' => $goods['store_id']); // 为什么要从goods表里查，而不直接改成从goods对象里读取store_id？不清楚...
                }
                if($result['store_id'] &&!in_array($result['store_id'], $stores)) {
                    $stores[] = $result['store_id'];
                    $data[$result['store_id']]['store_info'] = $store_model->get(array(
                        'conditions' => 'store_id = '.$result['store_id'],
                        'fields' => 'store_id, store_name, im_ww, tel, mk_name, dangkou_address'));
                    $data[$result['store_id']]['goods_list'][] = $goods;
                } else {
                    $data[$result['store_id']]['goods_list'][] = $goods;
                }
            }
        }

        //
        $model_orderrefund = & m('orderrefund');
        $refunds=$model_orderrefund->get(array(
            'conditions'=>'order_id='.$order_info['order_id'].' AND receiver_id='.$order_info['bh_id'].' AND closed=0 AND type=1'));
        if($refunds) {
            $model_behalf = & m('behalf');
            $refunds_behalf = $model_behalf->get($refunds['receiver_id']);
            $refunds['receiver_name'] = $refunds_behalf['bh_name'];
        }
        $apply_fees=$model_orderrefund->get(array(
            'conditions'=>'order_id='.$order_info['order_id'].' AND receiver_id='.$order_info['buyer_id'].' AND closed=0 AND type=2'));

        echo ecm_json_encode(array(
            'merge_sgoods' => $data,
            'order' => $order_info,
            'refunds' => $refunds,
            'apply_fees' => $apply_fees,
            'order_detail' => $order_detail['data']));
    }

    function get_alipay_order_info() {
        if (!IS_POST) {
            if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
                $order_info = $this->_order_mod->get($_GET['order_id']);
                if ($order_info) {
                    echo ecm_json_encode(array(
                        'order_id' => $_GET['order_id'],
                        'order_amount' => $order_info['order_amount'],
                        'order_info' => $this->_build_alipay_order_info(
                            $order_info['order_sn'],
                            $order_info['order_amount'],
                            '51zwd订单-'.$order_info['order_sn'],
                            local_date('Y-m-d H:i:s'))));
                } else {
                    $this->_ajax_error(400, ORDER_NOT_EXISTS, 'order not exists');
                }
            } else {
                $this->_ajax_error(400, ORDER_ID_PARAM_ERROR, 'error in order id');
            }
        } else {
            $this->_ajax_error(400, NOT_GET_ACTION, 'not a get action');
            return;
        }
    }

    function get_order_goods_info() {
        $spec_ids = $this->_make_sure_all_numeric('spec_ids', array());
        $spec_nums = $this->_make_sure_all_numeric('spec_nums', array());
        if (empty($spec_ids) || empty($spec_nums) || count($spec_ids) !== count($spec_nums)) {
            $this->_ajax_error(400, PARAMS_ERROR, 'parameters error');
        } else {
            $this->_get_order_goods_info($spec_ids, $spec_nums);
        }
    }

    function _get_order_goods_info($spec_ids, $spec_nums) {
        $goods = $this->_get_order_goods($spec_ids, $spec_nums);
        $merge_goods_info = $this->_get_goods_info($goods);
        if ($merge_goods_info === false) {
            $this->_ajax_error(500, ORDER_GOODS_NOT_FOUND, 'goods not found');
            return;
        }
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
                    $goods_info['items'][] = $goods_value;
                }
            }
            $goods_info['quantity'] = intval($goods_info['quantity']) + $value['quantity'];
            $goods_info['amount'] = floatval($goods_info['amount']) + $value['amount'];//2015-06-05 by tanaiquan,intval($goods_info['amount'])变为floatval($goods_info['amount'])
            $goods_info['store_name'] = $goods_info['store_name']." ".$value['store_name'];
            $goods_info['type'] = $value['type'];
            $goods_info['behalf_fee'] = floatval($goods_info['behalf_fee']) + floatval($value['behalf_fee']);
            $store_ids[] = $key;
        }

        $address_info = $this->_address_mod->get(array('conditions' => 'user_id='.$this->visitor->get('user_id')));
        $goods_info['default_address'] = $address_info;

        // behalfs
        $behalf_mod =& m('behalf');
        $behalfs = $behalf_mod->get_behalfs_deliverys();
        $goods_info['behalfs'] = $behalfs;

        echo ecm_json_encode($goods_info);
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
            $this->_ajax_error(400, SUBMIT_ORDER_PARAMS_ERROR, 'error in order params');
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
            'order_amount' => $order_info['order_amount'],
            'status' => $order_info['status']));
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
            'notify_url' => 'http://app.51zwd.com/ecmall51-app/gateway.php',
            'sign_type' => 'RSA',
            'timestamp' => $timestamp,
            'version' => '1.0');
        $params = http_build_query($keyVals);
        $c = new AopClient;
        $c->rsaPrivateKeyFilePath = MOBILE_ALIPAY_APP_PRIVATE_KEY_PATH;
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

    function _escape_string($unescaped_string) {
        return empty($unescaped_string) ? '' : db()->escape_string($unescaped_string);
    }
}

?>