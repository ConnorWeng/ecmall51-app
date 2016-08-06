<?php
define('BEHALF_GOODS_PREPARED',0);//代发商品准备中,下单后的默认状态
define('BEHALF_GOODS_READY',10);//代发商品准备好了
define('BEHALF_GOODS_SEND',11);//代发商品已发货
define('BEHALF_GOODS_TOMORROW',20);//代发商品明天有
define('BEHALF_GOODS_UNFORMED',30);//代发商品未出货
define('BEHALF_GOODS_UNSALE',40);//代发商品已下架
define('BEHALF_GOODS_REBACK',50);//代发商品已退货

class BehalfBaseModule extends IndexbaseModule
{
	var $_behalf_mod;         //数据调用模型
	var $_goods_warehouse_mod;//商品仓库数据模型
	var $_order_mod;          //订单数据模型
	var $_ordergoods_mod;     //订单商品数据模型
	var $_orderextm_mod;
	var $_orderlog_mod;
	var $_region_mod;
	var $_orderrefund_mod;    //退款退货数据模型
	var $_market_mod;		  //市场数据模型
	var $_goods_taker_inventory_mod;//拿货单
	var $_delivery_mod;       //快递数据模型 
	var $_store_mod;
	var $_goods_mod;
	var $_goods_statistics_mod;
	
	function __construct()
	{
		$this->BehalfBaseModule();
		$this->_behalf_mod = & m("behalf");
		$this->_goods_warehouse_mod =& m('goodswarehouse');
		$this->_order_mod =& m('order');
		$this->_ordergoods_mod =& m('ordergoods');
		$this->_orderextm_mod =& m('orderextm');
		$this->_orderlog_mod =& m('orderlog');
		$this->_region_mod =& m('region');
		$this->_orderrefund_mod =& m('orderrefund');
		$this->_market_mod =& m('market');
		$this->_delivery_mod =& m('delivery'); 
		$this->_goods_taker_inventory_mod =& m('goodstakerinventory');
		$this->_store_mod =& m('store');
		$this->_goods_mod =& m('goods');
		$this->_goods_statistics_mod =& m('goodsstatistics');
	}
	
