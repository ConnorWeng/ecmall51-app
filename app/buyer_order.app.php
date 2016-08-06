<?php

/**
 *    买家的订单管理控制器
 *
 *    @author    Garbin
 *    @usage    none
 */
class Buyer_orderApp extends MemberbaseApp
{
    function index()
    {
        /* 获取订单列表 */
        $this->_get_orders();

        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member',
                         LANG::get('my_order'), 'index.php?app=buyer_order',
                         LANG::get('order_list'));

        /* 当前用户中心菜单 */
        $type = (isset($_GET['type']) && $_GET['type'] != '') ? trim($_GET['type']) : 'all_orders';
        $this->_curmenu($type);
        $this->_curitem('my_order');
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('my_order'));
        $this->import_resource(array(
            'script' => array(
                array(
                    'path' => 'dialog/dialog.js',
                    'attr' => 'id="dialog_js"',
                ),
                array(
                    'path' => 'jquery.ui/jquery.ui.js',
                    'attr' => '',
                ),
                array(
                    'path' => 'jquery.ui/i18n/' . i18n_code() . '.js',
                    'attr' => '',
                ),
                array(
                    'path' => 'jquery.plugins/jquery.validate.js',
                    'attr' => '',
                ),
            ),
            'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
        ));


        /* 显示订单列表 */
        $this->display('buyer_order.index.html');
    }
    /**
     *    查看订单详情
     *
     *    @author    Garbin
     *    @return    void
     */
    function view()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $model_order =& m('order');
        //$order_info  = $model_order->get("order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id'));
        $order_info = $model_order->get(array(
            'fields'        => "*, order.add_time as order_add_time",
            'conditions'    => "order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id'),
            //'join'          => 'belongs_to_store',
            ));
        if (!$order_info)
        {
            $this->show_warning('no_such_order');

            return;
        }

        /* 团购信息 */
        if ($order_info['extension'] == 'groupbuy')
        {
            $groupbuy_mod = &m('groupbuy');
            $group = $groupbuy_mod->get(array(
                'join' => 'be_join',
                'conditions' => 'order_id=' . $order_id,
                'fields' => 'gb.group_id',
            ));
            $this->assign('group_id',$group['group_id']);
        }

        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member',
                         LANG::get('my_order'), 'index.php?app=buyer_order',
                         LANG::get('view_order'));

        /* 当前用户中心菜单 */
        $this->_curitem('my_order');

        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('order_detail'));

        /* 调用相应的订单类型，获取整个订单详情数据 */
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
                'fields'        => 'sku',
        ));
         ////商家编码
        $model_goodsattr =& m('goodsattr');
        foreach ($order_detail['data']['goods_list'] as $key => $goods)
        {
            $order_detail['data']['goods_list'][$key]['sku'] = $spec_info[$goods['spec_id']]['sku'];
            if(!$order_detail['data']['goods_list'][$key]['sku'])
            {
                $order_detail['data']['goods_list'][$key]['sku'] = getHuoHao($goods['goods_name']);
                if(!$order_detail['data']['goods_list'][$key]['sku'])
                {
                    $goods_AttrModel = &m('goodsattr');
                    $attrs = $goods_AttrModel->get(array(
                            'conditions' => "goods_id = ".$goods['goods_id']." AND attr_id = 13021751",
                    ));
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
        foreach ($order_detail['data']['goods_list'] as $key => $goods)
        {
            if(!empty($goods['goods_id']))
            {
                $result = $goods_model->get(array(
                        'fields'=>'store_id',
                        'conditions'=>'goods_id='.$goods['goods_id'],
                ));
                if($result['store_id'] &&!in_array($result['store_id'], $stores))
                {
                    $stores[] = $result['store_id'];
                    $data[$result['store_id']]['store_info'] = $store_model->get($result['store_id']);
                    $data[$result['store_id']]['goods_list'][] = $goods;
                }
                else
                {
                    $data[$result['store_id']]['goods_list'][] = $goods;
                }
            }

        }

        //
        $model_orderrefund = & m('orderrefund');
        $refunds=$model_orderrefund->get(array(
                'conditions'=>'order_id='.$order_info['order_id'].' AND receiver_id='.$order_info['bh_id'].' AND closed=0 AND type=1',
        ));
        if($refunds)
        {
            $model_behalf = & m('behalf');
            $refunds_behalf = $model_behalf->get($refunds['receiver_id']);
            $refunds['receiver_name'] = $refunds_behalf['bh_name'];
        }
        $apply_fees=$model_orderrefund->get(array(
                'conditions'=>'order_id='.$order_info['order_id'].' AND receiver_id='.$order_info['buyer_id'].' AND closed=0 AND type=2',
        ));



        $this->import_resource(array(
                'script' => array(
                        array(
                                'path' => 'dialog/dialog.js',
                                'attr' => 'id="dialog_js"',
                        ),
                        array(
                                'path' => 'jquery.ui/jquery.ui.js',
                                'attr' => '',
                        ),
                        array(
                                'path' => 'jquery.ui/i18n/' . i18n_code() . '.js',
                                'attr' => '',
                        ),
                        array(
                                'path' => 'jquery.plugins/jquery.validate.js',
                                'attr' => '',
                        ),
                ),
                'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
        ));
        $this->assign('merge_sgoods',$data);
        $this->assign('order', $order_info);
        $this->assign("refunds",$refunds);
        $this->assign("apply_fees",$apply_fees);
        $this->assign($order_detail['data']);
        $this->display('buyer_order.view.html');
    }

    /**
     *    取消订单
     *
     *    @author    Garbin
     *    @return    void
     */
    function cancel_order()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id)
        {
            echo Lang::get('no_such_order');

            return;
        }
        $model_order    =&  m('order');
        /* 只有待付款的订单可以取消 */
        $order_info     = $model_order->get("order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id') . " AND status " . db_create_in(array(ORDER_PENDING, ORDER_SUBMITTED)));
        if (empty($order_info))
        {
            echo Lang::get('no_such_order');

            return;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->display('buyer_order.cancel.html');
        }
        else
        {
            $model_order->edit($order_id, array('status' => ORDER_CANCELED));
            if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());

                return;
            }

            /* 加回商品库存 */
            $model_order->change_stock('+', $order_id);
            $cancel_reason = (!empty($_POST['remark'])) ? $_POST['remark'] : $_POST['cancel_reason'];
            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                'order_id'  => $order_id,
                'operator'  => addslashes($this->visitor->get('user_name')),
                'order_status' => order_status($order_info['status']),
                'changed_status' => order_status(ORDER_CANCELED),
                'remark'    => $cancel_reason,
                'log_time'  => gmtime(),
            ));

            /* 发送给卖家订单取消通知 */
            $model_member =& m('member');
            $seller_info   = $model_member->get($order_info['seller_id']);
            $mail = get_mail('toseller_cancel_order_notify', array('order' => $order_info, 'reason' => $_POST['remark']));
            //$this->_mailto($seller_info['email'], addslashes($mail['subject']), addslashes($mail['message']));

            $new_data = array(
                'status'    => Lang::get('order_canceled'),
                'actions'   => array(), //取消订单后就不能做任何操作了
            );

            /* 如果是关联到淘宝订单的话, 需要同时修改淘宝订单的状态 */
            $ordervendor_mod = &m('ordervendor');
            $ordervendor_mod->edit("ecm_order_id={$order_id}", array(
                'status' => VENDOR_ORDER_UNHANDLED,
                'ecm_order_id' => 0));

            $this->pop_warning('ok');
        }

    }

    /**
     *    确认订单(确认收货)
     *
     *    @author    Garbin
     *    @return    void
     */
    function confirm_order()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id)
        {
            echo Lang::get('no_such_order');

            return;
        }
        $model_order    =&  m('order');
        /* 只有已发货的订单可以确认 */
        $order_info     = $model_order->get("order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id') . " AND status=" . ORDER_SHIPPED);
        if (empty($order_info))
        {
            echo Lang::get('no_such_order');

            return;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->display('buyer_order.confirm.html');
        }
        else
        {
            $model_order->edit($order_id, array('status' => ORDER_FINISHED, 'finished_time' => gmtime()));
            if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());
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
                'log_time'  => gmtime(),
            ));

    /*商付通v2.2.1  更新商付通定单状态 确认收货 开始*/
    $my_money_mod =& m('my_money');
    $my_moneylog_mod =& m('my_moneylog');
    $my_moneylog_row=$my_moneylog_mod->getrow("select * from ".DB_PREFIX."my_moneylog where order_id='$order_id' and s_and_z=2 and caozuo=20");
    //$money=$my_moneylog_row['money'];//定单价格
    $money = $order_info['order_amount'];
    $sell_user_id=$my_moneylog_row['seller_id'];//卖家ID
    if($my_moneylog_row['order_id']==$order_id)
    {
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
            'money'=>$new_money,
            'money_dj'=>$new_money_dj,
        );
        $new_buy_money_array = array(
            'money'=>$new_buy_money,
        );
        if($new_money_dj > 0 ){
            $my_money_mod->edit('user_id='.$sell_user_id,$new_money_array);
        }

