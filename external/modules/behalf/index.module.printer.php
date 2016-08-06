<?php
/**
 * 代发打印
 * @author tanaiquan
 *
 */
class BehalfPrinterModule extends BehalfBaseModule
{
	function __construct()
	{
		$this->BehalfPrinterModule();
	}
	
	function BehalfPrinterModule()
	{
		parent::__construct();	
	}
	
	/**
	 * 面单打印
	 */
	public function mb_print()
	{
		$bh_id = $this->visitor->get('has_behalf');
		$this->_get_orders(true,'accepted',false,false);//include goods
	
		//$type = (isset($_GET['type']) && $_GET['type'] != '') ? trim($_GET['type']) : 'all_orders';
		//$this->_curmenu1($type);
		$behalf_info = $this->_behalf_mod->get($bh_id);
		$behalf_info['region_name'] = $this->_remove_China($behalf_info['region_name']);
	
		$this->assign('show_print',true);
		$this->_assign_leftmenu('print_manage');
		$this->assign('datevar',gmtime());
		$this->assign('behalf', $behalf_info);
		$this->assign('deliverys',$this->_behalf_mod->getRelatedData('has_delivery',$bh_id));
		$this->_import_css_js('dtall');
		$this->display('behalf.printer.md_print.html');
	}
	
	/**
	 * 普通快递单打印
	 */
	public function common_print()
	{
		$bh_id = $this->visitor->get('has_behalf');
		$this->_get_orders(true,'accepted',false,false);//include goods
		$behalf_info = $this->_behalf_mod->get($bh_id);
		$behalf_info['region_name'] = $this->_remove_China($behalf_info['region_name']);
	
		$print_templates = Conf::get('behalf_print_template_'.$this->visitor->get('user_id'));
		$this->assign('print_templates',stripslashes_deep($print_templates));
	
		$this->assign('show_print',true);
		$this->assign('datevar',gmtime());
		$this->assign('behalf', $behalf_info);
		$this->assign('deliverys',$this->_behalf_mod->getRelatedData('has_delivery',$bh_id));
		$this->_assign_leftmenu('print_manage');
		$this->_import_css_js('dtall');
		$this->display('behalf.printer.common_print.html');
	}
	
	/**
	 * 普通打印保存快递单号
	 */
	public function save_invoiceno()
	{
		$invoice_no = isset($_POST['invoiceno']) && $_POST['invoiceno'] ? trim($_POST['invoiceno']):'';
		$id = isset($_POST['id']) && $_POST['id'] ? trim($_POST['id']):'';
		$id && $idarr = explode('_', $id);
	
		if(!preg_match('/\d+/', $idarr[1]) || !preg_match('/([0-9]|[a-z]|[A-Z]){10,20}/', $invoice_no))
		{
			//echo Lang::get('invoice_no_input_error');
			//echo 'error';
			return false;
		}
		$check = exist_invoiceno($invoice_no);
		if($check)
		{
			return false;
		}
		$affect_rows = $this->_order_mod->edit($idarr[1],array('invoice_no'=>$invoice_no));
		if($affect_rows)
			return $invoice_no;
		//dump($idarr[1]);
	}
	
	public function check_invoiceno()
	{
		$invoiceno =  isset($_POST['invoiceno']) && $_POST['invoiceno'] ? trim($_POST['invoiceno']):"";
		if(!$invoiceno) return ecm_json_encode(false);
		return exist_invoiceno($invoiceno)? false: true;
	}
	
	/**
	 * 保存普通快递模板编辑
	 */
	function save_print_template()
	{
		$data = array ();
		$user_id = $this->visitor->get ( 'user_id' );
		$flag = $_POST ['f'];
		$result = $_POST ['result'];
		$result_arr = explode ( ';', $result );
		if ($result_arr)
		{
			for($i = 0; $i < 6; $i ++)
			{
				unset ( $result_arr [$i] );
			}
		}
		$result = implode ( ';', $result_arr );
		// dump($result);
		$model_setting = &af ( 'settings' );
		$setting = $model_setting->getAll ();
		if ($setting ['behalf_print_template_' . $user_id])
		{
			$data ['behalf_print_template_' . $user_id] = $setting ['behalf_print_template_' . $user_id];
		}
		else
		{
			$data ['behalf_print_template_' . $user_id] = array (
					'yto' => '',
					'zto' => '',
					'sto' => '' 
			);
		}
		
		foreach ( $data ['behalf_print_template_' . $user_id] as $key => $value )
		{
			if ($key == $flag) $data ['behalf_print_template_' . $user_id] [$key] = $result;
		}
		
		$model_setting->setAll ( $data );
		
		$this->json_result ( 1, Lang::get ( 'save_success' ) );
	}
	
