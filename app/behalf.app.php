<?php

class BehalfApp extends MallbaseApp
{
    function index()
    {
    	/* $mod_goodsspec = & m('goodsspec');
    	
    	$goodsspec = $mod_goodsspec->find(array('conditions'=>"goods_id='5093868'"));
    	
    	dump($goodsspec); */
        
        
        $mod_cart = & m('cart');
        $goods_info = $mod_cart->get('949488');
        
        $ccc = $mod_cart->find(array(
            'conditions' => "user_id = '10' AND store_id = '106623' AND session_id='7039c82626fac866c116ac563298252d'"." AND ".db_create_in(array('949488'),'rec_id'),
            //'join'       => 'belongs_to_goodsspec',
        ));
        
        dump($ccc);
        
        
        
    	$model_goodswarehouse = & m('goodswarehouse');
    	
    	$model_goodswarehouse->edit("order_id = '132'",array('store_address'=>'12'));
    	
    	$ddd = strtotime("2000-01-01");
    	
    	dump($ddd);
    	
    	
    	
    	$model_behalf = & m('behalf');
    	$result = $model_behalf->get_behalfs_deliverys(0);
    	
    	dump($result);
           // $mm = & m('storediscount');
        
           // $mm->get_goods_discount(5310,20);
            /* import('createOrderModeB');
            $orderMB = new CreateOrderModeB();
            $orderMB->getOrderModeB(); */
        
    	    $mm = gmtime();
            //$mmm = mktime(13,8,6,11,5,2015);//1446728886
            $sdate = mktime(0,0,0,date('m',$mm),date('d',$mm),date('Y',$mm));
    		$add_time = date("Y-m-d H:i:s",$mm);
    		
    		echo $mm."=now:".date("Y-m-d H:i:s",$mm)."<br>";
    		echo $sdate."=now_start:".date("Y-m-d H:i:s",$sdate)."<br>";
    		echo ($sdate-1)."=yes_end:".date("Y-m-d H:i:s",($sdate-1))."<br>";
    		echo ($sdate-24*60*60)."=yes_start:".date("Y-m-d H:i:s",($sdate-24*60*60))."<br>";
    		
    		echo "gmstr2time:".gmstr2time(date('Y-m-d',$sdate))."=".date('Y-m-d H:i:s',$sdate);
    		
    		dump($mm."=now:".$add_time);
    	    /*南城网不需要代发市场*/
            if(OEM =='nc')
            {
            	header('Location:index.php');exit;
            }
    	    //post
    	    //$conditions = '';
    	    
	    	$shipping_mod = & m('delivery');
	    	$market_mod = & m('market');
	    	$shippings = $shipping_mod->getAll('select dl_id,dl_name from '.DB_PREFIX.'delivery'.' where if_show > 0');
	    	$my_markets = $market_mod->get_list(1);
	    	$behalf_mod =& m('behalf');
	    	$behalf_mod->is_behalf_goods(10,array(1,2,3,4));
	    	if(!IS_POST)
	    	{
	    		$behalvies = $behalf_mod->find(array(
	    				'order'=>'sort_order',
	    				'limit'=>'0,30',	    		
	    		));
	    	}
	    	else
	    	{
	    		$postdata = $_POST;
	    		/* if(empty($postdata))
	    		{	    			
	    			$this->pop_warning('no_choice');
	    			return;
	    		} */
	    		extract($postdata);
	    		//从市场得到代发
	    		if(!empty($market))
	    		{
	    			$m_behalf = $market_mod->getRelatedData('belongs_to_behalf',$market);
	    		}
	    		if(!empty($m_behalf))
	    		{
	    			foreach($m_behalf as $key=>$value)
	    			{
	    				unset($m_behalf[$key]['mk_id']);
	    			}
	    			$m_behalf = $this->get_array_unique($m_behalf);
	    		}
	    		//从快递得到代发
	    		if(!empty($delivery))
	    		{
	    			$d_behalf = $shipping_mod->getRelatedData('belongs_to_behalf',$delivery);
	    		}
	    		if(!empty($d_behalf))
	    		{
	    			foreach($d_behalf as $key=>$value)
	    			{
	    				unset($d_behalf[$key]['dl_id']);
	    			}
	    			$d_behalf = $this->get_array_unique($d_behalf);
	    		}
	    		//合并二维数组，并去除二维数组中的重复值
	    		if(!empty($m_behalf) && !empty($d_behalf))
	    		{
	    			$t_behalf = array();
	    			foreach ($m_behalf as $key=>$value)
	    			{
	    				if(!in_array($value, $t_behalf))
	    					$t_behalf[] = $value; 
	    			}
	    			foreach ($d_behalf as $key=>$value)
	    			{
	    				if(!in_array($value, $t_behalf))
	    					$t_behalf[] = $value;
	    			}
	    			/* $t_behalf = array_map(null,$m_behalf,$d_behalf);
	    			foreach ($t_behalf as $val)
	    			{
	    				$val = call_user_func_array("array_merge", array_filter($val));
	    			}
	    			$behalvies = $t_behalf; */
	    			$behalvies = $t_behalf;
	    		}
	    		else if(empty($m_behalf) && empty($d_behalf))
	    		{
	    			$behalvies = null;
	    		}
	    		else
	    		{
	    			if(!empty($m_behalf)) $behalvies = $m_behalf;
	    			if(!empty($d_behalf)) $behalvies = $d_behalf;
	    		}
	    		 		
	    		
	    	}
	    	$newbehalvies = $behalf_mod->find(array('order'=>'create_time desc','limit'=>'0,6'));
	    	$order_behalf_mod =& m('orderbehalfs');
	    	$orderbehalfs = $order_behalf_mod->findAll(array('order'=>'rec_id desc','limit'=>'0,6'));
	    	foreach($orderbehalfs as $key=>$val)
	    	{
	    		$behalf = $behalf_mod->get($val["bh_id"]);    
	    		$orderbehalfs[$key]['bh_id'] = $behalf['bh_name'];
	    		$order_mod =& m('order');
	    		$order = $order_mod->get($val['order_id']);
	    		 substr($behalf['bh_name'],0,$i)."*";
	    		$orderbehalfs[$key]['buyer_name'] =  substr($order['buyer_name'],0,1)."***";
	    		$orderbehalfs[$key]['order_amount'] = $order['order_amount'];
	    		$orderbehalfs[$key]['evaluation_status'] = $order['evaluation_status'];
	    		
	    	}
	    	/*获取每一个代发与它签约的用户有哪些*/
	    	foreach ($behalvies as $key=>$behalf)
	    	{
	    		$userArray = array();
	    		$result = $behalf_mod->getRelatedData('be_signed',$behalf['bh_id']);
	    		if(!empty($result))
	    		{
	    			foreach ($result as $value)
	    			{
	    				$userArray[] = $value["user_id"];
	    			}
	    		}	    		
	    		$behalvies[$key]['users'] = $userArray;
	    	}
	    	
	    	$this->assign('behalf_count',count($behalf_mod->find()));
	    	$this->assign('markets',$my_markets);
	    	$this->assign('deliveries',$shippings);
	    	$this->assign("behalvies",$behalvies);
	    	$this->assign("newbehalvies",$newbehalvies);
	    	$this->assign("orders",$orderbehalfs);
	    	$this->assign("site_url",SITE_URL);
	    	$this->_config_seo(array(
	    			'title' => Lang::get('behalf_index') . ' - ' . Conf::get('site_title'),
	    	));
	    	$this->assign('icp_number', Conf::get('icp_number'));
	    	$this->assign('page_description', Conf::get('site_description'));
	    	$this->assign('page_keywords', Conf::get('site_keywords'));
	    	$this->import_resource(array(
	    			'style' => 'res:css/behalf.css',
	    	));
	    	$this->_config_seo(array(
	    			'title' => Lang::get('behalf_index') . ' - ' . Conf::get('site_title'),
	    	));
	        $this->display('behalf.index.html');
    }

