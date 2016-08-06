<?php

/**
 *    售货员控制器，其扮演实际交易中柜台售货员的角色，你可以这么理解她：你告诉我（售货员）要买什么东西，我会询问你你要的收货地址是什么之类的问题
 *        并根据你的回答来生成一张单子，这张单子就是“订单”
 *
 *    @author    Garbin
 *    @param    none
 *    @return    void
 */
class OrderApp extends ShoppingbaseApp
{

    /**
     *    填写收货人信息，选择配送，支付方式。
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function index()
    {   
        //强制用户 下单前，填写个人联系方式：qq,ww,tel
        $this->_check_member_profile();
        
        $goods_info = $this->_get_goods_info();        //dump($goods_info);
        
        if ($goods_info === false || empty($goods_info['items']))
        {
            /* 购物车是空的 */
            $this->show_warning('goods_empty');
            return;
        }
        
        /*代发订单类型 by tiq*/
        //$_GET['shipping_choice'] != 1 && $goods_info['otype'] = 'behalf';

        /*  检查库存 */
        $goods_beyond = $this->_check_beyond_stock($goods_info['items']);
        if ($goods_beyond && $goods_info['otype'] != 'behalf')
        {
            $str_tmp = '';
            foreach ($goods_beyond as $goods)
            {
                $str_tmp .= '<br /><br />' . $goods['goods_name'] . '&nbsp;&nbsp;' . $goods['specification'] . '&nbsp;&nbsp;' . Lang::get('stock') . ':' . $goods['stock'];
            }
            $this->show_warning(sprintf(Lang::get('quantity_beyond_stock'), $str_tmp));
            return;
        }
       
        if (IS_POST && $_POST['so'] == '1')
        {
            /* 根据商品类型获取对应订单类型 */
            $goods_type     =&  gt($goods_info['type']);
            $order_type     =&  ot($goods_info['otype']);
               
            /* 是否存在快递运费模板，用于卖家发货 */
            $exist_delivery_template = 0;
            $delivery_mod = &m('delivery_template');
            $delivery_method = $delivery_mod->get(array(
                        'conditions'=>'store_id='.intval($_GET['store_id']),
                        'order'=>'template_id',
            ));
           !empty($delivery_method) && $exist_delivery_template = 1;      
           
            /* 显示订单表单 */
            if(isset($_GET['shipping_choice']) && intval(trim($_GET['shipping_choice'])) == 1)
            {
                /* 检查买家的收货地址，因为用到了运费模板，如果没有收货地址，无法读取运费  tyioocom delivery */
                $address_model =& m('address');
                if(!$address_model->get('user_id=' . $this->visitor->get('user_id'))){
                    $this->show_warning('请添加收货地址 ，以便计算运费！', '添加地址', 'index.php?app=my_address');
                    return;
                }
                $form = $order_type->get_order_form($goods_info); // psmb
            }
            else
            {
                /*代发传送给模板开始,取得本店所在市场的所有代发*/
                $form = $order_type->get_order_form($_GET['store_id']);
                if(!empty($form['data']['my_behalfs']))
                {
                        $this->assign('behalfs',$form['data']['my_behalfs']);
                        //第一代发对应的快递传过去
                        $my_behalf_mod = & m('behalf');
                        $my_behalfs = array_values($form['data']['my_behalfs']);
                        $first_behalf = $my_behalfs[0]['bh_id'];
                        $my_deliverys = $my_behalf_mod->calculate_delivery_fee_bybehalf($first_behalf,$goods_info['quantity']);
                        $my_deliverys = !empty($my_deliverys)?array_values($my_deliverys):$my_deliverys;
                        $this->assign('deliverys',$my_deliverys);
                }
                else 
                {
                    $this->assign('not_exist_behalf_tips',$form['data']['my_behalfs_reason']);
                }
            }

            if ($form === false)
            {
                $this->show_warning($order_type->get_error());
                return;
            }
            $this->_curlocal(LANG::get('create_order'));

            $this->_config_seo('title', Lang::get('confirm_order') . ' - ' . Conf::get('site_title'));

            import('init.lib');
            $this->assign('coupon_list', Init_OrderApp::get_available_coupon($goods_info['store_id']));

            $this->assign("exist_delivery_template",$exist_delivery_template);
            $this->assign('goods_info', $goods_info);
            $this->assign($form['data']);
            $this->assign('gids',$_POST['gids']);
            $this->display('order.form.wind.html');
        }
        elseif(IS_POST && $_POST['so'] != '1')
        {   
            /* 在此获取生成订单的两个基本要素：用户提交的数据（POST），商品信息（包含商品列表，商品总价，商品总数量，类型），所属店铺 */
            $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
            if ($goods_info === false)
            {
                /* 购物车是空的 */
                $this->show_warning('goods_empty');
                return;
            }
           
            
            /* 优惠券数据处理 */
            if ($goods_info['allow_coupon'] && isset($_POST['coupon_sn']) && !empty($_POST['coupon_sn']))
            {
                $coupon_sn = trim($_POST['coupon_sn']);
                $coupon_mod =& m('couponsn');
                $coupon = $coupon_mod->get(array(
                    'fields' => 'coupon.*,couponsn.remain_times',
                    'conditions' => "coupon_sn.coupon_sn = '{$coupon_sn}' AND coupon.store_id = " . $store_id,
                    'join'  => 'belongs_to_coupon'));
                if (empty($coupon))
                {
                    $this->show_warning('involid_couponsn');
                    exit;
                }
                if ($coupon['remain_times'] < 1)
                {
                    $this->show_warning("times_full");
                    exit;
                }
                $time = gmtime();
                if ($coupon['start_time'] > $time)
                {
                    $this->show_warning("coupon_time");
                    exit;
                }

                if ($coupon['end_time'] < $time)
                {
                    $this->show_warning("coupon_expired");
                    exit;
                }
                if ($coupon['min_amount'] > $goods_info['amount'])
                {
                    $this->show_warning("amount_short");
                    exit;
                }
                unset($time);
                $goods_info['discount'] = $coupon['coupon_value'];
            }           
            /* 根据商品类型获取对应的订单类型 */
            $goods_type =& gt($goods_info['type']);
            $order_type =& ot($goods_info['otype']);
            
            //代发黑名单检测
            if($_POST['shipping_choice'] == '2' && $_POST['behalf'])
            {
                $check_result = $order_type->_check_behalf_blacklist($_POST['behalf'], $store_id);
                 
                if($check_result !== true)
                {
                    $this->show_warning(sprintf(Lang::get('store_exist_blacklist'),$check_result['store_name'],$check_result['bh_name']),'return_cart_order','index.php?app=cart');
                    return;
                }
            }

            /* 将这些信息传递给订单类型处理类生成订单(你根据我提供的信息生成一张订单) */
            $order_id = $order_type->submit_order(array(
                'goods_info'    =>  $goods_info,      //商品信息（包括列表，总价，总量，所属店铺，类型）,可靠的!
                'post'          =>  $_POST,           //用户填写的订单信息
            ));


            if (!$order_id)
            {
                $this->show_warning($order_type->get_error());
                return;
            }

            /*  检查是否添加收货人地址  */
           /*  if (isset($_POST['save_address']) && (intval(trim($_POST['save_address'])) == 1))
            {
                 $data = array(
                    'user_id'       => $this->visitor->get('user_id'),
                    'consignee'     => trim($_POST['consignee']),
                    'region_id'     => $_POST['region_id'],
                    'region_name'   => $_POST['region_name'],
                    'address'       => trim($_POST['address']),
                    'zipcode'       => trim($_POST['zipcode']),
                    'phone_tel'     => trim($_POST['phone_tel']),
                    'phone_mob'     => trim($_POST['phone_mob']),
                );
                $model_address =& m('address');
                $model_address->add($data);
            } */
            /* 下单完成后清理商品，如清空购物车，或将团购拍卖的状态转为已下单之类的 modify 2015-05-06 tiq */
            $this->_clear_goods($order_id,$goods_info['rec_ids']);

            /* 发送邮件 */
            $model_order =& m('order');

            /* 减去商品库存*/
            $model_order->change_stock('-', $order_id);

            /* 获取订单信息 */
            $order_info = $model_order->get($order_id);

            /* 发送事件 */
            $feed_images = array();
            foreach ($goods_info['items'] as $_gi)
            {
                $feed_images[] = array(
                    'url'   => SITE_URL . '/' . $_gi['goods_image'],
                    'link'  => SITE_URL . '/' . url('app=goods&id=' . $_gi['goods_id']),
                );
            }
            $this->send_feed('order_created', array(
                'user_id'   => $this->visitor->get('user_id'),
                'user_name' => addslashes($this->visitor->get('user_name')),
                'seller_id' => $order_info['seller_id'],
                'seller_name' => $order_info['seller_name'],
                'store_url' => SITE_URL . '/' . url('app=store&id=' . $order_info['seller_id']),
                'images'    => $feed_images,
            ));

            $buyer_address = $this->visitor->get('email');
            $model_member =& m('member');
            $member_info  = $model_member->get($goods_info['store_id']);
            $seller_address= $member_info['email'];

            /*发送给代发下单通知*/
            if (isset($_POST['shipping_choice']) && (intval(trim($_POST['shipping_choice'])) == 2))
            {
                $behalf_info = $model_member->get($_POST['behalf']);
                $behalf_address = $behalf_info['email'];
                $order_info['bh_name'] = $behalf_info['bh_name'];
                //dualven 20150803 close this!
//                $behalf_mail = get_mail('tobehalf_new_order_notify', array('order' => $order_info));
//                $this->_mailto($behalf_address, addslashes($behalf_mail['subject']), addslashes($behalf_mail['message']));
            }

            /* 发送给买家下单通知 */
//            $buyer_mail = get_mail('tobuyer_new_order_notify', array('order' => $order_info));
//            $this->_mailto($buyer_address, addslashes($buyer_mail['subject']), addslashes($buyer_mail['message']));

            /* 发送给卖家新订单通知 */
            $seller_mail = get_mail('toseller_new_order_notify', array('order' => $order_info));
//            $this->_mailto($seller_address, addslashes($seller_mail['subject']), addslashes($seller_mail['message']));

            /* 更新下单次数 */
            $model_goodsstatistics =& m('goodsstatistics');
            $goods_ids = array();
            foreach ($goods_info['items'] as $goods)
            {
                $goods_ids[] = $goods['goods_id'];
                //更新销售量
                $model_goodsstatistics->edit($goods['goods_id'], "sales=sales+{$goods['quantity']}");
            }
            $model_goodsstatistics->edit($goods_ids, 'orders=orders+1');

            /* 到收银台付款 */
            header('Location:index.php?app=cashier&order_id=' . $order_id);
        }
    }

    /**
     *    获取外部传递过来的商品
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function _get_goods_info()
    {
        $return = array(
            'items'     =>  array(),    //商品列表
            'quantity'  =>  0,          //商品总量
            'amount'    =>  0,          //商品总价
            'store_id'  =>  0,          //所属店铺
            'store_name'=>  '',         //店铺名称
            'type'      =>  null,       //商品类型
            'otype'     => $_GET['shipping_choice'] == 1 ? 'normal' : 'behalf',   //订单类型
            'allow_coupon'  => true,    //是否允许使用优惠券
            'rec_ids'=>array(),         //接受页面传入的cart rec_id,允许购买cart店铺部分商品custom by tiq
            'behalf_fee'=>0,            //非免费商品代发费
        );
        switch ($_GET['goods'])
        {
            case 'groupbuy':
                /* 团购的商品 */
                $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
                $user_id  = $this->visitor->get('user_id');
                if (!$group_id || !$user_id)
                {
                    return false;
                }
                /* 获取团购记录详细信息 */
                $model_groupbuy =& m('groupbuy');
                $groupbuy_info = $model_groupbuy->get(array(
                    'join'  => 'be_join, belong_store, belong_goods',
                    'conditions'    => $model_groupbuy->getRealFields("groupbuy_log.user_id={$user_id} AND groupbuy_log.group_id={$group_id} AND groupbuy_log.order_id=0 AND this.state=" . GROUP_FINISHED),
                    'fields'    => 'store.store_id, store.store_name, goods.goods_id, goods.goods_name, goods.default_image, groupbuy_log.quantity, groupbuy_log.spec_quantity, this.spec_price',
                ));

                if (empty($groupbuy_info))
                {
                    return false;
                }

                /* 库存信息 */
                $model_goodsspec = &m('goodsspec');
                $goodsspec = $model_goodsspec->find('goods_id='. $groupbuy_info['goods_id']);

                /* 获取商品信息 */
                $spec_quantity = unserialize($groupbuy_info['spec_quantity']);
                $spec_price    = unserialize($groupbuy_info['spec_price']);
                $amount = 0;
                $groupbuy_items = array();
                $goods_image = empty($groupbuy_info['default_image']) ? Conf::get('default_goods_image') : $groupbuy_info['default_image'];
                foreach ($spec_quantity as $spec_id => $spec_info)
                {
                    $the_price = $spec_price[$spec_id]['price'];
                    $subtotal = $spec_info['qty'] * $the_price;
                    $groupbuy_items[] = array(
                        'goods_id'  => $groupbuy_info['goods_id'],
                        'goods_name'  => $groupbuy_info['goods_name'],
                        'spec_id'  => $spec_id,
                        'specification'  => $spec_info['spec'],
                        'price'  => $the_price,
                        'quantity'  => $spec_info['qty'],
                        'goods_image'  => $goods_image,
                        'subtotal'  => $subtotal,
                        'stock' => $goodsspec[$spec_id]['stock'],
                    );
                    $amount += $subtotal;
                }

                $return['items']        =   $groupbuy_items;
                $return['quantity']     =   $groupbuy_info['quantity'];
                $return['amount']       =   $amount;
                $return['store_id']     =   $groupbuy_info['store_id'];
                $return['store_name']   =   $groupbuy_info['store_name'];
                $return['type']         =   'material';
                $return['otype']        =   'groupbuy';
                $return['allow_coupon'] =   false;
            break;
            default:
                /* 从购物车中取商品 */
                $_GET['store_id'] = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
                $store_id = $_GET['store_id'];
                if (!$store_id){return false;}
                
                //gids by tiq
                $gids = isset($_POST['gids'])?trim($_POST['gids']):'';
                if(!gids){return false;}
                
                $rec_gids = array();
                $gids_arr = explode(',', $gids);
                $gids_arr = array_filter($gids_arr);
                $gids_arr = array_unique($gids_arr);
                if(is_array($gids_arr))
                {
                    foreach ($gids_arr as $key=>$value)
                    {
                        $gids_arr[$key] = explode(":", $value);
                        $rec_gids[] = $gids_arr[$key][1];
                    }
                }
                //gids end

                $cart_model =& m('cart');

                $cart_items      =  $cart_model->find(array(
                    'conditions' => "user_id = " . $this->visitor->get('user_id') . " AND store_id = {$store_id} AND session_id='" . SESS_ID . "'"." AND ".db_create_in($rec_gids,'rec_id'),
                    'join'       => 'belongs_to_goodsspec',
                ));  
                if (empty($cart_items))
                {
                    return false;
                }
                //是否收取代发费
                $return['otype'] == 'behalf' && $levy_behalf_fee = belong_behalfarea($store_id);

                $store_model =& m('store');
                $store_info = $store_model->get($store_id);

                foreach ($cart_items as $rec_id => $cgoods)
                {
                    $return['quantity'] += $cgoods['quantity'];                      //商品总量
                    $return['amount']   += $cgoods['quantity'] * $cgoods['price'];    //商品总价
                    $cart_items[$rec_id]['subtotal']    =   $cgoods['quantity'] * $cgoods['price'];   //小计
                    empty($cgoods['goods_image']) && $cart_items[$rec_id]['goods_image']=Conf::get('default_goods_image');
                    $cart_items[$rec_id]['behalf_fee'] = $levy_behalf_fee === false ? $cgoods['quantity'] * floatval(BEHALF_GOODS_SERVICE_FEE) : 0; //非免费商品代发费
                    //商品价格少于5元不能下单
                   /*  if($goods['price'] < 5){
                        $this->show_warning('price_less_5');
                        return false;
                    } */
                }

                $return['items']        =   $cart_items;
                $return['store_id']     =   $store_id;
                $return['store_name']   =   $store_info['store_name'];
                $return['store_im_qq']  =   $store_info['im_qq']; // tyioocom
                $return['type']         =   'material';
                //$return['otype']        =   'normal';
                $return['rec_ids'] = $rec_gids; //下单后，清除出购物车 tiq
                $return['behalf_fee'] = $levy_behalf_fee === false ? $return['quantity'] * floatval(BEHALF_GOODS_SERVICE_FEE) : 0;
            break;
        }

        return $return;
    }


    /**
     *    下单完成后清理商品
     *
     *    @author    Garbin
     *    @return    void
     */
    function _clear_goods($order_id,$items)
    {
        switch ($_GET['goods'])
        {
            case 'groupbuy':
                /* 团购的商品 */
                $model_groupbuy =& m('groupbuy');
                $model_groupbuy->updateRelation('be_join', intval($_GET['group_id']), $this->visitor->get('user_id'), array(
                    'order_id'  => $order_id,
                ));
            break;
            default://购物车中的商品
                /* 订单下完后清空指定购物车 */
                $_GET['store_id'] = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
                $store_id = $_GET['store_id'];
                if (!$store_id)
                {
                    return false;
                }
                $model_cart =& m('cart');
                $model_cart->drop("store_id = {$store_id} AND session_id='" . SESS_ID . "'"." AND ".db_create_in($items,'rec_id'));
                //优惠券信息处理
                if (isset($_POST['coupon_sn']) && !empty($_POST['coupon_sn']))
                {
                    $sn = trim($_POST['coupon_sn']);
                    $couponsn_mod =& m('couponsn');
                    $couponsn = $couponsn_mod->get("coupon_sn = '{$sn}'");
                    if ($couponsn['remain_times'] > 0)
                    {
                        $couponsn_mod->edit("coupon_sn = '{$sn}'", "remain_times= remain_times - 1");
                    }
                }
            break;
        }
    }
    /**
     * 检查优惠券有效性
     */
    function check_coupon()
    {
        $coupon_sn = $_GET['coupon_sn'];
        $store_id = is_numeric($_GET['store_id']) ? $_GET['store_id'] : 0;
        if (empty($coupon_sn))
        {
            $this->js_result(false);
        }
        $coupon_mod =& m('couponsn');
        $coupon = $coupon_mod->get(array(
            'fields' => 'coupon.*,couponsn.remain_times',
            'conditions' => "coupon_sn.coupon_sn = '{$coupon_sn}' AND coupon.store_id = " . $store_id,
            'join'  => 'belongs_to_coupon'));
        if (empty($coupon))
        {
            $this->json_result(false);
            exit;
        }
        if ($coupon['remain_times'] < 1)
        {
            $this->json_result(false);
            exit;
        }
        $time = gmtime();
        if ($coupon['start_time'] > $time)
        {
            $this->json_result(false);
            exit;
        }


        if ($coupon['end_time'] < $time)
        {
            $this->json_result(false);
            exit;
        }

        // 检查商品价格与优惠券要求的价格

        $model_cart =& m('cart');
        $item_info  = $model_cart->find("store_id={$store_id} AND session_id='" . SESS_ID . "'");
        $price = 0;
        foreach ($item_info as $val)
        {
            $price = $price + $val['price'] * $val['quantity'];
        }
        if ($price < $coupon['min_amount'])
        {
            $this->json_result(false);
            exit;
        }
        $this->json_result(array('res' => true, 'price' => $coupon['coupon_value']));
        exit;

    }

    function _check_beyond_stock($goods_items)
    {
        $goods_beyond_stock = array();
        foreach ($goods_items as $rec_id => $goods)
        {
            if ($goods['quantity'] > $goods['stock'])
            {
                $goods_beyond_stock[$goods['spec_id']] = $goods;
            }
        }
        return $goods_beyond_stock;
    }

    /**
     * 通过代发落获得快递
     * ajax
     */
    function get_deliverys_by_behalf()
    {
        $id = isset($_POST['id'])?intval($_POST['id']):0;
        $quantity = isset($_POST['quantity'])?intval($_POST['quantity']):0;
        if(empty($quantity))
        {
            echo ecm_json_encode(false);
            return;
        }
        $my_behalf_mod = & m('behalf');
        $my_deliverys = $my_behalf_mod->calculate_delivery_fee_bybehalf($id,$quantity);
        $my_deliverys = array_values($my_deliverys);
        if(empty($my_deliverys))
        {
                echo ecm_json_encode(false);
        }
        else
        {
                echo ecm_json_encode($my_deliverys);
        }
    }

    /**
     * 通过代发id得到代发费用类目
     */
    function get_behalf_fee()
    {
        $id = isset($_POST['id'])?intval($_POST['id']):0;
        $category_behalf_mod =& m('category_behalf');
        $category_behalvies = $category_behalf_mod->getAll("select * from ".DB_PREFIX."category_behalf"." where bh_id = {$id} ");
        if(empty($category_behalvies))
        {
                echo ecm_json_encode(false);
        }
        else
        {
                foreach ($category_behalvies as $key=>$val)
                {
                        $gcategory_mod = & m('gcategory');
                        $gcategory =  $gcategory_mod->get("cate_id=".$val['cate_id']);
                        $category_behalvies[$key]['cate_name'] = $gcategory['cate_name'];
                }
                echo ecm_json_encode($category_behalvies);
        }
    }


    

    /**
     * 合并订单，采用代发配送
     */
    function merge_order_pay()
    {
        $this->_check_member_profile();
        $merge_goodsinfo = $this->_get_merge_goods_info();
        $goods_amount = 0;
//dump($merge_goodsinfo);
        $store_ids = array_keys($merge_goodsinfo);

        /*  检查库存 */
        foreach ($merge_goodsinfo as $goods_info)
        {
                $goods_amount += $goods_info['amount'];
               /*
                $goods_beyond = $this->_check_beyond_stock($goods_info['items']);
                if ($goods_beyond)
                {
                        $str_tmp = '';
                        foreach ($goods_beyond as $goods)
                        {
                                $str_tmp .= '<br /><br />' . $goods['goods_name'] . '&nbsp;&nbsp;' . $goods['specification'] . '&nbsp;&nbsp;' . Lang::get('stock') . ':' . $goods['stock'];
                        }
                        $this->show_warning(sprintf(Lang::get('quantity_beyond_stock'), $str_tmp));
                        return;
                }
                */
        } 
        

        /* 检查买家的收货地址，因为用到了运费模板，如果没有收货地址，无法读取运费  tyioocom delivery */
        $address_model =& m('address');
        if(!$address_model->get('user_id=' . $this->visitor->get('user_id'))){
                $this->show_warning('请先添加你的收货地址', '添加地址', 'index.php?app=my_address');
                return;
        }
        /* 在此获取生成订单的两个基本要素：用户提交的数据（POST），商品信息（包含商品列表，商品总价，商品总数量，类型），所属店铺 */
        if ($merge_goodsinfo === false || !is_array($merge_goodsinfo))
        {
            /* 购物车是空的 */
            $this->show_warning('goods_empty');
            return;
        }
        //订单总商品数
        $merge_goods_quantity = 0;
        $merge_behalf_amount = 0;
        
        foreach ($merge_goodsinfo as $mgoods)
        {
            $merge_goods_quantity += $mgoods['quantity'];
            $merge_behalf_amount += $mgoods['behalf_fee'];
        }
        if($merge_goods_quantity == 0)
        {
            /* 购物车是空的 */
            $this->show_warning('goods_empty');
            return;
        }

        if (IS_POST && $_POST['so'] == '1')
        {
                $form = $this->_get_merge_market_behalfs(array_keys($merge_goodsinfo));
                if(!empty($form['data']['my_behalfs']))
                {
                        $this->assign('behalfs',$form['data']['my_behalfs']);
                        //第一代发对应的快递传过去
                        $my_behalf_mod = & m('behalf');
                        $my_behalfs = array_values($form['data']['my_behalfs']);
                        $first_behalf = $my_behalfs[0]['bh_id'];
                        $my_deliverys = $my_behalf_mod->calculate_delivery_fee_bybehalf($first_behalf,$merge_goods_quantity);
                        $my_deliverys = !empty($my_deliverys)?array_values($my_deliverys):$my_deliverys;
                        $this->assign('deliverys',$my_deliverys);
                }
                else
                {
                    $this->assign('not_exist_behalf_tips',$form['data']['my_behalfs_reason']);
                }

                if ($form === false)
                {
                        return;
                }
                $this->_curlocal(
                                LANG::get('create_order')
                );

                $this->_config_seo('title', Lang::get('confirm_order') . ' - ' . Conf::get('site_title'));

                //import('init.lib');
                //$this->assign('coupon_list', Init_OrderApp::get_available_coupon($goods_info['store_id']));
                
                $this->assign("goods_amount",$goods_amount);
                $this->assign("store_quantity",count($merge_goodsinfo));
                $this->assign("merge_goods_quantity",$merge_goods_quantity);
                $this->assign("merge_behalf_amount",$merge_behalf_amount);
                $this->assign('merge_goodsinfo', $merge_goodsinfo);
                $this->assign($form['data']);
                $this->assign('gids',$_POST['gids']);
                $this->display('order.mergebehalf.form.wind.html');

        }
        elseif(IS_POST && $_POST['so'] != '1')
        {

                /* 优惠券数据暂不处理 ，不传输优惠券数据*/

                $goods_info = array(
                                'items'     =>  array(),    //商品列表
                                'quantity'  =>  0,          //商品总量
                                'amount'    =>  0,          //商品总价
                                'store_id'  =>  0,          //所属店铺
                                'store_name'=>  '',         //店铺名称
                                'type'      =>  null,       //商品类型
                                'otype'     =>  'behalf',   //订单类型
                                'allow_coupon'  => false,    //是否允许使用优惠券
                                'rec_ids'  => array(),
                                'behalf_fee' => 0           //商品代发费
                );

                foreach ($merge_goodsinfo as $key=>$value)
                {
                        if(!empty($value['items']))
                        {
                                foreach ($value['items'] as $goods_id=>$goods_value)
                                {
                                        $goods_info['items'][$goods_id] = $goods_value;
                                }
                        }
                        $goods_info['quantity'] = intval($goods_info['quantity']) + $value['quantity'];
                        $goods_info['amount'] = intval($goods_info['amount']) + $value['amount'];
                        $goods_info['store_name'] = $goods_info['store_name']." ".$value['store_name'];
                        $goods_info['type'] = $value['type'];
                        $goods_info['rec_ids'] = $value['rec_ids'];
                        $goods_info['behalf_fee'] = floatval($goods_info['behalf_fee']) + floatval($value['behalf_fee']);
                }
               
                
                /* 根据商品类型获取对应的订单类型 */
                $goods_type =& gt($goods_info['type']);
                $order_type =& ot($goods_info['otype']);
                
                //代发黑名单检测
                if($_POST['shipping_choice'] == '2' && $_POST['behalf'])
                {
                    $check_result = $order_type->_check_behalf_blacklist($_POST['behalf'], array_keys($merge_goodsinfo));
                     
                    if($check_result !== true)
                    {
                        $this->show_warning(sprintf(Lang::get('store_exist_blacklist'),$check_result['store_name'],$check_result['bh_name']),'return_cart_order','index.php?app=cart');
                        return;
                    }
                }

                /* 将这些信息传递给订单类型处理类生成订单(你根据我提供的信息生成一张订单) */
                $order_id = $order_type->submit_merge_order(array(
                                'goods_info'    =>  $goods_info,      //商品信息（包括列表，总价，总量，所属店铺，类型）,可靠的!
                                'post'          =>  $_POST,           //用户填写的订单信息
                ));

                if (!$order_id)
                {
                        $this->show_warning($order_type->get_error());
                        return;
                }

                /*  检查是否添加收货人地址  */
                /* if (isset($_POST['save_address']) && (intval(trim($_POST['save_address'])) == 1))
                {
                    $data = array(
                            'user_id'       => $this->visitor->get('user_id'),
                            'consignee'     => trim($_POST['consignee']),
                            'region_id'     => $_POST['region_id'],
                            'region_name'   => $_POST['region_name'],
                            'address'       => trim($_POST['address']),
                            'zipcode'       => trim($_POST['zipcode']),
                            'phone_tel'     => trim($_POST['phone_tel']),
                            'phone_mob'     => trim($_POST['phone_mob']),
                    );
                    $model_address =& m('address');
                    $model_address->add($data);
                } */

                /* 下单完成后清理商品，如清空购物车，或将团购拍卖的状态转为已下单之类的 */
                $model_cart =& m('cart');
                foreach ($store_ids as $store_id)
                {
                        $model_cart->drop("store_id = {$store_id} AND session_id='" . SESS_ID . "'"." AND ".db_create_in($goods_info['rec_ids'],'rec_id'));
                }

                /* 发送邮件 */
                $model_order =& m('order');

                /* 减去商品库存 */
                $model_order->change_stock('-', $order_id);

                /* 获取订单信息 */
                $order_info = $model_order->get($order_id);


                $buyer_address = $this->visitor->get('email');

                /*发送给代发下单通知*/
                $model_member =& m('member');

                $behalf_info = $model_member->get($_POST['behalf']);
                $behalf_address = $behalf_info['email'];
//dualven 20150803 close this!
//                $behalf_mail = get_mail('tobehalf_new_order_notify', array('order' => $order_info));
//                $this->_mailto($behalf_address, addslashes($behalf_mail['subject']), addslashes($behalf_mail['message']));


                /* 发送给买家下单通知 */
                //$buyer_mail = get_mail('tobuyer_new_order_notify', array('order' => $order_info));
                //$this->_mailto($buyer_address, addslashes($buyer_mail['subject']), addslashes($buyer_mail['message']));

                /* 更新下单次数 */
                $model_goodsstatistics =& m('goodsstatistics');
                $goods_ids = array();
                foreach ($goods_info['items'] as $goods)
                {
                        $goods_ids[] = $goods['goods_id'];
                        //更新销售量
                        $model_goodsstatistics->edit($goods['goods_id'], "sales=sales+{$goods['quantity']}");
                }
                $model_goodsstatistics->edit($goods_ids, 'orders=orders+1');

                /* 到收银台付款 */
                header('Location:index.php?app=cashier&order_id=' . $order_id);
        }

    }

    /**
     * 取得购物车中所有的商品
     * @return boolean|multitype:multitype:multitype: number string NULL boolean unknown mixed
     */
    function _get_merge_goods_info()
    {
        $data = array();

        //gids by tiq
        $gids = isset($_POST['gids'])?trim($_POST['gids']):'';
        if(!gids){ return false; }
        
        $rec_gids = array();
        $gids_arr = explode(',', $gids);
        $gids_arr = array_filter($gids_arr);
        $gids_arr = array_unique($gids_arr);
        if(is_array($gids_arr))
        {
            foreach ($gids_arr as $key=>$value)
            {
                $gids_arr[$key] = explode(":", $value);
                $rec_gids[] = $gids_arr[$key][1];
            }
        }
        //gids end

        // 存放合并订单中的store_id
        $cart_store = array();

        $cart_model =& m('cart');

        $cart_storeids = $cart_model->find(array(
                'conditions' => "user_id = " . $this->visitor->get('user_id') . " AND session_id='" . SESS_ID . "'"." AND ".db_create_in($rec_gids,'rec_id'),
                'fields' => 'store_id',
        ));
        if (empty($cart_storeids))
        {
                /* 购物车是空的 */
                $this->show_warning('goods_empty');
                return false;
        }

        foreach ($cart_storeids as $key=>$value)
        {
                if(!in_array($value['store_id'], $cart_store))
                {
                        $cart_store[] = $value['store_id'];
                }
        }

        //不同店铺
        foreach ($cart_store as $key=>$store_id)
        {
                $return = array(
                                'items'     =>  array(),    //商品列表
                                'quantity'  =>  0,          //商品总量
                                'amount'    =>  0,          //商品总价
                                'store_id'  =>  0,          //所属店铺
                                'store_name'=>  '',         //店铺名称
                                'type'      =>  null,       //商品类型
                                'otype'     =>  'behalf',   //订单类型
                                'allow_coupon'  => true,    //是否允许使用优惠券
                                'rec_ids'=>array(),
                                'behalf_fee' => 0           //收取商品代发费
                );

                $cart_items      =  $cart_model->find(array(
                                'conditions' => "user_id = " . $this->visitor->get('user_id') . " AND store_id = {$store_id} AND session_id='" . SESS_ID . "'"." AND ".db_create_in($rec_gids,'rec_id'),
                                'join'       => 'belongs_to_goodsspec',
                ));

                $store_model =& m('store');
                $store_info = $store_model->get($store_id);
                
                //是否收取代发费
                $levy_behalf_fee = belong_behalfarea($store_id);

                foreach ($cart_items as $rec_id => $cgoods)
                {
                        $return['quantity'] += $cgoods['quantity'];                      //商品总量
                        $return['amount']   += $cgoods['quantity'] * $cgoods['price'];    //商品总价
                        $cart_items[$rec_id]['subtotal']    =   $cgoods['quantity'] * $cgoods['price'];   //小计
                        empty($cgoods['goods_image']) && $cart_items[$rec_id]['goods_image']=Conf::get('default_goods_image');
                        $cart_items[$rec_id]['behalf_fee'] = $levy_behalf_fee === false ? $cgoods['quantity'] * floatval(BEHALF_GOODS_SERVICE_FEE) : 0; //非免费商品代发费
                        /* if($goods['price'] < 5)
                        {
                            $this->show_warning('price_less_5');
                            return;
                        } */
                }

                $return['items']        =   $cart_items;
                $return['store_id']     =   $store_id;
                $return['store_name']   =   $store_info['store_name'];
                $return['store_im_qq']  =   $store_info['im_qq']; // tyioocom
                $return['type']         =   'material';
                $return['otype']        =   'behalf';
                $return['rec_ids'] = $rec_gids;
                $return['behalf_fee'] = $levy_behalf_fee === false ? $return['quantity'] * floatval(BEHALF_GOODS_SERVICE_FEE) : 0;

                $data[$store_id] = $return;
        }
        //$data['rec_ids'] = $rec_gids;
        return $data;
    }

    /**
     * 合并市场代发,合并订单中有代发不拿货的市场，则不显示代发。2015-11-07
     * @return void|multitype:multitype:Ambigous <string, unknown> NULL multitype:unknown  Ambigous <multitype:, unknown>
     */
    function _get_merge_market_behalfs($store_ids)
    {
        $data = array();
        $visitor =& env('visitor');

        /* 获取我的收货地址 */
        $data['my_address']  = & m('address')->find('user_id=' . $visitor->get('user_id'));
        $data['addresses']  =   ecm_json_encode($data['my_address']);
        $regions =& m('region')->get_list(0);
        if ($regions)
        {
                $tmp  = array();
                foreach ($regions as $key => $value)
                {
                        $tmp[$key] = $value['region_name'];
                }
                $regions = $tmp;
        }
        $data['regions']            = $regions;

        if(empty($store_ids))  return;

        $interset_behalfs = $merge_behalfs = $bh_ids = array();
        $my_market_mod = & m('market');

        foreach ($store_ids as $store_id)
        {
                $my_storeinfo = & m('store')->get($store_id);
                //取得市场id
                $my_mk_id = $my_storeinfo['mk_id'];
                if(empty($my_mk_id))
                {
                        //return;
                        continue;
                }

                /*市场楼层id,应得到商城id*/
                $my_market = $my_market_mod->get($my_mk_id);
                $my_market_layer = $my_market_mod->get_layer($my_mk_id);
                if($my_market_layer == 3)
                {
                        $my_mk_id = $my_market['parent_id'];
                }
                if($my_market_layer == 1)
                {
                        $temp_array = array();
                        $my_mk_id = $my_market_mod->get_list($my_mk_id);
                        foreach($my_mk_id as $value)
                        {
                                $temp_array[] = $value['mk_id'];
                        }
                        $my_mk_id = $temp_array;
                }
                $my_behalfs = $my_market_mod->getRelatedData('belongs_to_behalf',$my_mk_id);
                $my_behalfs = !empty($my_behalfs)?array_values($my_behalfs):array();

                if(!empty($my_behalfs))
                {
                        foreach ($my_behalfs as $key=>$behalf)
                        {
                            if(!in_array($behalf['bh_id'], $bh_ids))
                            {
                                $interset_behalfs[$behalf['bh_id']] = 1;
                                $bh_ids[] = $behalf['bh_id'];
                                $merge_behalfs[$behalf['bh_id']] = $behalf;
                            }
                            else 
                            {
                                $interset_behalfs[$behalf['bh_id']] += 1;
                            }
                            
                        }
                }

        }

        if(!empty($interset_behalfs))
        {
                foreach ($interset_behalfs as $bh_id=>$v)
                {
                    if(count($store_ids) != $v)
                    {
                        unset($merge_behalfs[$bh_id]);
                    }
                }
                $my_behalfs = $merge_behalfs;
        }
       
        /* $is_inbehalfarea = true;//默认店铺在代发区
        foreach ($store_ids as $sid)
        {
            if(belong_behalfarea($sid) === false)
            {
                $is_inbehalfarea = false;
                $store_tmp_info= & m('store')->get($sid);
                $data['my_behalfs_reason'] = sprintf(Lang::get('store_not_inbehalfarea'),$store_tmp_info['store_name']);
            }
        }
        if($is_inbehalfarea === false)
        {
            $data['my_behalfs'] = false;            
        }
        else
        { */
            //随机排列代发
            shuffle($my_behalfs);
            $data['my_behalfs'] = $my_behalfs;
            if(empty($my_behalfs)) $data['my_behalfs_reason'] = Lang::get('stores_not_exist_morebehalfs');
        //}
        //随机排列代发       
        return array('data' => $data);

    }
    

    /**
     *   强制用户 下单前，填写个人联系方式：qq,ww,tel
     */
    function _check_member_profile()
    {

        $ms =& ms();    //连接用户系统
        $mprofile = $ms->user->_local_get(array(
                'conditions'=>'user_id='.$this->visitor->get('user_id'),
                'fields'=>'im_qq,im_aliww,phone_mob',
        ));
        if(!$mprofile['im_qq'] || !$mprofile['im_aliww'] || !$mprofile['phone_mob'])
        {
            $this->show_warning('请完善个人资料，方便我们为您发货，谢谢', '填写基本信息', 'index.php?app=member&act=profile');
            return false;
        }
    }
    

}
?>
