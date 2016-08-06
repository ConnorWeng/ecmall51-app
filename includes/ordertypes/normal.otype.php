<?php

/**
 *    卖家订单类型
 *    
 *    @author    tiq
 *    @usage    默认为 普通订单类型
 */
class NormalOrder extends BaseOrder
{
    var $_name = 'normal';

    /**
     *    查看订单
     *
     *    @author    Garbin
     *    @param     int $order_id
     *    @param     array $order_info
     *    @return    array
     */
    function get_order_detail($order_id, $order_info)
    {
        if (!$order_id)
        {
            return array();
        }

        /* 获取商品列表 */
        $data['goods_list'] =   $this->_get_goods_list($order_id);

        /* 配关信息 */
        $data['order_extm'] =   $this->_get_order_extm($order_id);
        
        /*如果有代发和快递，则取出其名称*/
        if(!empty($data['order_extm']['bh_id']) && !empty($data['order_extm']['dl_id']))
        {
        	$mod_behalf =& m('behalf');
        	$behalf = $mod_behalf->get($data['order_extm']['bh_id']);
        	$data['order_extm']['bh_id'] = $behalf['bh_name'];
        	//订单中需要展示代发信息
        	$data['behalf_info'] = $behalf;
        	
        	$model_delivery =& m('delivery');
        	$delivery = $model_delivery->get($data['order_extm']['dl_id']);
        	$data['order_extm']['dl_id'] = $delivery['dl_name'];
        }       

        /* 支付方式信息 */
        if ($order_info['payment_id'])
        {
            $payment_model      =& m('payment');
            $payment_info       =  $payment_model->get("payment_id={$order_info['payment_id']}");
            $data['payment_info']   =   $payment_info;
        }

        /* 订单操作日志 */
        $data['order_logs'] =   $this->_get_order_logs($order_id);

        return array('data' => $data);
    }   
	
    
    /* 显示订单表单 */
    function get_order_form($goods_info) // tyioocom delivery
    {
    	$data = array();
    	$template = 'order.form.wind.html';

    
    	$visitor =& env('visitor');
    
    	/* 获取我的收货地址 */
    	$data['my_address']         = $this->_get_my_address($visitor->get('user_id'));
    	$data['addresses']          =   ecm_json_encode($data['my_address']);
    	$data['regions']            = $this->_get_regions();
    
    	/* 配送方式  改为运费模板 tyioocom
    
    	/* 根据 goods_info['items'] 找出每个商品的运费模板id */
    	$goods_mod = &m('goods');
    	$delivery_mod = &m('delivery_template');
    
    	//tiq
    	$goods_delivery_weight = array();//存放本订单中有物流重量的商品goods_id,delivery_weight;因为cart中没有delivery_weight;
    	$deliverys = $base_deliverys = array();
    	foreach($goods_info['items'] as $goods)
    	{
    		$search_goods = $goods_mod->get(array(
    				'conditions'=>'goods_id='.$goods['goods_id'],
    				'fields'=>'delivery_template_id'
    		));
    		$template_id = $search_goods['delivery_template_id'];
    			
    		/* 如果商品的运费模板id为0，即未设置运费模板，则获取店铺默认的运费模板（取第一个） */
    		if(!$template_id || !$delivery_mod->get($template_id))
    		{
    			$delivery = $delivery_mod->get(array(
    					'conditions'=>'store_id='.$goods_info['store_id'],
    					'order'=>'template_id',
    			));
    			// 如果店铺也没有默认的运费模板
    			if(empty($delivery)){
    				$this->_error('store_no_delivery');
    				return false;
    			}
    				
    		} else {
    			$delivery = $delivery_mod->get($template_id);
    		}
    			
    		$base_deliverys[$goods['goods_id']] = $delivery;
    	}
    	
    	//tiq 检查商品为重量模板是否设置了物流重量
    	foreach ($base_deliverys as $gods_id=>$temp)
    	{
    		if($temp['price_type'] == 2)
    		{
    			$delivery_goods = $goods_mod->get(array(
    				'conditions'=>'goods_id='.$gods_id,
    				'fields'=>'delivery_weight,goods_name'
    		    ));
    			if($delivery_goods['delivery_weight'] <= 0)
    			{
    				$msg_delivery_goods = get_msg('goods_need_to_set_delivery_weight', array('delivery_goods' => $delivery_goods['goods_name']));
    				$this->_error($msg_delivery_goods);
    				return false;
    			}
    			else
    			{
    				$goods_delivery_weight[$gods_id] = $delivery_goods['delivery_weight'];
    			}
    		}
    	}
             
    	/* 根据运送目的地，获取运费情况 */
    	foreach($data['my_address'] as $addr_id=>$my_address)
    	{
    		$city_id = $my_address['region_id']; // 此处不是 city_id 的话，可能影响也不大。
    		foreach($base_deliverys as $key=>$delivery){
    			$deliverys[$key] = $delivery_mod->get_city_logist($delivery,$city_id);
    		}
    		/* 判断这些运费模板中的运费方式是否全等（只有在全等的情况下，才能统一计算运费，否则分开计算* /
    			/* 注：目前已经强制每个运费模板都必须设置三个运送方式，所以不存在不全等的情况，此处是留备日后拓展 */
    		$k = 0;
    		$can_merge = true;
    		foreach($deliverys as $delivery)
    		{
    			$k++;
    			if($k==1){
    				$first_template_types_count = count($delivery);
    			} elseif($first_template_types_count != count($delivery)){ // 如果有一个运送方式个数不等，则认为运送方式不全等
    				//$can_merge = false;
    			}
    		}
              
    		/* 一、如果每个商品可用的运送方式都一致，则统一计算；二、 如果有一个商品的运送方式不同，则进行组合计算 */
    		/* 注：目前已经强制每个运费模板都必须设置三个运送方式，所以不存在不全等的情况，此处是留备日后拓展 */
    		if($can_merge)
    		{
    			/* 1. 分别计算每个运送方式的费用：找出首费最大的那个运费方式，作为首费，并且找出作为首费的那个商品id，便于在统计运费总额时，该商品使用首费，其他商品使用续费计算 */
    			$merge_info = array(
    					'express' => array('start_fees'=>0,'goods_id'=>0),
    					'ems'     => array('start_fees'=>0,'goods_id'=>0),
    					'post'    => array('start_fees'=>0,'goods_id'=>0),
    			);
    			foreach($deliverys as $goods_id=>$delivery)
    			{
    				foreach($delivery as $template_types)
    				{
    					if($merge_info[$template_types['type']]['start_fees'] < $template_types['start_fees']){
    						$merge_info[$template_types['type']]['start_fees'] = $template_types['start_fees'];
    						$merge_info[$template_types['type']]['goods_id'] = $goods_id;
    					}
    				}
    			}
    			/* 计算每个订单（店铺）的商品的总件数（包括不同规格）和每个商品的总件数（包括不同规格），以下会用到总件数来计算运费 */
    			$total_quantity = 0;
    			$quantity = array();
    			foreach($goods_info['items'] as $goods)
    			{
    				$quantity[$goods['goods_id']] += $goods['quantity'];
    				$total_quantity += $goods['quantity'];
    			}
    			/* 计算总运费 */
    			$logist = array();
    			foreach($deliverys as $goods_id=>$delivery)
    			{
    				foreach($delivery as $template_types)
    				{
    					if($goods_id == $merge_info[$template_types['type']]['goods_id']){    						
    						if($template_types['price_type'] == 1)
    						{
    							if($total_quantity > $template_types['start_standards'] && $template_types['add_standards'] > 0){
    								$goods_fees = $merge_info[$template_types['type']]['start_fees'] + ($quantity[$goods_id]- $template_types['start_standards']) / $template_types['add_standards'] * $template_types['add_fees'];
    								//$logist[$template_types['type']]['list_fee'][$goods_id]['logist_fee'] +=  $goods_fees;
    							} else {
    								$goods_fees = $merge_info[$template_types['type']]['start_fees'];
    								//$logist[$template_types['type']]['list_fee'][$goods_id]['logist_fee'] +=  $goods_fees;
    							}
    						}
    						//tiq
    						if($template_types['price_type'] == 2)
    						{
    							$goods_id_weight = intval($quantity[$goods_id])*floatval($goods_delivery_weight[$gods_id]);
    							$goods_id_weight = ceil($goods_id_weight);
    							if($goods_id_weight > $template_types['start_standards'] && $template_types['add_standards']>0){
    								$goods_fees = $merge_info[$template_types['type']]['start_fees'] + ($goods_id_weight - $template_types['start_standards'])/$template_types['add_standards'] * $template_types['add_fees'];
    							} else {
    								$goods_fees = $merge_info[$template_types['type']]['start_fees'];
    							}
    						}
    					}
    					else
    					{
    					   if($template_types['price_type'] == 1)
    					   {
	    					   	if($template_types['add_standards']>0){
	    					   		$goods_fees = $quantity[$goods_id] / $template_types['add_standards'] * $template_types['add_fees'];
	    					   	} else {
	    					   		$goods_fees = $template_types['add_fees'];
	    					   	}
	    					   	//$logist[$template_types['type']]['list_fee'][$goods_id]['logist_fee'] += $goods_fees;
    					   }
    					   //tiq
    					   if($template_types['price_type'] == 2)
    					   {
    					   	  $goods_id_weight = intval($quantity[$goods_id])*floatval($goods_delivery_weight[$goods_id]);
    					   	  $goods_id_weight = ceil($goods_id_weight);
    					   	  if($template_types['add_standards']>0){
    					   	  	$goods_fees = $goods_id_weight / $template_types['add_standards'] * $template_types['add_fees'];
    					   	  } else {
    					   	  	$goods_fees = $template_types['add_fees'];
    					   	  }    					    	
    					   }
    						
    					}
    					$logist[$template_types['type']]['logist_fees'] += $goods_fees;
    					$logist[$template_types['type']] += $template_types;
    				}
    			}
    			$data['shipping_methods'][$addr_id] = $logist;
    		}
    	}
    	$data['shippings'] = ecm_json_encode($data['shipping_methods']);
    	$data['shipping_methods'] = current($data['shipping_methods']);// 取默认（第一条地区对应的运费）
    
    	
    	return array('data' => $data, 'template' => $template);
    }