	/**
	 * 打印模块同时发货
	 */
	function async_shipped()
	{
		$order_ids = $_POST['ids'];
		if(! $order_ids)
		{
			//$this->json_error(Lang::get('no_such_order'));
			return Lang::get('no_such_order');
		}
		$bh_id = $this->visitor->get('has_behalf');
		$order_ids = array_filter($order_ids);
		/* 只有未发货的订单可以生成快递打印单 */
		$orders = $this->_order_mod->findAll(array(
				'conditions' => "order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status= " .ORDER_ACCEPTED  . " AND order_alias.bh_id={$bh_id}",
				'join'=>'has_orderextm',
				'include'=>array('has_ordergoods')
		));
		$deliverys = $this->_behalf_mod->getRelatedData('has_delivery',$bh_id);
		$fail_orders = array();//失败的订单
		$success_count = 0; //发货成功的单数
	
		if($orders)
		{
			foreach($orders as $order)
			{
				if(! empty($order['invoice_no']))
				{
					//开启事务
					$success = $this->_start_transaction();
						
					$edit_data = array('status' => ORDER_SHIPPED,'ship_time' => gmtime());
					/*商付通v2.2.1 更新商付通定单状态 开始*/
					if($order['payment_code'] == 'sft' || $order['payment_code'] == 'chinabank' || $order['payment_code'] == 'alipay' || $order['payment_code'] == 'tenpay' || $order['payment_code'] == 'tenpay2')
					{
						$my_moneylog = & m('my_moneylog')->edit('order_id=' . $order['order_id'], array('caozuo' => 20));
						!$my_moneylog && $success = false; //rollback
					}
					/*商付通v2.2.1  更新商付通定单状态 结束*/
					$behalf_discount = 0;
					if(!empty($order['order_goods']))
					{
						foreach ($order['order_goods'] as $goods)
						{
							//不能缺货
							if($goods['oos_value'])
							{
								$behalf_discount += $goods['behalf_to51_discount'];
							}
						}
					}
					//快递费分润，8块分0.5
					if($order['shipping_fee'] > 0)
					{
						$shipping_fee = intval($order['shipping_fee']);
						$behalf_discount += (floor($shipping_fee/8))/2;
					}
					if($behalf_discount > 0)
					{
						$edit_data['behalf_discount'] = $behalf_discount;//写入订单
						//转账
						include_once(ROOT_PATH.'/app/fakemoney.app.php');
						$fakemoneyapp = new FakeMoneyApp();
						$fr_reason = Lang::get('behalf_to_51_fr_reason').local_date('Y-m-d H:i:s',gmtime());
						//给用户转账
						$my_money_result=$fakemoneyapp->to_user_withdraw($this->visitor->get('user_id'),FR_USER,$behalf_discount, $fr_reason,$order['order_id'],$order['order_sn']);
						$my_money_result !== true && $success = false; //rollback
					}
	
					$affect_rows = $this->_order_mod->edit($order['order_id'], $edit_data);
					!$affect_rows && $success = false;//roll back
						
					//商品仓库更新
					$affect_rows = $this->_goods_warehouse_mod->edit("order_id = '{$order['order_id']}' AND goods_status = '".BEHALF_GOODS_READY."'",array('goods_status'=>BEHALF_GOODS_SEND));
					//!$affect_rows && $trans = false;
						
					#TODO 发邮件通知
					/*记录订单操作日志 */
					$affect_rows = $this->_orderlog_mod->add(array(
					'order_id' => $order['order_id'],
					'operator' => addslashes($this->visitor->get('user_name')),
					'order_status' => order_status($order['status']),
					'changed_status' => order_status(ORDER_SHIPPED),
					'remark' => $_POST['remark'],
					'log_time' => gmtime()
					));
					!$affect_rows && $success = false;
					//commit or roll back
					$this->_end_transaction($success);
						
					if($success)
					{
						$success_count ++;
						/* 发送给买家订单已发货通知 */
						$buyer_info = ms()->user->_local_get($order['buyer_id']);
						if($buyer_info['phone_mob'])
						{
							foreach ($deliverys as $deli)
							{
								if($order['dl_id'] == $deli['dl_id'])
									$order['dl_name'] = $deli['dl_name'];
							}
							$smail = get_mail('sms_order_notify', array('order' => $order));
							$this->sendSaleSms($buyer_info['phone_mob'], addslashes($smail['message']));
						}
						/* 如果匹配到的话，修改第三方订单状态 */
						$ordervendor_mod = &m('ordervendor');
						$ordervendor_mod->edit("ecm_order_id={$order['order_id']}", array(
								'status' => VENDOR_ORDER_SHIPPED
						));
					}
					else
					{
						//记录错误信息，反馈给用户
						$fail_orders[] = $order['order_sn'];
					}
				} //-- end if invoice_no
			}//--end foreach orders
		}//-- end if orders
	
		if(!empty($fail_orders))
		{
			//$this->json_error(sprintf(Lang::get('shipped_fail_info'),$success,count($fail_orders),implode(',', $fail_orders)));
			return sprintf(Lang::get('shipped_fail_info'),$success,count($fail_orders),implode(',', $fail_orders));
		}
		else
		{
			//$this->json_result(1,sprintf(Lang::get('shipped_success_info'),$success_count));
			return true;
		}
	}
	
