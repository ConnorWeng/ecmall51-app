<?php

/**
 *    代发订单类型
 *    
 *    @author    tiq
 *    @usage    behalf_order
 */
class BehalfOrder extends BaseOrder
{
    var $_name = 'behalf';

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
	
    function get_order_form($store_id)
    {
        return $this->_get_market_behalfs($store_id);
    }

    /**
     *    提交生成订单，外部告诉我要下的单的商品类型及用户填写的表单数据以及商品数据，我生成好订单后返回订单ID
     *
     *    @author    tiq
     *    @param     array $data
     *    @return    int
     */
    function submit_order($data)
    {        
        /* 释放goods_info和post两个变量 */
        extract($data); 
        /* 处理订单基本信息 */
        $base_info = $this->_handle_order_info($goods_info, $post);
        if (!$base_info || empty($post['behalf']))
        {
            /* 基本信息验证不通过 */
            return 0;
        }
        
        /*商品代发费*/
        $base_info['behalf_fee'] =  $goods_info['behalf_fee'] > 0 ? $goods_info['behalf_fee'] : 0 ;
        
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
        $base_info['order_amount']  =   $base_info['goods_amount'] + $consignee_info['shipping_fee'] + $base_info['behalf_fee'] - $base_info['discount'];
        
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
            	'behalf_to51_discount'=>$goods_discount,
            	'behalf_fee'=> $value['behalf_fee']
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
    	/* 释放goods_info和post两个变量 */
    	extract($data);
    	/* 处理订单基本信息 */
    	$base_info = $this->_handle_order_info($goods_info, $post);
    	if (!$base_info)
    	{
    		/* 基本信息验证不通过 */    
    		return 0;
    	}
    	/*收取商品代发费*/
    	$base_info['behalf_fee'] =  $goods_info['behalf_fee'] > 0 ? $goods_info['behalf_fee'] : 0 ;
    	
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
    	$base_info['order_amount']  =   $base_info['goods_amount'] + $consignee_info['shipping_fee'] + $base_info['behalf_fee'] - $base_info['discount'];
    
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
    				'behalf_to51_discount'=>$goods_discount,
    				'behalf_fee'=> $value['behalf_fee']
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
	    			                'behalf_to51_discount'=> $goods_discount,
	    			                'behalf_fee'=>$goods['quantity'] > 0 ? round($goods['behalf_fee']/$goods['quantity'],2) : 0
			    			);
    			}
    		}
    	}
    	return $data;
    }
    
    function _get_market_behalfs($store_id)
    {
        $data = array();
        $visitor =& env('visitor');
    
        /* 获取我的收货地址 */
        $data['my_address']  = & m('address')->find(array(
            'conditions'=>'user_id=' . $visitor->get('user_id'),
            'order'=>'addr_id ASC'
        ));
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
    
        //$store_id = intval(trim($_GET['store_id']));
        $my_storeinfo = & m('store')->get($store_id);
        //取得市场id
        $my_mk_id = $my_storeinfo['mk_id'];
        if(empty($my_mk_id))
        {
            return;
        }
        $my_market_mod = & m('market');
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
        $my_behalfs = $my_market_mod->getRelatedData('belongs_to_behalf',$my_mk_id,array('order'=>'sort_order ASC'));
        $my_behalfs = !empty($my_behalfs)?array_values($my_behalfs):$my_behalfs;
    
        if($my_behalfs)
        {
            $model_behalf = & m('behalf');
            foreach ($my_behalfs as $k=>$behalf)
            {
                if(!$model_behalf->usable_behalf_by_max_orders($behalf['bh_id']))
                {
                    unset($my_behalfs[$k]);
                }
            }
        }
    
                
        /* if(belong_behalfarea($store_id) === false)
        {
            $data['my_behalfs'] = false;
            $data['my_behalfs_reason'] = sprintf(Lang::get('store_not_inbehalfarea'),$my_storeinfo['store_name']);
        }
        else
        { */
            //随机排列代发
            shuffle($my_behalfs);
            $data['my_behalfs'] = $my_behalfs;
            if(empty($my_behalfs)) $data['my_behalfs_reason'] = sprintf(Lang::get('store_not_existbehalf'),$my_storeinfo['store_name']);
        //}
    
        return array('data' => $data);
    
    }
    
    /**
     * 检测代发黑名单
     * $bh_id  代发id
     * $store_ids = array() 档口id
     */
    function _check_behalf_blacklist($bh_id,$store_ids)
    {
        if(is_numeric($store_ids)) $store_ids = array($store_ids);
    
        $mod_behalf = & m('behalf');
        $behalf_info = $mod_behalf->get($bh_id);
    
        $black_list = $mod_behalf->getRelatedData("has_blacklist_stores",$bh_id,array('fields'=>'s.store_id,s.store_name'));
    
        if($black_list && $store_ids)
        {
            foreach ($store_ids as $store_id)
            {
                foreach ($black_list as $black_store)
                {
                    if($black_store['store_id'] == $store_id)
                    {
                        return array('store_name'=>$black_store['store_name'],'bh_name'=>$behalf_info['bh_name']);
                    }
                }
            }
        }
    
    
        return true;
    }
    
}

?>