	function BehalfBaseModule()
	{
		parent::__construct();	
	}
	
	
	/**
	 *  获取订单列表
	 * @param string $include_goods
	 * @param string $ordertype
	 */
	protected function _get_orders($include_goods = false,$ordertype = 'all_orders',$attached = false,$include_lackgoods=true,$compensation=false)
	{
		$rows = isset($_GET['rows']) && $_GET['rows'] ? intval(trim($_GET['rows'])):10;
		$bh_id = $this->visitor->get('has_behalf');
		$page = $this->_get_page($rows);
		$model_goods=& m('goods');
		
		!$_GET['type'] && $_GET['type'] = $ordertype;
	
		$conditions = '';
		 
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
				array(      //按支付时间搜索,起始时间
						'field' => 'order_alias.pay_time',
						'name'  => 'add_time_from',
						'equal' => '>=',
						'handler'=> 'gmstr2time',
				),
				array(      //按下单时间搜索,结束时间
						'field' => 'order_alias.pay_time',
						'name'  => 'add_time_to',
						'equal' => '<=',
						'handler'=> 'gmstr2time',
				),
				array(      //按订单号
						'field' => 'order_sn',
				),
				/* array(      //按订单号
						'field' => 'invoice_no',
				), */
				array(
						//按档口
						'field' => 'seller_name',
						'equal' => 'LIKE',
				)));
	
		/**/
		$order_order =  'order_alias.pay_time DESC , order_alias.add_time DESC';
		
		if(trim($_GET['invoice_no']))
		{
			$invoiceno_conditions = $this->_getInvoicenoConditions (trim($_GET['invoice_no']),$bh_id);
			if(!$invoiceno_conditions) return ;
			$conditions .= $invoiceno_conditions;
		}
	
		/*市场中的店铺*/
		if(!empty($_GET['market']))
		{
			$store_conditions = $this->_getMarketConditions ( $_GET['market'] );
			$this->assign ( "query_mkid", $_GET['market'] );

		}	//dump($conditions);
		
		if($_GET['goods_name'])
		{
			$query_goods_condition = $this->_getGoodsNameConditions ($_GET['goods_name'],$bh_id);
			$this->assign ( "query_goods_name", $_GET['goods_name'] );
			if(!$query_goods_condition)  return ;
		}
		

		if ($_GET ['oos'])
		{
			$query_oos_condition = $this->_getOOSConditions ($_GET ['oos'],$bh_id);
			$this->assign ( 'query_oos', $_GET ['oos'] );
			if(!$query_oos_condition) return;
		}
		
		// 商家编码查询
		if ($_GET ['goods_seller_bm'])
		{
			$query_goods_seller_bm_condition = $this->_getSellerBMConditions ( $_GET ['goods_seller_bm'],$bh_id);
			$this->assign ( "query_goods_seller_bm", $_GET ['goods_seller_bm'] );
			if(!$query_goods_seller_bm_condition) return ;
		}
		
		//商品编码
		if($_GET['goods_no'])
		{
			$goodsware_condition = $this->_getGoodsNoConditions ( $_GET['goods_no'],$bh_id );
			if(!$goodsware_condition) return ;
		}
			
		// 已拒绝
		if ('refuse' == trim ( $_GET ['type'] ))
		{
			$query_refunds_condition = $this->_getRefuseConditions ( $bh_id );
			if(!$query_refunds_condition) return ;

		}
		// 待退款
		if ('refund' == trim ( $_GET ['type'] ))
		{
			$query_refunds_condition = $this->_getRefundConditions ( $bh_id);
			if(!$query_refunds_condition) return ;

		}
		// 待补差
		if ('applyfee' == trim ( $_GET ['type'] ))
		{
			$query_refunds_condition = $this->_getApplyFeeConditions ( $bh_id );
			if(!$query_refunds_condition) return ;

		}
		// 查找快递
		if (isset ( $_GET ['exp_delivery'] ) && ! empty ( $_GET ['exp_delivery'] ))
		{
			$query_dl_condition = ' AND dl_id=' . trim ( $_GET ['exp_delivery'] );
			$this->assign ( 'query_dl', $_GET ['exp_delivery'] );
		}
		//dump("order_alias.bh_id = ".$this->visitor->get('has_behalf').$query_goods_condition.$query_goods_seller_bm_condition.$query_refunds_condition."{$conditions}");
		/* 查找订单 */
		$findAll_conditions = array(
				'conditions'    => "order_alias.bh_id = ".$this->visitor->get('has_behalf').$goodsware_condition.$query_goods_condition.$query_goods_seller_bm_condition.$query_refunds_condition.$query_dl_condition.$query_oos_condition."{$conditions}",
				'fields' => 'order_alias.*,orderextm.shipping_fee,orderextm.consignee,orderextm.region_name as consignee_region,orderextm.phone_mob,orderextm.phone_tel,orderextm.dl_id,orderextm.zipcode,orderextm.address as consignee_address',
				'count'         => true,
				'join'          => 'has_orderextm',
				'limit'         => $page['limit'],
				'order'         => $order_order );
		if($include_goods)
		{
			$findAll_conditions['include']=array('has_goodswarehouse');
		}
		if($include_lackgoods === false)
		{
		    $refund_order_ids = $this->get_refunds_orders();
		    $findAll_conditions['conditions'] .= " AND order_alias.order_id NOT ".db_create_in($refund_order_ids);
		}
		
		//打印时的明天有刷选  1 只含；2不含
		if(in_array($_GET['tomorrow'],array('1','2')))
		{
		    $t_orders = $this->_order_mod->findAll(array(
		        'conditions'    => "order_alias.bh_id = ".$this->visitor->get('has_behalf').$goodsware_condition.$query_goods_condition.$query_goods_seller_bm_condition.$query_refunds_condition.$query_dl_condition.$query_oos_condition."{$conditions}",
		        'fields' => 'order_alias.order_id',
		        'join'          => 'has_orderextm',
		        'include'=>array('has_goodswarehouse')
		    ));
		    
		    $order_ids = $this->_get_order_ids_with_tomorrow_forprinter($t_orders, $_GET['tomorrow']);
		    if(!empty($order_ids))
		    { 
		        $findAll_conditions['conditions'] .= " AND order_alias.order_id ".db_create_in($order_ids);
		    }
		    else
		    {
		        $findAll_conditions['conditions'] .= " AND order_alias.order_id = '0' ";
		    }
		    
		    unset($t_orders);
		}
		//end filter
		$this->assign("goods_status_arr",$_GET['goods_status']?$_GET['goods_status']:array());//回显
		//商品状态有筛选  
		if($_GET['goods_status'])
		{
		    
		    //dump($_GET['goods_status']);
		    //_goods_status_translator
		    $tomorrow_orders = $this->_order_mod->findAll(array(
		        'conditions'    => "order_alias.bh_id = ".$this->visitor->get('has_behalf').$goodsware_condition.$query_goods_condition.$query_goods_seller_bm_condition.$query_refunds_condition.$query_dl_condition.$query_oos_condition."{$conditions}",
		        'fields' => 'order_alias.order_id',
		        'join'          => 'has_orderextm',
		        'include'=>array('has_goodswarehouse')
		    ));
		    
		    $goods_status_arr = $_GET['goods_status'];
		    foreach ($goods_status_arr as $goods_status_key=>$goods_status_value)
		    {
		        $goods_status_arr[$goods_status_key] = $this->_goods_status_translator($goods_status_value);
		    }
		    
		  $tomorrow_order_ids = $this->_get_orders_with_goodsstatus($tomorrow_orders,$goods_status_arr);

		  if(!empty($tomorrow_order_ids))
		  { 
		      $findAll_conditions['conditions'] .= " AND order_alias.order_id ".db_create_in($tomorrow_order_ids);
		  }
		  else
		  {
		      $findAll_conditions['conditions'] .= " AND order_alias.order_id ='0' ";
		  }
		  
		  unset($tomorrow_orders);
		  //dump($tomorrow_order_ids);
		  
		}
		
		$orders = $this->_order_mod->findAll($findAll_conditions);
		/* dump($findAll_conditions);
		 dump($orders); */
		$mod_order_compensation_behalf = & m('ordercompensationbehalf');
		
		$deliverys = $this->_delivery_mod->find();
		foreach ($orders as $key=>$value)
		{
		    //买家联系方式
		    $buyer_info = $this->_get_member_profile($value['buyer_id']);
		    $orders[$key]['buyer_qq'] = $buyer_info['im_qq'];
		    $orders[$key]['buyer_ww'] = $buyer_info['im_aliww'];
		    $orders[$key]['buyer_tel'] = $buyer_info['phone_mob'];
		    
		    
			$orders[$key]['consignee_region'] = $this->_remove_China($value['consignee_region']);
			if(empty($value['phone_mob']))
			{
			    $orders[$key]['phone_mob'] = $value['phone_tel'];
			}
			if(in_array($value['dl_id'],array_keys($deliverys)))
			{
				$orders [$key] ['dl_name'] = $deliverys[$value['dl_id']]['dl_name'];
				$orders [$key] ['delivery_bm'] = $deliverys[$value['dl_id']]['dl_desc'];
			}
			$lack_goods_amount_for_showbtn_amount = 0;//缺货商品金额，为了是否展示缺货退款按钮
			if($value['gwh'])
			{
				$order_goods_count = count($value['gwh']);//订单商品总数
				$order_goods_str = "";//商品信息
				$unsend_amount = 0;//订单未发货商品金额
				$ready_count = 0;//订单已备货数
				//$unset_flag = false;//是否剔除包含明天有的订单
				//$include_tomorrow_goods = false;//判断订单商品是否含有明天有的商品
				foreach ($value['gwh'] as $gwhkey=>$gwhgoods)
				{
					//$order_goods_count += $gwhgoods['goods_quantity'];
					$order_goods_str .= $this->_Attrvalue2Pinyin($gwhgoods['goods_attr_value']) . "(" . $gwhgoods['goods_specification'] . ")";
					//从order_goods 判断是否缺货
					/* $oos_result = $this->_ordergoods_mod->get("order_id='{$gwhgoods['order_id']}' AND spec_id='{$gwhgoods['goods_spec_id']}'");
					if(!$oos_result['oos_value'])
					{
					    $orders[$key]['gwh'][$gwhkey]['old_oos'] = 1;
					} */
					/* if($_GET['tomorrow'] == '2' && $gwhgoods['goods_status'] == BEHALF_GOODS_TOMORROW)
					{
					    $unset_flag = true;
					}
					if($gwhgoods['goods_status'] == BEHALF_GOODS_TOMORROW)
					{
					    $include_tomorrow_goods = true;
					} */
					
					if(in_array($gwhgoods['goods_status'], array(BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED,BEHALF_GOODS_UNSALE)))
					{
					    $lack_goods_amount_for_showbtn_amount += floatval($gwhgoods['goods_price']);
					}
					if(in_array($gwhgoods['goods_status'], array(BEHALF_GOODS_PREPARED,BEHALF_GOODS_READY,BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED,BEHALF_GOODS_UNSALE)))
					{
					    $unsend_amount += floatval($gwhgoods['goods_price']);
					}
					if(in_array($gwhgoods['goods_status'], array(BEHALF_GOODS_READY)))
					{
					    $ready_count++;
					}
					//档口联系方式
					$store_info = $this->_store_mod->get($gwhgoods['store_id']);
					$orders[$key]['gwh'][$gwhkey]['store_tel'] = $store_info['tel'];
					$orders[$key]['gwh'][$gwhkey]['store_qq'] = $store_info['im_qq'];
					$orders[$key]['gwh'][$gwhkey]['store_ww'] = $store_info['im_ww'];
				}
				$orders[$key]['goods_info'] = Lang::get('order_goods_quantity1').$order_goods_count.Lang::get('order_goods_quantity2').$order_goods_str;
				$orders[$key]['unsend_amount'] = $unsend_amount;
				$order_goods_count == $ready_count && $orders[$key]['goods_already'] = 1;
				
				$orders[$key]['show_lack_goods_btn'] = $lack_goods_amount_for_showbtn_amount > 0 ? true : false;
				/* if($_GET['tomorrow'] == '1' && $include_tomorrow_goods == false)
				{
				    unset($orders[$key]);
				}
				if($unset_flag === true)
				{
				    unset($orders[$key]);
				} */
				
				
			}
			if($attached)
			{
				$orders[$key]['refunds'] = $this->_orderrefund_mod->get("receiver_id='{$bh_id}' AND order_id='{$value['order_id']}' AND type='1' AND closed='0' AND status='0' ");
				$orders[$key]['apply_fee'] = $this->_orderrefund_mod->get("sender_id='{$bh_id}' AND order_id='{$value['order_id']}' AND type='2' AND closed='0' AND status='0' ");
				$model_ordernote = & m('behalfordernote');
				$orders[$key]['ordernote']=$model_ordernote->get("order_id='{$value['order_id']}'");
			}
			//代发主动退订单缺货款和发错货物赔偿运费
			if($compensation)
			{
			    $result_compensation = $mod_order_compensation_behalf->find(array('conditions'=>"order_id='".$value['order_id']."'"));
			    if($result_compensation)
			    {
			        foreach ($result_compensation as $ck=>$cv)
			        {
			            $cv['type'] == 'lack' && $orders[$key]['compensation_behalf_lack'] = $cv;
			            $cv['type'] == 'deli' && $orders[$key]['compensation_behalf_deli'] = $cv;
			        }
			    }			   
			}
		}
		 
		//dump($orders);
		$page ['item_count'] = $this->_order_mod->getCount ();
		$this->_format_page ( $page );
		$page['start_number'] = (intval($page['curr_page'])-1)*intval($page['pageper'])+1;
		if($page['start_number'] + $page['pageper'] <= $page['item_count'])
		{
			$page['end_number'] = intval($page['start_number']) + intval($page['pageper']) -1;
		}
		else
		{
			$page['end_number'] = intval($page['start_number']) - 1 + floor(intval($page['item_count']) % intval($page['pageper']));
		}
	
		$this->assign ( 'types', array (
				'all' => Lang::get ( 'all_orders' ),
				'pending' => Lang::get ( 'pending_orders' ),
				'submitted' => Lang::get ( 'submitted_orders' ),
				'accepted' => Lang::get ( 'accepted_orders' ),
				'shipped' => Lang::get ( 'shipped_orders' ),
				'finished' => Lang::get ( 'finished_orders' ),
				'canceled' => Lang::get ( 'canceled_orders' )
		) );
		$this->assign ( 'type', $_GET ['type'] );
		$this->assign ( 'orders', $orders );
		$this->assign ( 'page_info', $page );
	}
	
	/**
	 *    打印时筛选明天有的商品
	 */
    private function _get_order_ids_with_tomorrow_forprinter($tomorrow_orders, $flag)
    {
        //dump($tomorrow_orders);

        $tomorrow_order_ids = array();
        if($tomorrow_orders)
        {
            if($flag == '2')//不含 明天有
            {
                foreach ($tomorrow_orders as $tkey=>$tvalue)
                {
                    if($tvalue['gwh'])
                    {
                        foreach ($tvalue['gwh'] as $tgoods)
                        {
                            if($tgoods['goods_status'] == BEHALF_GOODS_TOMORROW)
                            {
                                unset($tomorrow_orders[$tkey]);
                            }
                        }
                    }
                }
            }
            if($flag == '1')//只含 明天有
            {

                foreach ($tomorrow_orders as $tkey=>$tvalue)
                {
                    if($tvalue['gwh'])
                    {
                        $unset_flag = false; //订单商品是否包含明天有商品
                        foreach ($tvalue['gwh'] as $tgoods)
                        {
                            if($tgoods['goods_status'] == BEHALF_GOODS_TOMORROW)
                            {
                                $unset_flag = true;
                            }
                        }
                        if($unset_flag == false)
                        {
                            unset($tomorrow_orders[$tkey]);
                        }
                    }
                }
            }
            foreach ($tomorrow_orders as $torder)
            {
                $tomorrow_order_ids[] = $torder['order_id'];
            }
        }
        return $tomorrow_order_ids;
    }

	
	/**
	 *   获取订单包含 明天有的货物
	 *   @param $orders 订单
	 *   @parameter $goods_status_arr 商品状态
	 */
    private function _get_orders_with_goodsstatus($orders, $goods_status_arr)
    {
        //dump($orders);
        $order_ids = array();
        if($orders && $goods_status_arr)
        {
                foreach ($orders as $tkey=>$tvalue)
                {
                    if($tvalue['gwh'])
                    {                        
                        $gwh_status = array();
                        foreach ($tvalue['gwh'] as $tgoods)
                        {                           
                            if(!in_array($tgoods['goods_status'], $gwh_status))
                            {
                                $gwh_status[] = $tgoods['goods_status'];
                            }
                        }
                        
                        $gwh_status = array_unique($gwh_status);
                        $gwh_status = array_filter($gwh_status);
                         //print_r($gwh_status);
                           
                        $add_flag = true;//
                        foreach ($goods_status_arr as $goods_status_value)
                        {
                            if(!in_array($goods_status_value, $gwh_status))
                            {
                                $add_flag = false;
                            }
                        }
                       $add_flag == true &&  $order_ids[] = $tvalue['order_id'];
                       
                    }
                }
        }
        //echo $tomorrow_flag."<br>";
        //dump($order_ids);
        return $order_ids;
    }

	/**
	 * 
	 */
	private function _getInvoicenoConditions($invoiceno,$bh_id)
	{
		$condition = '';
		$order_ids = array();
		$orders = $this->_order_mod->find("bh_id='{$bh_id}' AND invoice_no='{$invoiceno}'");
		$order_refunds = $this->_orderrefund_mod->find("receiver_id='{$bh_id}' AND invoice_no='{$invoiceno}'");
		if($orders)
		{
			foreach ($orders as $order)
				if(!in_array($order['order_id'], $order_ids))
					$order_ids[] = $order['order_id'];
		}
		if($order_refunds)
		{
			foreach ($order_refunds as $rorder)
				if(!in_array($rorder['order_id'], $order_ids))
					$order_ids[] = $rorder['order_id'];
		}
		if($order_ids)
		{
			$condition = " AND ".db_create_in($order_ids,'order_alias.order_id');
		}
		
		return $condition;
	}

	/**
	 * @param _GET
	 */
	private function _getMarketConditions($mk_id)
	{
		$mk_id = intval ($mk_id);
		$mk_ids = array ();
		$mk_ids [] = $mk_id;
		$son_ids = $this->_market_mod->get_list ( $mk_id );
		foreach ( $son_ids as $sid )
		{
			$mk_ids [] = $sid ['mk_id'];
		}
		$mk_stores = $this->_market_mod->getRelatedData ( 'has_store', $mk_ids );
		$mk_storeids = array ();
		foreach ( $mk_stores as $mst )
		{
			$mk_storeids [] = $mst ['store_id'];
		}
		$store_conditions = '';
		if (! empty ( $mk_storeids ))
		{
			$store_conditions .= ' AND order_alias.seller_id IN (' . implode ( ',', $mk_storeids ) . ') ';
		}
		else
		{
			$store_conditions .= ' AND order_alias.seller_id is NULL';
		}
		return $store_conditions;
		
		//$this->assign ( "query_mkid", $mk_id );
	}

	/**
	 * @param query_refunds_condition
	 * @param orderrefund_ids
	 */
	private function _getApplyFeeConditions($bh_id)
	{
		$query_refunds_condition = '';
		$orderrefund_result = $this->_orderrefund_mod->find ( array (
				'conditions' => "sender_id='{$bh_id}' AND status=0 AND closed=0 AND type=2",
				'fields' => 'order_id'
		) );
		if ($orderrefund_result)
		{
			$orderrefund_ids = array ();
			foreach ( $orderrefund_result as $value )
			{
				if (! in_array ( $value ['order_id'], $orderrefund_ids )) $orderrefund_ids [] = $value ['order_id'];
			}
			$query_refunds_condition = " AND " . db_create_in ( $orderrefund_ids, 'order_alias.order_id' ) . " AND " . db_create_in ( array (
					ORDER_ACCEPTED,
					ORDER_SHIPPED,
					ORDER_FINISHED
			), 'order_alias.status' );
		}
		return $query_refunds_condition;
	}

	/**
	 * @param query_refunds_condition
	 * @param orderrefund_ids
	 */
	private function _getRefundConditions($bh_id)
	{
		$query_refunds_condition = '';
		$orderrefund_result = $this->_orderrefund_mod->find ( array (
				'conditions' => "receiver_id='{$bh_id}' AND status=0 AND closed=0 AND type=1",
				'fields' => 'order_id'
		) );
		if ($orderrefund_result)
		{
			$orderrefund_ids = array ();
			foreach ( $orderrefund_result as $value )
			{
				if (! in_array ( $value ['order_id'], $orderrefund_ids )) $orderrefund_ids [] = $value ['order_id'];
			}
			$query_refunds_condition = " AND " . db_create_in ( $orderrefund_ids, 'order_alias.order_id' ) . " AND " . db_create_in ( array (
					ORDER_ACCEPTED,
					ORDER_SHIPPED,
					ORDER_FINISHED
			), 'order_alias.status' );
		}
		
		return $query_refunds_condition;
	}

	/**
	 * @param unknown $bh_id
	 * @return string
	 */
	private function _getRefuseConditions($bh_id)
	{
		$query_refunds_condition = '';
		$orderrefund_result = $this->_orderrefund_mod->find ( array (
				'conditions' => "receiver_id='{$bh_id}' AND status=2 AND closed=0 AND type=1",
				'fields' => 'order_id',
				'order' => 'apply_amount DESC'
		) );
		if ($orderrefund_result)
		{
			$orderrefund_ids = array ();
			foreach ( $orderrefund_result as $value )
			{
				if (! in_array ( $value ['order_id'], $orderrefund_ids )) $orderrefund_ids [] = $value ['order_id'];
			}
			$query_refunds_condition = " AND " . db_create_in ( $orderrefund_ids, 'order_alias.order_id' ) . " AND " . db_create_in ( array (
					ORDER_ACCEPTED,
					ORDER_SHIPPED,
					ORDER_FINISHED
			), 'order_alias.status' );
		}
		
		return $query_refunds_condition;
	}

	/**
	 * @param unknown $goodsno
	 * @param unknown $bh_id
	 * @return void|string
	 */
	private function _getGoodsNoConditions($goodsno,$bh_id)
	{
		$goodsware_condition = '';
		$goodsware = $this->_goods_warehouse_mod->get("goods_no='".trim($_GET['goods_no'])."' AND bh_id='{$bh_id}'");
		if($goodsware)
		{
			$goodsware_condition = " AND order_alias.order_id={$goodsware['order_id']}";
		}
		return $goodsware_condition;
	}

	/**
	 * @param unknown $sellerBM
	 * @param unknown $bh_id
	 * @return void|string
	 */
	 private function _getSellerBMConditions($sellerBM , $bh_id)
	 {
		// 找出代发所有订单
		$query_goods_seller_bm = trim ($sellerBM);
		$query_goods_seller_bm_condition = '';
		$query_goods_seller_bm_orders = $this->_order_mod->find ( array (
				'conditions' => "bh_id='{$bh_id}'",
				'fields' => 'order_id'
		) );
		if (! empty ( $query_goods_seller_bm_orders ))
		{
			$query_goods_seller_bm_orders_ids = array ();
			foreach ( $query_goods_seller_bm_orders as $value )
			{
				$query_goods_seller_bm_orders_ids [] = $value ['order_id'];
			}
			// 找出 有传入关键字的订单
			$attrs = $model_goods->get_Mem_list ( array (
					'order' => 'views desc',
					'fields' => 'g.goods_id,',
					'limit' => 20,
					'conditions_tt' => array (
							$query_goods_seller_bm
					)
			), null, false, true, $total_found );

			$query_goods_seller_bm_goods_ids = array ();
			foreach ( $attrs as $value )
			{
				if (! in_array ( $value ['goods_id'], $query_goods_seller_bm_goods_ids )) $query_goods_seller_bm_goods_ids [] = $value ['goods_id'];
			}
			// dump($attrs);
			$query_goods_seller_bm_order_goods = $this->_ordergoods_mod->find ( array (
					'conditions' => db_create_in ( $query_goods_seller_bm_goods_ids, 'goods_id' ),
					'fields' => 'order_id'
			) );
			$query_goods_seller_bm_order_result = array ();
			foreach ( $query_goods_seller_bm_order_goods as $value )
			{
				if (! in_array ( $value ['order_id'], $query_goods_seller_bm_order_result )) $query_goods_seller_bm_order_result [] = $value ['order_id'];
			}
			//$this->assign ( "query_goods_seller_bm", $query_goods_seller_bm );
			if ($query_goods_seller_bm_order_result)
			{
				$query_goods_seller_bm_condition = " AND " . db_create_in ( $query_goods_seller_bm_order_result, 'order_alias.order_id' );
			}
			// dump($query_goods_name_order_result);
		}
		return $query_goods_seller_bm_condition;
	}

	/**
	 * 
	 * @param 是否缺货 $oos
	 * @param 代发 $bh_id
	 * @return string
	 */
	 private function _getOOSConditions($oos,$bh_id)
	 {
		$query_oos = intval ( trim ($oos) ) == 1 ? 1 : 0;
		$query_oos_condition = '';
		if ($query_oos)
		{
			$query_oos_orders = $this->_order_mod->find ( array (
					'conditions' => "bh_id='{$bh_id}' AND status" . db_create_in ( array (
							ORDER_ACCEPTED,
							ORDER_SHIPPED
					) ),
					'fields' => 'order_id'
			) );
			if ($query_oos_orders)
			{
				$query_oos_order_ids = array ();
				foreach ( $query_oos_orders as $value )
				{
					$query_oos_order_ids [] = $value ['order_id'];
				}
				// 找出 有传入关键字的订单
				$query_order_goods = $this->_ordergoods_mod->find ( array (
						'conditions' => db_create_in ( $query_oos_order_ids, 'order_id' ) . " AND oos_value = 0",
						'fields' => 'order_id'
				) );
				if ($query_order_goods)
				{
					$query_oos_order_result = array ();
					foreach ( $query_order_goods as $value )
					{
						if (! in_array ( $value ['order_id'], $query_oos_order_result ))
						{
							$query_oos_order_result [] = $value ['order_id'];
						}
					}
					//$this->assign ( 'query_oos', $query_oos );
					if ($query_oos_order_result)
					{
						$query_oos_condition = " AND " . db_create_in ( $query_oos_order_result, 'order_alias.order_id' );
					}
				}
				
			}
		}
		return $query_oos_condition;
	 }

	
	/**
	 * @param 商品名称 $goods_name
	 * @param 代发 $bh_id
	 * @return string
	 */
	private function _getGoodsNameConditions($goods_name,$bh_id)
	{
		$query_goods_name = trim ( $goods_name );
		$query_goods_condition = '';
		//商品名称查询
		if ($query_goods_name)
		{
			// 找出代发所有订单
			$query_goods_name_orders = $this->_order_mod->find ( array (
					'conditions' => "bh_id='{$bh_id}'",
					'fields' => 'order_id'
			) );
			if (! empty ( $query_goods_name_orders ))
			{
				$query_goods_name_order_ids = array ();
				foreach ( $query_goods_name_orders as $value )
				{
					$query_goods_name_order_ids [] = $value ['order_id'];
				}
				// 找出 有传入关键字的订单
				$query_order_goods = $this->_ordergoods_mod->find ( array (
						'conditions' => db_create_in ( $query_goods_name_order_ids, 'order_id' ) . " AND goods_name like '%" . $query_goods_name . "%'",
						'fields' => 'order_id'
				) );
				$query_goods_name_order_result = array ();
				foreach ( $query_order_goods as $value )
				{
					if (! in_array ( $value ['order_id'], $query_goods_name_order_result )) $query_goods_name_order_result [] = $value ['order_id'];
				}
			
				if ($query_goods_name_order_result)
				{
					$query_goods_condition = " AND " . db_create_in ( $query_goods_name_order_result, 'order_alias.order_id' );
				}
				// dump($query_goods_name_order_result);
			}
		}
		return $query_goods_condition;
	}

	
	
	
	
		
	function _goods_status_translator($goods_action)
	{
		switch ($goods_action)
		{
			case 'warehouse':    //入库
				return BEHALF_GOODS_READY;
				break;
			case 'tomorrow':         //明天
				return BEHALF_GOODS_TOMORROW;
				break;
			case 'unformed':     //未出货
				return BEHALF_GOODS_UNFORMED;
				break;
			case 'outdated':   //已下架
				return BEHALF_GOODS_UNSALE;
				break;
			case 'refused':   //已退货
				return BEHALF_GOODS_REBACK;
				break;
			default:            //备货中
				return BEHALF_GOODS_PREPARED;
				break;
		}
	}
	
	function _goods_chinese_translator($goods_action)
	{
		switch ($goods_action)
		{
			case 'warehouse':    //已备好
				return Lang::get('goods_warehouse');
				break;
			case 'tomorrow':         //明天
				return Lang::get('goods_tomorrow');
				break;
			case 'unformed':     //未出货
				return Lang::get('goods_unformed');
				break;
			case 'outdated':   //已下架
				return Lang::get('goods_unsaled');
				break;
			case 'refused':   //已退货
				return Lang::get('goods_refused');
				break;
			default:            //备货中
				return Lang::get('goods_prepared');
				break;
		}
	}
	
	/**
	 * 获取本代发 正在退款中的订单
	 */
	protected function get_refunds_orders()
	{
	    $order_ids = $this->_orderrefund_mod->getCol("SELECT order_id FROM ".
	        $this->_orderrefund_mod->table." WHERE receiver_id='{$this->visitor->get('has_behalf')}' AND ".
	            "status='0' AND closed='0' AND type='1'");
	
	        return $order_ids;
	}
	
	
	function _run_action()
	{
		$this->_display_member_info();
		parent::_run_action();	
	}
	
	/*
	 * 去除开头 "中国"二字
	 */
	protected function _remove_China($str)
	{
		$str = $this->_trimall($str);
		mb_internal_encoding("UTF-8");
		if(strpos($str,'中国') == 0 && strpos($str,'中国') !== false )
			$str = mb_substr($str, 2);
		return $str;
	}
	
	/**
	 * 	删除前后空格
	 */
	protected function _trimall($str)
	{
		$qian=array(" ","　","\t","\n","\r");
		$hou=array("","","","","");
		$str = str_replace($qian,$hou,$str);
		return strval(preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", "", strip_tags($str)));
	}
	
	function _get_prov_city($region_id)
	{
		$res = array();
	
		//$model_region =& m('region');
		$regions = $this->_region_mod->get_layer($region_id);
		$res['prov'] = $regions[1]['region_name'];
		$city = $regions[2]['region_name'];
		for($i=3;$i<count($regions);$i++)
		{
		$city .= ','.$regions[$i]['region_name'];
		}
        $res['city'] = $city;
		return $res;
	}
	
	/**
	 * 商家编码 ：市场转拼音，去掉价格
	 * @param 商家编码 $attr_value
	 */
	protected function _Attrvalue2Pinyin($attr_value)
	{
		//商品编码转换为 拼音首字母
		$goods_sku = explode('_',trim($attr_value));
		$result = ecm_iconv("UTF-8", "GBK",$goods_sku[0]);
		$result = strtoupper(GetPinyin($result,1,1));
		$goods_sku[0] = $result;
		//去掉价格
		foreach ($goods_sku as $pkey=>$pvalue)
		{
			$goods_sku[$pkey] = str_replace("&nbsp;", "",$this->_trimall($pvalue));
			if(preg_match('/P\d+/i', trim($pvalue)))
				unset($goods_sku[$pkey]);
		}
	
		$goods_sku = array_filter($goods_sku);//去掉空项
		return implode("_", $goods_sku);
	}
	
	/**
	 * 拿货员对应的代发
	 */
	protected function _get_bh_id()
	{
		$bh_id = 0;
		$login_id = $this->visitor->get('user_id');
		if($this->visitor->get('pass_behalf'))
		{
			$bh_id = $login_id;
		}
		else
		{
			$member_info = ms()->user->_local_get($login_id);
			$member_info['behalf_goods_taker'] && $bh_id = $member_info['behalf_goods_taker'];
		}
		return $bh_id;
	}
	
	/**
	 * 导入bootstrap datatable
	 * @param string $s
	 */
	protected function _import_css_js($s='simple')
	{
		if($s == 'dt')
		{
			$this->import_resource(array(
					'style'=>'DataTables/css/jquery.dataTables.min.css,DataTables/css/dataTables.bootstrap.min.css',
					'script'=>'bootstrap/js/json2.js,DataTables/js/jquery.dataTables.min.js,DataTables/js/dataTables.bootstrap.min.js'
			));
		}
		
		if($s == 'dtall')
		{
			$this->import_resource(array(
					'style'=>'DataTables/css/jquery.dataTables.min.css,DataTables/css/dataTables.bootstrap.min.css,DataTables/css/buttons.dataTables.min.css',
					'script'=>'DataTables/js/jquery.dataTables.min.js,DataTables/js/dataTables.bootstrap.min.js,DataTables/js/dataTables.buttons.min.c.js'
			));
		}
	}
	
	/**
	 * 开始数据库事务
	 * @return boolean
	 */
	protected function _start_transaction()
	{
		//开始数据库事务
		$db_transaction_success = true;//默认事务执行成功，不用回滚
		$db_transaction_begin = db()->query("START TRANSACTION");
		if($db_transaction_begin === false)
		{
			//$this->pop_warning('fail_caozuo');
			$db_transaction_success = false;
		}
		
		return $db_transaction_success;
	}
	
	/**
	 * 提交或回滚
	 * @param unknown $success
	 * @return boolean
	 */
	protected function _end_transaction($success)
	{
		if($success === false)
		{
			db()->query("ROLLBACK");//回滚
			return false;
		}
		else
		{
			db()->query("COMMIT");//提交
			return true;
		}
	}
	
	
	function _assign_leftmenu($menu_name)
	{
		if($this->visitor->get('pass_behalf'))
		{  
			$menus = $this->_get_leftmenu($menu_name);
		}
		else 
		{
			$menus = $this->_get_leftmenu_p($menu_name);
		}
		

		$cur_name = $_GET['act'] ? trim($_GET['act']) :'index';
		$this->assign('_curitem',$cur_name);
		
		if($menus)
		{
			foreach ($menus as $key=>$menu)
			{
				if(in_array($cur_name,array_keys($menu['submenu']))) $menus[$key]['name'] = 'hit';
			}
		}
		
		$this->assign('nav_name',$menu_name);
		$this->assign('_left_menu',$menus);
	}
		
	function _get_leftmenu($menu)
	{
		$array = array(
			'dashboard'=>array(
					     '0'=> array(
					           		'name'=>'',
									'text'=>Lang::get('usual_mani'),
					     			'icon'=>'menu-hamburger',
									'submenu'   =>array(
											'index'  => array(
													'text'  => Lang::get('welcome_page'),
													'url'   => 'index.php?module=behalf&act=index',
													'name'  => 'index',
													'icon'  => 'triangle-right',
										    ),
											'order_list'  => array(
													'text'  => Lang::get('order_list'),
													'url'   => 'index.php?module=behalf&act=order_list',
													'name'  => 'order_list',
													'icon'  => 'triangle-right',
										    ),
											'mb_print'  => array(
													'text'  => Lang::get('mb_print'),
													'url'   => 'index.php?module=behalf&act=mb_print',
													'name'  => 'mb_print',
													'icon'  => 'triangle-right',
										    ),
									)
				         ),
					     '1'=> array(
					           		'name'=>'',
									'text'=>Lang::get('helper'),
					     			'icon'=>'menu-hamburger',
									'submenu'   =>array(
											'faq'  => array(
													'text'  => Lang::get('faq'),
													'url'   => 'index.php?module=behalf&act=faq',
													'name'  => 'faq',
													'icon'  => 'triangle-right',
										    ),
											'myquestion'  => array(
													'text'  => Lang::get('myquestion'),
													'url'   => 'index.php?module=behalf&act=myquestion',
													'name'  => 'myquestion',
													'icon'  => 'triangle-right',
										    )
											
									)
				         )
			),
			'setting'=>array(
					'0'=> array(
							'name'=>'',
							'text'=>Lang::get('base_setting'),
							'icon'=>'menu-hamburger',
							'submenu'   =>array(
									'see_behalf'  => array(
											'text'  => Lang::get('see_behalf'),
											'url'   => 'index.php?module=behalf&act=see_behalf',
											'name'  => 'see_behalf',
											'icon'  => 'triangle-right',
									),
									'set_behalf'  => array(
											'text'  => Lang::get('set_behalf'),
											'url'   => 'index.php?module=behalf&act=set_behalf',
											'name'  => 'set_behalf',
											'icon'  => 'triangle-right',
									),
									'set_delivery'  => array(
											'text'  => Lang::get('set_delivery'),
											'url'   => 'index.php?module=behalf&act=set_delivery',
											'name'  => 'set_delivery',
											'icon'  => 'triangle-right',
									),
									'set_delivery_fee'  => array(
											'text'  => Lang::get('set_delivery_fee'),
											'url'   => 'index.php?module=behalf&act=set_delivery_fee',
											'name'  => 'set_delivery_fee',
											'icon'  => 'triangle-right',
									),
									'set_behalf_market'  => array(
											'text'  => Lang::get('set_behalf_market'),
											'url'   => 'index.php?module=behalf&act=set_behalf_market',
											'name'  => 'set_behalf_market',
											'icon'  => 'triangle-right',
									)
							)
					),
					'1'=> array(
							'name'=>'',
							'text'=>Lang::get('account_setting'),
							'icon'=>'menu-hamburger',
							'submenu'   =>array(
									'set_mbaccount'  => array(
											'text'  => Lang::get('set_mbaccount'),
											'url'   => 'index.php?module=behalf&act=set_mbaccount',
											'name'  => 'set_mbaccount',
											'icon'  => 'triangle-right',
									),
									'getMailCounter'  => array(
											'text'  => Lang::get('getMailCounter'),
											'url'   => 'index.php?module=behalf&act=getMailCounter',
											'name'  => 'getMailCounter',
											'icon'  => 'triangle-right',
									)
							)
					),
					'2'=> array(
							'name'=>'',
							'text'=>Lang::get('peihuo_manage'),
							'icon'=>'menu-hamburger',
							'submenu'   =>array(
									/* 'set_markettaker'  => array(
											'text'  => Lang::get('set_markettaker'),
											'url'   => 'index.php?module=behalf&act=set_markettaker',
											'name'  => 'set_markettaker',
											'icon'  => 'menu-right',
									), */
									'manage_goodstaker'  => array(
											'text'  => Lang::get('manage_goodstaker'),
											'url'   => 'index.php?module=behalf&act=gen_taker_list&act=manage_goodstaker',
											'name'  => 'manage_goodstaker',
											'icon'  => 'triangle-right',
									)
							)
					),
			),
			'order_manage'=>array(
					'0'=> array(
							'name'=>'',
							'text'=>Lang::get('order_manage'),
							'icon'=>'menu-hamburger',
							'submenu'   =>array(
									'order_list'  => array(
											'text'  => Lang::get('order_list'),
											'url'   => 'index.php?module=behalf&act=order_list',
											'name'  => 'order_list',
											'icon'  => 'triangle-right',
									)
							)
					),
					'1'=> array(
							'name'=>'',
							'text'=>Lang::get('peihuo_manage'),
							'icon'=>'menu-hamburger',
							'submenu'   =>array(
									'gen_taker_list'  => array(
											'text'  => Lang::get('gen_taker_list'),
											'url'   => 'index.php?module=behalf&act=gen_taker_list',
											'name'  => 'gen_taker_list',
											'icon'  => 'triangle-right',
									),
									'manage_taker_list'  => array(
											'text'  => Lang::get('manage_taker_list'),
											'url'   => 'index.php?module=behalf&act=manage_taker_list',
											'name'  => 'manage_taker_list',
											'icon'  => 'triangle-right',
									),
									'manage_goods_warehouse'  => array(
											'text'  => Lang::get('manage_goods_warehouse'),
											'url'   => 'index.php?module=behalf&act=manage_goods_warehouse',
											'name'  => 'manage_goods_warehouse',
											'icon'  => 'triangle-right',
									)
							)
					),
					'2'=> array(
							'name'=>'',
							'text'=>Lang::get('order_stat'),
							'icon'=>'menu-hamburger',
							'submenu'   =>array(
									'stat_shipped_order'  => array(
											'text'  => Lang::get('stat_shipped_order'),
											'url'   => 'index.php?module=behalf&act=stat_shipped_order',
											'name'  => 'stat_shipped_order',
											'icon'  => 'triangle-right',
									),
									'stat_enter_warehouse'  => array(
											'text'  => Lang::get('stat_enter_warehouse'),
											'url'   => 'index.php?module=behalf&act=stat_enter_warehouse',
											'name'  => 'stat_enter_warehouse',
											'icon'  => 'triangle-right',
									)
							)
					),
			),
			'print_manage'=>array(
					'0'=> array(
							'name'=>'',
							'text'=>Lang::get('mb_print'),
							'icon'=>'menu-hamburger',
							'submenu'   =>array(
									'mb_print'  => array(
											'text'  => Lang::get('mb_print'),
											'url'   => 'index.php?module=behalf&act=mb_print',
											'name'  => 'mb_print',
											'icon'  => 'triangle-right',
									)
							)
					),
					'1'=> array(
							'name'=>'',
							'text'=>Lang::get('common_print'),
							'icon'=>'menu-hamburger',
							'submenu'   =>array(
									'common_print'  => array(
											'text'  => Lang::get('common_print'),
											'url'   => 'index.php?module=behalf&act=common_print',
											'name'  => 'common_print',
											'icon'  => 'triangle-right',
									)
							)
					),
			),
			'client_manage'=>array(
					'0'=> array(
							'name'=>'',
							'text'=>Lang::get('client_manage'),
							'icon'=>'menu-hamburger',
							'submenu'   =>array(
									'mb_print'  => array(
											'text'  => Lang::get('member_list'),
											'url'   => 'index.php?module=behalf&act=member_list',
											'name'  => 'member_list',
											'icon'  => 'triangle-right',
									),
									'vip_list'  => array(
											'text'  => Lang::get('vip_list'),
											'url'   => 'index.php?module=behalf&act=vip_list',
											'name'  => 'vip_list',
											'icon'  => 'triangle-right',
									),
									'black_list'  => array(
											'text'  => Lang::get('black_list'),
											'url'   => 'index.php?module=behalf&act=black_list',
											'name'  => 'black_list',
											'icon'  => 'triangle-right',
									),
									'store_black_list'  => array(
											'text'  => Lang::get('store_black_list'),
											'url'   => 'index.php?module=behalf&act=store_black_list',
											'name'  => 'store_black_list',
											'icon'  => 'triangle-right',
									)
							)
					),
			),
			'other_manage'=>array(
					
			)
		);
	
		return $array[$menu];
	}
	
	/**
	 * 拿货员菜单
	 * @param unknown $menu
	 * @return Ambigous <multitype:, multitype:multitype:string mixed multitype:multitype:string Ambigous <mixed, string>    , multitype:multitype:string mixed multitype:multitype:string Ambigous <mixed, string>    multitype:string multitype:multitype:string Ambigous <mixed, string>   Ambigous <mixed, string, unknown>  >
	 */
	function _get_leftmenu_p($menu)
	{
		$array = array(
			'dashboard'=>array(
					     '0'=> array(
					           		'name'=>'',
									'text'=>Lang::get('usual_mani'),
					     			'icon'=>'menu-hamburger',
									'submenu'   =>array(
											'index'  => array(
													'text'  => Lang::get('welcome_page'),
													'url'   => 'index.php?module=behalf&act=index',
													'name'  => 'index',
													'icon'  => 'menu-right',
										    ) , 
											/*'gen_taker_list'  => array(
													'text'  => Lang::get('gen_taker_list'),
													'url'   => 'index.php?module=behalf&act=gen_taker_list',
													'name'  => 'gen_taker_list',
													'icon'  => 'menu-right',
										    ), */
											'manage_goods_warehouse'  => array(
													'text'  => Lang::get('manage_goods_warehouse'),
													'url'   => 'index.php?module=behalf&act=manage_goods_warehouse',
													'name'  => 'manage_goods_warehouse',
													'icon'  => 'menu-right',
										    )/* ,
											'stat_enter_warehouse'  => array(
													'text'  => Lang::get('stat_enter_warehouse'),
													'url'   => 'index.php?module=behalf&act=stat_enter_warehouse',
													'name'  => 'stat_enter_warehouse',
													'icon'  => 'menu-right',
											) */
											/* 'stat_shipped_order'  => array(
													'text'  => Lang::get('stat_shipped_order'),
													'url'   => 'index.php?module=behalf&act=stat_shipped_order',
													'name'  => 'stat_shipped_order',
													'icon'  => 'menu-right',
										    ),
											'mb_print'  => array(
													'text'  => Lang::get('mb_print'),
													'url'   => 'index.php?module=behalf&act=mb_print',
													'name'  => 'mb_print',
													'icon'  => 'menu-right',
										    ), */
									)
				         ),
					     '1'=> array(
					           		'name'=>'',
									'text'=>Lang::get('helper'),
					     			'icon'=>'menu-hamburger',
									'submenu'   =>array(
											'faq'  => array(
													'text'  => Lang::get('faq'),
													'url'   => 'index.php?module=behalf&act=faq',
													'name'  => 'faq',
													'icon'  => 'menu-right',
										    ),
											'myquestion'  => array(
													'text'  => Lang::get('myquestion'),
													'url'   => 'index.php?module=behalf&act=myquestion',
													'name'  => 'myquestion',
													'icon'  => 'menu-right',
										    )
											
									)
				         )
			)
		);
	
		return $array[$menu];
	}
	
	function _display_member_info()
	{
		$login_type = $this->visitor->get('pass_behalf')?'admin':'employee';
		 
		$this->assign('login_type',$login_type);
		$this->assign('navtime',gmtime());
		$this->assign('login_name',$this->visitor->get('pass_behalf')? Lang::get('behalf_manager'):Lang::get('goods_manager'));
		$this->assign('user_name',$this->visitor->get('user_name'));
	}
	
	/**
	 * 获取用户联系方式
	 * @param 用户id $user_id
	 * @return object
	 */
	function _get_member_profile($user_id)
	{
	    $ms =& ms();    //连接用户系统
	    $mprofile = $ms->user->_local_get(array(
	        'conditions'=>'user_id='.$user_id,
	        'fields'=>'im_qq,im_aliww,phone_mob',
	    ));
	    return $mprofile;
	}
	
	protected function faq()
	{
		$model_behalfhelper = & m('behalfhelper');
		if(IS_POST)
		{
			$data = array(
					'pid'=>trim($_POST['pid']),
					'content'=>html_filter(trim($_POST['content'])),
					'login_id'=>$this->visitor->get('user_id'),
					'login_name'=>$this->visitor->get('user_name'),
					'login_ip'=>real_ip(),
					'create_time'=>gmtime()
			);
			if(!empty($data['content']))
			{
				$affection_rows = $model_behalfhelper->add($data);
			}
		}
		$list = $model_behalfhelper->find(array(
				'conditions'=>"pid ='0' ",
				'order' => 'id DESC'
		));
		if($list)
		{
			foreach ($list as $key=>$value)
			{
				$sublist = $model_behalfhelper->find("pid={$value['id']}");
				$list[$key]['anwsers'] = $sublist;
			}
		}
		$this->_assign_leftmenu('dashboard');
		$this->assign('list',$list);
		$this->display('behalf.info.helper.html');
	}
	
	protected function myquestion()
	{
		if(IS_POST)
		{
			$data = array(
				'title'=>html_filter(trim($_POST['title'])),
				'login_id'=>$this->visitor->get('user_id'),
				'login_name'=>$this->visitor->get('user_name'),
				'login_ip'=>real_ip(),
				'create_time'=>gmtime()
			);
			$model_behalfhelper = & m('behalfhelper');
			$affection_rows = $model_behalfhelper->add($data);
			if($affection_rows)
			{
				$this->json_result(1);
			}
			else
			{
				$this->json_error('error');
			}
		}
		else
		{
			$this->_assign_leftmenu('dashboard');
			$this->display('behalf.info.question.html');
		}
	}
}