    function _get_hot_keywords()
    {
        $keywords = explode(',', conf::get('hot_search'));
        return $keywords;
    }
    
    /**
     * 去除2维数组重复的值
     * @param unknown $array
     * @return multitype:unknown
     */
    function get_array_unique($array,$stkeep=false,$ndformat=true)
    {
    	//判断是否保留一级数组键（一级数组键可以为非数字）
    	if($stkeep) $stArr = array_keys($array);
    	//判断是否保留二级数组键(所有二级数组键必须相同)
    	if($ndformat) $ndArr = array_keys(end($array));
    	//降维，也可以用implode，将一维数组转换为用逗号连接的字符串
    	foreach($array as $v)
    	{
    		$v = join(',', $v);
    		$temp[] = $v;
    	}
    	//去掉重复的字符串，也就是重复的一维数组
    	$temp = array_unique($temp);
    	//再将拆开的数组重新组装
    	foreach ($temp as $k=>$v)
    	{
    		if($stkeep) $k = $stArr[$k];
    		if($ndformat)
    		{
    			$tempArr = explode(",", $v);
    			foreach ($tempArr as $ndkey=>$ndval)
    				$output[$k][$ndArr[$ndkey]] = $ndval;
    		}
    		else
    		{
    			$output[$k] = explode(',', $v);
    		}
    	}
    	return $output;
    }
    