    /**
     *    提交生成订单，外部告诉我要下的单的商品类型及用户填写的表单数据以及商品数据，我生成好订单后返回订单ID
     *
     *    @author    Garbin
     *    @param     array $data
     *    @return    int
     */
    function submit_order($data)
    {
        /* 释放goods_info和post两个变量 */
        extract($data); 
        /* 处理订单基本信息 */
        $base_info = $this->_handle_order_info($goods_info, $post);
        if (!$base_info)
        {
            /* 基本信息验证不通过 */
            return 0;
        }

        /* 处理订单收货人信息 */
        $consignee_info = $this->_handle_consignee_info($goods_info, $post);
        if (!$consignee_info)
        {
            /* 收货人信息验证不通过 */
            return 0;
        }

        /* 至此说明订单的信息都是可靠的，可以开始入库了 */
       
        /* 插入订单基本信息 */
        //订单总实际总金额，可能还会在此减去折扣等费用
        $base_info['order_amount']  =   $base_info['goods_amount'] + $consignee_info['shipping_fee'] - $base_info['discount'];
        
        /* 如果优惠金额大于商品总额和运费的总和 */
        if ($base_info['order_amount'] < 0)
        {
            $base_info['order_amount'] = 0;
            $base_info['discount'] = $base_info['goods_amount'] + $consignee_info['shipping_fee'];
        }
        
        /*如果是代发*/
      /*   if((isset($post['shipping_choice'])) && (intval($post['shipping_choice']) == 2))
        {
        	$base_info['bh_id'] = $post['behalf'];
        	
        }

        if(empty($base_info['bh_id']))
        {
            $this->_error('fail!');
            return 0;
        } */
        if($post['behalf'])
        {
            $this->_error('not behalf!');
            return 0;
        }
        
        //开启事务
        $db_transaction_begin = db()->query("START TRANSACTION");
        if($db_transaction_begin === false)
        {
        	//$this->pop_warning('fail_caozuo');
        	$this->_error('fail_to_transaction');
        	return;
        }
        $db_transaction_success = true;//默认事务执行成功，不用回滚
        $db_transaction_reason = 'fail_to_gen_order';//回滚的原因
                
        $order_model =& m('order');
        $order_id    = $order_model->add($base_info);

        if (!$order_id)
        {
            /* 插入基本信息失败 */
            $this->_error('create_order_failed');

            //return 0;
        	$db_transaction_success = false;
        }

        /* 插入收货人信息 */
        $consignee_info['order_id'] = $order_id;
        $order_extm_model =& m('orderextm');
        $affect_id = $order_extm_model->add($consignee_info);
        if(!$affect_id) 
        {
        	$this->_error('fail_to_consignee');
        	$db_transaction_success = false;   
        }

        /* 插入商品信息 */
        $model_goodsattr =& m('goodsattr');
        $model_goods=& m('goods');
        $model_storediscount=& m('storediscount');
        $goods_items = array();
        foreach ($goods_info['items'] as $key => $value)
        {
           // $temp_store_id = $model_goods->getOne("SELECT store_id FROM {$model_goods->table} WHERE goods_id={$value['goods_id']}");
           // $goods_discount = $model_storediscount->get_goods_discount($temp_store_id,$value['price']);
          //  $goods_discount = $goods_discount * $value['quantity'];
            $goods_items[] = array(
                'order_id'      =>  $order_id,
                'goods_id'      =>  $value['goods_id'],
                'goods_name'    =>  $value['goods_name'],
                'spec_id'       =>  $value['spec_id'],
                'specification' =>  $value['specification'],
                'price'         =>  $value['price'],
                'quantity'      =>  $value['quantity'],
                'goods_image'   =>  $value['goods_image'],
            	'attr_value'=>$model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$value['goods_id']} AND attr_id=1"),
            	//'store_id'=>$temp_store_id,
            	//'behalf_to51_discount'=>$goods_discount
            );
        }
        $order_goods_model =& m('ordergoods');
        if(!$order_goods_model->add(addslashes_deep($goods_items)))
        {
        	$this->_error('fail_to_goods');
        	$db_transaction_success = false;
        }
        
        
        //代发 拿货仓库 ，下单时入库
       /*  $goods_warehouse = $this->_handle_goods_warehouse($base_info,$goods_items,$consignee_info);
        if($goods_warehouse)
        {
        	$model_goodswarehouse = & m('goodswarehouse');
        	if(!$model_goodswarehouse->add(addslashes_deep($goods_warehouse)))
        	{
        		$this->_error('fail_to_warehouse');
        		$db_transaction_success = false;
        	}
        }
        else 
        {
        	$db_transaction_success = false;
        } */
        
