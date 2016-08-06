<?php

/**
 *    买家的订单管理控制器
 *
 *    @author    Garbin
 *    @usage    none
 */
class Seller_orderApp extends StoreadminbaseApp
{
    function index()
    {
        /* 获取订单列表 */
        $this->_get_orders();

        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                         LANG::get('order_manage'), 'index.php?app=seller_order',
                         LANG::get('order_list'));

        /* 当前用户中心菜单 */
        $type = (isset($_GET['type']) && $_GET['type'] != '') ? trim($_GET['type']) : 'all_orders';
        $this->_curitem('order_manage');
        $this->_curmenu($type);
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('order_manage'));
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
        $this->display('seller_order.index.html');
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
        $order_info  = $model_order->findAll(array(
            'conditions'    => "order_alias.order_id={$order_id} AND seller_id=" . $this->visitor->get('manage_store'),
            'join'          => 'has_orderextm',
        ));
        $order_info = current($order_info);
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
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                         LANG::get('order_manage'), 'index.php?app=seller_order',
                         LANG::get('view_order'));

        /* 当前用户中心菜单 */
        $this->_curitem('order_manage');
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('detail'));

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

        $this->assign('order', $order_info);
        $this->assign($order_detail['data']);
        $this->display('seller_order.view.html');
    }
    /**
     *    收到货款
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function received_pay()
    {
        return false; // stop 2016-05-05
        list($order_id, $order_info)    = $this->_get_valid_order_info(ORDER_PENDING);
        if (!$order_id)
        {
            echo Lang::get('no_such_order');

            return;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->display('seller_order.received_pay.html');
        }
        else
        {
            $model_order    =&  m('order');
            $model_order->edit(intval($order_id), array('status' => ORDER_ACCEPTED, 'pay_time' => gmtime()));
            if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());

                return;
            }

            #TODO 发邮件通知
            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                'order_id'  => $order_id,
                'operator'  => addslashes($this->visitor->get('user_name')),
                'order_status' => order_status($order_info['status']),
                'changed_status' => order_status(ORDER_ACCEPTED),
                'remark'    => $_POST['remark'],
                'log_time'  => gmtime(),
            ));

            /* 发送给买家邮件，提示等待安排发货 */
            $model_member =& m('member');
            $buyer_info   = $model_member->get($order_info['buyer_id']);
            $mail = get_mail('tobuyer_offline_pay_success_notify', array('order' => $order_info));
            $this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));

            $new_data = array(
                'status'    => Lang::get('order_accepted'),
                'actions'   => array(
                    'cancel',
                    'shipped'
                ), //可以取消可以发货
            );

            $this->pop_warning('ok');
        }

    }

    /**
     *    货到付款的订单的确认操作
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function confirm_order()
    {
        return false; //stop by tanaiquan 2015-05-27
        list($order_id, $order_info)    = $this->_get_valid_order_info(ORDER_SUBMITTED);
        if (!$order_id)
        {
            echo Lang::get('no_such_order');

            return;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->display('seller_order.confirm.html');
        }
        else
        {
            $model_order    =&  m('order');
            $model_order->edit($order_id, array('status' => ORDER_ACCEPTED));
            if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());

                return;
            }

            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                'order_id'  => $order_id,
                'operator'  => addslashes($this->visitor->get('user_name')),
                'order_status' => order_status($order_info['status']),
                'changed_status' => order_status(ORDER_ACCEPTED),
                'remark'    => $_POST['remark'],
                'log_time'  => gmtime(),
            ));

            /* 发送给买家邮件，订单已确认，等待安排发货 */
           /*  $model_member =& m('member');
            $buyer_info   = $model_member->get($order_info['buyer_id']);
            $mail = get_mail('tobuyer_confirm_cod_order_notify', array('order' => $order_info));
            $this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message'])); */

            $new_data = array(
                'status'    => Lang::get('order_accepted'),
                'actions'   => array(
                    'cancel',
                    'shipped'
                ), //可以取消可以发货
            );

            $this->pop_warning('ok');;
        }
    }

    /**
     *    调整费用
     *
     *    @author    Garbin
     *    @return    void
     */
    function adjust_fee()
    {
        list($order_id, $order_info)    = $this->_get_valid_order_info(array(ORDER_SUBMITTED, ORDER_PENDING));
        if (!$order_id)
        {
            echo Lang::get('no_such_order');

            return;
        }
        $model_order    =&  m('order');
        $model_orderextm =& m('orderextm');
        $shipping_info   = $model_orderextm->get($order_id);
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->assign('shipping', $shipping_info);
            $this->display('seller_order.adjust_fee.html');
        }
        else
        {
            /* 配送费用 */
            $shipping_fee = isset($_POST['shipping_fee']) ? abs(floatval($_POST['shipping_fee'])) : 0;
            /* 折扣金额 */
            $goods_amount     = isset($_POST['goods_amount'])     ? abs(floatval($_POST['goods_amount'])) : 0;
            /* 订单实际总金额 */
            $order_amount = round($goods_amount + $shipping_fee, 2);
            if ($order_amount <= 0)
            {
                /* 若商品总价＋配送费用扣队折扣小于等于0，则不是一个有效的数据 */
                $this->pop_warning('invalid_fee');

                return;
            }
            $data = array(
                'goods_amount'  => $goods_amount,    //修改商品总价
                'order_amount'  => $order_amount,     //修改订单实际总金额
                'pay_alter' => 1    //支付变更
            );

            if ($shipping_fee != $shipping_info['shipping_fee'])
            {
                /* 若运费有变，则修改运费 */

                $model_extm =& m('orderextm');
                $model_extm->edit($order_id, array('shipping_fee' => $shipping_fee));
            }
            $model_order->edit($order_id, $data);

            if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());

                return;
            }
            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                'order_id'  => $order_id,
                'operator'  => addslashes($this->visitor->get('user_name')),
                'order_status' => order_status($order_info['status']),
                'changed_status' => order_status($order_info['status']),
                'remark'    => Lang::get('adjust_fee'),
                'log_time'  => gmtime(),
            ));

            /* 发送给买家邮件通知，订单金额已改变，等待付款 */
            /* $model_member =& m('member');
            $buyer_info   = $model_member->get($order_info['buyer_id']);
            $mail = get_mail('tobuyer_adjust_fee_notify', array('order' => $order_info));
            $this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message'])); */

            $new_data = array(
                'order_amount'  => price_format($order_amount),
            );

            $this->pop_warning('ok');
        }
    }

    /**
     *    待发货的订单发货
     *
     *    @author    Garbin
     *    @return    void
     */
    function shipped()
    {
        list($order_id, $order_info)    = $this->_get_valid_order_info(array(ORDER_ACCEPTED, ORDER_SHIPPED));
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }        
               
        $model_order    =&  m('order');
        $mod_delivery =& m('delivery');
        $model_orderextm= & m('orderextm');
        if (!IS_POST)
        {
            /* 显示发货表单 */
            header('Content-Type:text/html;charset=' . CHARSET);
            
            /* 获取物流公司名称，用于填写订单物流*/
            $deliveries = $mod_delivery->find();
            //$orderextm = $model_orderextm->get($order_id);
            //$this->assign('delivery',$orderextm['shipping_name']);
            $this->assign('deliveries',$deliveries);            
            $this->assign('order', $order_info);
            $this->display('seller_order.shipped.html');
        }
        else
        {
        	
            if (!$_POST['invoice_no'])
            {
                $this->pop_warning('invoice_no_empty');
                return;
            }
            if (empty($_POST['delivery_name']))
            {
            	$this->pop_warning('logistics_empty');
            	return;
            } 
            
            //开始数据库事务
            $db_transaction_success = true;//默认事务执行成功，不用回滚
            $db_trans_reason = '';
            $db_transaction_begin = db()->query("START TRANSACTION");
            if($db_transaction_begin === false)
            {          
                $db_trans_reason ='not_open_trans';
                $db_transaction_success = false;
            }
            
            /*分润:（订单快递费-6 ）/2 */
            $orderextm_info = $model_orderextm->get($order_id);
            if(!$orderextm_info || $orderextm_info['shipping_fee'] <= 0) {$db_trans_reason ='shipping_fee_empty' ; $db_transaction_success = false; } //roll back
            if(floatval($orderextm_info['shipping_fee']) > 6 )
            {
                $behalf_discount = round((floatval($orderextm_info['shipping_fee']) - 6)/2,2);
                $edit_data['behalf_discount'] = $behalf_discount;//档口快递分润
                //转账
                include_once(ROOT_PATH.'/app/fakemoney.app.php');
                $fakemoneyapp = new FakeMoneyApp();
                $fr_reason = Lang::get('seller_to_51_fr_reason').local_date('Y-m-d H:i:s',gmtime());
                //管理员解冻分润资金
                $affect_result = $fakemoneyapp->manuRefro($this->visitor->get('user_id'), $behalf_discount);
                if($affect_result !== true){$db_trans_reason='Refro_fail'; $db_transaction_success = false; }
                //给用户转账
                $my_money_result = $fakemoneyapp->to_user_withdraw($this->visitor->get('user_id'),FR_USER,$behalf_discount, $fr_reason,$order_id,$order_info['order_sn']);
                if($my_money_result !== true){$db_trans_reason='fr_fail'; $db_transaction_success = false; }
            }
        	           
            $delivery_name = $mod_delivery->get(intval($_POST['delivery_name']));        	
            $edit_data = array('status' => ORDER_SHIPPED, 'invoice_no' => trim($_POST['invoice_no']),'logistics'=>$delivery_name['dl_name']);
            $is_edit = true;
            if (empty($order_info['invoice_no']))
            {
				/*商付通v2.2.1 更新商付通定单状态 开始*/
				if($order_info['payment_code']=='sft' || $order_info['payment_code']=='chinabank' || $order_info['payment_code']=='alipay' || $order_info['payment_code']=='tenpay' || $order_info['payment_code']=='tenpay2')
				{
					$my_moneylog=& m('my_moneylog')->edit('order_id='.$order_id,array('caozuo'=>20));
					if(empty($my_moneylog)){ $db_trans_reason='update_sft_fail'; $db_transaction_success = false; }// roll back
				}
				/*商付通v2.2.1  更新商付通定单状态 结束*/
                /* 不是修改发货单号 */
                $edit_data['ship_time'] = gmtime();	               
                $is_edit = false;
            }
            $affect_rows = $model_order->edit(intval($order_id), $edit_data);
            if(empty($affect_rows)){$db_trans_reason='edit_order_fail'; $db_transaction_success = false;}//roll back
            /* if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());
                return;
            }            */
            $affect_rows = $model_orderextm->edit(intval($order_id),array('shipping_id'=>intval($_POST['delivery_name']),'shipping_name'=>$delivery_name['dl_name']));
            if($affect_rows === false) {$db_trans_reason='edit_extm_fail'; $db_transaction_success = false;}//roll back
            /* if($model_orderextm->has_error())
            {
            	$this->pop_warning($model_orderextm->get_error());
            	return;
            } */
            
            #TODO 发邮件通知
            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $affect_rows = $order_log->add(array(
                'order_id'  => $order_id,
                'operator'  => addslashes($this->visitor->get('user_name')),
                'order_status' => order_status($order_info['status']),
                'changed_status' => order_status(ORDER_SHIPPED),
                'remark'    => $_POST['remark'],
                'log_time'  => gmtime(),
            ));
            if(empty($affect_rows)) {$db_trans_reason='add_order_log_fail'; $db_transaction_success = false;}//roll back
            
            if($db_transaction_success === false)
            {
                db()->query("ROLLBACK");//回滚
                $this->pop_warning($db_trans_reason);
                return false;
            }
            else
            {
                db()->query("COMMIT");//提交
                //return true;
            }
            

            /* 发送给买家订单已发货通知 */
            /*$model_member =& m('member');
            $buyer_info   = $model_member->get($order_info['buyer_id']);
            $order_info['invoice_no'] = $edit_data['invoice_no'];
            $mail = get_mail('tobuyer_shipped_notify', array('order' => $order_info));
            $this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));
             if($buyer_info['phone_mob']){
                             $this->sendSaleSms($buyer_info['phone_mob'],  addslashes($mail['message']));
             }  */
            $new_data = array(
                'status'    => Lang::get('order_shipped'),
                'actions'   => array(
                    'cancel',
                    'edit_invoice_no'
                ), //可以取消可以发货
            );
            if ($order_info['payment_code'] == 'cod')
            {
                $new_data['actions'][] = 'finish';
            }

            $this->pop_warning('ok','seller_order_shipped');
        }
    }

    function test(){
         $this->sendSaleSms('15900402562',  addslashes('my englisth teacherr!!'));
    }
    /**
     *    取消订单
     *
     *    @author    Garbin
     *    @return    void
     */
    function cancel_order()
    {
        /* 取消的和完成的订单不能再取消 */
        //list($order_id, $order_info)    = $this->_get_valid_order_info(array(ORDER_SUBMITTED, ORDER_PENDING, ORDER_ACCEPTED, ORDER_SHIPPED));
        $order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
        }
        $status = array(ORDER_SUBMITTED, ORDER_PENDING, ORDER_ACCEPTED, ORDER_SHIPPED);
        $order_ids = explode(',', $order_id);
        if ($ext)
        {
            $ext = ' AND ' . $ext;
        }

        $model_order    =&  m('order');
        /* 只有已发货的货到付款订单可以收货 */
        $order_info     = $model_order->find(array(
            'conditions'    => "order_id" . db_create_in($order_ids) . " AND seller_id=" . $this->visitor->get('manage_store') . " AND status " . db_create_in($status) . $ext,
        ));
        $ids = array_keys($order_info);
        if (!$order_info)
        {
            echo Lang::get('no_such_order');

            return;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('orders', $order_info);
            $this->assign('order_id', count($ids) == 1 ? current($ids) : implode(',', $ids));
            $this->display('seller_order.cancel.html');
        }
        else
        {
            $model_order    =&  m('order');
            foreach ($ids as $val)
            {
                $id = intval($val);
                $model_order->edit($id, array('status' => ORDER_CANCELED));
                if ($model_order->has_error())
                {
                    //$_erros = $model_order->get_error();
                    //$error = current($_errors);
                    //$this->json_error(Lang::get($error['msg']));
                    //return;
                    continue;
                }

				/*商付通v2.2.1  更新商付通定单状态 开始*/
				$my_money_mod =& m('my_money');
				$my_moneylog_mod =& m('my_moneylog');
				$my_moneylog_row=$my_moneylog_mod->getrow("select * from ".DB_PREFIX."my_moneylog where order_id='$id' and (caozuo='10' or caozuo='20') and s_and_z=1");
				$money=$my_moneylog_row['money'];//定单价格
				$buy_user_id=$my_moneylog_row['buyer_id'];//买家ID
				$sell_user_id=$my_moneylog_row['seller_id'];//卖家ID
				if($my_moneylog_row['order_id']==$id)
				{
					$buy_money_row=$my_money_mod->getrow("select * from ".DB_PREFIX."my_money where user_id='$buy_user_id'");
					$buy_money=$buy_money_row['money'];//买家的钱
					
					$sell_money_row=$my_money_mod->getrow("select * from ".DB_PREFIX."my_money where user_id='$sell_user_id'");
					$sell_money=$sell_money_row['money_dj'];//卖家的冻结资金
					
					$new_buy_money = $buy_money+$money;
					$new_sell_money = $sell_money-$money;
					//更新数据
					$my_money_mod->edit('user_id='.$buy_user_id,array('money'=>$new_buy_money));
					$my_money_mod->edit('user_id='.$sell_user_id,array('money_dj'=>$new_sell_money));
					//更新商付通log为 定单已取消
					$my_moneylog_mod->edit('order_id='.$id,array('caozuo'=>30));
				}
				/*商付通v2.2.1  更新商付通定单状态 结束*/
                
                /* 加回订单商品库存 */
                $model_order->change_stock('+', $id);
                $cancel_reason = (!empty($_POST['remark'])) ? $_POST['remark'] : $_POST['cancel_reason'];
                /* 记录订单操作日志 */
                $order_log =& m('orderlog');
                $order_log->add(array(
                    'order_id'  => $id,
                    'operator'  => addslashes($this->visitor->get('user_name')),
                    'order_status' => order_status($order_info[$id]['status']),
                    'changed_status' => order_status(ORDER_CANCELED),
                    'remark'    => $cancel_reason,
                    'log_time'  => gmtime(),
                ));

                /* 发送给买家订单取消通知 */
                $model_member =& m('member');
                $buyer_info   = $model_member->get($order_info[$id]['buyer_id']);
                $mail = get_mail('tobuyer_cancel_order_notify', array('order' => $order_info[$id], 'reason' => $_POST['remark']));
                $this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));

                $new_data = array(
                    'status'    => Lang::get('order_canceled'),
                    'actions'   => array(), //取消订单后就不能做任何操作了
                );
            }
            $this->pop_warning('ok', 'seller_order_cancel_order');
        }

    }

    /**
     *    完成交易(货到付款的订单)
     *
     *    @author    Garbin
     *    @return    void
     */
    function finished()
    {
        return false; //stop 2016-05-05
        list($order_id, $order_info)    = $this->_get_valid_order_info(ORDER_SHIPPED, 'payment_code=\'cod\'');
        if (!$order_id)
        {
            echo Lang::get('no_such_order');

            return;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            /* 当前用户中心菜单 */
            $this->_curitem('seller_order');
            /* 当前所处子菜单 */
            $this->_curmenu('finished');
            $this->assign('_curmenu','finished');
            $this->assign('order', $order_info);
            $this->display('seller_order.finished.html');
        }
        else
        {
            $now = gmtime();
            $model_order    =&  m('order');
            $model_order->edit($order_id, array('status' => ORDER_FINISHED, 'pay_time' => $now, 'finished_time' => $now));
            if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());

                return;
            }

            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                'order_id'  => $order_id,
                'operator'  => addslashes($this->visitor->get('user_name')),
                'order_status' => order_status($order_info['status']),
                'changed_status' => order_status(ORDER_FINISHED),
                'remark'    => $_POST['remark'],
                'log_time'  => gmtime(),
            ));

            /* 更新累计销售件数 */
            $model_goodsstatistics =& m('goodsstatistics');
            $model_ordergoods =& m('ordergoods');
            $order_goods = $model_ordergoods->find("order_id={$order_id}");
            foreach ($order_goods as $goods)
            {
                $model_goodsstatistics->edit($goods['goods_id'], "sales=sales+{$goods['quantity']}");
            }
            
            
            /* 发送给买家交易完成通知，提示评论 */
            $model_member =& m('member');
            $buyer_info   = $model_member->get($order_info['buyer_id']);
            $mail = get_mail('tobuyer_cod_order_finish_notify', array('order' => $order_info));
            $this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));

            $new_data = array(
                'status'    => Lang::get('order_finished'),
                'actions'   => array(), //完成订单后就不能做任何操作了
            );

            $this->pop_warning('ok');
        }

    }
    
    /**
     * 显示退款申请
     */
    function show_refund()
    {
        $refund_id = $_GET['id']?trim($_GET['id']):0;
        
        $model_orderstorerefund = & m('orderstorerefund');
        
        $refund_info = $model_orderstorerefund->get($refund_id);
        
        if(empty($refund_info) || $refund_info['refund_closed'] == 1)
        {
            //$this->pop_warning('hack attempt');
            echo 'hack attempt';
            return;
        }
        //退款总金额
        $refund_info['refund_total_amount'] = floatval($refund_info['refund_delivery_amount']) + floatval($refund_info['refund_goods_amount']);
        //买家信息
        $buyer_info = &ms()->user->get($refund_info['applicant_id']);     
        //分情况处理退款申请
        $model_order = & m('order');
        $model_ordergoods = & m('ordergoods');
        
        $order_info     = $model_order->get(array(
        'conditions'=>"order_alias.order_id={$refund_info['order_id']} AND buyer_id={$refund_info['applicant_id']}" . " AND status " . db_create_in(array(ORDER_ACCEPTED, ORDER_SHIPPED,ORDER_FINISHED)),
        'join'=>'has_orderextm'
            ));
               
        $goods_amount = 0;
        
        if(!$order_info['bh_id'] && $order_info['extension'] == 'normal')
        {
            $order_goods = $model_ordergoods->find(array(
                'conditions'=>"order_id = '{$refund_info['order_id']}' AND ".db_create_in(explode(',', $refund_info['goods_info']),'goods_id')
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
        $this->assign('buyer_info',$buyer_info);        
        $this->display('seller_order.refund_show.html');        
    }
    
    /**
     * 同意退款退货请求
     */
    function agree_protocol()
    {
        $refund_id = $_GET['id']?trim($_GET['id']):0;
        
        $model_orderstorerefund = & m('orderstorerefund');
        
        $refund_info = $model_orderstorerefund->get($refund_id);
        
        if(empty($refund_info) || !in_array($refund_info['refund_status'], array(REFUND_APPLYING,REFUND_MODIFIED,REFUND_SHIPPED)) || $refund_info['refund_closed'] == 1)
        {
            echo 'hack attempt<br>';
            return;
        }             
        
        if(!IS_POST)
        {
            $this->assign('refund_info',$refund_info);
            $this->display('seller_order.agree.refund_form.html');
        }
        else 
        {
            $order_info = & m('order')->get($refund_info['order_id']);
            //开始数据库事务
            $db_transaction_begin = db()->query("START TRANSACTION");
            if($db_transaction_begin === false)
            {
                //$this->pop_warning('fail_caozuo');
                echo Lang::get("fail_caozuo");
                return;
            }
            $db_transaction_success = true;//默认事务执行成功，不用回滚
            $db_transaction_reason = '';//回滚的原因
            
            if($_POST['type'] == 'a')
            {
                $data = array('refund_status'=>REFUND_PENDING);
            }
            if($_POST['type'] == 'z')
            {
                $data = array(
                    'refund_status'=>REFUND_FINISHED,
                    'pay_time'=>gmtime()
                );
                $zf_pass = isset($_POST['zf_pass'])?trim($_POST['zf_pass']):'';
                if(empty($zf_pass))
                {
                    $db_transaction_reason = "passwd_again";
                    $db_transaction_success = false;
                    //return;
                }
                
                $refund_amount = floatval($refund_info['refund_goods_amount']) + floatval($refund_info['refund_delivery_amount']);
                
                include_once(ROOT_PATH.'/app/my_money.app.php');
                $my_moneyapp = new My_moneyApp();
                
                //给用户转账
                $my_money_result=$my_moneyapp->to_user_withdraw($order_info['buyer_name'],$refund_amount, $order_info['order_id'],$order_info['order_sn'],$zf_pass);
                if($my_money_result !== true)
                {                    
                    $db_transaction_success = false;
                    $db_transaction_reason = 'to_user_withdraw_fail';
                }
            }
            
            $affect_row = $model_orderstorerefund->edit($refund_id,$data);
            if(!$affect_row)
            {
                $db_transaction_success = false;
                $db_transaction_reason = 'update refund error!';
            }
            
            if($db_transaction_success === false)
            {
                db()->query("ROLLBACK");//回滚
            }
            else
            {
                db()->query("COMMIT");//提交
            }
            
            if($db_transaction_success)
            {
                if($_POST['type'] == 'z')
                {
                    /* 连接用户系统 */
                    $ms =& ms();
                    $msg_id = $ms->pm->send($this->visitor->get('user_id'), array($order_info['buyer_id']), '', sprintf(Lang::get('refund_success'),  strval($order_info['order_sn'])));
                     
                    echo Lang::get('refund_ok');
                }
                $this->pop_warning('ok','','index.php?app=seller_order&act=show_refund&id='.$refund_id);
            }
            else
            {
                echo Lang::get($db_transaction_reason) ;
                $this->pop_warning('ok','','index.php?app=seller_order&act=show_refund&id='.$refund_id);
            }
            
        }
    }
        
    /**
     * 打回退款退货请求
     */
    function refuse_protocol()
    {
        $refund_id = $_GET['id']?trim($_GET['id']):0;
        
        $model_orderstorerefund = & m('orderstorerefund');
        
        $refund_info = $model_orderstorerefund->get($refund_id);
        
        if(empty($refund_info) || !in_array($refund_info['refund_status'],array(REFUND_APPLYING,REFUND_MODIFIED)) || $refund_info['refund_closed'] == 1)
        {
            echo 'hack attempt<br>';
            return;
        }
        
        if(!IS_POST)
        {
            $this->assign('refund_info',$refund_info);
            $this->display('seller_order.refuse.refund_form.html');
        }
        else 
        {
            $data = array(
                'refuse_reason'=>html_filter(trim($_POST['refuse_reason'])),
                'refund_status'=>REFUND_MODIFIED
            );
            
            $affect_rows = $model_orderstorerefund->edit($refund_id,$data);
            
            $this->pop_warning('ok','','index.php?app=seller_order&act=show_refund&id='.$refund_id);
           
        }
        
    }
    
    /**
     * 确认收货并转账
     */
    function confirm_protocol()
    {
        $refund_id = $_GET['id']?trim($_GET['id']):0;
        
        $model_orderstorerefund = & m('orderstorerefund');
        
        $refund_info = $model_orderstorerefund->get($refund_id);
        
        if(empty($refund_info) || !in_array($refund_info['refund_status'], array(REFUND_SHIPPED)) || $refund_info['refund_closed'] == 1)
        {
            echo 'hack attempt<br>';
            return;
        }
        
        if(!IS_POST)
        {
            $this->assign('refund_info',$refund_info);
            $this->display('seller_order.confirm.refund_form.html');
        }
        else
        {
            $order_info = & m('order')->get($refund_info['order_id']);
            //开始数据库事务
            $db_transaction_begin = db()->query("START TRANSACTION");
            if($db_transaction_begin === false)
            {
                //$this->pop_warning('fail_caozuo');
                echo Lang::get("fail_caozuo");
                return;
            }
            $db_transaction_success = true;//默认事务执行成功，不用回滚
            $db_transaction_reason = '';//回滚的原因
        
           
                $data = array(
                    'refund_status'=>REFUND_FINISHED,
                    'pay_time'=>gmtime()
                );
                $zf_pass = isset($_POST['zf_pass'])?trim($_POST['zf_pass']):'';
                if(empty($zf_pass))
                {
                    $db_transaction_reason = "passwd_again";
                    $db_transaction_success = false;
                    //return;
                }
        
                $refund_amount = floatval($refund_info['refund_goods_amount']) + floatval($refund_info['refund_delivery_amount']);
        
                include_once(ROOT_PATH.'/app/my_money.app.php');
                $my_moneyapp = new My_moneyApp();
        
                //给用户转账
                $my_money_result=$my_moneyapp->to_user_withdraw($order_info['buyer_name'],$refund_amount, $order_info['order_id'],$order_info['order_sn'],$zf_pass);
                if($my_money_result !== true)
                {
                    $db_transaction_success = false;
                    $db_transaction_reason = 'to_user_withdraw_fail';
                }
            
        
            $affect_row = $model_orderstorerefund->edit($refund_id,$data);
            if(!$affect_row)
            {
                $db_transaction_success = false;
                $db_transaction_reason = 'update refund error!';
            }
        
            if($db_transaction_success === false)
            {
                db()->query("ROLLBACK");//回滚
            }
            else
            {
                db()->query("COMMIT");//提交
            }
        
            if($db_transaction_success)
            {
               
                /* 连接用户系统 */
                $ms =& ms();
                $msg_id = $ms->pm->send($this->visitor->get('user_id'), array($order_info['buyer_id']), '', sprintf(Lang::get('refund_success'),  strval($order_info['order_sn'])));
              
                $this->pop_warning('ok','','index.php?app=seller_order&act=show_refund&id='.$refund_id);
            }
            else
            {
                echo Lang::get($db_transaction_reason) ;
                $this->pop_warning('ok','','index.php?app=seller_order&act=show_refund&id='.$refund_id);
            }
        
        }
    }

    /**
     *    获取有效的订单信息
     *
     *    @author    Garbin
     *    @param     array $status
     *    @param     string $ext
     *    @return    array
     */
    function _get_valid_order_info($status, $ext = '')
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id)
        {

            return array();
        }
        if (!is_array($status))
        {
            $status = array($status);
        }

        if ($ext)
        {
            $ext = ' AND ' . $ext;
        }

        $model_order    =&  m('order');
        /* 只有已发货的货到付款订单可以收货 */
        $order_info     = $model_order->get(array(
            'conditions'    => "order_id={$order_id} AND seller_id=" . $this->visitor->get('manage_store') . " AND status " . db_create_in($status) . $ext,
        ));
        if (empty($order_info))
        {

            return array();
        }

        return array($order_id, $order_info);
    }
    /**
     *    获取订单列表
     *
     *    @author    Garbin
     *    @return    void
     */
    function _get_orders()
    {
        $page = $this->_get_page();
        $model_order =& m('order');
        $model_orderstorerefund = & m('orderstorerefund');

        !$_GET['type'] && $_GET['type'] = 'all_orders';

        $conditions = '';

        // 团购订单
        if (!empty($_GET['group_id']) && intval($_GET['group_id']) > 0)
        {
            $groupbuy_mod = &m('groupbuy');
            $order_ids = $groupbuy_mod->get_order_ids(intval($_GET['group_id']));
            $order_ids && $conditions .= ' AND order_alias.order_id' . db_create_in($order_ids);
        }
        
        /*得到所有的代发订单order_id,去掉卖家所有相关的代 发订单*/
        /* $orderbehalfs = array();
        $model_orderbehalf = & m('orderbehalfs');
        $_orderbehalfs = $model_orderbehalf->find();
        foreach ($_orderbehalfs as $v)
        {
        	$orderbehalfs[] = $v['order_id'];
        }        
        $orderbehalfs_str = implode(',', $orderbehalfs);
        if(!empty($orderbehalfs_str))
       	 $orderbehalfs_sql = ' AND ( order_alias.order_id NOT IN ('.$orderbehalfs_str.') ) '; */
        
        $conditions .= $this->_get_query_conditions(array(
            array(      //按订单状态搜索
                'field' => 'status',
                'name'  => 'type',
                'handler' => 'order_status_translator',
            ),
            array(      //按买家名称搜索
                'field' => 'buyer_name',
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
            )
        ));

        /* 查找订单 */
        $orders = $model_order->findAll(array(
            'conditions'    => "seller_id=" . $this->visitor->get('manage_store') ." AND order_alias.bh_id = 0"."{$conditions}",
            'count'         => true,
            'join'          => 'has_orderextm',
            'limit'         => $page['limit'],
            'order'         => 'add_time DESC',
            'include'       =>  array(
                'has_ordergoods',       //取出商品
            ),
        ));
        foreach ($orders as $key1 => $order)
        {
            $refund_info = $model_orderstorerefund->get(array(
               'conditions'=>"order_id = {$order['order_id']} and applicant_id = {$order['buyer_id']}",
               'order'=>'id DESC'
            ));
            
            $orders[$key1]['refund_info'] = $refund_info;
            
            foreach ($order['order_goods'] as $key2 => $goods)
            {
                empty($goods['goods_image']) && $orders[$key1]['order_goods'][$key2]['goods_image'] = Conf::get('default_goods_image');
            }
        }
        
        $page['item_count'] = $model_order->getCount();
        $this->_format_page($page);
        $this->assign('types', array('all' => Lang::get('all_orders'),
                                     'pending' => Lang::get('pending_orders'),
                                     'submitted' => Lang::get('submitted_orders'),
                                     'accepted' => Lang::get('accepted_orders'),
                                     'shipped' => Lang::get('shipped_orders'),
                                     'finished' => Lang::get('finished_orders'),
                                     'canceled' => Lang::get('canceled_orders')));
        $this->assign('type', $_GET['type']);
        $this->assign('orders', $orders);
        $this->assign('page_info', $page);
    }
    /*三级菜单*/
    function _get_member_submenu()
    {
        $array = array(
            array(
                'name' => 'all_orders',
                'url' => 'index.php?app=seller_order&amp;type=all_orders',
            ),
            array(
                'name' => 'pending',
                'url' => 'index.php?app=seller_order&amp;type=pending',
            ),
            array(
                'name' => 'submitted',
                'url' => 'index.php?app=seller_order&amp;type=submitted',
            ),
            array(
                'name' => 'accepted',
                'url' => 'index.php?app=seller_order&amp;type=accepted',
            ),
            array(
                'name' => 'shipped',
                'url' => 'index.php?app=seller_order&amp;type=shipped',
            ),
            array(
                'name' => 'finished',
                'url' => 'index.php?app=seller_order&amp;type=finished',
            ),
            array(
                'name' => 'canceled',
                'url' => 'index.php?app=seller_order&amp;type=canceled',
        ),
        );
        return $array;
    }
}

?>