    function a()
    {
    	$model_orderrefund =& m('orderrefund');
    	$orderrefunds = $model_orderrefund->find(array(
    			'fields'=>'order_id',
    			'conditions'=>'status = 0 and closed = 0'
    	));
    	$model_order = & m('order');
    	$orders = $model_order->find(array(
    			'fields' => 'order_id,buyer_id,order_amount',
    			//'conditions' => "ship_time + {$interval} < {$now} AND status = " . ORDER_SHIPPED,
    	));
    	
    	if(!empty($orderrefunds))
    	{
    		$refunds = array();
    		foreach ($orderrefunds as $refund)
    		{
    			$refunds[] = $refund['order_id'];
    		}
    	}
    	dump($refunds);
    }
    
    function s()
    {
        $data = array();
        $m_order =& m('order');
        $st1 = strtotime("2015-11-05 13:08:06");
        $et1 = strtotime("2015-11-05 23:59:59");
        $st = strtotime("2015-11-06 00:00:00");
        $et = strtotime("2015-11-06 23:59:59");
        
        $status = array(ORDER_SHIPPED,ORDER_FINISHED);
        
        
        $sql1 = "SELECT count(order_id) FROM ".$m_order->table." WHERE bh_id='10919' AND ship_time >='$st1' AND ship_time <='$et1' AND status ".db_create_in($status);
        $sql3 = "SELECT count(order_id) FROM ".$m_order->table." WHERE bh_id='10919' AND ship_time >='$st1' AND status ".db_create_in(array(ORDER_SHIPPED));
        $sql4 = "SELECT count(order_id) FROM ".$m_order->table." WHERE bh_id='10919' AND ship_time >='$st1' AND status ".db_create_in(array(ORDER_FINISHED));
        
        $result = $m_order->getOne($sql1);
        echo $sq1."<br>".date("Y-m-d",$st1)."#".$result."<br>";
        echo $sq3."<br>"."shipped#".$m_order->getOne($sql3)."<br>";
        echo $sq4."<br>"."finished#".$m_order->getOne($sql4)."<br>";
        
        while($st <= gmtime())
        {
            $sql = "SELECT count(order_id) FROM ".$m_order->table." WHERE bh_id='10919' AND ship_time >='".$st."' AND ship_time <='".$et."' AND status ".db_create_in($status);
            
           echo  $sql."<br>";
           
            $arr = array(
                'date'=>date("Y-m-d",$st),
                'count'=>$m_order->getOne($sql)
            );
            $data[] = $arr;
            $st += 24*60*60;
            $et += 24*60*60;
        }
        
        
        
        
        
        
        
        
      
        
        
        
        
        
        /* $m_ordergoods = & m('ordergoods');
        $m_orderextm = & m('orderextm');
        
        $sql = "SELECT sum(behalf_discount) as behalf_total FROM ".$m_order->table." WHERE bh_id='10919'";
        $behalf_total = $m_order->getOne($sql);
        
        $sql_r = "SELECT count(o.order_id) as c FROM ".$m_order->table." o LEFT JOIN ".$m_orderextm->table." e ON o.order_id=e.order_id WHERE o.bh_id='10919' AND o.ship_time >= 1446728886 AND e.shipping_fee >= 8";
        $order_total = $m_order->getOne($sql_r);
        
        $sql1 = "SELECT o.order_id FROM ".$m_order->table." as o WHERE o.bh_id='10919'";
        //$orders = $m_order->getCol($sql1);
        
        $sql2 = "SELECT sum(behalf_to51_discount) as behalf_in FROM ".$m_ordergoods->table." as og  WHERE og.oos_value > 0 AND og.order_id in (".$sql1.")";
        $sql3 = "SELECT sum(zwd51_tobehalf_discount) as behalf_out FROM ".$m_ordergoods->table." as og  WHERE og.oos_value > 0 AND og.order_id in (".$sql1.")";
        $behalf_in = $m_ordergoods->getOne($sql2);
        $behalf_out = $m_ordergoods->getOne($sql3);
        
        $str = "total=".$behalf_total."<br>".
                "in=".$behalf_in."<br>".
                "out=".$behalf_out."<br>".
                "order_total=".$order_total."<br>"; */
        
        dump($data);
        
        
    }
    
    
}

?>