	/**
	 * 获取快递单号
	 */
	function get_invoice_no()
	{
		//限制其它代发不能使用，以后要 修正
		$user_id = $this->visitor->get('has_behalf');
		$accounts = Conf::get('behalf_modeb_account_'.$user_id);
		if(empty($accounts))
		{
			//$this->json_error('account_unexist');
			return 'account_unexist';
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
			//$this->json_error('no_such_order');
			return 'no_such_order';
		}
		$status = array(ORDER_ACCEPTED);
		//$order_ids = explode(',', $order_id);
		$order_ids = array_filter($order_ids);
	
		//$model_order = &  m('order');
		$model_ordermodeb = & m('ordermodeb');
		//$model_behalf = & m('behalf');
		//$model_delivery = & m('delivery');
		//
		$dl_id = $this->_delivery_mod->get(array(
				'conditions' => "dl_desc like 'yuantong'"
        ));
		$zto_id = $this->_delivery_mod->get(array(
				'conditions' => "dl_desc like 'zhongtong'"
        ));
	
        /* 只有未发货的订单可以生成快递打印单 */
		$orders = $this->_order_mod->findAll(array(
				'conditions' => "order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) . " AND order_alias.bh_id={$user_id}",
				'join' => 'has_orderextm',
						'include' => array(	'has_ordergoods' //取出商品
						)
		));
		$fail_info = '';//没得到单号的错误信息
			//dump($orders);
		if(! empty($orders))
		{
			$behalf = $this->_behalf_mod->get($user_id);
		
			import('logistic/createOrderModeB');
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
							$this->_order_mod->edit($value['order_id'], array(
									'invoice_no' => strval($invoice_no)
							));
					//dump($ret_xml);
				}
				else
				{
					//$this->json_error(sprintf('get_mailno_fail',$value['order_sn'],$ret_xml));
					$fail_info .= sprintf(Lang::get('yto_get_mailno_fail'),$value['order_sn'],$xml->reason)."<br>";
						continue;
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
						$this->_order_mod->edit($value['order_id'], array(
								'invoice_no' => strval($invoice_no)
						));
										//dump($ret_xml);
				}
				else
				{
					//$this->json_error(sprintf('get_mailno_fail',$value['order_sn'],$ret_arr->remark));
					$fail_info .= sprintf(Lang::get('zto_get_mailno_fail'),$value['order_sn'],$ret_arr->remark)."<br>";
						continue;
				}
			}
		
		}
		else
		{
				//$this->json_error('find_fail');
				return 'find_fail';
		}
	
		if(!empty($fail_info))
		{
				//$this->json_error($fail_info);
				return $fail_info;
		}
		else
		{
				//$this->json_result(1,'modeb_success');
				return true;
		}
	
					//$this->json_result(1,'success!');
	}
	
	/**
	 * 获取中通可用单号数
	 */
	public function getMailCounter($delivery = 'zto')
	{
	    //限制其它代发不能使用，以后要 修正
	    $user_id = $this->visitor->get('has_behalf');
	    $accounts = Conf::get('behalf_modeb_account_'.$user_id);
	    if(empty($accounts))
	    {
	        //$this->json_error('account_unexist');
	        return 'account_unexist';
	    }
	    else
	    {
	        $yto_account = $accounts['yto_account'];
	        $yto_pass = $accounts['yto_pass'];
	        $zto_account = $accounts['zto_account'];
	        $zto_pass = $accounts['zto_pass'];
	    }
	    //echo $zto_account."##".$zto_pass;
	    if($delivery == 'zto')
	    {
	        import('logistic/zto.lib');
	        $ztoMB = new ZtoModeB($zto_account,$zto_pass);
	        $result = $ztoMB->getMailCounter();
	       /*  $result_turn = ecm_json_decode($result);
	        if($result_turn->result == 'true')
	        {
	            return $result_turn->counter->available;
	            //dump($ret_xml);
	        } */
	        //echo 'result='.$result;
	        return $result;
	    }
	    return false;
	    
	}
	
	/**
	 * 导出已使用电子面单
	 */
	public function exportMailNo()
	{
	    //1.find orders related order_modeb with shipped finished canceled
	    
	    //2.filter zto attribute true
	}
		
		
		
	private function _gen_yto_order($value, $behalf)
	{
		$order = array ();
		$order ['order_sn'] = $value ['order_sn'];
		$order ['sender_name'] = $behalf ['owner_name'];
		$order ['sender_code'] = $behalf ['zipcode'];
		$order ['sender_mob'] = $behalf ['bh_tel'];
		$tmp = $this->_trimall( $behalf ['region_name'] );
		$region_arr = explode ( ',', $tmp );
		$region_arr = array_filter ( $region_arr );
		$order ['sender_prov'] = $region_arr [1];
		$city = $region_arr [2];
		for($i = 3; $i < count ( $region_arr ); $i ++)
		{
			$city .= ',' . $region_arr [$i];
		}
		$order ['sender_city'] = $city;
		$order ['sender_address'] = $behalf ['bh_address'];
	
		$order ['receiver_name'] = $value ['consignee'];
		$order ['receiver_code'] = $value ['zipcode'];
		if (! preg_match ( '/^\d{6}$/', $value ['zipcode'] ) || preg_match ( '/\d{7,}/', $value ['zipcode'] ))
		{
			$order ['receiver_code'] = '000000';
		}
		$order ['receiver_phone'] = $value ['phone_tel'];
		$order ['receiver_mob'] = $value ['phone_mob'];
		$tmp = $this->_get_prov_city ( $value ['region_id'] );
		$order ['receiver_prov'] = $tmp ['prov'];
		$order ['receiver_city'] = $tmp ['city'];
	
		$order ['receiver_address'] = $value ['address'];
	
		$order ['goods_amount'] = $value ['goods_amount'];
		$order ['order_amount'] = $value ['order_amount'];
		$order ['order_goods'] = $value ['order_goods'];
		return $order;
	}
			
		private function _gen_zto_order($value, $behalf)
		{
			$order = array ();
			$order ['id'] = $value ['order_sn'];
			$order ['type'] = '';
			$order ['sender'] ['name'] = $behalf ['owner_name'];
			$order ['sender'] ['mobile'] = $behalf ['bh_tel'];
			$tmp = $this->_trimall ( $behalf ['region_name'] );
			$region_arr = explode ( ',', $tmp );
			$region_arr = array_filter ( $region_arr );
		// $order['sender_prov'] = $region_arr[1];
			$city = $region_arr [1] . ',' . $region_arr [2];
			for($i = 3; $i < count ( $region_arr ); $i ++)
			{
			$city .= ',' . $region_arr [$i];
			}
			$order ['sender'] ['city'] = $city;
			$order ['sender'] ['address'] = $behalf ['bh_address'];
	
			$order ['receiver'] ['name'] = $value ['consignee'];
			$order ['receiver'] ['phone'] = $value ['phone_tel'];
			$order ['receiver'] ['mobile'] = $value ['phone_mob'];
			$tmp = $this->_get_prov_city ( $value ['region_id'] );
			// $order['receiver_prov'] = $tmp['prov'];
			$order ['receiver'] ['city'] = $tmp ['prov'] . ',' . $tmp ['city'];
	
			$order ['receiver'] ['address'] = $value ['address'];
			$order ['items'] = array ();
			if (! empty ( $value ['order_goods'] ))
			{
				foreach ( $value ['order_goods'] as $goods )
				{
						$one = array ();
						$one ['id'] = $goods ['rec_id'];
						$one ['name'] = $goods ['goods_name'];
						$one ['quantity'] = $goods ['quantity'];
								$one ['unitprice'] = $goods ['price'];
								$order ['items'] [] = $one;
				}
			}
	
			return $order;
		}
	
	
	function _run_action()
	{
		parent::_run_action();
	}
}

?>