        if($db_transaction_success === false)
        {
        	db()->query("ROLLBACK");//回滚
        	//$this->pop_warning($db_transaction_reason);
        	return 0;
        }
        else
        {
        	db()->query("COMMIT");//提交
        }
        
        /*如果是选择代发，则插入代发与快递*/
       /*  if((isset($post['shipping_choice'])) && (intval($post['shipping_choice']) == 2))
        {
        	$orderBehalfData = array(
        		'order_id'=>$order_id,
        		'bh_id' =>$post['behalf'],
        		'dl_id' =>$post['delivery'],
        	);
        	$order_behalf_model =& m('orderbehalfs');
        	$order_behalf_model->add($orderBehalfData);
        } */

        return $order_id;
    }
    
    function submit_merge_order($data)
    {
        return 0;//stop it 20160502
    	/* 释放goods_info和post两个变量 */
    	extract($data);
    	/* 处理订单基本信息 */
    	$base_info = $this->_handle_order_info($goods_info, $post);
    	if (!$base_info)
    	{
    		/* 基本信息验证不通过 */
    
    		return 0;
    	}
    	
    	/* 处理订单收货人信息 */
    	$consignee_info = $this->_handle_consignee_info($goods_info, $post);
    	if (!$consignee_info)
    	{
    		/* 收货人信息验证不通过 */
    		return 0;
    	}     	
    
    	/* 至此说明订单的信息都是可靠的，可以开始入库了 */
    	
    	/* 插入订单基本信息 */
    	//订单总实际总金额，可能还会在此减去折扣等费用
    	$base_info['order_amount']  =   $base_info['goods_amount'] + $consignee_info['shipping_fee'] - $base_info['discount'];
    
    	/* 如果优惠金额大于商品总额和运费的总和 */
    	if ($base_info['order_amount'] < 0)
    	{
    		$base_info['order_amount'] = 0;
    		$base_info['discount'] = $base_info['goods_amount'] + $consignee_info['shipping_fee'];
    	}
    	
    	/*如果是代发*/
    	if((isset($post['shipping_choice'])) && (intval($post['shipping_choice']) == 2))
    	{
    		$base_info['bh_id'] = $post['behalf'];
    	}
    	if(empty($base_info['bh_id']))
    	{
    		$this->_error('fail!');
    		return 0;
    	}
    	//开启事务
    	$db_transaction_begin = db()->query("START TRANSACTION");
    	if($db_transaction_begin === false)
    	{
    		//$this->pop_warning('fail_caozuo');
    		$this->_error('fail_to_transaction');
    		return;
    	}
    	$db_transaction_success = true;//默认事务执行成功，不用回滚
    	$db_transaction_reason = 'fail_to_gen_order';//回滚的原因
    
    	$order_model =& m('order');
    	$order_id    = $order_model->add($base_info);
   
    	if (!$order_id)
    	{
    		/* 插入基本信息失败 */
    		$this->_error('create_order_failed');    
    		//return 0;
    		$db_transaction_success = false;
    	}
    	
    	/*如果是选择代发，则插入代发与快递*/    	 
    	/* $orderBehalfData = array(
    			'order_id'=>$order_id,
    			'bh_id' =>$post['behalf'],
    			'dl_id' =>$post['delivery'],
    	);
    	$order_behalf_model =& m('orderbehalfs');
    	$order_behalf_info = $order_behalf_model->add($orderBehalfData);
    	if(!$order_behalf_info)
    	{
    		$this->_error('create_order_failed');
    		return 0;
    	} */
    
    	/* 插入收货人信息 */
    	$consignee_info['order_id'] = $order_id;
    	$order_extm_model =& m('orderextm');
    	if( !$order_extm_model->add($consignee_info) )
    	{
    		$this->_error('fail_to_consignee');
    		$db_transaction_success = false;
    	}
    	
    
    	/* 插入商品信息 */
    	$model_goodsattr =& m('goodsattr');
    	$model_goods=& m('goods');
    	$model_storediscount=& m('storediscount');
    	$goods_items = array();
    	foreach ($goods_info['items'] as $key => $value)
    	{
    	    $temp_store_id = $model_goods->getOne("SELECT store_id FROM {$model_goods->table} WHERE goods_id={$value['goods_id']}");
    	    $goods_discount = $model_storediscount->get_goods_discount($temp_store_id,$value['price']);
    	    $goods_discount = $goods_discount * $value['quantity'];
    		$goods_items[] = array(
    				'order_id'      =>  $order_id,
    				'goods_id'      =>  $value['goods_id'],
    				'goods_name'    =>  $value['goods_name'],
    				'spec_id'       =>  $value['spec_id'],
    				'specification' =>  $value['specification'],
    				'price'         =>  $value['price'],
    				'quantity'      =>  $value['quantity'],
    				'goods_image'   =>  $value['goods_image'],
    				'attr_value'=>$model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$value['goods_id']} AND attr_id=1"),
    				'store_id'=>$temp_store_id,
    				'behalf_to51_discount'=>$goods_discount
    		);
    	}
    	$order_goods_model =& m('ordergoods');
    	if(!$order_goods_model->add(addslashes_deep($goods_items)))
    	{
    		$this->_error('fail_to_goods');
    		$db_transaction_success = false;
    	}
    	
    	//代发 拿货仓库 ，下单时入库
    	$goods_warehouse = $this->_handle_goods_warehouse($base_info,$goods_items,$consignee_info);
    	if($goods_warehouse)
    	{
    		$model_goodswarehouse = & m('goodswarehouse');
    		if(!$model_goodswarehouse->add(addslashes_deep($goods_warehouse)))
    		{
    			$this->_error('fail_to_warehouse');
    			$db_transaction_success = false;
    		}
    	}
    	else
    	{
    		$db_transaction_success = false;
    	}
    	
    	if($db_transaction_success === false)
    	{
    		db()->query("ROLLBACK");//回滚
    		//$this->pop_warning($db_transaction_reason);
    		return 0;
    	}
    	else
    	{
    		db()->query("COMMIT");//提交
    	}
    	
    	//db()->query("END");
    	
    
    	return $order_id;
    }
    
    /**
     * 此为 代发专用，故不放入 父类baseorder
     * @param 订单信息 $base_info
     * @param 商品信息 $goods_items
     * @param 收货人信息 $consignee_info
     */
    function _handle_goods_warehouse($base_info,$goods_items,$consignee_info)
    {
        return false;//stop it 20160502
    	if(empty($base_info['bh_id']) || empty($goods_items))
    	{
    		return false;
    	}
    	
    	$model_market = & m('market');
    	$model_delivery =& m('delivery');
    	$model_store =& m('store');
    	$model_storediscount=& m('storediscount');
    	$markets = $model_market->get_list(1);
    	foreach ($markets as $key=>$m)
    	{
    		$markets[$key]['children'] = $model_market->get_list($m['mk_id']);
    	}
    	$deliverys = $model_delivery->find();
    	
    	$data = array();
    	if($goods_items)
    	{
    		foreach ($goods_items as $key=>$goods)
    		{
    			$store = $model_store->get($goods['store_id']);
    			$floor_id = $store['mk_id'];
    			$floor_name = '';
    			$market_id = 0;
    			$market_name ='';
    			$delivery_name = '';
    			
    			$goods_discount = $model_storediscount->get_goods_discount($goods['store_id'],$goods['price']);
    			//找出 市场和楼层信息
    			foreach ($markets as $mkey=>$mm)
    			{
    				if(in_array($floor_id, array_keys($mm['children'])))
    				{
    					$market_id = $mm['mk_id'];
    					$market_name = $mm['mk_name'];
    					foreach ($mm['children'] as $floor)
    					{
    						if($floor['mk_id'] == $floor_id)
    						{
    							$floor_name = $floor['mk_name'];
    						}
    					}
    				}
    			}
    			//快递名称
    			foreach ($deliverys as $delivery)
    			{
    				if($delivery['dl_id'] == $consignee_info['dl_id'])
    				{
    					$delivery_name = $delivery['dl_name'];
    				}
    			}
    			//货号
    			$attrArr = explode('_', $goods['attr_value']);
    			
    			for($i=1;$i<=$goods['quantity'];$i++)
    			{	
	    			$data[] = array(
			    					'goods_no' => $base_info['order_sn'].str_pad($key, 2,'0',STR_PAD_LEFT).str_pad($i, 2,'0',STR_PAD_LEFT),  //拿货商品编码
			    					'goods_id' => $goods['goods_id'], //商品ID
			    					'goods_name' => $goods['goods_name'] ,// '商品名称'
			    					'goods_price' => $goods['price'] ,//'商品价格'
			    					'goods_quantity' => $goods['quantity'] ,// '订单此规格数量'
			    					'goods_sku' => end($attrArr) ,// '货号'
			    					'goods_attr_value' => $goods['attr_value'] ,// '商家编码'
			    					'goods_image' => $goods['goods_image'] ,// '商品图片',
			    					'goods_status' => 0 ,// '商品状态如备货中明天'默认 0 备货中
			    					'goods_spec_id' => $goods['spec_id'] ,// '规格ID',
			    					'goods_specification' => $goods['specification'] ,// '颜色尺寸',
			    					'store_id' => $goods['store_id'] ,// '店铺ID',
			    					'store_name' => $store['store_name'] ,// '店铺名称',
	    							'store_address' => $store['address'] ,// '档口地址',
			    					'store_bargin' => ($goods['behalf_to51_discount']/$goods['quantity'])*2 ,// '店铺每件优惠' 分润则为一半
			    					'market_id' => $market_id ,// '市场ID',
			    					'market_name' => $market_name ,// '市场名称',
			    					'floor_id' => $floor_id ,// '楼层ID',
			    					'floor_name' => $floor_name ,// '楼层名称',
			    					'order_id' => $goods['order_id'] ,// '订单ID',
			    					'order_sn' => $base_info['order_sn'] ,// '订单编号',
			    					'order_goods_quantity' => '0' ,// '订单商品数量',
			    					'order_add_time' => $base_info['add_time'] ,// '下单时间',
			    					'order_pay_time' => 0 ,// '支付时间',
			    					'order_postscript' => $base_info['postscript'] ,// '买家留言',
			    					'delivery_id' => $consignee_info['dl_id'] ,// '快递ID',
			    					'delivery_name' => $delivery_name, // '快递名称',
			    					'bh_id' => $base_info['bh_id'], // '代发',
	    			                'behalf_to51_discount'=> $goods_discount
			    			);
    			}
    		}
    	}
    	return $data;
    }
    
}

?>