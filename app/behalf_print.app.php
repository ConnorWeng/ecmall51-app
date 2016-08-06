<?php

/**
 *    代发打印控制器
 *
 *    @author    tiq
 *    @usage    none
 */
class Behalf_printApp extends MemberbaseApp
{

    function index()
    {
        /* 显示订单列表 */
        $this->display('behalf_print.index.html');
    }
    
    function get_behalf()
    {
        $model_behalf = & m('behalf');
        $this->assign('behalf', $model_behalf->get($this->visitor->get('user_id')));        
        $this->display('behalf_print.behalf.info.html');
    }

    /**
     * 面单打印
     */
    function md_print()
    {
        $model_behalf = & m('behalf');
        
        $this->assign('behalf', $model_behalf->get($this->visitor->get('user_id')));
        $this->assign('deliverys',$model_behalf->getRelatedData('has_delivery',$this->visitor->get('user_id')));
        $this->display('behalf_print.md_print.html');
    }
    /**
     * 快递单打印
     */
    function kdd_print()
    {
        $model_behalf = & m('behalf');
        $print_templates = Conf::get('behalf_print_template_'.$this->visitor->get('user_id'));
        $this->assign('print_templates',stripslashes_deep($print_templates));
        $this->assign('behalf', $model_behalf->get($this->visitor->get('user_id')));
        $this->assign('deliverys',$model_behalf->getRelatedData('has_delivery',$this->visitor->get('user_id')));
        $this->display('behalf_print.kdd_print.html');
    }
    
    /**
     * 快递单模板
     */
    function kdd_template()
    {
        $this->display('behalf_print.kdd_template.html');
    }
    
    /**
     * 保存快递单号
     */
    function save_invoice()
    {
        //dump($_POST);
        $order_ids = array_filter($_POST['ids']);
        $order_ins = array_filter($_POST['ins']);
        if(count($order_ids) != count($order_ins))
        {
            echo 'error!';
            return ;
        }
        //dump($_POST);
        if(! $order_ids)
        {
            echo Lang::get('no_such_order');
            return;
        }
        
        foreach ($order_ins as $invoiceno)
        {
            if(exist_invoiceno($invoiceno))
            {                
                $this->json_error($invoiceno.','.Lang::get('invoice_no_exist'));
                return;
            }
        }
        
        
        $status = array(
            ORDER_ACCEPTED
        );
        $order_ids = array_filter($order_ids);
        $model_order = &  m('order');        
        /* 只有未发货的订单可以生成快递打印单 */
        $orders = $model_order->find(array(
            'conditions' => "order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) . " AND order_alias.bh_id=" . $this->visitor->get('has_behalf')
        ));
        if(!empty($orders))
        {
            foreach($orders as $order)
            {
                foreach($order_ids as $key=>$value)
                {
                    if($order['order_id'] == $value)//&& empty($order['invoice_no'])
                    {
                        $model_order->edit($order['order_id'],array('invoice_no'=>$order_ins[$key]));
                    }
                }
            }
        }
    }
    
    

    function _get_query_conditions($query_item)
    {
        $str = '';
        $query = array();
        foreach($query_item as $options)
        {
            if(is_string($options))
            {
                $field = $options;
                $options['field'] = $field;
                $options['name'] = $field;
            }
            ! isset($options['equal']) && $options['equal'] = '=';
            ! isset($options['assoc']) && $options['assoc'] = 'AND';
            ! isset($options['type']) && $options['type'] = 'string';
            ! isset($options['name']) && $options['name'] = $options['field'];
            ! isset($options['handler']) && $options['handler'] = 'trim';
            if(isset($_POST[$options['name']]))
            {
                $input = $_POST[$options['name']];
                $handler = $options['handler'];
                $value = ($input == '' ? $input : $handler($input));
                if($value === '' || $value === false)
                { //若未输入，未选择，或者经过$handler处理失败就跳过
                    continue;
                }
                strtoupper($options['equal']) == 'LIKE' && $value = "%{$value}%";
                if($options['type'] != 'numeric')
                {
                    $value = "'{$value}'"; //加上单引号，安全第一
                }
                else
                {
                    $value = floatval($value); //安全起见，将其转换成浮点型
                }
                $str .= " {$options['assoc']} {$options['field']} {$options['equal']} {$value}";
                $query[$options['name']] = $input;
            }
        }
        $this->assign('query', stripslashes_deep($query));
        
        return $str;
    }