//        $my_money_mod->edit('user_id='.$this->visitor->get('user_id'),$new_buy_money_array);
        //更新商付通log为 定单已完成
        $my_moneylog_mod->edit('order_id='.$order_id,array('caozuo'=>40));
    }
    else{
//        $buy_user_id = $this->visitor->get('user_id');
//        $buy_money_row=$my_money_mod->getrow("select * from ".DB_PREFIX."my_money where user_id='$buy_user_id'");
//        $buy_money = $buy_money_row['money'];  //买家资金
//        $new_buy_money = $buy_money;
//        $new_buy_money_array = array(
//            'money'=>$new_buy_money,
//        );
//        $my_money_mod->edit('user_id='.$this->visitor->get('user_id'),$new_buy_money_array);
    }
    /*商付通v2.2.1  更新商付通定单状态 确认收货 结束*/

            /* 发送给卖家买家确认收货邮件，交易完成 */
            $model_member =& m('member');
            $seller_info   = $model_member->get($order_info['seller_id']);
            $mail = get_mail('toseller_finish_notify', array('order' => $order_info));
            //$this->_mailto($seller_info['email'], addslashes($mail['subject']), addslashes($mail['message']));

            $new_data = array(
                'status'    => Lang::get('order_finished'),
                'actions'   => array('evaluate'),
            );

            /* 更新累计销售件数 */
            /* $model_goodsstatistics =& m('goodsstatistics');
            $model_ordergoods =& m('ordergoods');
            $order_goods = $model_ordergoods->find("order_id={$order_id}");
            foreach ($order_goods as $goods)
            {
                $model_goodsstatistics->edit($goods['goods_id'], "sales=sales+{$goods['quantity']}");
            } */

            $this->pop_warning('ok','','index.php?app=buyer_order&act=evaluate&order_id='.$order_id);;
        }

    }

    /**
     *    给卖家评价
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function evaluate()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id)
        {
            $this->show_warning('no_such_order');

            return;
        }

        /* 验证订单有效性 */
        $model_order =& m('order');
        $order_info  = $model_order->get("order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id'));
        if (!$order_info)
        {
            $this->show_warning('no_such_order');

            return;
        }
        if ($order_info['status'] != ORDER_FINISHED)
        {
            /* 不是已完成的订单，无法评价 */
            $this->show_warning('cant_evaluate');

            return;
        }
        if ($order_info['evaluation_status'] != 0)
        {
            /* 已评价的订单 */
            $this->show_warning('already_evaluate');

            return;
        }
        $model_ordergoods =& m('ordergoods');
        /* $model_orderbehalfs =& m('orderbehalfs');
        $model_behalf =& m('behalf'); */

        if (!IS_POST)
        {
            /* 显示评价表单 */
            /* 获取订单商品 */
            $goods_list = $model_ordergoods->find("order_id={$order_id}");
           /*  $behalf_list = $model_orderbehalfs->find("order_id={$order_id}");
            if(!empty($behalf_list))
            {
                $behalf_list = array_values($behalf_list);
                $bh_id = $behalf_list[0]['bh_id'];
                $behalf = $model_behalf->find("bh_id = {$bh_id}");
                $behalf = array_values($behalf);
                $behalf = array_merge($behalf[0],$behalf_list[0]);
            } */
            foreach ($goods_list as $key => $goods)
            {
                empty($goods['goods_image']) && $goods_list[$key]['goods_image'] = Conf::get('default_goods_image');
            }
            $this->_curlocal(LANG::get('member_center'), 'index.php?app=member',
                             LANG::get('my_order'), 'index.php?app=buyer_order',
                             LANG::get('evaluate'));
            $this->assign('goods_list', $goods_list);
           // $this->assign('behalf_list',$behalf);
            $this->assign('order', $order_info);

            $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('credit_evaluate'));
            $this->display('buyer_order.evaluate.html');
        }
        else
        {
            $evaluations = array();
            /* 写入评价 */
            foreach ($_POST['evaluations'] as $rec_id => $evaluation)
            {
                if ($evaluation['evaluation'] <= 0 || $evaluation['evaluation'] > 3)
                {
                    $this->show_warning('evaluation_error');

                    return;
                }
                switch ($evaluation['evaluation'])
                {
                    case 3:
                        $credit_value = 1;
                    break;
                    case 1:
                        $credit_value = -1;
                    break;
                    default:
                        $credit_value = 0;
                    break;
                }
                $evaluations[intval($rec_id)] = array(
                    'evaluation'    => $evaluation['evaluation'],
                    'comment'       => addslashes($evaluation['comment']),
                    'credit_value'  => $credit_value
                );
            }
            $goods_list = $model_ordergoods->find("order_id={$order_id}");
            foreach ($evaluations as $rec_id => $evaluation)
            {
                $model_ordergoods->edit("rec_id={$rec_id} AND order_id={$order_id}", $evaluation);
                $goods_url = SITE_URL . '/' . url('app=goods&id=' . $goods_list[$rec_id]['goods_id']);
                $goods_name = $goods_list[$rec_id]['goods_name'];
                $this->send_feed('goods_evaluated', array(
                    'user_id'   => $this->visitor->get('user_id'),
                    'user_name'   => $this->visitor->get('user_name'),
                    'goods_url'   => $goods_url,
                    'goods_name'   => $goods_name,
                    'evaluation'   => Lang::get('order_eval.' . $evaluation['evaluation']),
                    'comment'   => $evaluation['comment'],
                    'images'    => array(
                        array(
                            'url' => SITE_URL . '/' . $goods_list[$rec_id]['goods_image'],
                            'link' => $goods_url,
                        ),
                    ),
                ));
            }

            /*写入代发评价*/
           /*  $bevaluations = array();
            foreach ($_POST['bevaluations'] as $rec_id => $evaluation)
            {
                if ($evaluation['evaluation'] <= 0 || $evaluation['evaluation'] > 3)
                {
                    $this->show_warning('evaluation_error');

                    return;
                }
                switch ($evaluation['evaluation'])
                {
                    case 3:
                        $credit_value = 1;
                        break;
                    case 1:
                        $credit_value = -1;
                        break;
                    default:
                        $credit_value = 0;
                        break;
                }
                $bevaluations[intval($rec_id)] = array(
                        'evaluation'    => $evaluation['evaluation'],
                        'comment'       => $evaluation['comment'],
                        'credit_value'  => $credit_value
                );
            }


           foreach ($bevaluations as $rec_id => $evaluation)
           {
              $model_orderbehalfs->edit("rec_id={$rec_id} AND order_id={$order_id}", $evaluation);
           } */

            /* 更新订单评价状态 */
            $model_order->edit($order_id, array(
                'evaluation_status' => 1,
                'evaluation_time'   => gmtime()
            ));

            /* 更新卖家信用度及好评率 */
            $model_store =& m('store');
            $model_store->edit($order_info['seller_id'], array(
                'credit_value'  =>  $model_store->recount_credit_value($order_info['seller_id']),
                'praise_rate'   =>  $model_store->recount_praise_rate($order_info['seller_id'])
            ));

            /* 更新商品评价数 */
            $model_goodsstatistics =& m('goodsstatistics');
            $goods_ids = array();
            foreach ($goods_list as $goods)
            {
                $goods_ids[] = $goods['goods_id'];
            }
            $model_goodsstatistics->edit($goods_ids, 'comments=comments+1');


            $this->show_message('evaluate_successed',
                'back_list', 'index.php?app=buyer_order');
        }
    }

    /**
     *    获取订单列表
     *
     *    @author    Garbin
     *    @return    void
     */
    function _get_orders()
    {
        $page = $this->_get_page(10);
        $model_order =& m('order');
        $model_ordergoods =& m('ordergoods');
        $model_goods =& m('goods');
        $model_gs =& m('goodsspec');
        $model_goodsattr =& m('goodsattr');
        $model_orderrefund=& m('orderrefund');//behalf
        $model_orderstorerefund = & m('orderstorerefund');//store
        $model_orderextm = & m('orderextm');

        $model_ordervendor =& m('ordervendor');

        @!$_GET['type'] && $_GET['type'] = 'all_orders';
        $con = array(
            array(      //按订单状态搜索
                'field' => 'status',
                'name'  => 'type',
                'handler' => 'order_status_translator',
            ),
            array(      //按店铺名称搜索
                'field' => 'seller_name',
                'equal' => 'LIKE',
            ),
            array(      //按下单时间搜索,起始时间
                'field' => 'add_time',
                'name'  => 'add_time_from',
                'equal' => '>=',
                'handler'=> 'gmstr2time',
            ),
            array(      //按下单时间搜索,结束时间
                'field' => 'add_time',
                'name'  => 'add_time_to',
                'equal' => '<=',
                'handler'=> 'gmstr2time_end',
            ),
            array(      //按订单号
                'field' => 'order_sn',
            ),
        );

        //商品名称查询
        if(@$_GET['goods_name'])
        {
            //找出代发所有订单
            $query_goods_name = trim($_GET['goods_name']);
            $query_goods_name_orders = $model_order->find(array(
                    'conditions'=>"buyer_id=".$this->visitor->get('user_id'),
                    'fields'=>'order_id',
            ));
            if(!empty($query_goods_name_orders))
            {
                $query_goods_name_order_ids = array();
                foreach ($query_goods_name_orders as $value)
                {
                    $query_goods_name_order_ids[] = $value['order_id'];
                }
                //找出 有传入关键字的订单
                $query_order_goods = $model_ordergoods->find(array(
                        'conditions'=>db_create_in($query_goods_name_order_ids,'order_id')." AND goods_name like '%".$query_goods_name."%'",
                        'fields'=>'order_id',
                ));
                $query_goods_name_order_result = array();
                foreach ($query_order_goods as $value)
                {
                    if(!in_array($value['order_id'], $query_goods_name_order_result))
                        $query_goods_name_order_result[] = $value['order_id'];
                }
                $this->assign("query_goods_name",$query_goods_name);
                if($query_goods_name_order_result)
                {
                    $query_goods_condition = " AND ".db_create_in($query_goods_name_order_result,'order_alias.order_id');
                }
                else
                {
                    return;
                }
                //dump($query_goods_name_order_result);
            }
        }
        //商家编码查询
        if(@$_GET['goods_seller_bm'])
        {
            //找出代发所有订单
            $query_goods_seller_bm = trim($_GET['goods_seller_bm']);
            $query_goods_seller_bm_orders = $model_order->find(array(
                    'conditions'=>"buyer_id=".$this->visitor->get('user_id'),
                    'fields'=>'order_id',
            ));
            if(!empty($query_goods_seller_bm_orders))
            {
                $query_goods_seller_bm_orders_ids = array();
                foreach ($query_goods_seller_bm_orders as $value)
                {
                    $query_goods_seller_bm_orders_ids[] = $value['order_id'];
                }
                //找出 有传入关键字的订单
                ////商家编码
                /* $goods_AttrModel = & m('goodsattr');
                 $attrs = $goods_AttrModel->find(array(
                        'conditions' => "attr_value like '%".$query_goods_seller_bm."%' AND attr_id = 1",
                        'fields'=>'goods_id',
                 )); */
                $attrs = $model_goods->get_Mem_list(array(
                        'order'=>'views desc',
                        'fields'=>'g.goods_id,',
                        'limit'=>20,
                        'conditions_tt'=>array($query_goods_seller_bm)
                ),null,false,true,$total_found);

                $query_goods_seller_bm_goods_ids = array();
                foreach ($attrs as $value)
                {
                    if(!in_array($value['goods_id'], $query_goods_seller_bm_goods_ids))
                        $query_goods_seller_bm_goods_ids[] = $value['goods_id'];
                }
                //dump($attrs);

                $query_goods_seller_bm_order_goods = $model_ordergoods->find(array(
                        'conditions'=>db_create_in($query_goods_seller_bm_goods_ids,'goods_id'),
                        'fields'=>'order_id',
                ));
                $query_goods_seller_bm_order_result = array();
                foreach ($query_goods_seller_bm_order_goods as $value)
                {
                    if(!in_array($value['order_id'], $query_goods_seller_bm_order_result))
                        $query_goods_seller_bm_order_result[] = $value['order_id'];
                }
                $this->assign("query_goods_seller_bm",$query_goods_seller_bm);
                if($query_goods_seller_bm_order_result)
                {
                    $query_goods_seller_bm_condition = " AND ".db_create_in($query_goods_seller_bm_order_result,'order_alias.order_id');
                }
                else
                {
                    return;
                }
                //dump($query_goods_name_order_result);
            }
        }

        //待退款
        if(isset($_GET['type']) && 'refund'==trim($_GET['type']))
        {
            $orderrefund_result = $model_orderrefund->find(array(
                    'conditions'=>'sender_id='.$this->visitor->get('user_id').' AND status=0 AND closed=0 AND type=1',
                    'fields'=>'order_id',
            ));
            if($orderrefund_result)
            {
                $orderrefund_ids = array();
                foreach ($orderrefund_result as $value)
                {
                    if(!in_array($value['order_id'], $orderrefund_ids))
                        $orderrefund_ids[] = $value['order_id'];
                }
                $query_refunds_condition =" AND ".db_create_in($orderrefund_ids,'order_alias.order_id')." AND ".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED),'order_alias.status');
            }
            else{
                return;
            }
        }
        //待补差
        if(isset($_GET['type']) && 'applyfee'==trim($_GET['type']))
        {
            $orderrefund_result = $model_orderrefund->find(array(
                    'conditions'=>'receiver_id='.$this->visitor->get('user_id').' AND status=0 AND closed=0 AND type=2',
                    'fields'=>'order_id',
            ));
            if($orderrefund_result)
            {
                $orderrefund_ids = array();
                foreach ($orderrefund_result as $value)
                {
                    if(!in_array($value['order_id'], $orderrefund_ids))
                        $orderrefund_ids[] = $value['order_id'];
                }
                $query_refunds_condition =" AND ".db_create_in($orderrefund_ids,'order_alias.order_id')." AND ".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED),'order_alias.status');
            }
            else{
                return;
            }
        }
        //收件人
        if(isset($_GET['consignee']) && !empty($_GET['consignee']))
        {
            $consignee = trim($_GET['consignee']);
            $consignee_result = $model_orderextm->find(array(
                'conditions'=>"consignee like '%".$consignee."%'",
                'fields'=>'order_id',
            ));
            $this->assign('cosignee_query',$consignee);
            if(!empty($consignee_result))
            {
                $consignee_arr = array();
                foreach ($consignee_result as $value)
                {
                    $consignee_arr[] = $value['order_id'];
                }
                $query_consignee_condition = " AND ".db_create_in($consignee_arr,'order_alias.order_id');
            }
            else
            {
                return;
            }

        }


        $conditions = $this->_get_query_conditions($con);
        /* 查找订单 */
        $orders = $model_order->findAll(array(
            'conditions'    => "buyer_id=" . $this->visitor->get('user_id') .@$query_goods_condition.@$query_goods_seller_bm_condition.@$query_refunds_condition.@$query_consignee_condition. "{$conditions}",
            'fields'        => 'this.*',
            'count'         => true,
            'limit'         => $page['limit'],
            'order'         => 'add_time DESC',
            'include'       =>  array(
                //'has_ordergoods',       //取出商品
                'has_goodswarehouse'
            ),
        ));
        foreach ($orders as $key1 => $order)
        {
            if(!empty($order['gwh']))
            {
                foreach ($order['gwh'] as $key2 => $goods)
                {
                    empty($goods['goods_image']) && $orders[$key1]['gwh'][$key2]['goods_image'] = Conf::get('default_goods_image');
                    //获取货号
                    $result = $model_gs->get(array(
                        'fields' => 'sku',
                        'conditions' => 'spec_id = '.$goods['goods_spec_id'],
                    ));
                    $orders[$key1]['gwh'][$key2]['sku'] = $result['sku'];
                    if(!$orders[$key1]['gwh'][$key2]['sku'])
                    {
                        $orders[$key1]['gwh'][$key2]['sku'] = getHuoHao($goods['goods_name']);
                        if(!$orders[$key1]['gwh'][$key2]['sku'])
                        {
                            $goods_AttrModel = &m('goodsattr');
                            $attrs = $goods_AttrModel->get(array(
                                'conditions' => "goods_id = ".$goods['goods_id']." AND attr_id = 13021751",
                            ));
                            $orders[$key1]['gwh'][$key2]['sku'] = $attrs['attr_value'];
                        }
                    }
                    //档口地址
                    $result = $model_goods->get(array(
                        'conditions' => 'goods_id = '.$goods['goods_id'],
                        'fields' =>'mk_name,dangkou_address,tel,im_qq,im_ww,store_name,s.store_id',
                        'join'=>'belongs_to_store',
                    ));
                    $orders[$key1]['gwh'][$key2]['dk_address'] = $result['mk_name']."-".$result['dangkou_address'];
                    $orders[$key1]['gwh'][$key2]['tel']=$result['tel'];
                    $orders[$key1]['gwh'][$key2]['im_qq']=$result['im_qq'];
                    $orders[$key1]['gwh'][$key2]['im_ww']=$result['im_ww'];
                    $orders[$key1]['gwh'][$key2]['store_id']=$result['store_id'];
                    $orders[$key1]['gwh'][$key2]['store_name']=$result['store_name'];
                    ////商家编码
                    $orders[$key1]['gwh'][$key2]['goods_seller_bm'] = $goods['goods_attr_value'];
                    if(empty($orders[$key1]['gwh'][$key2]['goods_seller_bm']))
                    {
                        $result = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$goods['goods_id']} AND attr_id=1");
                        $orders[$key1]['gwh'][$key2]['goods_seller_bm'] = $result;
                    }
                }
            }
            else
            {
                $order_goods = $model_ordergoods->find(array('conditions'=>'order_id='.$order['order_id']));
                if($order_goods)
                {
                    $orders[$key1]['order_goods'] = $order_goods;
                    $order['order_goods'] = $order_goods;

                    foreach ($order['order_goods'] as $key2 => $goods)
                    {
                        empty($goods['goods_image']) && $orders[$key1]['order_goods'][$key2]['goods_image'] = Conf::get('default_goods_image');
                        //获取货号
                        $result = $model_gs->get(array(
                            'fields' => 'sku',
                            'conditions' => 'spec_id = '.$goods['spec_id'],
                        ));
                        $orders[$key1]['order_goods'][$key2]['sku'] = $result['sku'];
                        if(!$orders[$key1]['order_goods'][$key2]['sku'])
                        {
                            $orders[$key1]['order_goods'][$key2]['sku'] = getHuoHao($goods['goods_name']);
                            if(!$orders[$key1]['order_goods'][$key2]['sku'])
                            {
                                $goods_AttrModel = &m('goodsattr');
                                $attrs = $goods_AttrModel->get(array(
                                    'conditions' => "goods_id = ".$goods['goods_id']." AND attr_id = 13021751",
                                ));
                                $orders[$key1]['order_goods'][$key2]['sku'] = $attrs['attr_value'];
                            }
                        }
                        //档口地址
                        $result = $model_goods->get(array(
                            'conditions' => 'goods_id = '.$goods['goods_id'],
                            'fields' =>'mk_name,dangkou_address,tel,im_qq,im_ww,store_name,s.store_id',
                            'join'=>'belongs_to_store',
                        ));
                        $orders[$key1]['order_goods'][$key2]['dk_address'] = $result['mk_name']."-".$result['dangkou_address'];
                        $orders[$key1]['order_goods'][$key2]['tel']=$result['tel'];
                        $orders[$key1]['order_goods'][$key2]['im_qq']=$result['im_qq'];
                        $orders[$key1]['order_goods'][$key2]['im_ww']=$result['im_ww'];
                        $orders[$key1]['order_goods'][$key2]['store_id']=$result['store_id'];
                        $orders[$key1]['order_goods'][$key2]['store_name']=$result['store_name'];
                        ////商家编码
                        $orders[$key1]['order_goods'][$key2]['goods_seller_bm'] = $goods['attr_value'];
                        if(empty($orders[$key1]['order_goods'][$key2]['goods_seller_bm']))
                        {
                            $result = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$goods['goods_id']} AND attr_id=1");
                            $orders[$key1]['order_goods'][$key2]['goods_seller_bm'] = $result;
                        }
                    }
                }

            }
            $orders[$key1]['delivery_bm'] = $model_order->get_delivery_bm_bybehalf($order['order_id']);
            if(in_array($order['status'],array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED)))
            {
                if(!empty($order['bh_id']))
                {
                    $model_behalf = & m('behalf');
                    $behalf = $model_behalf->get($order['bh_id']);
                    $orders[$key1]['behalf'] = $behalf;
                    //behalf refund
                    $orders[$key1]['refund']=$model_orderrefund->get(array(
                        'conditions'=>'order_id='.$order['order_id'].' AND receiver_id='.$order['bh_id'].' AND closed=0 AND type=1',
                    ));
                    $orders[$key1]['apply_fee']=$model_orderrefund->get(array(
                        'conditions'=>'order_id='.$order['order_id'].' AND receiver_id='.$order['buyer_id'].' AND closed=0 AND type=2',
                    ));
                }
                else
                {
                    $orders[$key1]['storerefund'] = $model_orderstorerefund->get(array(
                        'conditions'=>"order_id='{$order['order_id']}' AND applicant_id=".$this->visitor->get('user_id')
                                        ." AND refund_closed='0'",
                         'order'=>'id DESC'
                    ));
                }
            }

            //获取运费
            $orderextm = $model_orderextm->get($order['order_id']);
            $orders[$key1]['shipping_fee'] = $orderextm['shipping_fee'];
            //淘宝订单号
            $ordervendor = $model_ordervendor->get(array('conditions'=>'seller_id = '.$order['buyer_id'].' AND ecm_order_id='.$order['order_id']));
            $orders[$key1]['taobao_order_sn'] = $ordervendor['order_sn'];
        }
        /*
        foreach ($orders as $key1 => $order)
        {
            if(!empty($order['order_goods']))
            {
                foreach ($order['order_goods'] as $key2 => $goods)
                {
                    empty($goods['goods_image']) && $orders[$key1]['order_goods'][$key2]['goods_image'] = Conf::get('default_goods_image');
                     //获取货号
                     $result = $model_gs->get(array(
                            'fields' => 'sku',
                            'conditions' => 'spec_id = '.$goods['spec_id'],
                    ));
                    $orders[$key1]['order_goods'][$key2]['sku'] = $result['sku'];
                    if(!$orders[$key1]['order_goods'][$key2]['sku'])
                    {
                        $orders[$key1]['order_goods'][$key2]['sku'] = getHuoHao($goods['goods_name']);
                        if(!$orders[$key1]['order_goods'][$key2]['sku'])
                        {
                            $goods_AttrModel = &m('goodsattr');
                            $attrs = $goods_AttrModel->get(array(
                                    'conditions' => "goods_id = ".$goods['goods_id']." AND attr_id = 13021751",
                            ));
                            $orders[$key1]['order_goods'][$key2]['sku'] = $attrs['attr_value'];
                        }
                    }
                    //档口地址
                    $result = $model_goods->get(array(
                            'conditions' => 'goods_id = '.$goods['goods_id'],
                            'fields' =>'mk_name,dangkou_address,tel,im_qq,im_ww,store_name,s.store_id',
                            'join'=>'belongs_to_store',
                    ));
                    $orders[$key1]['order_goods'][$key2]['dk_address'] = $result['mk_name']."-".$result['dangkou_address'];
                    $orders[$key1]['order_goods'][$key2]['tel']=$result['tel'];
                    $orders[$key1]['order_goods'][$key2]['im_qq']=$result['im_qq'];
                    $orders[$key1]['order_goods'][$key2]['im_ww']=$result['im_ww'];
                    $orders[$key1]['order_goods'][$key2]['store_id']=$result['store_id'];
                    $orders[$key1]['order_goods'][$key2]['store_name']=$result['store_name'];
                    ////商家编码
                    $orders[$key1]['order_goods'][$key2]['goods_seller_bm'] = $goods['attr_value'];
                    if(empty($orders[$key1]['order_goods'][$key2]['goods_seller_bm']))
                    {
                        $result = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$goods['goods_id']} AND attr_id=1");
                        $orders[$key1]['order_goods'][$key2]['goods_seller_bm'] = $result;
                    }
                }
             }
            $orders[$key1]['delivery_bm'] = $model_order->get_delivery_bm_bybehalf($order['order_id']);
            if(!empty($order['bh_id']))
            {
                $model_behalf = & m('behalf');
                $behalf = $model_behalf->get($order['bh_id']);
                $orders[$key1]['behalf'] = $behalf;
            }
            $orders[$key1]['refund']=$model_orderrefund->get(array(
                    'conditions'=>'order_id='.$order['order_id'].' AND receiver_id='.$order['bh_id'].' AND closed=0 AND type=1',
            ));
            $orders[$key1]['apply_fee']=$model_orderrefund->get(array(
                    'conditions'=>'order_id='.$order['order_id'].' AND receiver_id='.$order['buyer_id'].' AND closed=0 AND type=2',
            ));
            //获取运费
            $orderextm = $model_orderextm->get($order['order_id']);
            $orders[$key1]['shipping_fee'] = $orderextm['shipping_fee'];
            //淘宝订单号
            $ordervendor = $model_ordervendor->get(array('conditions'=>'seller_id = '.$order['buyer_id'].' AND ecm_order_id='.$order['order_id']));
            $orders[$key1]['taobao_order_sn'] = $ordervendor['order_sn'];
        }
        */
        //dump($orders);
        $page['item_count'] = $model_order->getCount();
        $this->assign('types', array('all'     => Lang::get('all_orders'),
                                     'pending' => Lang::get('pending_orders'),
                                     'submitted' => Lang::get('submitted_orders'),
                                     'accepted' => Lang::get('accepted_orders'),
                                     'shipped' => Lang::get('shipped_orders'),
                                     'finished' => Lang::get('finished_orders'),
                                     'canceled' => Lang::get('canceled_orders')));
        $this->assign('type', $_GET['type']);
        $this->assign('orders', $orders);
        $this->_format_page($page);
        $this->assign('page_info', $page);
    }

   /*  function _get_member_submenu()
    {
        $menus = array(
            array(
                'name'  => 'order_list',
                'url'   => 'index.php?app=buyer_order',
            ),
        );
        return $menus;
    } */

    /*三级菜单*/
    function _get_member_submenu()
    {
        $array = array(
                array(
                        'name' => 'all_orders',
                        'url' => 'index.php?app=buyer_order&amp;type=all_orders',
                ),
                array(
                        'name' => 'pending',
                        'url' => 'index.php?app=buyer_order&amp;type=pending',
                ),
                array(
                        'name' => 'submitted',
                        'url' => 'index.php?app=buyer_order&amp;type=submitted',
                ),
                array(
                        'name' => 'accepted',
                        'url' => 'index.php?app=buyer_order&amp;type=accepted',
                ),
                array(
                        'name' => 'shipped',
                        'url' => 'index.php?app=buyer_order&amp;type=shipped',
                ),
                array(
                        'name' => 'finished',
                        'url' => 'index.php?app=buyer_order&amp;type=finished',
                ),
                array(
                        'name' => 'canceled',
                        'url' => 'index.php?app=buyer_order&amp;type=canceled',
                ),
                array(
                        'name' => 'refund',
                        'url' => 'index.php?app=buyer_order&amp;type=refund',
                ),
                array(
                        'name' => 'applyfee',
                        'url' => 'index.php?app=buyer_order&amp;type=applyfee',
                ),
        );
        return $array;
    }
    /**
     * 申请退款，改进版
     * 目前只针对 档口退款，还没有融入代发
     * @date 2016-05-15
     * @author tanaiquan
     */
    function refund_apply()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

        $model_order = & m('order');
        $model_ordergoods = & m('ordergoods');
        $model_orderstorerefund = & m('orderstorerefund');

        $refund_info = $model_orderstorerefund->find(array(
            'conditions'=>"order_id = {$order_id} and refund_closed=0"
        ));

        if(!empty($refund_info))
        {
            foreach ($refund_info as $refund)
            {
                if($refund['refund_closed'] == 1)
                {
                    $this->show_warning('refund_apply_closed','ret_order_list','index.php?app=buyer_order');
                    return;
                }
                if(in_array($refund['refund_status'],array(REFUND_APPLYING,REFUND_PENDING,REFUND_SHIPPED,REFUND_MODIFIED)))
                {
                    $this->show_warning('refund_apply_exist','ret_order_list','index.php?app=buyer_order');
                    return ;
                }
                if($refund['refund_status'] == REFUND_FINISHED)
                {
                    $this->show_warning('refund_success_handle','ret_order_list','index.php?app=buyer_order');
                    return ;
                }
            }
        }

        $order_info     = $model_order->get(array(
           'conditions'=>"order_alias.order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id') . " AND status " . db_create_in(array(ORDER_ACCEPTED, ORDER_SHIPPED,ORDER_FINISHED)),
           //'join'=>'has_orderextm'
        ));

        if(!$order_info['bh_id'] && $order_info['extension'] == 'normal')
        {
            $order_goods = $model_ordergoods->find(array(
               'conditions'=>"order_id = '{$order_id}'"
            ));
            if($order_goods)
            {
                foreach ($order_goods as $key=>$goods)
                {
                    $order_goods[$key]['subtotal'] = $goods['price'] * $goods['quantity'];
                }
            }
            $this->assign('order_goods',$order_goods);
        }

        $this->assign('order_info',$order_info);
        $this->display('refund.apply.wind.html');
    }
    /**
     * 接收退款货品，给出退款申请单
     */
    function fill_refund_sheet()
    {
        if($_GET['ajax'] == 'no')
        { //用于修改申请单
            $refund_id = $_GET['id'];
            $mod_orderstorerefund = & m('orderstorerefund');
            $refund_info = $mod_orderstorerefund->get($refund_id);
            if(!$refund_info)
            {
                $this->show_warning('hack attempt!'); return ;
            }
            $order_id = $refund_info['order_id'];
            $goods_ids_arr = explode(',', $refund_info['goods_info']);
            $this->assign('refund_info',$refund_info);
        }
        else
        {  //申请退货
            $order_id = $_REQUEST['order_id'];
            $goods_ids_arr = $_POST['ginfo'];
        }

        $model_order = & m('order');
        $model_ordergoods = & m('ordergoods');

        $order_info     = $model_order->get(array(
        'conditions'=>"order_alias.order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id') . " AND status " . db_create_in(array(ORDER_ACCEPTED, ORDER_SHIPPED,ORDER_FINISHED)),
        'join'=>'has_orderextm'
        ));

        $goods_amount = 0;

        if(!$order_info['bh_id'] && $order_info['extension'] == 'normal')
        {
            $order_goods = $model_ordergoods->find(array(
                'conditions'=>"order_id = '{$order_id}' AND ".db_create_in($goods_ids_arr,'goods_id')
            ));
            if($order_goods)
            {
                foreach ($order_goods as $key=>$goods)
                {
                    $order_goods[$key]['subtotal'] = $goods['price'] * $goods['quantity'];
                    $goods_amount += $goods['price'] * $goods['quantity'];
                }
            }
            $this->assign('goods_ids',$goods_ids_arr ? implode(',', $goods_ids_arr):array());
            $this->assign('order_goods',$order_goods);
        }

        $this->import_resource(array(
            'script' => array(
                array(
                    'path' => 'jquery.plugins/jquery.validate.js',
                    'attr' => '',
                )
            )
        ));

        $this->assign('refund_goods_amount',$goods_amount);
        $this->assign('order_info',$order_info);

        $this->display('refund.fill_sheet.html');
    }
    /**
     * 保存退款申请单
     */
    function save_refund_sheet()
    {
        $data = array(
          'is_receive_goods'=>$_POST['is_receive_goods'],//是否收到货物
          'is_reback_goods'=>$_POST['is_reback_goods'],//是否需要退货
          'order_id'=>$_POST['order_id'],
          'refund_category'=>$_POST['refund_category'],
          'refund_delivery_amount'=>floatval($_POST['refund_delivery_amount']),
          'refund_goods_amount'=>floatval($_POST['refund_goods_amount']),
          'refund_intro'=>html_filter($_POST['refund_intro']),
          'goods_info'=>trim($_POST['goods_info']),
          'applicant_id'=>$this->visitor->get('user_id'),//申请人
          'refund_status'=>REFUND_APPLYING,
          'apply_time'=>gmtime()
        );

        $model_order =& m('order');
        $model_orderstorerefund = & m('orderstorerefund');

        $fp = zwd51_handle_concurrence_with_file_open('apply_store_refund');

        if(!$fp)
        {
            $this->show_warning('cannot write','ret_order_list','index.php?app=buyer_order');
            return;
        }
        flock($fp, LOCK_EX);

        $refund_info = $model_orderstorerefund->find(array(
            'conditions'=>"order_id = {$data['order_id']} and refund_closed=0"
        ));

        if(!empty($refund_info))
        {
            foreach ($refund_info as $refund)
            {
                if(in_array($refund['refund_status'],array(REFUND_APPLYING,REFUND_PENDING,REFUND_SHIPPED,REFUND_MODIFIED)))
                {
                    zwd51_handle_concurrence_with_file_close($fp);
                    $this->show_warning('refund_apply_exist','ret_order_list','index.php?app=buyer_order');
                    return ;
                }
                if($refund['refund_status'] == REFUND_FINISHED)
                {
                    zwd51_handle_concurrence_with_file_close($fp);
                    $this->show_warning('refund_success_handle','ret_order_list','index.php?app=buyer_order');
                    return ;
                }
            }
        }

        $order_info = $model_order->get($data['order_id']);

        if(empty($order_info) || $order_info['bh_id'] || !$order_info['seller_id'] || $data['refund_goods_amount'] > $order_info['goods_amount'] || $data['refund_goods_amount'] + $data['refund_delivery_amount'] > $order_info['order_amount'] || $data['refund_delivery_amount'] > $order_info['order_amount'] - $order_info['goods_amount'])
        {
            zwd51_handle_concurrence_with_file_close($fp);
            $this->show_warning('hack attempt','ret_order_list','index.php?app=buyer_order');
            return ;
        }

        $data['store_id'] = $order_info['seller_id'];

        $model_orderstorerefund->add($data);

        zwd51_handle_concurrence_with_file_close($fp);
        if($model_orderstorerefund->has_error())
        {
            $this->show_warning($model_orderstorerefund->get_error());
        }

        $this->show_message('refund_apply_success','ret_order_list','index.php?app=buyer_order');
    }
    /**
     * 查看退货退款单
     */
    function refund_view()
    {
        $user_id = $this->visitor->get('user_id');
        $order_id = $_GET['order_id'] ? $_GET['order_id'] : 0;

        $model_orderstorerefund = & m('orderstorerefund');
        $refund_info = $model_orderstorerefund->get(array(
            'conditions'=>"order_id = $order_id and applicant_id = $user_id",
            'order'=>'id DESC'
        ));


        $model_order = & m('order');
        $model_ordergoods = & m('ordergoods');

        $order_info     = $model_order->get(array(
        'conditions'=>"order_alias.order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id') . " AND status " . db_create_in(array(ORDER_ACCEPTED, ORDER_SHIPPED,ORDER_FINISHED)),
        'join'=>'has_orderextm'
            ));

        $goods_amount = 0;

        if(!$order_info['bh_id'] && $order_info['extension'] == 'normal')
        {
            $order_goods = $model_ordergoods->find(array(
                'conditions'=>"order_id = '{$order_id}' AND ".db_create_in(explode(',', $refund_info['goods_info']),'goods_id')
            ));
            if($order_goods)
            {
                foreach ($order_goods as $key=>$goods)
                {
                    $order_goods[$key]['subtotal'] = $goods['price'] * $goods['quantity'];
                    $goods_amount += $goods['price'] * $goods['quantity'];
                }
            }
            //$this->assign('goods_ids',implode(',', $goods_ids_arr));
            $this->assign('order_goods',$order_goods);
        }

        $refund_info['total_refund_amount'] = floatval($refund_info['refund_goods_amount']) + floatval($refund_info['refund_delivery_amount']);

        $this->import_resource(array(
            'script' => array(
                array(
                    'path' => 'dialog/dialog.js',
                    'attr' => 'id="dialog_js"',
                ),
                array(
                    'path' => 'jquery.ui/jquery.ui.js',
                    'attr' => '',
                ),
                array(
                    'path' => 'jquery.ui/i18n/' . i18n_code() . '.js',
                    'attr' => '',
                ),
                array(
                    'path' => 'jquery.plugins/jquery.validate.js',
                    'attr' => '',
                )
            ),
            'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
        ));

        $this->assign('refund_goods_amount',price_format($goods_amount));
        $this->assign('order_info',$order_info);
        $this->assign('refund_info',$refund_info);

        $this->display('refund.view.html');
    }

    /**
     * 档口退款提交发货信息
     */
    function submit_invoice()
    {
        $refund_id = $_GET['id']?trim($_GET['id']):0;

        $model_orderstorerefund = & m('orderstorerefund');

        $refund_info = $model_orderstorerefund->get($refund_id);

        if(empty($refund_info) || !in_array($refund_info['refund_status'], array(REFUND_PENDING)) || $refund_info['refund_closed'] == 1)
        {
            echo 'hack attempt<br>';
            return;
        }
        $mod_deli = & m('delivery');
        $deliveries = $mod_deli->find(array('conditions'=>'dl_desc IS NOT NULL','order'=>'sort_order ASC'));

        if(!IS_POST)
        {

            $this->assign('deliveries',$deliveries);
            $this->assign('refund_info',$refund_info);
            $this->display('refund.submit_invoice.html');
        }
        else
        {
            $data = array(
              'th_deli_id'=>$_POST['th_deli_id'],
              'th_deli_name'=>$deliveries[$_POST['th_deli_id']]['dl_name'],
              'th_invoice'=>$_POST['th_invoice'],
              'th_detail'=>html_filter(trim($_POST['th_detail'])),
              'ship_time'=>gmtime(),
              'refund_status'=>REFUND_SHIPPED
            );

            $model_orderstorerefund->edit($refund_id,$data);

            $this->pop_warning('ok','','index.php?app=buyer_order&act=refund_view&order_id='.$refund_info['order_id']);
        }

    }


    /**
     * 申请退货退款 ，最开始用于代发
     */
    function apply_refund()
    {
        //$this->pop_warning('no_such_order');

        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }
        $model_order    =&  m('order');
        $model_orderrefund = & m('orderrefund');
        $model_ordergoods =& m('ordergoods');
        $model_behalf =& m('behalf');
        $model_delivery =& m('delivery');

        $lock_file = ROOT_PATH."/data/apply_refund.lock";
        if(!file_exists($lock_file))
        {
            file_put_contents($lock_file, 1);
        }

        //对文件加锁
        $fp = fopen($lock_file, 'a+');
        if(!$fp)
        {
            echo 'fail to open file,server is busy!';
            return;
        }
        flock($fp, LOCK_EX);

        /* 只有已付款和已经发货、已完成的订单可以申请退货退款 */
        $order_info     = $model_order->findAll(array(
                'conditions'=>"order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id') . " AND status " . db_create_in(array(ORDER_ACCEPTED, ORDER_SHIPPED,ORDER_FINISHED)),
                'include'       =>  array(
                        //'has_ordergoods',       //取出商品
                        'has_goodswarehouse'
                ),
        ));
        if (!empty($order_info))
        {
            $order_info = current($order_info);
            if($order_info['bh_id'] == '10919')
            {
                echo Lang::get('behalf1_refused');
                return;
            }
            $refund_result=$model_orderrefund->find(array(
                    'conditions'=>'order_id='.$order_info['order_id'].' AND receiver_id='.$order_info['bh_id'].'',
            ));
        }

        $q_deliverys = $model_delivery->find(array('conditions'=>'dl_desc IS NOT NULL','order'=>'sort_order ASC'));
        foreach ($q_deliverys as $key=>$value)
        {
            if(empty($value['dl_desc']))
            {
                unset($q_deliverys[$key]);
            }
        }

        /*文件解锁*/
        flock($fp, LOCK_UN);
        fclose($fp);

        //status 0:申请，1：已同意，2：已拒绝  closed 0:未关闭 1：已关闭
        if(!empty($refund_result))
        {
            if(count($refund_result) > 1)
            {
                echo Lang::get('feifashenqing_gt2');
                return;
            }
            $exist_refund = current($refund_result);
            if($exist_refund['status'] != 2 && $exist_refund['closed'] != 1)
            {
                echo Lang::get('feifashenqing');
                return ;
            }
        }
        //dump($order_info);
        if (empty($order_info))
        {
            echo Lang::get('no_such_order');
            return;
        }
        //计算是否应该收取退货代发费
        $levy_reback_goods_fee = false;
        if($order_info['gwh'])
        {
            $query_gwh_ids = array();
            foreach ($order_info['gwh'] as $order_gwh)
            {
                $query_gwh_ids[] = $order_gwh['id'];
            }
           $levy_reback_goods_fee = after_goods_taker_inventory($order_info['pay_time'],$query_gwh_ids);
        }

        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->assign('deliverys',$q_deliverys);
            $this->assign('levy_reback_goods_fee',$levy_reback_goods_fee);//是否征收退货代发费
            $this->display('buyer_order.apply_refund.html');
        }
        else
        {
            //dump($_POST);


            $refund_amount = isset($_POST['refund_amount'])?floatval(trim($_POST['refund_amount'])):0;
            $invoice_no = isset($_POST['invoice_no']) && $_POST['invoice_no'] ?trim($_POST['invoice_no']):'';

            if($refund_amount <= 0)
            {
                echo "hack attacked";
                return;
            }

            if($invoice_no)
            {
                if(exist_invoiceno($invoice_no))
                {
                    $this->pop_warning('invoice_no_exist');
                    return;
                }

                $rec_ids = $_POST['goods_ids'];

                //判断退货申请金额
                if($order_info['status'] == ORDER_ACCEPTED && $levy_reback_goods_fee)
                {

                    if($refund_amount > ($order_info['order_amount'] - 2*count($rec_ids)))
                    {
                        $this->pop_warning(sprintf(Lang::get('apply_back_fee_too_big'),$order_info['order_amount'] - 2*count($rec_ids)));
                        return;
                    }
                }
                elseif(in_array($order_info['status'], array(ORDER_SHIPPED,ORDER_FINISHED)) && $levy_reback_goods_fee)
                {
                    if($refund_amount > ($order_info['goods_amount'] - 2*count($rec_ids)))
                    {
                        $this->pop_warning(sprintf(Lang::get('apply_back_fee_too_big'),$order_info['goods_amount'] - 2*count($rec_ids)));
                        return;
                    }
                }

                $refund_intro = $goods_ids = '';
                $refund_total = 0;
                $rec_goods = &m('goodswarehouse')->find(array(
                    'conditions'=>'order_id='.$order_id." AND ".db_create_in($rec_ids,'id'),
                ));
                if($rec_goods)
                {
                    foreach ($rec_goods as $goods)
                    {
                        $goods_ids .= $goods['id'].',';
                        $refund_intro .= $goods['goods_name'].' '.$goods['goods_specification'].'  &yen;'.$goods['goods_price'].';';
                        $refund_total += floatval($goods['goods_price']);
                    }
                    /* if($refund_total > $refund_amount)
                    {
                        $this->pop_warning('cuowu_jinebudui');
                        return;
                    } */
                }
                /*
                $rec_goods = $model_ordergoods->find(array(
                    'conditions'=>'order_id='.$order_id." AND ".db_create_in($rec_ids,'rec_id'),
                ));
                if($rec_goods)
                {
                    foreach ($rec_goods as $goods)
                    {
                        $goods_ids .= $goods['rec_id'].',';
                        $refund_intro .= $goods['goods_name'].' '.$goods['specification'].'  &yen;'.$goods['price'].' '.$goods['quantity'].';';
                        $refund_total += $goods['price'] * $goods['quantity'];
                    }
                    if($refund_total > $refund_amount)
                    {
                        $this->pop_warning('cuowu_jinebudui');
                        return;
                    }
                }*/
                $goods_ids && $goods_ids = rtrim($goods_ids,',');
                $refund_intro && $refund_intro = rtrim($refund_intro,';');
                //$refund_intro = implode(';', $_POST['goods_ids']);
                //快递参数有冒号隔开
                if(isset($_POST['delivery_name']) && $_POST['delivery_name'])
                {
                    $delivery_name = explode(':', $_POST['delivery_name']);
                }
                else
                {
                    echo "hack attacked";
                    return;
                }
            }
            else
            {
                $refund_intro = html_filter($_POST['refund_intro']);
            }
            if($order_info['status'] != ORDER_ACCEPTED && $refund_amount > $order_info['goods_amount'])
            {
                echo "hack attacked";
                return;
            }
            if($refund_amount > $order_info['order_amount'])
            {
                echo "hack attacked";
                return;
            }
            if(!isset($_POST['refund_reason']) || empty($_POST['refund_reason']))
            {
                echo "hack attacked";
                return;
            }
            if(empty($order_info['bh_id']))
            {
                echo 'hack attacked';
                return;
            }
            $data=array(
                'order_id'=>$order_info['order_id'],
                'order_sn'=>$order_info['order_sn'],
                'sender_id'=>$this->visitor->get('user_id'),
                'sender_name'=>$this->visitor->get('user_name'),
                'receiver_id'=>$order_info['bh_id'],
                'refund_reason'=>html_filter($_POST['refund_reason']),
                'refund_intro'=>$refund_intro,
                'goods_ids'=>$goods_ids,
                'goods_ids_flag'=>$goods_ids?1:0,
                'apply_amount'=>$refund_amount,
                'invoice_no'=>$invoice_no,
                'dl_id'=>intval($delivery_name[0]),
                'dl_name'=>trim($delivery_name[1]),
                'dl_code'=>trim($delivery_name[2]),
                'refund_amount'=>0,
                'create_time'=>gmtime(),
                'pay_time'=>0,
                'status'=>0,
                'closed'=>0,
                'type'=>1,//1:代表申请退款退货 2：代表代发申请补邮
            );

            /* //开始数据库事务
            $db_transaction_begin = db()->query("START TRANSACTION");
            if($db_transaction_begin === false)
            {
                $this->pop_warning('fail_caozuo');
                return;
            }
            $db_transaction_success = true;//默认事务执行成功，不用回滚
            $db_transaction_reason = '';//回滚的原因 */


            $model_orderrefund=& m('orderrefund');
            $affect_id = $model_orderrefund->add($data);
            if(empty($affect_id) || $model_orderrefund->has_error())
            {
                $this->pop_warning($model_orderrefund->get_error());
                return;
                //$db_transaction_success = false;
                //$db_transaction_reason = 'write_db_failed';
            }

            /*如果订单是已完成的，则要冻结申请资金*/
            if($order_info['status'] == ORDER_FINISHED)
            {
                include_once(ROOT_PATH.'/app/fakemoney.app.php');
                $fakemoneyapp = new FakeMoneyApp();
                $affect_result = $fakemoneyapp->manuFro($order_info['bh_id'],$refund_amount);
                if($affect_result === false)
                {
                    //$db_transaction_success = false;
                    //$db_transaction_reason = 'frozen_failed';
                }
            }

            /* if($db_transaction_success === false)
            {
                db()->query("ROLLBACK");//回滚
            }
            else
            {
                db()->query("COMMIT");//提交
            }

            //db()->query("END");
            if($db_transaction_success === false)
            {
                $this->pop_warning($db_transaction_reason);
                return;
            } */


            //$refund_message = Lang::get('refund_message').$order_info['order_sn'];
            /* 连接用户系统 */
            /*  $ms =& ms();
            $msg_id = $ms->pm->send($this->visitor->get('user_id'), array($order_info['bh_id']), '', $refund_message); */

            /* 发送给卖家订单取消通知 */
            //$model_member =& m('member');
            //$seller_info   = $model_member->get($order_info['bh_id']);
            /*短信通知*/
            //$this->sendSaleSms($seller_info['phone_mob'], $refund_message);
            /* $mail = get_mail('toseller_apply_refund_notify', array('order' => $order_info, 'reason' => $_POST['refund_reason']));
            $this->_mailto($seller_info['email'], addslashes($mail['subject']), $refund_message);  */

            $new_data = array(
                    'status'    => Lang::get('apply_refund'),
                    'actions'   => array(), //取消订单后就不能做任何操作了
            );

            $this->pop_warning('ok');
        }

    }

    /**
     * 处理补收差价
     */
    function applied_fee()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }
        $model_order    =&  m('order');
        $model_orderrefund = & m('orderrefund');
        /* 只有已付款,已发货、已完成的订单可以补收差价*/
        $order_info     = $model_order->get("order_id={$order_id}  AND buyer_id=" . $this->visitor->get('user_id')." AND status " . db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED)));
        if (empty($order_info))
        {
            echo Lang::get('no_such_order');
            return;
        }
        $refund_result=$model_orderrefund->find(array(
                'conditions'=>'order_id='.$order_info['order_id'].' AND sender_id='.$order_info['bh_id'].' AND receiver_id='.$order_info['buyer_id'].' AND status=0 AND closed=0',
        ));
        if(count($refund_result) != 1)
        {
            echo Lang::get('feifashenqi');
            return ;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->assign('refund',current($refund_result));
            $this->display('buyer_order.applied_fee.html');
        }
        else
        {

            $refund_agree = isset($_POST['agree'])?intval(trim($_POST['agree'])):0;
            $zf_pass = isset($_POST['zf_pass'])?trim($_POST['zf_pass']):'';
            if($refund_agree == 0)
            {
                return;
            }
            if(empty($zf_pass) && $refund_agree==1)
            {
                return;
            }
            $refund_result = current($refund_result);
            //开始转账
            if($refund_agree == 1)
            {
                include_once(ROOT_PATH.'/app/my_money.app.php');
                $my_moneyapp = new My_moneyApp();
                $my_money_result=$my_moneyapp->to_user_withdraw($refund_result['sender_name'],$refund_result['apply_amount'], $order_id,$order_info['order_sn'],$zf_pass);
                if($my_money_result !== true)
                {
                    //$this->pop_warning(Lang::get('refund_failed'));
                    $this->pop_warning($my_money_result);
                    return;
                }
                $data=array(
                        'order_id'=>$order_info['order_id'],
                        'order_sn'=>$order_info['order_sn'],
                        'refund_amount'=>$refund_result['apply_amount'],
                        'pay_time'=>gmtime(),
                        'status'=>$refund_agree,
                        'closed'=>0
                );
                $refund_message = Lang::get('refund_message1').$order_info['order_sn'].','.$refund_result['apply_amount'];
            }

            if($refund_agree == 2)
            {
                $data=array(
                        'order_id'=>$order_info['order_id'],
                        'order_sn'=>$order_info['order_sn'],
                        'status'=>$refund_agree,
                        'closed'=>0
                );
                $refund_message = Lang::get('refund_message_disagree').$order_info['order_sn'].','.$refund_result['apply_amount'];
            }
            $model_orderrefund->edit($refund_result['id'],$data);
            if($model_orderrefund->has_error())
            {
                $this->pop_warning($model_orderrefund->get_error());
                return;
            }

            /* 连接用户系统 */
            /* $ms =& ms();
            $msg_id = $ms->pm->send($this->visitor->get('user_id'), array($order_info['bh_id']), '', $refund_message); */

            /* $new_data = array(
             'status'    => Lang::get('apply_refund'),
                    'actions'   => array(), //取消订单后就不能做任何操作了
            ); */
            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                    'order_id'  => $order_info['order_id'],
                    'operator'  => addslashes($this->visitor->get('user_name')),
                    'order_status' => order_status($order_info['status']),
                    'changed_status' => order_status($order_info['status']),
                    'remark'    => $refund_message,
                    'log_time'  => gmtime(),
            ));

            /* 发送给卖家订单转账通知 */
            $model_member =& m('member');
            $seller_info   = $model_member->get($order_info['bh_id']);
            /*短信通知*/
            $this->sendSaleSms($seller_info['phone_mob'], $refund_message);
            //$mail = get_mail('toseller_apply_fee_notify', array('order' => $order_info, 'reason' =>$refund_message ));
            //$this->_mailto($seller_info['email'], addslashes($mail['subject']), $refund_message);

            $this->pop_warning('ok');
        }
    }

    function unicodeToUtf8($str,$order="little")
    {
        $utf8string ="";
        $n=strlen($str);
        for ($i=0;$i<$n ;$i++ )
        {
            if ($order=="little")
            {
                $val = str_pad(dechex(ord($str[$i+1])), 2, 0, STR_PAD_LEFT) .
                str_pad(dechex(ord($str[$i])),      2, 0, STR_PAD_LEFT);
            }
            else
            {
                $val = str_pad(dechex(ord($str[$i])),      2, 0, STR_PAD_LEFT) .
                str_pad(dechex(ord($str[$i+1])), 2, 0, STR_PAD_LEFT);
            }
            $val = intval($val,16); // 由于上次的.连接，导致$val变为字符串，这里得转回来。
            $i++; // 两个字节表示一个unicode字符。
            $c = "";
            if($val < 0x7F)
            { // 0000-007F
                $c .= chr($val);
            }
            elseif($val < 0x800)
            { // 0080-07F0
                $c .= chr(0xC0 | ($val / 64));
                $c .= chr(0x80 | ($val % 64));
            }
            else
            { // 0800-FFFF
                $c .= chr(0xE0 | (($val / 64) / 64));
                $c .= chr(0x80 | (($val / 64) % 64));
                $c .= chr(0x80 | ($val % 64));
            }
            $utf8string .= $c;
        }
        /* 去除bom标记 才能使内置的iconv函数正确转换 */
        if (ord(substr($utf8string,0,1)) == 0xEF && ord(substr($utf8string,1,2)) == 0xBB && ord(substr($utf8string,2,1)) == 0xBF)
        {
            $utf8string = substr($utf8string,3);
        }
        return $utf8string;
    }

    /**
     * 导入订单,某些非淘宝卖家用户有大量订单导入
     * @author tanaiquan
     * @date 2015-07-05
     */
    function im_order()
    {
        return;//by tanaiquan 2015-09-14 20:00
        if (!IS_POST)
        {
            $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('my_im_order'));
            $this->_curlocal(LANG::get('member_center'), 'index.php?app=member',
                    LANG::get('im_buyer'), 'index.php?app=buyer_order&act=im_order',
                    LANG::get('my_im_order'));
            $this->_curitem('buyer_order');
            $this->_curmenu('my_im_order');
            $this->display('import.my_order.html');
        }
        else
        {
            //检查是否最大上传数
            $this->_check_upload_goods_amount();
            //设置地区信息
            setlocale(LC_ALL, 'zh_CN');

            $file = $_FILES['csv'];
            if ($file['error'] != UPLOAD_ERR_OK)
            {
                $this->show_warning('select_file');
                return;
            }
            import('uploader.lib'); // 导入上传类
            $uploader = new Uploader();
            $uploader->allowed_type('csv'); // 限制文件类型
            $uploader->allowed_size(SIZE_CSV_TAOBAO); // 限制单个文件大小2M
            $uploader->addFile($file);
            if (!$uploader->file_info())
            {
                $this->show_warning($uploader->get_error());
                return;
            }
            /* 初始化统计 */
            $num_image = 0; // 需要导入的图片数量
            $num_record = 0; // 成功导入的记录条数

            //$csv_string = $this->unicodeToUtf8(file_get_contents($file['tmp_name']));
            //$csv_string = addslashes($csv_string); // 必须在转码后进行引号转义
            //dump(mb_detect_encoding($csv_string,array('ascii','gbk','gb2312','utf-8')));
            //$records = $this->_parse_order_csv($csv_string);
            //dump($records);

            $records = $this->_parse_csv($file['tmp_name']);
            //dump($records);
            //$handle=fopen($file['tmp_name'], 'r');
            //$records = $this->input_csv($handle);
            if ($this->has_error())
            {
                $this->show_warning($this->get_error());
                return;
            }
            if(count($records) < 1)
            {
                $this->show_warning('csv_empty'); // 欲导入的字段列数跟实际CSV文件中列数不符
                return false;
            }
            //fclose($handle);
            //records 全部是utf-8
            //验证订单商品的有效性
            $this->_check_import_orders_goods($records);
            //组合成订单
            $im_orders = $this->_generate_import_orders($records );
            /* $mmmmmm = $this->_order_fields();
            dump(mb_detect_encoding($mmmmmm['order_sn'],array('ascii','gbk','gb2312','utf-8'))); */
            //dump($im_orders);
            //加入数据库
            $orderVendorMod = &m('ordervendor');
            $goodsVendorMod = &m('goodsvendor');
            foreach ($im_orders as $order)
            {
                $order_id = $orderVendorMod->add($order['order']);
                if($orderVendorMod->has_error())
                {
                    $this->show_warning($orderVendorMod->get_error());
                    return;
                }
                foreach ($order['order_goods'] as $goods)
                {
                    $goods['order_id'] = $order_id;
                    $goodsVendorMod->add($goods);
                    if($goodsVendorMod->has_error())
                    {
                        $this->show_warning($goodsVendorMod->get_error());
                        return;
                    }
                }
            }
            //
            $ms = & ms();
            $ms->user->_local_edit($this->visitor->get('user_id'),array('upload_goods'=>count($records),'upload_goods_time'=>gmtime()));

            $this->show_message('csv_import_success',
                'back_list', 'index.php?app=taobao_order&vendor=1');
        }
    }

    /**
     * @param today_date
     * @param upload_goods_date
     */
      function _check_upload_goods_amount() {
        $ms =& ms();    //连接用户系统
        $msUploadGoods = $ms->user->_local_get(array(
                'conditions'=>'user_id='.$this->visitor->get('user_id'),
                'fields'=>'upload_goods,upload_goods_time',
        ));
        $upload_goods_time = empty($msUploadGoods)?0:intval($msUploadGoods['upload_goods_time']);
        $isSameDate = false;
        if($upload_goods_time > 0 )
        {
            $today_date = getdate(gmtime());
            $upload_goods_date = getdate($upload_goods_time);
            $isSameDate = $this->isSameDays($today_date, $upload_goods_date);
        }
        //先写死一人一日上传最大商品数200
        if($isSameDate && $msUploadGoods['upload_goods'] >= 200)
        {
            $this->show_warning('upload_goods_limit');
            return;
        }
    }


    //判断两天是否是同一天
    function isSameDays($last_date,$this_date){
        if(($last_date['year']===$this_date['year'])&&($this_date['yday']===$last_date['yday'])){
            return true;
        }else{
            return false;
        }
    }
    /**
     * @param records
     */
      function _generate_import_orders($records)
      {
        $ret_im_orders = array();
        $im_orders = array();
        $im_orders_keys = array();
        /* for($i=1;$i<count($records);$i++)
        {
            if(!in_array($records[$i][0], $im_orders_keys))
            {
                $im_orders_keys[] = $records[$i][0];
                $im_orders[$records[$i][0]][]=$records[$i];
            }
            else
            {
                $im_orders[$records[$i][0]][]=$records[$i];
            }
        } */
        foreach ($records as $ik=>$iv)
        {
            if(!in_array($iv['order_sn'], $im_orders_keys))
            {
                $im_orders_keys[] = $iv['order_sn'];
                $im_orders[$iv['order_sn']][]=$iv;
            }
            else
            {
                $im_orders[$iv['order_sn']][]=$iv;
            }
        }
        foreach ($im_orders as $key=>$value)
        {
            $data = array();
            $order_goods = array();
            $data['order_sn']=$this->_gen_import_order_sn();
            $data['seller_id']=$this->visitor->get('user_id');
            $data['seller_name']=$this->visitor->get('user_name');
            $data['buyer_name']=trim($value[0]['member']);
            $data['receiver_name']=trim($value[0]['consignee_name']);
            $data['receiver_mobile']=trim($value[0]['consignee_phone']);
            $data['receiver_address']=trim($value[0]['consignee_address']);
            $data['status'] = VENDOR_ORDER_UNHANDLED;
            $data['vendor'] = 1; //代表excel导入
            $data['add_time'] = gmtime();
            $goods_amount = 0;
            foreach ($value as $kk=>$vv)
            {
                $goods_amount += floatval($vv['price']) * intval($vv['goods_amount']);
            }
            $data['price'] = $goods_amount; //订单商品价格
            $data['post_fee'] = floatval(trim($value[0]['post_fee'])); //邮费可以不用填写
            $data['receiver_state'] = trim($value[0]['consignee_prov']);
            $data['receiver_city'] = trim($value[0]['consignee_city']);
            $data['receiver_district'] = trim($value[0]['consignee_dist']);
            $data['receiver_zip'] = trim($value[0]['consignee_zipcode']);
            $data['total_fee'] = $data['price'] + $data['post_fee'];
            $ret_im_orders[$key]['order'] = $data;
            foreach ($value as $kkk=>$vvv)
            {
                $im_goods = array();
                $im_goods['goods_name'] = $vvv['goods_name'];
                $im_goods['outer_iid'] = $vvv['market'].$vvv['dangkou'].'_P'.$vvv['price'].'_'.$vvv['sku'];
                $im_goods['spec_name_1'] = Lang::get('color');
                $im_goods['spec_value_1'] = trim($vvv['color']);
                $im_goods['spec_name_2'] = Lang::get('size');
                $im_goods['spec_value_2'] = trim($vvv['size']);
                $im_goods['price'] = floatval(trim($vvv['price']));
                $im_goods['num'] = intval(trim($vvv['goods_amount']));
                $order_goods[] = $im_goods;
            }
            $ret_im_orders[$key]['order_goods']=$order_goods;
        }
        return $ret_im_orders;
    }
    /**
     *    生成订单号
     *
     *    @author    Garbin
     *    @return    string
     */
    function _gen_import_order_sn()
    {
        /* 选择一个随机的方案 */
        mt_srand((double) microtime() * 1000000);
        $timestamp = gmtime();
        $y = date('y', $timestamp);
        $z = date('z', $timestamp);
        $h = date('H',$timestamp);
        $i = date('i',$timestamp);
        $s = date('s',$timestamp);
        $order_sn = $y . str_pad($z, 3, '0', STR_PAD_LEFT) .$h.$i.$s. str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        $model_ordervender =& m('ordervendor');
        $orders = $model_ordervender->find('order_sn=' . $order_sn);
        if (empty($orders))
        {
            /* 否则就使用这个订单号 */
            return $order_sn;
        }

        /* 如果有重复的，则重新生成 */
        return $this->_gen_import_order_sn();
    }

    /**
     * @param records
     */
     function _check_import_orders_goods($records)
     {
        $import_fields = $this->_order_fields();
        //$import_fields = array_values($import_fields);
        $import_order_flag = array();

        foreach ($records as $key=>$line)
        {
            foreach ($line as $kk=>$vv)
            {
                if($kk == 'order_sn' && !in_array($vv, $import_order_flag))
                {
                    $import_order_flag[] = $vv;
                    if(in_array($kk, array('delivery','post_fee','member','consignee_name','consignee_phone','consignee_prov','consignee_city','consignee_dist','consignee_address',
            'consignee_zipcode' )) && empty($vv))
                    {
                        $this->show_warning(sprintf(Lang::get('csv_line_wrong'),$key,$vv));
                        return;
                    }
                }
                if(in_array($kk, array('order_sn','market','floor','dangkou','sku','color','size','price','goods_amount')) && empty($vv))
                {
                    $this->show_warning(sprintf(Lang::get('csv_line_wrong'),$key,$vv));
                    return;
                }
            }
        }


        //数据合法性检查

    }


    /* 解析上传订单CSV数据 */
    function _parse_order_csv($csv_string)
    {
        /* 定义CSV文件中几个标识性的字符的ascii码值 */
        define('ORD_SPACE', 32); // 空格
        define('ORD_QUOTE', 34); // 双引号
        define('ORD_TAB',    9); // 制表符
        define('ORD_N',     10); // 换行\n
        define('ORD_R',     13); // 换行\r
        define('ORD_D', 44);//逗号 add by tanaiquan

        /* 字段信息 */
        $import_fields = $this->_order_fields(); // 需要导入的字段在CSV中显示的名称
        $fields_cols = array(); // 每个字段所在CSV中的列序号，从0开始算
        $csv_col_num = 0; // csv文件总列数

        $pos = 0; // 当前的字符偏移量
        $status = 0; // 0标题未开始 1标题已开始
        $title_pos = 0; // 标题开始位置
        $records = array(); // 记录集
        $field = 0; // 字段号
        $start_pos = 0; // 字段开始位置
        $field_status = 0; // 0未开始 1双引号字段开始 2无双引号字段开始
        $line =0; // 数据行号
        while($pos < strlen($csv_string))
        {
            $t = ord($csv_string[$pos]); // 每个UTF-8字符第一个字节单元的ascii码
            $next = ord($csv_string[$pos + 1]);
            $next2 = ord($csv_string[$pos + 2]);
            $next3 = ord($csv_string[$pos + 3]);
            //dump($t);
            if ($status == 0 && !in_array($t, array(ORD_SPACE, ORD_TAB, ORD_N, ORD_R)))
            {
                $status = 1;
                $title_pos = $pos;
            }

            if ($status == 1)
            {
                if ($field_status == 0 && $t== ORD_N)
                {
                    static $flag = null;
                    if ($flag === null)
                    {
                        $title_str = substr($csv_string, $title_pos, $pos - $title_pos);
                        $title_arr = explode("\t", trim($title_str));
                        $fields_cols = $this->_order_fields_cols($title_arr, $import_fields);
                        if (count($fields_cols) != count($import_fields))
                        {
                            $csv_string = substr($csv_string,$pos - $title_pos);
                            continue;
                            $this->_error('csv_fields_error'); // 欲导入的字段列数跟实际CSV文件中列数不符
                            return false;
                        }
                        $csv_col_num = count($title_arr); // csv总列数
                        $flag = 1;
                    }

                    if ($next == ORD_QUOTE)
                    {
                        $field_status = 1; // 引号数据单元开始
                        $start_pos = $pos = $pos + 2; // 数据单元开始位置(相对\n偏移+2)
                    }
                    else
                    {
                        $field_status = 2; // 无引号数据单元开始
                        $start_pos = $pos = $pos + 1; // 数据单元开始位置(相对\n偏移+1)
                    }
                    continue;
                }

                if($field_status == 1 && $t == ORD_QUOTE && in_array($next, array(ORD_N, ORD_R, ORD_TAB))) // 引号+换行 或 引号+\t
                {
                    $records[$line][$field] = addslashes(substr($csv_string, $start_pos, $pos - $start_pos));
                    $field++;
                    if ($field == $csv_col_num)
                    {
                        $line++;
                        $field = 0;
                        $field_status = 0;
                        continue;
                    }
                    if (($next == ORD_N && $next2 == ORD_QUOTE) || ($next == ORD_TAB && $next2 == ORD_QUOTE) || ($next == ORD_R && $next2 == ORD_QUOTE))
                    {
                        $field_status = 1;
                        $start_pos = $pos = $pos + 3;
                        continue;
                    }
                    if (($next == ORD_N && $next2 != ORD_QUOTE) || ($next == ORD_TAB && $next2 != ORD_QUOTE) || ($next == ORD_R && $next2 != ORD_QUOTE))
                    {
                        $field_status = 2;
                        $start_pos = $pos = $pos + 2;
                        continue;
                    }
                    if ($next == ORD_R && $next2 == ORD_N && $next3 == ORD_QUOTE)
                    {
                        $field_status = 1;
                        $start_pos = $pos = $pos + 4;
                        continue;
                    }
                    if ($next == ORD_R && $next2 == ORD_N && $next3 != ORD_QUOTE)
                    {
                        $field_status = 2;
                        $start_pos = $pos = $pos + 3;
                        continue;
                    }
                }

                if($field_status == 2 && in_array($t, array(ORD_N, ORD_R, ORD_TAB))) // 换行 或 \t
                {
                    $records[$line][$field] = addslashes(substr($csv_string, $start_pos, $pos - $start_pos));
                    $field++;
                    if ($field == $csv_col_num)
                    {
                        $line++;
                        $field = 0;
                        $field_status = 0;
                        continue;
                    }
                    if (($t == ORD_N && $next == ORD_QUOTE) || ($t == ORD_TAB && $next == ORD_QUOTE) || ($t == ORD_R && $next == ORD_QUOTE))
                    {
                        $field_status = 1;
                        $start_pos = $pos = $pos + 2;
                        continue;
                    }
                    if (($t == ORD_N && $next != ORD_QUOTE) || ($t == ORD_TAB && $next != ORD_QUOTE) || ($t == ORD_R && $next != ORD_QUOTE))
                    {
                        $field_status = 2;
                        $start_pos = $pos = $pos + 1;
                        continue;
                    }
                    if ($t == ORD_R && $next == ORD_N && $next2 == ORD_QUOTE)
                    {
                        $field_status = 1;
                        $start_pos = $pos = $pos + 3;
                        continue;
                    }
                    if ($t == ORD_R && $next == ORD_N && $next2 != ORD_QUOTE)
                    {
                        $field_status = 2;
                        $start_pos = $pos = $pos + 2;
                        continue;
                    }
                }
            }

            if($t > 0 && $t <= 127) {
                $pos++;
            } elseif(192 <= $t && $t <= 223) {
                $pos += 2;
            } elseif(224 <= $t && $t <= 239) {
                $pos += 3;
            } elseif(240 <= $t && $t <= 247) {
                $pos += 4;
            } elseif(248 <= $t && $t <= 251) {
                $pos += 5;
            } elseif($t == 252 || $t == 253) {
                $pos += 6;
            } else {
                $pos++;
            }
        }
        $return = array();
        foreach ($records as $key => $record)
        {
            foreach ($record as $k => $col)
            {
                $col = trim($col); // 去掉数据两端的空格
                /* 对字段数据进行分别处理 */
                switch ($k)
                {
                    case $fields_cols['description'] :  $return[$key]['description'] = str_replace(array("\\\"\\\"", "\"\""), array("\\\"", "\""), $col); break;
                    case $fields_cols['goods_image'] :  $result = $this->_parse_taobao_image($col); $return[$key]['goods_image'] = $result['data']; $return[$key]['image_count'] = $result['count'];break;
                    case $fields_cols['if_show'] :      $return[$key]['if_show'] = $col == 1 ? 0 : 1; break;
                    case $fields_cols['goods_name'] :   $return[$key]['goods_name'] = $col; break;
                    case $fields_cols['stock'] :        $return[$key]['stock'] = $col; break;
                    case $fields_cols['price']:         $return[$key]['price'] = $col; break;
                    case $fields_cols['recommended'] :  $return[$key]['recommended'] = $col; break;
                    case $fields_cols['sale_attr'] :    $return[$key]['sale_attr'] = $col; break;
                    case $fields_cols['sale_attr_alias'] :    $return[$key]['sale_attr_alias'] = $col; break;
                    case $fields_cols['cid'] :          $return[$key]['cid'] = $col; break;
                }
            }
        }
        return $return;
    }

    /* 需要导入的字段在CSV中显示的名称 */
    function _order_fields()
    {
        return array(
                'order_sn'		=>'订单编号',
                'goods_name'    =>'商品名称',
                'market'        =>'所在市场',
                'floor'			=>'所属楼层',
                'dangkou'		=>'档口地址',
                'sku'			=>'货号',
                'color'			=>'颜色',
                'size'			=>'尺码',
                'price'			=>'单价',
                'goods_amount'	=>'购买数量',
                'delivery'		=>'快递公司',
                'post_fee'		=>'快递费用',
                'member'		=>'会员名称',
                'consignee_name'=>'收件人姓名',
                'consignee_phone'=>'收件人电话',
                'consignee_prov' =>'收件人省',
                'consignee_city' =>'收件人市',
                'consignee_dist' =>'收件人区',
                'consignee_address'=>'收件人详细地址',
                'consignee_zipcode'=>'收件人邮编'
        );
    }

    /* 每个字段所在CSV中的列序号，从0开始算 */
    function _order_fields_cols($title_arr, $import_fields)
    {
        $fields_cols = array();
        foreach ($import_fields as $k => $field)
        {
            $pos = array_search($field, $title_arr);
            if ($pos !== false)
            {
                $fields_cols[$k] = $pos;
            }
        }
        return $fields_cols;
    }

    function input_csv($handle)
    {
        $out = array();
        $n = 0;
        while($data=fgetcsv($handle,10000))
        {
            $num = count($data);
            for($i=0;$i<$num;$i++)
            {
                $out[$n][$i] = $data[$i];
            }
            $n++;
        }
        //unicodeToUtf-8
        foreach ($out as $k=>$v)
        {
            foreach ($v as $kk=>$vv)
            {
                $encoding = mb_detect_encoding($vvv,array('ascii','gbk','gb2312','utf-8'));
                $out[$k][$kk]=ecm_iconv_deep($encoding, 'UTF-8', trim($vv));
            }
        }
        //dump($out);
        /* if (CHARSET =='big5')
        {
            $out = ecm_iconv_deep('utf-8', 'gbk', $out);//dump($chs);
            $out = ecm_iconv_deep('gbk', 'big5', $out);
        }
        else
        {
            $out = ecm_iconv_deep('utf-8', CHARSET, $out);
        } */
        //assert
        $import_fields = $this->_order_fields(); // 需要导入的字段在CSV中显示的名称
        foreach ($import_fields as $kkk=>$vvv)
        {
            $encoding = mb_detect_encoding($vvv,array('ascii','gbk','gb2312','utf-8'));
            $import_fields[$kkk]=ecm_iconv_deep($encoding, 'utf-8', $vvv);
        }
        //$import_fields = ecm_iconv_deep('gbk', CHARSET, $import_fields);
        //dump($import_fields);
        //$fields_cols = $this->_order_fields_cols($out[0], $import_fields);
        //dump($fields_cols);
        if (count($out[0]) != count($import_fields))
        {
            $this->_error('csv_fields_error'); // 欲导入的字段列数跟实际CSV文件中列数不符
            return false;
        }
        if(count($out) > 201)
        {
            $this->show_warning('upload_goods_amount_more');
            return;
        }
        return $out;
    }

    function _parse_csv($data)
    {
        /* 将文件按行读入数组，逐行进行解析 */
        $line_number = 0;
        $arr = array();
        $goods_list = array();
        $field_list = array_keys($this->_order_fields()); // 字段列表
        //$data = file($_FILES['file']['tmp_name']);
        $data = file($data);
            foreach ($data AS $line)
            {
                // 跳过第一行
                if ($line_number == 0)
                {
                    $line_number++;
                    continue;
                }

                // 转换编码

                $line = ecm_iconv_deep('gbk', 'UTF-8', $line);


                // 初始化
                $arr    = array();
                $buff   = '';
                $quote  = 0;
                $len    = strlen($line);
                for ($i = 0; $i < $len; $i++)
                {
                    $char = $line[$i];

                    if ('\\' == $char)
                    {
                        $i++;
                        $char = $line[$i];

                        switch ($char)
                        {
                            case '"':
                                $buff .= '"';
                                break;
                            case '\'':
                                $buff .= '\'';
                                break;
                            case ',';
                                $buff .= ',';
                                break;
                            default:
                                $buff .= '\\' . $char;
                                break;
                        }
                    }
                    elseif ('"' == $char)
                    {
                        if (0 == $quote)
                        {
                            $quote++;
                        }
                        else
                        {
                            $quote = 0;
                        }
                    }
                    elseif (',' == $char)
                    {
                        if (0 == $quote)
                        {
                            if (!isset($field_list[count($arr)]))
                            {
                                continue;
                            }
                            $field_name = $field_list[count($arr)];
                            $arr[$field_name] = trim($buff);
                            $buff = '';
                            $quote = 0;
                        }
                        else
                        {
                            $buff .= $char;
                        }
                    }
                    else
                    {
                        $buff .= $char;
                    }

                    if ($i == $len - 1)
                    {
                        if (!isset($field_list[count($arr)]))
                        {
                            continue;
                        }
                        $field_name = $field_list[count($arr)];
                        $arr[$field_name] = trim($buff);
                    }
                }
                $goods_list[] = $arr;
            }
        return $goods_list;
    }




}

?>