    /**
     *    获取订单列表
     *
     *    @author    Garbin
     *    @return    void
     */
    function get_orders()
    {
        //dump($_POST);
        $rows = isset($_POST['rows']) && $_POST['rows'] ? intval($_POST['rows']) : 10;
        $page = $this->_get_page($rows);
        $model_order = & m('order');
        $model_goodsattr = & m('goodsattr');
        $model_ordergoods = & m('ordergoods');
        $model_goods = & m('goods');
        $model_orderrefund = & m('orderrefund');
        ! $_GET['type'] && $_GET['type'] = 'all_orders';
        
        $conditions = '';
        
        $conditions .= $this->_get_query_conditions(array(
            array( //按订单状态搜索
                'field' => 'status',
                'name' => 'type',
                'handler' => 'order_status_translator'
            ),
            array( //按买家名称搜索
                'field' => 'buyer_name',
                'equal' => 'LIKE'
            ),
            array( //按支付时间搜索,起始时间
                'field' => 'order_alias.pay_time',
                'name' => 'add_time_from',
                'equal' => '>=',
                'handler' => 'gmstr2time'
            ),
            array( //按下单时间搜索,结束时间
                'field' => 'order_alias.pay_time',
                'name' => 'add_time_to',
                'equal' => '<=',
                'handler' => 'gmstr2time'
            ),
            array( //按订单号
                'field' => 'order_sn'
            ),
            array(
                //按档口
                'field' => 'seller_name',
                'equal' => 'LIKE'
            )
        )
        );
        //dump($conditions."bb");
        

        $order_order = 'order_alias.pay_time DESC , order_alias.add_time DESC';
        
        /*市场中的店铺*/
        if(! empty($_GET['market']))
        {
            $mk_id = intval($_GET['market']);
            $market_mod = & m('market');
            $mk_ids = array();
            $mk_ids[] = $mk_id;
            $son_ids = $market_mod->get_list($mk_id);
            foreach($son_ids as $sid)
            {
                $mk_ids[] = $sid['mk_id'];
            }
            $mk_stores = $market_mod->getRelatedData('has_store', $mk_ids);
            $mk_storeids = array();
            foreach($mk_stores as $mst)
            {
                $mk_storeids[] = $mst['store_id'];
            }
            $store_conditions = '';
            if(! empty($mk_storeids))
            {
                $store_conditions .= ' AND order_alias.seller_id IN (' . implode(',', $mk_storeids) . ') ';
            }
            else
            {
                $store_conditions .= ' AND order_alias.seller_id is NULL';
            }
            $this->assign("query_mkid", $mk_id);
        } //dump($conditions);
        //商品名称查询
        if($_GET['goods_name'])
        {
            //找出代发所有订单
            $query_goods_name = trim($_GET['goods_name']);
            $query_goods_name_orders = $model_order->find(array(
                'conditions' => "bh_id=" . $this->visitor->get('has_behalf'),
                'fields' => 'order_id'
            ));
            if(! empty($query_goods_name_orders))
            {
                $query_goods_name_order_ids = array();
                foreach($query_goods_name_orders as $value)
                {
                    $query_goods_name_order_ids[] = $value['order_id'];
                }
                //找出 有传入关键字的订单
                $query_order_goods = $model_ordergoods->find(array(
                    'conditions' => db_create_in($query_goods_name_order_ids, 'order_id') . " AND goods_name like '%" . $query_goods_name . "%'",
                    'fields' => 'order_id'
                ));
                $query_goods_name_order_result = array();
                foreach($query_order_goods as $value)
                {
                    if(! in_array($value['order_id'], $query_goods_name_order_result)) $query_goods_name_order_result[] = $value['order_id'];
                }
                $this->assign("query_goods_name", $query_goods_name);
                if($query_goods_name_order_result)
                {
                    $query_goods_condition = " AND " . db_create_in($query_goods_name_order_result, 'order_alias.order_id');
                }
                else
                {
                    return;
                }
                //dump($query_goods_name_order_result);
            }
        }
        //商家编码查询
        if($_POST['goods_seller_bm'])
        {
            //找出代发所有订单
            $query_goods_seller_bm = trim($_POST['goods_seller_bm']);
            $query_goods_seller_bm_orders = $model_order->find(array(
                'conditions' => "bh_id=" . $this->visitor->get('has_behalf'),
                'fields' => 'order_id'
            ));
            if(! empty($query_goods_seller_bm_orders))
            {
                $query_goods_seller_bm_orders_ids = array();
                foreach($query_goods_seller_bm_orders as $value)
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
                    'order' => 'views desc',
                    'fields' => 'g.goods_id,',
                    'limit' => 20,
                    'conditions_tt' => array(
                        $query_goods_seller_bm
                    )
                ), null, false, true, $total_found);
                
                $query_goods_seller_bm_goods_ids = array();
                foreach($attrs as $value)
                {
                    if(! in_array($value['goods_id'], $query_goods_seller_bm_goods_ids)) $query_goods_seller_bm_goods_ids[] = $value['goods_id'];
                }
                //dump($attrs);
                

                $query_goods_seller_bm_order_goods = $model_ordergoods->find(array(
                    'conditions' => db_create_in($query_goods_seller_bm_goods_ids, 'goods_id'),
                    'fields' => 'order_id'
                ));
                $query_goods_seller_bm_order_result = array();
                foreach($query_goods_seller_bm_order_goods as $value)
                {
                    if(! in_array($value['order_id'], $query_goods_seller_bm_order_result)) $query_goods_seller_bm_order_result[] = $value['order_id'];
                }
                //$this->assign("query_goods_seller_bm", $query_goods_seller_bm);
                if($query_goods_seller_bm_order_result)
                {
                    $query_goods_seller_bm_condition = " AND " . db_create_in($query_goods_seller_bm_order_result, 'order_alias.order_id');
                }
                else
                {
                    return;
                }
                //dump($query_goods_name_order_result);
            }
        }
        
        //待退款
        if(isset($_GET['type']) && 'refund' == trim($_GET['type']))
        {
            $orderrefund_result = $model_orderrefund->find(array(
                'conditions' => 'receiver_id=' . $this->visitor->get('user_id') . ' AND status=0 AND closed=0 AND type=1',
                'fields' => 'order_id'
            ));
            if($orderrefund_result)
            {
                $orderrefund_ids = array();
                foreach($orderrefund_result as $value)
                {
                    if(! in_array($value['order_id'], $orderrefund_ids)) $orderrefund_ids[] = $value['order_id'];
                }
                $query_refunds_condition = " AND " . db_create_in($orderrefund_ids, 'order_alias.order_id') . " AND " . db_create_in(array(
                    ORDER_ACCEPTED,
                    ORDER_SHIPPED,
                    ORDER_FINISHED
                ), 'order_alias.status');
            }
            else
            {
                return;
            }
        }
        //待补差
        if(isset($_GET['type']) && 'applyfee' == trim($_GET['type']))
        {
            $orderrefund_result = $model_orderrefund->find(array(
                'conditions' => 'sender_id=' . $this->visitor->get('user_id') . ' AND status=0 AND closed=0 AND type=2',
                'fields' => 'order_id'
            ));
            if($orderrefund_result)
            {
                $orderrefund_ids = array();
                foreach($orderrefund_result as $value)
                {
                    if(! in_array($value['order_id'], $orderrefund_ids)) $orderrefund_ids[] = $value['order_id'];
                }
                $query_refunds_condition = " AND " . db_create_in($orderrefund_ids, 'order_alias.order_id') . " AND " . db_create_in(array(
                    ORDER_ACCEPTED,
                    ORDER_SHIPPED,
                    ORDER_FINISHED
                ), 'order_alias.status');
            }
            else
            {
                return;
            }
        }
        //查找快递
        if(isset($_POST['exp_delivery']) && ! empty($_POST['exp_delivery']))
        {
            $query_dl_condition = ' AND dl_id=' . trim($_POST['exp_delivery']);
            //$this->assign('query_dl', $_GET['exp_delivery']);
        }
        //dump("order_alias.bh_id = " . $this->visitor->get('has_behalf') .' AND order_alias.status '.db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED)). $query_goods_condition . $query_goods_seller_bm_condition . $query_refunds_condition . $query_dl_condition . "{$conditions}");
        /* 查找订单 */
        $orders = $model_order->findAll(array(
            'conditions' => "order_alias.bh_id = " . $this->visitor->get('has_behalf') .' AND order_alias.status '.db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED)). $query_goods_condition . $query_goods_seller_bm_condition . $query_refunds_condition . $query_dl_condition . "{$conditions}",
            'fields' => 'order_alias.*,orderextm.*',
            'count' => true,
            'join' => 'has_orderextm',
            'limit' => $page['limit'],
            'order' => $order_order,
            'include' => array(
                'has_ordergoods' //取出商品
            )
        ));
        //dump($orders);
        foreach($orders as $key1 => $order)
        {
            if(! empty($order['order_goods']))
            {
                $goods_amount = 0;
                $goods_str = "";
                foreach($order['order_goods'] as $key2 => $orderGoods)
                {
                    $goods_amount += $orderGoods['quantity'];
                    ////商家编码
                    $result = $orderGoods['attr_value'];
                    if(empty($result))
                    {
                        $result = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$orderGoods['goods_id']} AND attr_id=1");
                        $orders[$key1]['order_goods'][$key2]['attr_value'] = $result;
                    }
                    if(! empty($result))
                    {
                        $goods_sku = explode("_", trim($result));
                        //转拼音首字母
                        $result = ecm_iconv("UTF-8", "GBK", trim($goods_sku[0]));
                        $result = strtoupper(GetPinyin($result, 1, 1));
                        $goods_sku[0] = $result;
                       //去掉价格
                        foreach ($goods_sku as $ppkey=>$ppvalue)
                        {
                            $goods_sku[$ppkey] = trim($ppvalue);
                            if(preg_match('/^P\d+$/i', trim($ppvalue)))
                               unset($goods_sku[$ppkey]);
                        }
                        $goods_sku = array_filter($goods_sku);
                        $result = implode("_", $goods_sku);
                        $goods_str .= $result . "(" . $orderGoods['specification'] . ' ' . Lang::get('goods_quantity_1') . ":" . $orderGoods['quantity'] . ")";
                    }
                }
                $orders[$key1]['goods_amount'] = Lang::get('order_goods_quantity1').$goods_amount.Lang::get('order_goods_quantity2').$goods_str;
            }
            $orders[$key1]['dl_name'] = $model_order->get_delivery_bybehalf($order['order_id'], $order['bh_id']);
            $orders[$key1]['refunds'] = $model_orderrefund->get(array(
                'conditions' => 'order_id=' . $order['order_id'] . ' AND receiver_id=' . $this->visitor->get('user_id') . ' AND status=0 AND closed=0 AND type=1'
            ));
            $orders[$key1]['apply_fee'] = $model_orderrefund->get(array(
                'conditions' => 'order_id=' . $order['order_id'] . ' AND receiver_id=' . $order['buyer_id'] . ' AND status=0 AND closed=0 AND type=2'
            ));
            if($order['pay_time'])
            {
                $orders[$key1]['pay_time'] = local_date('m-d-H-i',$order['pay_time']);
            }
            $region_arr = $this->_get_prov_city($order['region_id']);
            $region_str = $region_arr['prov'].','.$region_arr['city'];
            $region_str = explode(',', $region_str);
            $region_str = implode(' ', $region_str);
            $orders[$key1]['region_id']  = $region_str;
            
            if($orders[$key1]['refunds'] || $orders[$key1]['apply_fee'])
            {
                unset($orders[$key1]);
            }
            
            
            
            
        }
        //dump($orders);
        //找出所有待发货的订单order_id
        /* $order_accepted = $model_order->findAll(array(
            'conditions'    => "order_alias.bh_id = ".$this->visitor->get('has_behalf').$query_goods_condition.$query_goods_seller_bm_condition.$query_refunds_condition.$query_dl_condition."{$conditions}"." AND order_alias.status ".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED)),
            'fields' => 'order_alias.order_id',
            'join'          => 'has_orderextm',
            'order'         => $order_order,
        ));
        $order_accepted_str="";
        if($order_accepted)
        {
            foreach ($order_accepted as $key=>$value)
            {
                $order_accepted_str .= $key.",";
            }
            $order_accepted_str = rtrim($order_accepted_str,",");
        } */
        
        //         foreach ($orders as $key=>$value)
        //         {
        //             $member_info = $this->_get_member_profile($value['buyer_id']);
        //             $orders[$key]['im_qq'] = $member_info['im_qq'];
        //             $orders[$key]['im_aliww'] = $member_info['im_aliww'];
        //             $orders[$key]['delivery_bm'] = $model_order->get_delivery_bm_bybehalf($value['order_id']);
        //$delivery_bm[] = $orders[$key]['delivery_bm'];//test
        //$orders[$key]['dl_name'] = $model_order->get_delivery_bybehalf($value['order_id'],$value['bh_id']);
        //         }
        //  dump($orders);
        //dump($delivery_bm);//test
        $page['item_count'] = $model_order->getCount();
        //        $this->_format_page($page);
        //         $this->assign('types', array('all' => Lang::get('all_orders'),
        //             'pending' => Lang::get('pending_orders'),
        //             'submitted' => Lang::get('submitted_orders'),
        //             'accepted' => Lang::get('accepted_orders'),
        //             'shipped' => Lang::get('shipped_orders'),
        //             'finished' => Lang::get('finished_orders'),
        //             'canceled' => Lang::get('canceled_orders')));
        //         $this->assign('type', $_GET['type']);
        //         $this->assign('orders', $orders);
        //         $this->assign('page_info', $page);
        //         $this->assign("order_accepted_str",$order_accepted_str);
        //$this->assign('order_shipped_str',$order_shipped_str);
        $ret_arr = array();
        $items = array();
        $ret_arr['total'] = $page['item_count'];
        foreach($orders as $order)
        {
            $items[] = $order;
        }
        
        $ret_arr['rows'] = $items;
        echo ecm_json_encode($ret_arr);
        
        unset($result);
        unset($ret_arr);
        unset($items);
    }

    /**
     * 获取快递单号
     */
    function get_invoice_no()
    {
        //限制其它代发不能使用，以后要 修正
        $user_id = $this->visitor->get('user_id');
        $accounts = Conf::get('behalf_modeb_account_'.$user_id);
        if(empty($accounts))
        {            
            return;
        }
        else
        {
            $yto_account = $accounts['yto_account'];
            $yto_pass = $accounts['yto_pass'];
            $zto_account = $accounts['zto_account'];
            $zto_pass = $accounts['zto_pass'];
        } 
        
        $order_ids = $_POST['ids'];
        if(! $order_ids)
        {
            echo Lang::get('no_such_order');
            return;
        }
        $status = array(ORDER_ACCEPTED);
        //$order_ids = explode(',', $order_id);
        $order_ids = array_filter($order_ids);
        
        $model_order = &  m('order');
        $model_ordermodeb = & m('ordermodeb');
        $model_behalf = & m('behalf');
        $model_delivery = & m('delivery');
        //
        $dl_id = $model_delivery->get(array(
            'conditions' => "dl_desc like 'yuantong'"
        ));
        $zto_id = $model_delivery->get(array(
            'conditions' => "dl_desc like 'zhongtong'"
        ));
        
        /* 只有未发货的订单可以生成快递打印单 */
        $orders = $model_order->findAll(array(
            'conditions' => "order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) . " AND order_alias.bh_id=" . $this->visitor->get('has_behalf'),
            'join' => 'has_orderextm',
            'include' => array(
                'has_ordergoods' //取出商品
            )
        )
        );
        //dump($orders);
        if(! empty($orders))
        {
            $behalf = $model_behalf->get($this->visitor->get('has_behalf'));

            import('createOrderModeB');
            $orderMB = new CreateOrderModeB($yto_account, $yto_pass);
            //$orderMB = new CreateOrderModeB('K24000154', 'weH71Rbq', 'http://58.32.246.71:8000/CommonOrderModeBServlet.action');//test account'K200225829', '3dv20UFA'
            
            foreach($orders as $key => $value)
            {
                //不是圆通，不获取订单快递号
                if($dl_id['dl_id'] != $value['dl_id']) continue;
                if(! empty($value['invoice_no'])) continue;
                $order = $this->_gen_yto_order($value, $behalf);
                //dump($order);
                $orderMB->setOrder($order, 'yto');
                $ret_xml = $orderMB->getOrderModeB();
                $xml = simplexml_load_string($ret_xml);
                //record modeb
                $modeb = $model_ordermodeb->get($value['order_id']);
                if(empty($modeb))
                {
                    $model_ordermodeb->add(array(
                        'order_id' => $value['order_id'],
                        'md_content' => $ret_xml,
                        'name'=>'yto'
                    ));
                }
                else
                {
                    $model_ordermodeb->edit($value['order_id'], array(
                        'md_content' => $ret_xml,
                        'name'=>'yto'
                    ));
                }
                if(strval($xml->success) == 'true')
                {
                    $invoice_no = $xml->orderMessage->mailNo;
                    $model_order->edit($value['order_id'], array(
                        'invoice_no' => strval($invoice_no)
                    ));
                    //dump($ret_xml);
                }
                else 
                {
                    $this->json_error($ret_xml);
                }
            }
           
            import('logistic/zto.lib');
            $ztoMB = new ZtoModeB($zto_account,$zto_pass);
            foreach($orders as $key => $value)
            {
                if($zto_id['dl_id'] != $value['dl_id']) continue;
                if(! empty($value['invoice_no'])) continue;
                $order = $this->_gen_zto_order($value, $behalf);
                //dump($order);
                $ztoMB->setOrder($order);
                $ret_json = $ztoMB->getOrderModeB();
                $modeb = $model_ordermodeb->get($value['order_id']);
                if(empty($modeb))
                {
                    $model_ordermodeb->add(array(
                        'order_id' => $value['order_id'],
                        'md_content' => $ret_json,
                        'name'=>'zto'
                    ));
                }
                else
                {
                    $model_ordermodeb->edit($value['order_id'], array(
                        'md_content' => $ret_json,
                        'name'=>'zto'
                    ));
                }
                $ret_arr = ecm_json_decode($ret_json);
                if($ret_arr->result == 'true' && !empty($ret_arr->keys->mailno))
                {
                    $invoice_no = $ret_arr->keys->mailno;
                    $model_order->edit($value['order_id'], array(
                        'invoice_no' => strval($invoice_no)
                    ));
                    //dump($ret_xml);
                }
                else 
                {
                    $this->json_error($ret_arr);
                } 
            }
            
        }
        echo 'end';
        //$this->json_result(1,'success!');
    }
    
    private function _gen_yto_order($value,$behalf)
    {
        $order = array();
        $order['order_sn'] = $value['order_sn'];
        $order['sender_name'] = $behalf['owner_name'];
        $order['sender_code'] = $behalf['zipcode'];
        $order['sender_mob'] = $behalf['bh_tel'];
        $tmp = $this->turnspace($behalf['region_name']);
        $region_arr = explode(',', $tmp);
        $region_arr = array_filter($region_arr);
        $order['sender_prov'] = $region_arr[1];
        $city = $region_arr[2];
        for($i = 3; $i < count($region_arr); $i ++)
        {
            $city .= ',' . $region_arr[$i];
        }
        $order['sender_city'] = $city;
        $order['sender_address'] = $behalf['bh_address'];
    
        $order['receiver_name'] = $value['consignee'];
        $order['receiver_code'] = $value['zipcode'];
        if(!preg_match('/^\d{6}$/',$value['zipcode']) || preg_match('/\d{7,}/',$value['zipcode']))
        {
            $order['receiver_code'] = '000000';
        }
        $order['receiver_phone'] = $value['phone_tel'];
        $order['receiver_mob'] = $value['phone_mob'];
        $tmp = $this->_get_prov_city($value['region_id']);
        $order['receiver_prov'] = $tmp['prov'];
        $order['receiver_city'] = $tmp['city'];
         
        $order['receiver_address'] = $value['address'];
    
        $order['goods_amount'] = $value['goods_amount'];
        $order['order_amount'] = $value['order_amount'];
        $order['order_goods'] = $value['order_goods'];
        return $order;
    }
    
    private function _gen_zto_order($value,$behalf)
    {
        $order = array();
        $order['id'] = $value['order_sn'];
        $order['type'] = '';
        $order['sender']['name'] = $behalf['owner_name'];
        $order['sender']['mobile'] = $behalf['bh_tel'];
        $tmp = $this->turnspace($behalf['region_name']);
        $region_arr = explode(',', $tmp);
        $region_arr = array_filter($region_arr);
        //$order['sender_prov'] = $region_arr[1];
        $city = $region_arr[1].','.$region_arr[2];
        for($i = 3; $i < count($region_arr); $i ++)
        {
            $city .= ',' . $region_arr[$i];
        }
        $order['sender']['city'] = $city;
        $order['sender']['address'] = $behalf['bh_address'];
    
        $order['receiver']['name'] = $value['consignee'];
        $order['receiver']['phone'] = $value['phone_tel'];
        $order['receiver']['mobile'] = $value['phone_mob'];
        $tmp = $this->_get_prov_city($value['region_id']);
        //$order['receiver_prov'] = $tmp['prov'];
        $order['receiver']['city'] = $tmp['prov'].','.$tmp['city'];
         
        $order['receiver']['address'] = $value['address'];   
        $order['items'] = array();
        if(!empty($value['order_goods']))
        {
            foreach ($value['order_goods'] as $goods)
            {
                $one = array();
                $one['id']= $goods['rec_id'];
                $one['name']= $goods['goods_name'];
                $one['quantity']= $goods['quantity'];
                $one['unitprice']= $goods['price'];
                $order['items'][] = $one;
            }
        }
        
        return $order;
    }

    function async_shipped()
    {
        $order_ids = $_POST['ids'];
        if(! $order_ids)
        {
            $this->json_error(Lang::get('no_such_order'));
            return;
        }       
        $status = array(
            ORDER_ACCEPTED
        );
        //$order_ids = explode(',', $order_id);
        $order_ids = array_filter($order_ids);
        
        $model_order = &  m('order');
        $order_log = & m('orderlog');
        $model_member = & m('member');
        $model_ordergoods=& m('ordergoods');
        $model_storediscount=& m('storediscount');
        $ordervendor_mod = &m('ordervendor');
        $model_orderextm =& m('orderextm');
        /* 只有未发货的订单可以生成快递打印单 */
        $orders = $model_order->find(array(
            'conditions' => "order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) . " AND order_alias.bh_id=" . $this->visitor->get('has_behalf')
        ));
        
        if($orders)
        {
            foreach($orders as $order)
            {
                $order_id = $order['order_id'];
                if(! empty($order['invoice_no']) && $order['status'] == ORDER_ACCEPTED)
                {
                    $edit_data = array(
                        'status' => ORDER_SHIPPED,
                        'ship_time' => gmtime()
                    );
                    /*商付通v2.2.1 更新商付通定单状态 开始*/
                    if($order['payment_code'] == 'sft' || $order['payment_code'] == 'chinabank' || $order['payment_code'] == 'alipay' || $order['payment_code'] == 'tenpay' || $order['payment_code'] == 'tenpay2')
                    {
                        $my_moneylog = & m('my_moneylog')->edit('order_id=' . $order['order_id'], array(
                            'caozuo' => 20
                        ));
                    }
                    /*商付通v2.2.1  更新商付通定单状态 结束*/
                    $fr_order = $model_order->findAll(array(
                        'conditions'=>'order_id='.$order['order_id'].' AND status='.ORDER_ACCEPTED,
                        'include'=>array('has_ordergoods')
                    ));
                    $behalf_delivery = $model_orderextm->get($order_id);
                    //分润
                    if(!empty($fr_order))
                    {
                        $behalf_discount = 0;
                        if(!empty($fr_order[$order_id]['order_goods']))
                        {
                            foreach ($fr_order[$order_id]['order_goods'] as $goods)
                            {
                                //不能缺货
                                if($goods['oos_value'])
                                {
                                    $behalf_discount += $goods['behalf_to51_discount'];
                                }
                            }
                        }
                        //快递费分润，8块分1
                        if($behalf_delivery['shipping_fee'] > 0)
                        {
                            $shipping_fee = intval($behalf_delivery['shipping_fee']);
                            $behalf_discount += intval($shipping_fee/8);
                        }
                        if($behalf_discount > 0)
                        {
                            $edit_data['behalf_discount'] = $behalf_discount;//写入订单
                            //转账
                            include_once(ROOT_PATH.'/app/fakemoney.app.php');
                            $fakemoneyapp = new FakeMoneyApp();
                            $fr_reason = Lang::get('behalf_to_51_fr_reason').local_date('Y-m-d H:i:s',gmtime());
                            //给用户转账
                            $my_money_result=$fakemoneyapp->to_user_withdraw($this->visitor->get('user_id'),FR_USER,$behalf_discount, $fr_reason,$order['order_id'],$fr_order[$order_id]['order_sn']);
                            if($my_money_result !== true)
                            {
                                $this->json_error($my_money_result);
                                return;
                            }
                        }
                    
                    }
                    
                    
                    $affect_rows = $model_order->edit($order['order_id'], $edit_data);
                    if($model_order->has_error())
                    {
                        $this->json_error($model_order->get_error());
                        continue;
                    }
                    #TODO 发邮件通知
                    /*记录订单操作日志 */
                    $order_log->add(array(
                        'order_id' => $order['order_id'],
                        'operator' => addslashes($this->visitor->get('user_name')),
                        'order_status' => order_status($order['status']),
                        'changed_status' => order_status(ORDER_SHIPPED),
                        'remark' => $_POST['remark'],
                        'log_time' => gmtime()
                    ));
                    /* 发送给买家订单已发货通知 */
                    $buyer_info = $model_member->get($order['buyer_id']);
                    //$mail = get_mail('tobuyer_shipped_notify', array('order' => $order_info));
                    //$this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));
                    if($buyer_info['phone_mob'])
                    {
                        $com = $model_order->get_delivery_bybehalf($order['order_id'], $order['bh_id']);
                        $order['dl_name'] = $com;
                        $smail = get_mail('sms_order_notify', array(
                            'order' => $order
                        ));
                        $this->sendSaleSms($buyer_info['phone_mob'], addslashes($smail['message']));
                    }
                    /* 如果匹配到的话，修改第三方订单状态 */
                    $ordervendor_mod->edit("ecm_order_id={$order['order_id']}", array(
                        'status' => VENDOR_ORDER_SHIPPED
                    ));
                }
            }
        } 
        
        //echo ecm_json_encode(true);
    }
    
    /**
     *  获取订单失败原因
     */
    function get_failinfo()
    {
        $ret_str = Lang::get('invoice_empty');
        $order_id = isset($_POST['ids']) && $_POST['ids'] ? intval($_POST['ids']) :0;
        if(empty($order_id))
            return $ret_str;
        $model_ordermodeb =& m('ordermodeb');
    
        $failinfo = $model_ordermodeb->get($order_id);
        
        if(empty($failinfo))
        {
            echo $ret_str;
        }
        else
        {
            $xml = simplexml_load_string($failinfo['md_content']);
            
            if(strval($xml->success) == 'true')
            {
                $this->json_result(1,Lang::get('success'));
            }
            else 
            {
                $this->json_error($xml->reason);
            }
            
        }
    }
    
    /**
     * 单号作废
     */
    function cancel_invoice()
    {
        $order_ids = $_POST['ids'];
        if(! $order_ids)
        {
            $this->json_error(Lang::get('no_such_order'));
            return;
        }
        $status = array(
            ORDER_ACCEPTED
        );
        $order_ids = array_filter($order_ids);
        $model_order = &  m('order');
        $model_modeb = & m('ordermodeb');
        $model_delivery = & m('delivery');
        //
        $dl_id = $model_delivery->get(array(
            'conditions' => "dl_desc like 'yuantong'"
        ));
        /* 只有未发货的订单可以生成快递打印单 */
        $orders = $model_order->find(array(
            'conditions' => "order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) . " AND order_alias.bh_id=" . $this->visitor->get('has_behalf'),
            'join'=>'has_orderextm'
        ));
        
        if(!empty($orders))
        {
            //已有面单信息订单
            $ids = array();
            foreach ($orders as $order)
            {
                //目前只能取消圆通面单
                if($order['dl_id'] == $dl_id['dl_id'])
                {
                    $ids[] = $order['order_id'];
                }
            }
            $modebs = $model_modeb->find(array(
                'conditions'=>"order_id" . db_create_in($ids)
            ));
            
            if(!empty($modebs))
            {
                import('createOrderModeB');
                $orderMB = new CreateOrderModeB('K200225829', '3dv20UFA', 'http://service.yto56.net.cn/CommonOrderModeBServlet.action');
                //$orderMB = new CreateOrderModeB('K24000154', 'weH71Rbq', 'http://58.32.246.71:8000/CommonOrderModeBServlet.action');//test account
                
                foreach($modebs as $mkey=>$mb)
                {
                    $xml = simplexml_load_string($mb['md_content']);
                    //print_r($xml);
                    if(strval($xml->success) == 'true')
                    {
                        if(!empty($orders[$mkey]['invoice_no']) && trim($orders[$mkey]['invoice_no']) == strval($xml->orderMessage->mailNo))
                        {
                            //echo strval($xml->success)."#";
                            //yto
                            $c_order =array();
                            $c_order['logisticProviderID'] = strval($xml->logisticProviderID);
                            $c_order['clientID'] = strval($xml->orderMessage->clientID);
                            $c_order['mailNo'] = strval($xml->orderMessage->mailNo);
                            $c_order['txLogisticID'] = strval($xml->orderMessage->txLogisticID);
                            $c_order['infoType'] = "INSTRUCTION";
                            $c_order['infoContent'] = "WITHDRAW";
                            $c_order['remark'] = "behalf_withdraw";
                            //dump($c_order);
                            $orderMB->setUpdateInfo($c_order, 'yto');
                            $ret_xml = $orderMB->getOrderModeB();
                            $xml = simplexml_load_string($ret_xml);
                            
                            if(strval($xml->success) == 'true')
                            {                                
                                $model_order->edit($mb['order_id'], array(
                                    'invoice_no' => ""
                                ));
                                //dump($ret_xml);
                            }
                        }
                    }
                    else 
                    {
                        $this->json_error($ret_xml);
                    }
                
                }
            }
            
            
            
            //dump(strval($xml->success));
        }
    }
    
    function turnspace($str)//转换为,
    {
        $qian=array(" ","　","\t","\n","\r");
        $hou=array(",",",",",",",",",");
        return str_replace($qian,$hou,$str);
    }
    
    function _get_prov_city($region_id)
    {
        $res = array();
        
        $model_region =& m('region');
        $regions = $model_region->get_layer($region_id);
        $res['prov'] = $regions[1]['region_name'];
        $city = $regions[2]['region_name'];
        for($i=3;$i<count($regions);$i++)
        {
            $city .= ','.$regions[$i]['region_name'];
        }
        $res['city'] = $city;
        return $res;
    }
    
    function constructing()
    {
        $this->display('behalf_print.ing.html');
    }
    
    /**
     * 保存普通快递模板编辑
     */
    function save_print_template()
    {
        $data = array();
        $user_id = $this->visitor->get('user_id');
        $flag = $_POST['f'];
        $result = $_POST['result'];
        $result_arr = explode(';', $result);
        if($result_arr)
        {
            for($i = 0;$i < 6;$i++)
            {
                unset($result_arr[$i]);
            }
        }
        $result = implode(';', $result_arr);
        //dump($result);
        $model_setting = &af('settings');
        $setting = $model_setting->getAll();
        if($setting['behalf_print_template_'.$user_id])
        {
            $data['behalf_print_template_'.$user_id] = $setting['behalf_print_template_'.$user_id];
        }
        else 
        {
            $data['behalf_print_template_'.$user_id] = array('yto'=>'','zto'=>'','sto'=>'');
        }
        
        foreach ($data['behalf_print_template_'.$user_id] as $key=>$value)
        {
            if($key == $flag)
                $data['behalf_print_template_'.$user_id][$key] = $result;
        }
        
        
        $model_setting->setAll($data);
        
        $this->json_result(1,Lang::get('save_success')); 
    }
}

?>
