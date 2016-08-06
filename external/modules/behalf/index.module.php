<?php
include_once ROOT_PATH.'/external/modules/behalf/index.module.base.php';
include_once ROOT_PATH.'/external/modules/behalf/index.module.printer.php';
include_once ROOT_PATH.'/external/modules/behalf/index.module.client.php';

class BehalfModule extends BehalfBaseModule
{
    var $_login_name;         //登录者：代发管理员、货物管理员(拿货员)
    var $_behalf_printer;    //打印类
    var $_behalf_client;
    
    function __construct()
    {
        $this->BehalfModule();
    }

    function BehalfModule()
    {
    	$this->_behalf_printer = new BehalfPrinterModule();
    	$this->_behalf_client = new BehalfClientModule();
        parent::__construct();
       
    }
    
    function test()
    {
    	/* $result = $this->_goods_warehouse_mod->find(array(
    			'conditions'=>'gwh.bh_id = 0',
    			//'fields'=>'gwh.*,gwh.bh_id as gwhbh_id,order_alias.*,order_alias.bh_id as orderbh_id',
    			'fields'=>'gwh.order_id as g_order,gwh.bh_id as gbh_id,order_alias.order_id as o_order,order_alias.bh_id as obh_id',
    			'join'=>'belongs_to_order'
    	)); */
    	$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."member" );
    	dump($result);
    }
     
    
    function updateShiptime()
    {
    	/* $result = $this->_goods_warehouse_mod->find(array(
    			'conditions'=>"bh_id='73499' AND goods_status > 0 "
    	));
    	
    	if(!empty($result))
    	{
    		foreach ($result as $r)
    		{
    			$this->_goods_warehouse_mod->edit($r['id'],array('taker_id'=>$r['bh_id'],'taker_time'=>(gmtime()-4800)));
    		}
    	}
    	
    	echo  count($result);     
    	dump($result); */
    	
    	/* $orderlogs = $this->_orderlog_mod->find(array(
    			'conditions'=>"order_status='待发货' AND changed_status='已发货' AND "
    			.db_create_in(array(ORDER_SHIPPED,ORDER_FINISHED),'order_alias.status')
    			." AND order_alias.ship_time is NULL "
    			." AND bh_id <> '10919'",
    			'join'=>'belongs_to_order',
    			'fields'=>'order_alias.order_id,order_alias.bh_id,order_alias.status,order_alias.ship_time,log_time',
    			'count'=>true
    	));//946656000 2000-01-01
    	 
    	echo count($orderlogs);73499
    	echo "<pre>";
    	print_r ($this->_behalf_mod->find());
    	echo "</pre>";
    	dump($orderlogs); */
    	
    	
    	/* $orderlogs = $this->_orderlog_mod->find(array(
    		'conditions'=>"order_status='待发货' AND changed_status='已发货' AND "
    			.db_create_in(array(ORDER_SHIPPED,ORDER_FINISHED),'order_alias.status')
    			." AND order_alias.ship_time > log_time "
    			." AND bh_id <> '10919'",
    		'join'=>'belongs_to_order',
    		'fields'=>'order_alias.order_id,order_alias.bh_id,order_alias.status,order_alias.ship_time,log_time',
    		'count'=>true	
    	));
    	
    	echo count($orderlogs);
    	if(!empty($orderlogs))
    	{
    		foreach ($orderlogs as $orderlog)
    		{
    			$this->_order_mod->edit($orderlog['order_id'],array('ship_time'=>$orderlog['log_time']));
    		}
    	} */
    }

    /**
     * 主体framesets
     * @see BaseApp::index()
     */
    function index()
    {        
    	$bh_id = $this->visitor->get('has_behalf');
    	if($bh_id)
    	{
	    	/* $goods_list = $this->_goods_warehouse_mod->find(array(
	    			'conditions'=>"gwh.bh_id = {$bh_id} AND ".db_create_in(array(BEHALF_GOODS_PREPARED,BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED),'goods_status')." AND order_alias.status=".ORDER_ACCEPTED,
	    			'fields'=>'gwh.id',
	    			'join'=>'belongs_to_order',
	    	));
	    	$start_time = gmstr2time('yesterday');
	    	$end_time = $start_time + 24*60*60;
	    	$order_list = $this->_order_mod->find(array(
	    			'conditions'=>"bh_id={$bh_id} AND ship_time >= {$start_time} AND ship_time < {$end_time} AND ".db_create_in(array(ORDER_SHIPPED,ORDER_FINISHED),'status'),
	    					));
	    	$order_list1 = $this->_order_mod->find(array(
	    			'conditions'=>"bh_id={$bh_id} AND status=".ORDER_ACCEPTED
	    					)); */
	    	
	    	$behalf_info = $this->_behalf_mod->get($bh_id);
	    	$behalf_info['region_name'] = $this->_remove_China($behalf_info['region_name']);
	    	
	    	$mail_counter = $this->_behalf_printer->getMailCounter(); 
	    	$mail_counter = object_array(json_decode($mail_counter));  
	    	if($mail_counter['result'] == false) {$mail_count = 0;}
	    	else{
	    	    $mail_count = empty($mail_counter['counter']['available']) ? 0 :$mail_counter['counter']['available'] ;
	    	}
    	}
	    	
    	//$this->assign('accepted_goods',count($goods_list));
    	//$this->assign('accepted_orders',count($order_list1));
    	//$this->assign('shipped_orders',count($order_list));
    	$this->assign('mail_counter',$mail_count);
    	$this->assign('behalf',$behalf_info);
    	$this->_assign_leftmenu('dashboard');
    	//$this->_assign_curleftmenu('welcome_page');
        $this->display('index.whole.html');
    }
    
    /**
     * 订单列表
     */
    function order_list()
    {
    	/*获取市场列表*/
    	$this->_get_markets();
    	/* 获取订单列表 */
    	$this->_get_orders(true,'all_orders',true,true,true);
    	/*获取可用快递*/
    	$this->_get_related_delivery();
    	/* 当前用户中心菜单 */
    	$type = (isset($_GET['type']) && $_GET['type'] != '') ? trim($_GET['type']) : 'all_orders';
    	$this->_curmenu($type);
    	$this->_import_css_js('dt');
    	$this->_assign_leftmenu('order_manage');
    	$this->display('behalf.order.list_detail.html');
    }
    
    /**
     * 出入库管理
     */
    function manage_goods_warehouse()
    {
    	if(IS_POST)
    	{
    		$goods_no = trim($_POST['goods_no']);
    		$goods_action =$this->_goods_status_translator(trim($_POST['goods_action']));
    		$goods_action_chinese = $this->_goods_chinese_translator(trim($_POST['goods_action']));
    		//商品编码是否存在
    		$goods_info = $this->_goods_warehouse_mod->get("goods_no='{$goods_no}'");
    		if($goods_info)
    		{
    		    $order_id = $goods_info['order_id'];
    		    $spec_id = $goods_info['goods_spec_id'];
    		    $goods_id = $goods_info['goods_id'];
    		}
    		else
    		{
    		    $this->json_error('goods_no_not_exist');
    		    return;
    		}
    		//所在订单是否待发货或待付款
    		$order = $this->_order_mod->get($order_id);
    		if(!in_array($order['status'], array(ORDER_ACCEPTED,ORDER_PENDING)))
    		{
    		    $this->json_error('order_is_not_accepted');
    		    return;
    		}
    		//开启事务
    		$success = $this->_start_transaction();
    		
    		if(in_array($goods_action, array(BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED,BEHALF_GOODS_UNSALE)))
    		{
    				$affect_rows = $this->_goods_warehouse_mod->edit("goods_no='{$goods_no}'",array('goods_status'=>$goods_action,'taker_id'=>$this->visitor->get('user_id'),'taker_time'=>gmtime()));
    				!$affect_rows && $success = false;
    				$affect_rows = $this->_ordergoods_mod->edit("order_id='{$order_id}' AND spec_id = '{$spec_id}' AND goods_id ='{$goods_id}' ",array('oos_value'=>'0','oos_reason'=>$goods_action_chinese));
    				$affect_rows === false && $success = false;  //可能已更新，返回0
    				//缺货统计
    				$goods_statistics = $this->_goods_statistics_mod->get("{$goods_id}");
    				if($goods_statistics)
    				{
    				    $affect_rows = $this->_goods_statistics_mod->edit("{$goods_id}",'oos=oos+1');
    				    $affect_rows === false && $success = false;
    				}else{
    				    $affect_rows = $this->_goods_statistics_mod->add(array('goods_id'=>"{$goods_id}",'oos'=>1));
    				    !$affect_rows && $success = false;
    				}
    		}
    		//同时下架
    		if($goods_action == BEHALF_GOODS_UNSALE)
    		{
    		    $affect_rows = $this->_goods_mod->edit("goods_id ='{$goods_id}'",array('if_show'=>'0','closed'=>'1','close_reason'=>"behalf[{$this->visitor->get('user_name')}] close it."));
    		    !$affect_rows && $success = false;
    		}
    		if($goods_action == BEHALF_GOODS_READY)
    		{
    			$result = $this->_goods_warehouse_mod->edit("goods_no='{$goods_no}' AND ".db_create_in(array(BEHALF_GOODS_PREPARED,BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED),'goods_status'),array(
    					'goods_status'=>$goods_action,'taker_id'=>$this->visitor->get('user_id'),'taker_time'=>gmtime()
    			));
    			!$result && $success = false;
    			$result = $this->_ordergoods_mod->edit("order_id='{$order_id}' AND spec_id = '{$spec_id}' AND goods_id ='{$goods_id}' ",array('oos_value'=>'1'));
    			//!$result && $success = false;
    		}
    		
    		$this->_end_transaction($success);
    		
    		if($success)
    		{
    			$this->json_result(1,'entern_goodswarehouse_success');
    			return;
    		}
    		else 
    		{
    			$this->json_error('entern_goodswarehouse_fail');
    			return;
    		}
	    	
    	}
    	
    	$this->_import_css_js ('dt');
    	if($this->visitor->get('has_behalf'))
    	{
    		$this->_assign_leftmenu('order_manage');
    	}else 
    	{
    		$this->_assign_leftmenu('dashboard');
    	}
    	
    	$this->display("behalf.goods.warehouse.manage.html");
    }
    
	
    
    
    /**
     * 管理拿货员
     */
    function manage_goodstaker()
    {
    	$bh_id = $this->_get_bh_id();
    	$model_member =& m('member');
    	if (IS_POST)
    	{
    		$user_name = isset($_POST['user_name']) && $_POST['user_name']?trim($_POST['user_name']):'';
    		if(empty($user_name))
    		{
    			$this->json_error('user name empty!');
    			return ;
    		}
    		$infos = Lang::get('unvalid_user_name');
    		$member_info = ms()->user->_local_get(array('conditions'=>"user_name='{$user_name}'"));
    		if($member_info['user_id'] == $this->visitor->get('user_id'))
    		{
    			$infos = Lang::get('self_not_allow_to_taker');
    			$member_info = array();
    		}
    		$this->assign('show_member',true);
    		$this->assign('info_type',empty($member_info)?'warning':'info');
    		$this->assign('infos',$infos);
    		$this->assign('member_info',$member_info);
    	}
    	
    	$members = $model_member->find(array(
    		'conditions'=>'behalf_goods_taker='.$bh_id	
    	));
    	
    	$this->_import_css_js();
    	$this->_assign_leftmenu('setting');
    	$this->assign('members',$members);
    	$this->display('behalf.goods.takers.manage.html');
    }
    
    /**
     * 管理拿货单
     */
    function manage_taker_list()
    {
    	$login_id = $this->visitor->get('user_id');
    	if($this->visitor->get('pass_behalf'))
    	{
    		$condition =" bh_id = {$login_id} ";
    	}
    	else
    	{
    		$condition =" taker_id = {$login_id} ";
    	}
    	$nhd_list = $this->_goods_taker_inventory_mod->find(array(
    		'conditions'=>' visible = 1 AND '.$condition,
    	    'order'=>'createtime DESC'
    	));
    	if($nhd_list)
    	{
    	    foreach ($nhd_list as $key=>$nhd)
    	    {
    	        $goods_ids = explode(',', $nhd['content']);
    	        $goods_list = $this->_goods_warehouse_mod->find(array(
    	            'conditions'=>db_create_in($goods_ids,'id')
    	        ));
    	        $goods_details = array(
    	          'ready'=>array(
    	              'count'=>0, //已备货数量
    	              'amount'=>0, //已备货金额
    	              'discount'=>0 //已备货档口优惠
    	          ),
    	            'lack'=>array(
    	                'count'=>0,//缺货数量
    	                'amount'=>0,
    	                'discount'=>0
    	            ),
    	            'outhouse'=>array(
    	                'count'=>0,//未入库数量
    	                'amount'=>0,
    	                'discount'=>0
    	            ),
    	            'reback'=>array(
    	                'count'=>0,//已退货数量
    	                'amount'=>0,
    	                'discount'=>0
    	            )
    	        );
    	        
    	        if($goods_list)
    	        {
    	            foreach ($goods_list as $gkey=>$goods)
    	            {
    	                if(in_array($goods['goods_status'],array(BEHALF_GOODS_PREPARED)))
    	                {
    	                    $goods_details['outhouse']['count']++;
    	                    $goods_details['outhouse']['amount'] += floatval($goods['goods_price']);
    	                    $goods_details['outhouse']['discount'] += floatval($goods['store_bargin']);
    	                }
    	                elseif(in_array($goods['goods_status'],array(BEHALF_GOODS_REBACK)))
    	                {
    	                    $goods_details['reback']['count']++;
    	                    $goods_details['reback']['amount'] += floatval($goods['goods_price']);
    	                    $goods_details['reback']['discount'] += floatval($goods['store_bargin']);
    	                }
    	                elseif(in_array($goods['goods_status'], array(BEHALF_GOODS_READY,BEHALF_GOODS_SEND)))
    	                {
    	                    $goods_details['ready']['count']++;
    	                    $goods_details['ready']['amount'] += floatval($goods['goods_price']);
    	                    $goods_details['ready']['discount'] += floatval($goods['store_bargin']);
    	                }
    	                elseif(in_array($goods['goods_status'], array(BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED,BEHALF_GOODS_UNSALE)))
    	                {
    	                    $goods_details['lack']['count']++;
    	                    $goods_details['lack']['amount'] += floatval($goods['goods_price']);
    	                    $goods_details['lack']['discount'] += floatval($goods['store_bargin']);
    	                }
    	            }
    	        }
    	       
    	        $nhd_list[$key]['goods_details'] = $goods_details;    	        
    	    }
    	}
    	$this->_import_css_js('dtall');
    	$this->_assign_leftmenu('order_manage');
    	$this->assign('nhd_list',$nhd_list);
    	$this->display('behalf.goods.taker.list.manage.html');
    }
	/**
	 * 设置 拿货员
	 */
	function edit_goods_taker()
	{
		$bh_id = $this->_get_bh_id();
		$model_member =& m('member');
		
		if(isset($_GET['m']) && $_GET['m'])
    	{
    		$user_id = intval($_GET['id']);    		
    		//设为拿货员
    		if($_GET['m'] == 1)
    		{
    			$affect_rows = $model_member->edit($user_id,array('behalf_goods_taker'=>$bh_id));
    			if($affect_rows)
    			{
    				$this->json_result(1,'set_ok');
    			}
    			else 
    			{
    				$this->json_error('set_fail');
    			}
    		}
    		//解除拿货员
    		if($_GET['m'] == 2)
    		{
    			$affect_rows = $model_member->edit($user_id,array('behalf_goods_taker'=>'0'));
    			if($affect_rows)
    			{
    				$this->json_result(1,'set_ok');
    			}
    			else
    			{
    				$this->json_error('set_fail');
    			}
    		}
    	}
	}

    
    /**
     * 生成拿货单
     */
    function gen_taker_list()
    {
    	$bh_id = $this->visitor->get('user_id');
    	$bh_markets = $this->_behalf_mod->getRelatedData('has_market',$bh_id);
    	if($bh_markets)
    	{
    	    $sort_arr = array();//用于多维排序
    	    foreach ($bh_markets as $k=>$v)
    	    {
    	        $sort_arr[] = $v['sort_ord'];
    	    }
    	    array_multisort($sort_arr,SORT_ASC,$bh_markets);
    	}   	
    	
    	$bh_deliverys = $this->_behalf_mod->getRelatedData('has_delivery',$bh_id);
    	$behalf_info =$this->_behalf_mod->get($bh_id);
    	$refund_order_ids = $this->get_refunds_orders();
    	$conditions_refund = "";
    	if(!empty($refund_order_ids))
    	{
    	   $conditions_refund = " AND order_alias.order_id NOT ".db_create_in($refund_order_ids);    
    	}
    	
    	if(IS_POST)
    	{
    		$goods_ids = array();//仓库商品ids
	    	$market_names = array();//市场名称
	    	$start_time = $_POST['query_time'] ? gmstr2time($_POST['query_time']) : 0;
	    	$end_time = $_POST['query_endtime'] ? gmstr2time($_POST['query_endtime']) : 0;
	    	
	    	$mk_ids = $_POST['market'];//市场id
	    	$dl_id = $_POST['delivery'] ? intval($_POST['delivery']) : 0;
	    	$condition_dl = $dl_id > 0 ?" AND delivery_id = '{$dl_id}' ":""; 
	    	
	    	foreach ($bh_markets as $mark)
	    	{
	    		foreach ($mk_ids as $mkid)
	    		{
	    			if($mkid == $mark['mk_id'])
	    			{
	    				$market_names[] = $mark['mk_name'];
	    			}
	    		}	
	    	}
	    	
	    	if(!empty($mk_ids) && $start_time)
	    	{
		    	$result = $this->_goods_warehouse_mod->find(array(
		    		'conditions'=>"gwh.bh_id = {$bh_id} AND order_alias.pay_time >= '{$start_time}' AND order_alias.pay_time <= '{$end_time}' {$condition_dl} AND ".
		    		db_create_in(array(BEHALF_GOODS_PREPARED,BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED),'goods_status').
		    		" AND ".db_create_in($mk_ids,'market_id')." AND order_alias.status=".ORDER_ACCEPTED.$conditions_refund,
		    		'fields'=>'gwh.*,order_alias.status',
		    		'join'=>'belongs_to_order',
		    		'order'=>'market_id ASC,floor_id ASC,store_address ASC'	
		    	));
		    	if($result)
		    	{
		    		$total_count = count($result);//商品件数
		    		$total_amount = 0; //商品总金额
		    		$store_bargin = 0;//店家优惠
		    		foreach ($result as $gkey=>$goods)
		    		{
		    			$goods_ids[] = $goods['id'];
		    			$total_amount += floatval($goods['goods_price']);
		    			$store_bargin += floatval($goods['store_bargin']);
		    			$result[$gkey]['goods_attr_value'] = $this->_Attrvalue2Pinyin($goods['goods_attr_value']);
		    		}
		    		
		    		$this->assign('takers',$this->_behalf_mod->getRelatedData('has_membertaker',$bh_id));//拿货员
		    		$this->assign('content',implode(',',$goods_ids));//保存拿货单用
		    		$this->assign('mkids',implode(',',$mk_ids));//保存拿货单用
		    		$this->assign('mknames',implode(',',$market_names));//保存拿货单用
		    		$this->assign('bh_id',$bh_id);//保存拿货单用
		    		
		    		$this->assign('total_count',$total_count);
		    		$this->assign('total_amount',$total_amount);
		    		$this->assign('store_bargin',$store_bargin);
		    		$this->assign('last_amount',$total_amount-$store_bargin);
		    	}
		    	//dump($result);
		    	$this->assign("end_time",$_POST['query_endtime']);
		    	$this->assign('start_time',$_POST['query_time']);
		    	$this->assign('goods_list',$result);
	    	}
    	}
    	else
    	{
    		$model_goods_warehouse = & m('goodswarehouse');
    		$result = $model_goods_warehouse->find(array(
    				'conditions'=>"gwh.bh_id = {$bh_id} AND ".db_create_in(array(BEHALF_GOODS_PREPARED,BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED),'goods_status').
    				" AND order_alias.status=".ORDER_ACCEPTED.$conditions_refund,
    				'fields'=>'gwh.*,order_alias.status,order_alias.pay_time',
    				'join'=>'belongs_to_order',
    				'order'=>'market_id ASC,floor_id ASC,store_address ASC',
    				'limit'=>'50',
    				'count'=>true
    		));
    		if($result)
    		{
    			$count_ttt = $model_goods_warehouse->getCount();
    			
    			$total_count = count($result);//商品件数
    			
    			$total_amount = 0; //商品总金额
    			$store_bargin = 0;//店家优惠
    			$start_time = gmtime();
    			$end_time = 0;//找出最小时间和最大时间
    			foreach ($result as $gkey=>$goods)
    			{
    				$goods_ids[] = $goods['id'];
    				$total_amount += floatval($goods['goods_price']);
    				$store_bargin += floatval($goods['store_bargin']);
    				$result[$gkey]['goods_attr_value'] = $this->_Attrvalue2Pinyin($goods['goods_attr_value']);
    				$goods['pay_time'] > $end_time && $end_time = $goods['pay_time'];
    				$goods['pay_time'] < $start_time && $start_time = $goods['pay_time'];
    			}
    			
    			$rest_count = intval($count_ttt) - intval($total_count);
    			
    			$this->assign('rest_count',$rest_count);
    			$this->assign('default_search',true);
    			$this->assign('total_count',$total_count);
    			$this->assign('total_amount',$total_amount);
    			$this->assign('store_bargin',$store_bargin);
    			$this->assign('last_amount',$total_amount-$store_bargin);
    		}
    		
    		$this->assign("end_time",local_date('Y-m-d H:i:s',$end_time));
    		$this->assign('start_time',local_date('Y-m-d H:i:s',$start_time));
    		$this->assign('goods_list',$result);
    	}
    	
    	$this->assign('bh_name',$behalf_info['bh_name']);
    	$this->assign('show_print',true);
    	$this->_assign_leftmenu('order_manage');
    	$this->_import_css_js('dtall');    	
    	$this->assign('delivery',$_POST['delivery']);
    	$this->assign('market_choice',$mk_ids?$mk_ids:array());
    	$this->assign('markets',$bh_markets);
    	$this->assign("deliverys",$bh_deliverys);
    	$this->display('behalf.goods.taker.list.html');
    	
    }
    
    
    /**
     * 获取拿货单商品详情
     */
    function get_nhd_goods()
    {
    	$id = isset($_POST['id']) && $_POST['id'] ? trim($_POST['id']) : '';
    	if(!$id)
    	{
    		$this->json_error('nothing');
    		return;
    	}
    	
    	$taker_invertory = $this->_goods_taker_inventory_mod->get($id);
    	if($taker_invertory)
    	{
    	    $ids = explode(',', $taker_invertory['content']);
        	$goods_list = $this->_goods_warehouse_mod->find(array(
        		'conditions'=>db_create_in($ids,'id')	
        	));
    	}
    	
    	$this->assign('show_print',true);
    	$this->_assign_leftmenu('order_manage');
    	$this->_import_css_js('dtall');
    	$this->assign('goods_list',$goods_list);
    	$this->display('behalf.goods.taker.list.goods_detail.html');
    	
    }
    
    /**
     * 保存拿货单信息
     */
    function save_nhd()
    {
    	
    	$data = array(
    		'bh_id'=>trim($_POST['behalf']),	
    		'goods_count'=>intval(trim($_POST['goods_count'])),	
    		'goods_amount'=>floatval(trim($_POST['goods_amount'])),	
    		'store_bargin'=>floatval(trim($_POST['store_bargin'])),	
    		'content'=>trim($_POST['content']),	
    		'mk_ids'=>trim($_POST['market_id']),	
    		'mk_names'=>trim($_POST['market_name']),	
    		'taker_id'=>trim($_POST['nhd_taker']),	
    		'name'=>html_filter(trim($_POST['nhd_name'])),	
    		'deal_time'=>0,	
    		'createtime'=>gmtime(),
    	    'search_time'=>trim($_POST['search_time']),
    	    'search_delivery'=>trim($_POST['search_delivery'])
    	);
    	
    	if($data['bh_id'] != $this->visitor->get('user_id'))
    	{
    		$this->json_error('feifacaozuo');
    		return;	
    	}
    	if($data['taker_id'])
    	{
    		$member_info = ms()->user->_local_get($data['taker_id']);
    		$data['taker_name'] = $member_info['user_name']." | ".$member_info['real_name'];
    	}
    	if(empty($data['search_delivery']))
    	{
    	    $data['search_delivery'] = Lang::get('all_deliveries');
    	}
    	else 
    	{
    	    $delivery_result = $this->_delivery_mod->get($data['search_delivery']);
    	    $data['search_delivery'] = $delivery_result['dl_name'];
    	}
    	
    	$result = $this->_goods_taker_inventory_mod->add($data);
    	if($result)
    	{
    	    $nhd_info = $this->_goods_taker_inventory_mod->get($result);
    		$this->json_result(local_date("Y-m-d H:i:s",$nhd_info['createtime']),'caozuo_success');
    	}
    	else
    	{
    		$this->json_error('caozuo_fail');
    	}
    }
    
    /**
     * 统计多个拿货单
     */
    function stat_nhd()
    {
        $ids = explode(',',$_GET['ids']);
        if(!$ids)
        {
            $this->json_error('caozuo_fail');
            return;
        }
        
        $nhd_list = $this->_goods_taker_inventory_mod->find(array(
            'conditions'=>"bh_id='{$this->visitor->get('user_id')}' AND ".' visible = 1 AND id '.db_create_in($ids)
        ));
        if($nhd_list)
        {
            $goods_ids = array();
            
            foreach ($nhd_list as $key=>$nhd)
            {
                $temp_ids =  explode(',', $nhd['content']);
                $goods_ids = array_merge($goods_ids,$temp_ids);
            }
            $goods_ids = array_unique($goods_ids);//filter repeat values
            $goods_ids = array_filter($goods_ids);//filter null
            
            
            //$goods_ids = explode(',', $nhd['content']);
            $goods_list = $this->_goods_warehouse_mod->find(array(
                'conditions'=>db_create_in($goods_ids,'id')
            ));
            $goods_details = array(
                'ready'=>array(
                    'count'=>0, //已备货数量
                    'amount'=>0, //已备货金额
                    'discount'=>0 //已备货档口优惠
                ),
                'lack'=>array(
                    'count'=>0,//缺货数量
                    'amount'=>0,
                    'discount'=>0
                ),
                'outhouse'=>array(
                    'count'=>0,//未入库数量
                    'amount'=>0,
                    'discount'=>0
                ),
                'reback'=>array(
                    'count'=>0,//退货数量
                    'amount'=>0,
                    'discount'=>0
                )
            );
                 
            if($goods_list)
            {
                foreach ($goods_list as $gkey=>$goods)
                {
                    if(in_array($goods['goods_status'],array(BEHALF_GOODS_PREPARED)))
                    {
                        $goods_details['outhouse']['count']++;
                        $goods_details['outhouse']['amount'] += floatval($goods['goods_price']);
                        $goods_details['outhouse']['discount'] += floatval($goods['store_bargin']);
                    }
                    elseif(in_array($goods['goods_status'],array(BEHALF_GOODS_REBACK)))
                    {
                        $goods_details['reback']['count']++;
                        $goods_details['reback']['amount'] += floatval($goods['goods_price']);
                        $goods_details['reback']['discount'] += floatval($goods['store_bargin']);
                    }
                    elseif(in_array($goods['goods_status'], array(BEHALF_GOODS_READY,BEHALF_GOODS_SEND)))
                    {
                        $goods_details['ready']['count']++;
                        $goods_details['ready']['amount'] += floatval($goods['goods_price']);
                        $goods_details['ready']['discount'] += floatval($goods['store_bargin']);
                    }
                    elseif(in_array($goods['goods_status'], array(BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED,BEHALF_GOODS_UNSALE)))
                    {
                        $goods_details['lack']['count']++;
                        $goods_details['lack']['amount'] += floatval($goods['goods_price']);
                        $goods_details['lack']['discount'] += floatval($goods['store_bargin']);
                    }
                }
            }
        
           //$nhd_list[$key]['goods_details'] = $goods_details;
           $this->json_result(array('total'=>count($goods_ids),'details'=>$goods_details),'success');
           return;
        }
        else 
        {
            $this->json_error('caozuo_empty');
            return;
        }
        
        
    }
   
    
    /**
     * 发货统计
     */
    function stat_shipped_order()
    {
    	$bh_id = $this->visitor->get('has_behalf');
    	$start_time = $_POST['query_time'] ? gmstr2time($_POST['query_time']):0;
    	$end_time = $start_time + 86400;
    	
    	if($start_time)
    	{
    		$order_list = $this->_order_mod->find(array(
    			'conditions'=>"order_alias.bh_id={$bh_id} AND order_alias.ship_time >= {$start_time} AND order_alias.ship_time < {$end_time} AND ".db_create_in(array(ORDER_SHIPPED,ORDER_FINISHED),'order_alias.status'),	
    			'join'=>'has_orderextm'
    		));
    		
    		$order_count = 0;//订单总数
    		$order_goods_amount = 0;//商品总金额
    		$goods_count = 0;//商品总件数
    		$order_amount = 0;//订单总金额
    		$lack_goods_count = 0;//缺货件数
    		$lack_goods_amount = 0;//缺货总金额
    		$back_order_count = 0;//退货订单数
    		$back_order_amount =0;//退货总金额
    		$stat_delivery = array();//快递数据
    		$total_fr = 0;//总分润
    		$goods_fr = 0;//商品分润
    		$reback_fr = 0;//返回的分润
    		if($order_list)
    		{
    			$deliverys = $this->_delivery_mod->find(); 
    			
    			$order_count = count($order_list);
    			$order_ids_arr = array();
    			foreach ($order_list as $order)
    			{
    				$order_goods_amount += floatval($order['goods_amount']);
    				$order_amount += floatval($order['order_amount']);
    				$order_ids_arr[] = $order['order_id'];
    				$total_fr += floatval($order['behalf_discount']);//订单分润
    				if(!in_array($order['dl_id'],array_keys($stat_delivery)))
    				{
    					$stat_delivery[$order['dl_id']] = array(
    						'count'=>1,
    						'name'=>$deliverys[$order['dl_id']]['dl_name']	
    					);
    				}
    				else 
    				{
    					$stat_delivery[$order['dl_id']]['count'] += 1;
    				}
    			}
    			
    			$order_refunds = $this->_orderrefund_mod->find(array(
    					'conditions'=>"receiver_id = {$bh_id} AND ".db_create_in($order_ids_arr,'order_id')." AND status=1 AND closed=0"
    			));
    			if($order_refunds)
    			{
    				$order_refund_ids = array();
    				foreach ($order_refunds as $orefund)
    				{
    					$order_refund_ids[] = $orefund['order_id'];
    					$back_order_amount += floatval($orefund['refund_amount']);
    				}
    				$back_order_count = count(array_unique($order_refund_ids));
    			}
    			
    			$order_goods = $this->_goods_warehouse_mod->find(array(
    			    'conditions'=>db_create_in($order_ids_arr,'order_id')
    			));
    			if($order_goods)
    			{
    			    $goods_count = count($order_goods);
    			    foreach ($order_goods as $ogoods)
    			    {
    			        if(in_array($ogoods['goods_status'], array(BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED,BEHALF_GOODS_UNSALE,BEHALF_GOODS_PREPARED)))
    			        {
    			            $lack_goods_count ++;
    			            $lack_goods_amount += floatval($ogoods['goods_price']);
    			        }
    			        else
    			        {
    			            $goods_fr += floatval($ogoods['behalf_to51_discount']);
    			            $reback_fr += floatval($ogoods['zwd51_tobehalf_discount']);
    			        }
    			    }
    			}
    			/*
    			$order_goods = $this->_ordergoods_mod->find(array(
    			    'conditions'=>db_create_in($order_ids_arr,'order_id')
    			));
    			if($order_goods)
    			{
    			    foreach ($order_goods as $ogoods)
    			    {
    			        $goods_count  += intval($ogoods['quantity']);
    			        if(!$ogoods['oos_value'])
    			        {
    			            $lack_goods_count += intval($ogoods['quantity']);
    			            $lack_goods_amount += intval($ogoods['quantity']) * floatval($ogoods['price']);
    			        }
    			        else 
    			        {
    			            $goods_fr += floatval($ogoods['behalf_to51_discount']);
    			            $reback_fr += floatval($ogoods['zwd51_tobehalf_discount']);
    			        }
    			    }
    			}
    			*/
    			
    			$this->assign('order_list',$order_list);
    		}
    		
    		$real_amount = $order_amount - $lack_goods_amount - $back_order_amount;
    		
    		$this->assign('lack_goods_count',$lack_goods_count);
    		$this->assign('lack_goods_amount',$lack_goods_amount);
    		$this->assign('back_order_count',$back_order_count);
    		$this->assign('back_order_amount',$back_order_amount);
    		$this->assign('real_amount',$real_amount);
    		$this->assign('deliverys',$stat_delivery);
    		
    		$this->assign("order_count",$order_count);
    		$this->assign("goods_count",$goods_count);
    		$this->assign("order_goods_amount",$order_goods_amount);
    		$this->assign("order_amount",$order_amount);
    		$this->assign('kd_fr',$total_fr-$goods_fr);
    		$this->assign('goods_fr',$goods_fr-$reback_fr);
    	}
    	
    	$this->_assign_leftmenu('order_manage');
    	$this->_import_css_js('dtall');
    	$this->assign('start_time',$_POST['query_time']);
    	$this->display('behalf.stat.order.shipped.html');
    }
    
    /**
     * 入库统计
     */
    function stat_enter_warehouse()
    {  
   		$bh_id = $this->visitor->get('has_behalf');
    	$bh_markets = $this->_behalf_mod->getRelatedData('has_market',$bh_id);
    	$bh_deliverys = $this->_behalf_mod->getRelatedData('has_delivery',$bh_id);
    	
    	$behalf_info =$this->_behalf_mod->get($bh_id);
    	if(IS_POST)
    	{
    		$start_time = $_POST['query_time'] ? gmstr2time($_POST['query_time']) : 0;
    		$end_time = $_POST['query_endtime'] ? gmstr2time($_POST['query_endtime']) : 0;
    	
    		$mk_ids = $_POST['market'];//市场id
    		$dl_id = $_POST['delivery'] ? intval($_POST['delivery']) : 0;
    		$condition_dl = $dl_id > 0 ?" AND delivery_id = '{$dl_id}' ":"";
    	
    		foreach ($bh_markets as $mark)
    		{
    			foreach ($mk_ids as $mkid)
    			{
    				if($mkid == $mark['mk_id'])
    				{
    					$market_names[] = $mark['mk_name'];
    				}
    			}
    		}
    	
    		if(!empty($mk_ids) && $start_time)
    		{
    			$goods_count = 0; //拿货件数
    			$goods_amount =0 ;//拿货金额
    			$store_bargin = 0;//档口优惠金额
    			$order_ids = array();//涉及订单数
    			$members = array();
    			$goods_list = $this->_goods_warehouse_mod->find(array(
    					'conditions'=>"bh_id = {$bh_id} AND taker_time >= '{$start_time}' AND taker_time < '{$end_time}' {$condition_dl} AND "
    							.db_create_in(array(BEHALF_GOODS_READY),'goods_status')." AND ".db_create_in($mk_ids,'market_id'),
    							'order'=>'taker_time DESC'
    							));
    			if($goods_list)
	    		{
	    			$goods_count = count($goods_list);
	    			foreach ($goods_list as $goods)
	    			{
	    				$goods_amount += floatval($goods['goods_price']);
	    				$store_bargin += floatval($goods['store_bargin']);
	    				if(!in_array($goods['order_id'], $order_ids))
	    				{
	    					$order_ids[] = $goods['order_id'];
	    				}
	    				if(!in_array($goods['taker_id'], $members))
	    				{
	    					$members[$goods['taker_id']] = array('user_id'=>$goods['taker_id']);
	    				}
	    			}
	    			
	    			foreach ($members as $mkey=>$m)
	    			{
	    				$member_info = ms()->user->_local_get($m['user_id']);
	    				$members[$mkey]['user_name'] = $member_info['user_name'];
	    			}
	    			
	    			foreach ($goods_list as $gkey=>$g)
	    			{
	    				$goods_list[$gkey]['taker_name'] = $members[$g['taker_id']]['user_name'];
	    			}
	    		}
	    		
	    		$this->assign("goods_count",$goods_count);
	    		$this->assign("goods_amount",$goods_amount);
	    		$this->assign("store_bargin",$store_bargin);
	    		$this->assign("last_amount",$goods_amount - $store_bargin);
	    		$this->assign("order_count",count($order_ids));
	    		$this->assign('goods_list',$goods_list);
    		}
    		
    		$this->assign("end_time",$_POST['query_endtime']);
    		$this->assign('start_time',$_POST['query_time']);
    	}
    	
    	$this->_import_css_js('dt');
   		if($this->visitor->get('has_behalf'))
    	{
    		$this->_assign_leftmenu('order_manage');
    	}else 
    	{
    		$this->_assign_leftmenu('dashboard');
    	}
    	
    	$this->assign('delivery',$_POST['delivery']);
    	$this->assign('market_choice',$mk_ids?$mk_ids:array());
    	$this->assign('markets',$bh_markets);
    	$this->assign("deliverys",$bh_deliverys);
    	$this->display('behalf.stat.warehouse.enter.html');
    }
    
    
    /**
     * 设置拿货市场
     */
    function set_markettaker()
    {    	
    	$bh_id = $this->visitor->get('user_id');
    	$bh_markets = $this->_behalf_mod->getRelatedData('has_market',$bh_id);
    	if(!IS_POST)
    	{
    		$bh_markettakers = $this->_behalf_mod->getRelatedData('has_markettakers',$bh_id);
    		$this->_import_css_js();
    		$this->_assign_leftmenu('setting');
    		$this->assign('markets',$bh_markets);
    		$this->assign('markettakers',$bh_markettakers);
    		$this->display('behalf.goods.markettaker.set.html');
    	}
    	else 
    	{
    		$mt_name = trim($_POST['mt_name']);
    		$mk_ids = $_POST['market']?strval(implode(',', $_POST['market'])):'';
    		$mk_names = array();
    		foreach ($_POST['market'] as $mid)
    		{
    			foreach ($bh_markets as $bm)
    			{
    				if($mid == $bm['mk_id'])
    				{
    					$mk_names[] = $bm['mk_name'];
    				}
    			}
    		}
    		
    		$data = array(
    			'mt_name'=>$mt_name,
    			'mk_ids'=>$mk_ids,
    			'mk_names'=>implode(',', $mk_names),
    			'bh_id'=>$bh_id	
    		);
    		$model_markettaker =& m('markettaker');
    		$affect_id = $model_markettaker->add($data);
    		if($affect_id)
    		{
    			$this->json_result(1,'add_success');
    		}
    	}
    	
    	//dump($bh_markets);
    }
    
    function see_behalf()
    {
    	$user_id = $this->visitor->get('has_behalf');
    	$behalf = $this->_behalf_mod->get($user_id);
    	$this->_assign_leftmenu('setting');
    	$this->assign('behalf',$behalf);
    	$this->display('behalf.info.see.html');
    }
    
    
    /**
     * 设置代发
     */
    function set_behalf()
    {
    	$user_id = $this->visitor->get('has_behalf');
    	if (!IS_POST)
    	{
    		/* 当前位置 */    		
    		$behalf = $this->_behalf_mod->get($user_id);
    
    		$region_mod =& m('region');
    		$this->assign('regions', $region_mod->get_options(0));
    
    		$this->assign("behalf",$behalf);
    		$this->_assign_leftmenu('setting');
    		//$this->import_resource('jquery.plugins/jquery.validate.min.js,mlselection.js');
    		$this->display('behalf.info.set.html');
    	}
    	else
    	{
    		if(!$this->_allow_behalf_setting('set_behalf')) return;
    
    		$data = $_POST;
    		$data['max_orders'] = abs(intval($data['max_orders']));
    		foreach ($data as $key=>$value)
    		{
    			if(empty($value) && $data['max_orders'])
    				unset($data[$key]);
    		}
    		/* 检查名称是否已存在 */
    		if (!$this->_behalf_mod->unique(trim($data['bh_name']),$data['bh_id']))
    		{
    			$this->json_error('name_exist');
    			return;
    		}
    
    		$this->_behalf_mod->edit($data['bh_id'], $data);
    		if($this->_behalf_mod->has_error())
    		{
    			$this->json_error('update failed!');
    			return;
    		}
    		$this->json_result(1,'edit_delivery_successed');
    	}
    
    }
    /**
     * 设置代发关联快递
     */
    function set_delivery()
    {
    	$user_id = $this->visitor->get('has_behalf');
    	$behalf_deliveries = $this->_behalf_mod->getRelatedData('has_delivery',$user_id);
    	$exist_deliveries = array();
    	foreach ($behalf_deliveries as $value)
    	{
    	    $exist_deliveries[] = $value['dl_id'];
    	}
    	if (!IS_POST)
    	{
    		/* 当前位置 */
    		$behalf = $this->_behalf_mod->get($user_id);
    	
    		$deliveries = $this->_delivery_mod->find();
    		
    	
    		$this->assign("behalf",$behalf);
    		$this->assign("deliveries",$deliveries);
    		$this->assign("exist_deliveries",$exist_deliveries);
    		$this->_assign_leftmenu('setting');
    		//$this->import_resource('jquery.plugins/jquery.validate.min.js,mlselection.js');
    		$this->display('behalf.info.set_delivery.html');
    	}
    	else
    	{
    		if(!$this->_allow_behalf_setting('set_delivery')) return;
    	
    		$data = $_POST;
    		extract($data);
    		if(!empty($data))
    		{
    		    //dump($deliveries);
    		    $drop_ids = array_diff($exist_deliveries, $deliveries);
    		    $create_ids = array_diff($deliveries,$exist_deliveries);
    		    if(!empty($drop_ids))
    		    {
    		        $this->_behalf_mod->unlinkRelation('has_delivery',$user_id,$drop_ids);
    		    }
    		    if(!empty($create_ids))
    		    {
    		        $this->_behalf_mod->createRelation('has_delivery',$user_id,$create_ids);
    		    }    		    
    		    if($this->_behalf_mod->has_error())
    		    {
    		        $this->json_error('update failed!');
    		        return;
    		    }
    		   
    		}
    		$this->json_result(1,'edit_delivery_successed');
    	}
    }
    /**
     * 设置代发关联快递费用
     */
    function set_delivery_fee()
    {
    	$user_id = $this->visitor->get('has_behalf');
    	if (!IS_POST)
    	{
    		/* 当前位置 */
    		$behalf = $this->_behalf_mod->get($user_id);
    	
    		$behalf_deliveries = $this->_behalf_mod->getRelatedData('has_delivery',$user_id);
    		$exist_deliveries = array();
    		foreach ($behalf_deliveries as $value)
    		{
    			$exist_deliveries[] = $value['dl_id'];
    		}
    	
    		$this->assign("behalf",$behalf);
    		$this->assign('deliveries',$behalf_deliveries);
    		$this->_assign_leftmenu('setting');
    		//$this->import_resource('jquery.plugins/jquery.validate.min.js,mlselection.js');
    		$this->display('behalf.info.set_delivery_fee.html');
    	}
    	else
    	{
    		if(!$this->_allow_behalf_setting('set_delivery_fee')) return;
    	
    		$data = $_POST;
    		extract($data);
    		if(!empty($data))
    		{
    			/* $behalf_mod->unlinkRelation('has_delivery',$user_id);
    			 $behalf_mod->createRelation('has_delivery',$user_id,$deliveries); */
    			$deliveries_fees = array();
    			foreach ($dl_ids as $key=>$dl_id)
    			{
    				$deliveries_fees[$key]['dl_id'] = intval($dl_id);
    				$deliveries_fees[$key]['first_amount'] = abs(intval($dl1_quantity[$key])) > 1 ? abs(intval($dl1_quantity[$key])):1;
    				$deliveries_fees[$key]['first_price'] = abs(floatval($dl1_fee[$key]));
    				$deliveries_fees[$key]['step_amount'] = abs(intval($dl2_quantity[$key])) > 1 ? abs(intval($dl2_quantity[$key])):1;
    				$deliveries_fees[$key]['step_price'] = abs(floatval($dl2_fee[$key]));
    			}
    		
    			$this->_behalf_mod->unlinkRelation('has_delivery',$user_id);
    			$this->_behalf_mod->createRelation('has_delivery',$user_id,$deliveries_fees);
    			//$behalf_mod->updateRelation('has_delivery',$user_id,$deliveries_ids,$deliveries_fees);
    			if($this->_behalf_mod->has_error())
    			{
    				$this->json_error('update failed!');
    				return;
    			}
    		}
    		$this->json_result(1,'edit_delivery_successed');
    	}
    }
    /**
     * 设置代发关联快递费用
     */
    function set_behalf_market()
    {
    	$user_id = $this->visitor->get('has_behalf');
    	if (!IS_POST)
    	{
    		/* 当前位置 */
    		$behalf = $this->_behalf_mod->get($user_id);
    	
    		$markets = $this->_market_mod->get_list(1);
            $behalf_markets = $this->_behalf_mod->getRelatedData('has_market',$user_id);
            //dump($behalf_markets);
            $exist_markets = array();
            foreach ($behalf_markets as $value)
            {
                $exist_markets[$value['mk_id']] = array('mk_id'=>$value['mk_id'],'sort_ord'=>$value['sort_ord']);
            }
            
            //behalf_markets sign to markets
            if($markets)
            {
                $sort_arr = array();//用于多维排序
                foreach ($markets as $k=>$v)
                {
                   $sort_arr[] = $markets[$k]['sort_order'] = $exist_markets[$k]['sort_ord'] ? $exist_markets[$k]['sort_ord'] : 255;
                     
                }
                array_multisort($sort_arr,SORT_ASC,$markets);
            }
            
            
            $this->assign("behalf",$behalf);
            $this->assign('markets',$markets);
            $this->assign('exist_markets',$exist_markets);
    		
    		$this->_assign_leftmenu('setting');
    		//$this->import_resource('jquery.plugins/jquery.validate.min.js,mlselection.js');
    		$this->display('behalf.info.set_behalf_market.html');
    	}
    	else
    	{
    		if(!$this->_allow_behalf_setting('set_behalf_market')) return;
    	      
    		$data = $_POST;
    		//dump($data);
            extract($data);
            if(!empty($data))
            {                
                $this->_behalf_mod->unlinkRelation('has_market',$user_id);
                $this->_behalf_mod->createRelation('has_market',$user_id,$markets);
                if($markets)
                {
                    foreach ($markets as $mark)
                    {
                        $this->_behalf_mod->updateRelation('has_market',$user_id,$mark,"sort_ord={$sorts[$mark]}");
                    }
                    
                }               
                if($this->_behalf_mod->has_error())
                {
                    $this->json_error('update failed!');
                    return;
                }
            }
            else 
            {
                $this->_behalf_mod->unlinkRelation('has_market',$user_id);
                if($this->_behalf_mod->has_error())
                {
                    $this->json_error('update failed!');
                    return;
                }
            }
    		$this->json_result(1,'edit_delivery_successed');
    	}
    }
    
    /**
     * 面单账号设置
     */
    function set_mbaccount()
    {
    	$user_id = $this->visitor->get('has_behalf');
    	
    	if (IS_POST)
    	{
    		if(!$this->_allow_behalf_setting('set_behalf_account'))  return;
    		$data = array();
    		$data['behalf_modeb_account_'.$user_id] = array();
    		$yto_account = empty($_POST['yto_account'])?'':trim($_POST['yto_account']);
    		$yto_pass = empty($_POST['yto_pass'])?'':trim($_POST['yto_pass']);
    		$zto_account = empty($_POST['zto_account'])?'':trim($_POST['zto_account']);
    		$zto_pass = empty($_POST['zto_pass'])?'':trim($_POST['zto_pass']);
    		
    		if(!empty($yto_pass) && !empty($yto_account))
    		{
    			$data['behalf_modeb_account_'.$user_id]['yto_account'] = $yto_account;
    			$data['behalf_modeb_account_'.$user_id]['yto_pass']= $yto_pass;
    		}
    		if(!empty($zto_pass) && !empty($zto_account))
    		{
    			$data['behalf_modeb_account_'.$user_id]['zto_account'] = $zto_account;
    			$data['behalf_modeb_account_'.$user_id]['zto_pass']= $zto_pass;
    		}
    		
    		$model_setting = &af('settings');
    		   		
    		$model_setting->setAll($data);
    		$this->json_result(1,'edit_behalf_account_successed');
    	}
    	else 
    	{
	    	$this->assign('infos',Conf::get('behalf_modeb_account_'.$user_id));
	    	$this->_assign_leftmenu('setting');
	    	$this->display('behalf.info.account.set.html');
    	}
    }
    
    /**
     * 通过商品编码查找订单
     */
    function search_goods_no()
    {
    	$goods_no = isset($_GET['goods_no']) && $_GET['goods_no'] ? trim($_GET['goods_no']) : '';
    	if(!preg_match('/^\d{14,20}$/', $goods_no))
    	{
    		$this->json_error('find_fail');
    		return;
    	}
    	$goods_info = $this->_goods_warehouse_mod->get(array('conditions'=>"goods_no='{$goods_no}'"));
    	if(!$goods_info)
    	{
    		$this->json_error('find_fail');
    		return;
    	}
    	$order_info = $this->_order_mod->findAll(array(
    		'conditions'=>"order_alias.order_id = {$goods_info['order_id']}",
    		'join'=>'has_orderextm',
    		'include'=>array('has_goodswarehouse')	
    	));
    	$order_info = current($order_info);
    	if($order_info)
    	{
    		$delivery = $this->_delivery_mod->get($order_info['dl_id']);
    		$order_info['dl_name'] = $delivery['dl_name'];
    		$behalf = $this->_behalf_mod->get($order_info['bh_id']);
    		$order_info['bh_name'] = $behalf['bh_name'];
    		$order_info['region_name'] = $this->_remove_China($order_info['region_name']);
    		
    		$orderrefunds = $this->_orderrefund_mod->find(array('conditions'=>"order_id={$order_info['order_id']}"));
    		if(!empty($orderrefunds))
    		{
    			foreach($orderrefunds as $refund)
    			{
    				if($refund['type'] == 1 && $refund['receiver_id'] == $order_info['bh_id'] && $refund['status'] == 0 && $refund['closed'] == 0)
    				{
    					empty($order_info['refunds']) && $order_info['refunds'] = $refund;
    				}
    				if($refund['type'] == 2 && $refund['sender_id'] == $order_info['bh_id'] && $refund['status'] == 0 && $refund['closed'] == 0)
    				{
    					empty($order_info['apply_fee']) && $order_info['apply_fee'] = $refund;
    				}
    			}
    		}
    	}
    	
    	//代发备忘录
    	$model_ordernote = & m('behalfordernote');
    	$note_info = $model_ordernote->get("{$order_info['order_id']}");
    	 
    	$this->assign('behalfordernote',$note_info);
    	
    	$this->assign('orderlogs',$this->_orderlog_mod->find(array('conditions'=>"order_id={$order_info['order_id']}"))); 
    	$this->assign('goods_info',$goods_info);
    	$this->assign('order_info',$order_info);
    	$this->_import_css_js();
    	$this->display('behalf.goods.search.order.html');
    }
    
    function show_order_detail()
    {
    	$order_id = isset($_GET['order_id']) && $_GET['order_id'] ? trim($_GET['order_id']) : '';
    	$bh_id = $this->visitor->get('has_behalf');
    	
    	if(!$order_id)
    	{
    		$this->json_error('find_fail');
    		return;
    	}
    	
    	$order_info = $this->_order_mod->findAll(array(
    			'conditions'=>"order_alias.order_id = {$order_id}",
    			'join'=>'has_orderextm',
    			'include'=>array('has_goodswarehouse')
    	));
    	$order_info = current($order_info);
    	if($order_info)
    	{
    		$delivery = $this->_delivery_mod->get($order_info['dl_id']);
    		$order_info['dl_name'] = $delivery['dl_name'];
    		$behalf = $this->_behalf_mod->get($order_info['bh_id']);
    		$order_info['bh_name'] = $behalf['bh_name'];
    		$order_info['region_name'] = $this->_remove_China($order_info['region_name']);
    		
    		$orderrefunds = $this->_orderrefund_mod->find(array('conditions'=>"order_id={$order_id}"));
    		if(!empty($orderrefunds))
    		{
    			foreach($orderrefunds as $refund)
    			{
    				if($refund['type'] == 1 && $refund['receiver_id'] == $bh_id && $refund['status'] == 0 && $refund['closed'] == 0)
    				{
    					empty($order_info['refunds']) && $order_info['refunds'] = $refund;
    				}
    				if($refund['type'] == 2 && $refund['sender_id'] == $bh_id && $refund['status'] == 0 && $refund['closed'] == 0)
    				{
    					empty($order_info['apply_fee']) && $order_info['apply_fee'] = $refund;
    				}
    			}
    			
    			$this->assign('refunds',$orderrefunds);
    		}
    	}

    	//代发备忘录
    	$model_ordernote = & m('behalfordernote');
    	$note_info = $model_ordernote->get("{$order_info['order_id']}");
    	
    	$this->assign('behalfordernote',$note_info);
    	
    	$this->assign('orderlogs',$this->_orderlog_mod->find(array('conditions'=>"order_id={$order_info['order_id']}")));
    	$this->assign('order_info',$order_info);
    	$this->_import_css_js();
    	$this->display('behalf.order.detail.show.html');
    }
    
    function check_name()
    {
    	$bh_name = empty($_GET['bh_name']) ? '' : trim($_GET['bh_name']);
    	$bh_id = empty($_GET['bh_id']) ? 0 : intval($_GET['bh_id']);
    
    	if($bh_id == 0){
    		echo ecm_json_encode(true);
    		return;
    	}
    
    	if (!$this->_behalf_mod->unique($bh_name, $bh_id))
    	{
    		echo ecm_json_encode(false);
    		return;
    	}
    	echo ecm_json_encode(true);
    }
    
    
    
    /**
     * 商品仓库货物列表,dataTables,pipe-ajax
     */
    function get_pipe_goods()
    {
    	$bh_id = $this->_get_bh_id();
    	
    	$start = intval($_GET['start']);
    	$page_per = intval($_GET['length']);
    	//search
    	$search = trim($_GET['search']['value']);
    	//order
    	$order_column = $_GET['order']['0']['column'];//那一列排序，从0开始
    	$order_dir = $_GET['order']['0']['dir'];//asc desc 升序或者降序
    	
    	//拼接排序sql
    	$orderSql = "";
    	if(isset($order_column)){
    		$i = intval($order_column);
    		switch($i){
    			case 1:$orderSql = " goods_no ".$order_dir;break;
    			case 3:$orderSql = " goods_name ".$order_dir;break;
    			case 4:$orderSql = " goods_attr_value ".$order_dir;break;
    			case 5:$orderSql = " goods_specification ".$order_dir;break;
    			case 6:$orderSql = " goods_price ".$order_dir;break;
    			case 7:$orderSql = " store_bargin ".$order_dir;break;
    			default:$orderSql = ' taker_time DESC';
    		}
    	}
    	
    	$recordsTotal = 0;
    	$recordsFiltered = 0;
    	$goods_list = array();
    	
    	$goods_list =$this->_goods_warehouse_mod->find(array(
    			'conditions'=>"bh_id='{$bh_id}'",
    			'count'=>true,
    			'order'=>$orderSql." ,order_add_time DESC",
    			'limit'=>"{$start},{$page_per}"
    	));
    	$recordsTotal = $recordsFiltered = $this->_goods_warehouse_mod->getCount();
    	
    	if(strlen($search) > 0)
    	{
    		$goods_list =$this->_goods_warehouse_mod->find(array(
    				'conditions'=>"bh_id='{$bh_id}' AND goods_no like '%".$search."%'",
    				'count'=>true,
    				'order'=>$orderSql." ,order_add_time DESC",
    				'limit'=>"{$start},{$page_per}"
    				
    		));
    		$recordsFiltered = $this->_goods_warehouse_mod->getCount();
    	}
    	
    	echo ecm_json_encode(array('draw'=>intval($_GET['draw']),'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>array_values($goods_list))); 
    }
    
    /**
     * 在某行查看订单详情
     */
    function show_order_details()
    {
    	
    	$order_id = $_POST['id']?intval($_POST['id']):0;
    	$ajax = $_POST['ajax'] ? intval($_POST['ajax']):0;
    	$bh_id = $this->visitor->get('has_behalf');
    	
    	$model_goodsattr =& m('goodsattr');
    	$model_store=& m('store');
    	
    	$orders = $this->_order_mod->findAll(array(
    			'conditions'    => "order_alias.bh_id = ".$bh_id." AND order_alias.order_id=".$order_id,
    			'fields' => 'order_alias.*,orderextm.shipping_fee,orderextm.consignee,orderextm.region_name as consignee_region,orderextm.phone_mob,orderextm.address as consignee_address',
    			'join'          => 'has_orderextm',
    			'include'       =>  array(
    					'has_ordergoods',       //取出商品
    			)));
    	if(empty($orders))
    	{
    		$this->json_error('order is not exist!');
    		return;
    	}
    	// dump($orders);
    	foreach ( $orders as $key1 => $order )
    	{
    		if (! empty ( $order ['order_goods'] ))
    		{
    			$total_quantity = 0;
    			foreach ( $order ['order_goods'] as $key2 => $goods )
    			{
    				$total_quantity += intval($goods['quantity']);
    				empty ( $goods ['goods_image'] ) && $orders [$key1] ['order_goods'] [$key2] ['goods_image'] = Conf::get ( 'default_goods_image' );
    				// //商家编码
    				if (empty ( $goods ['attr_value'] ))
    				{
    					$result = $model_goodsattr->getOne ( "SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$goods['goods_id']} AND attr_id=1" );
    					$orders [$key1] ['order_goods'] [$key2] ['goods_seller_bm'] = $result;
    				}
    				else
    				{
    					$orders [$key1] ['order_goods'] [$key2] ['goods_seller_bm'] = $goods ['attr_value'];
    				}
    				/* $store = $model_store->get ( array (
    						'conditions' => 'store_id=' . $goods ['store_id'],
    						
    				) );
    				if (! empty ( $store ))
    				{
    					//$store = current ( $store );
    					$orders [$key1] ['order_goods'] [$key2] ['tel'] = $store ['tel'];
    					$orders [$key1] ['order_goods'] [$key2] ['im_qq'] = $store ['im_qq'];
    					$orders [$key1] ['order_goods'] [$key2] ['im_ww'] = $store ['im_ww'];
    				} */
    				$orders[$key1]['total_quantity'] = $total_quantity;
    			}
    		}
    		
    		$orderrefunds = $this->_orderrefund_mod->find(array('conditions'=>"order_id={$order_id}"));
    		if(!empty($orderrefunds))
    		{
    			foreach($orderrefunds as $refund)
    			{
    				if($refund['type'] == 1 && $refund['receiver_id'] == $bh_id && $refund['status'] == 0 && $refund['closed'] == 0)
    				{
    					empty($orders [$key1]['refunds']) && $orders [$key1]['refunds'] = $refund;
    				}
    				if($refund['type'] == 2 && $refund['sender_id'] == $bh_id && $refund['status'] == 0 && $refund['closed'] == 0)
    				{
    					empty($orders [$key1]['apply_fee']) && $orders [$key1]['apply_fee'] = $refund;
    				}
    			}
    		}
    		
    	}
    	
    	foreach ( $orders as $key => $value )
    	{
    		$member_info = $this->_get_member_profile ( $value ['buyer_id'] );
    		$orders [$key] ['im_qq'] = $member_info ['im_qq'];
    		$orders [$key] ['im_aliww'] = $member_info ['im_aliww'];
    		$orders [$key] ['delivery_bm'] = $this->_order_mod->get_delivery_bm_bybehalf ( $value ['order_id'] );
    		$orders [$key] ['dl_name'] = $this->_order_mod->get_delivery_bybehalf ( $value ['order_id'], $value ['bh_id'] );
    	}
    	//dump($orders);
    	$order_info = current($orders);
    	$order_info['order_goods'] = array_values($order_info['order_goods']);
    	$order_info['add_time'] = $order_info['add_time'] ? local_date("Y-m-d H:i:s",$order_info['add_time']):'';
    	$order_info['pay_time'] = $order_info['pay_time'] ? local_date("Y-m-d H:i:s",$order_info['pay_time']):'';
    	$order_info['ship_time'] = $order_info['ship_time'] ? local_date("Y-m-d H:i:s",$order_info['ship_time']):'';
    	$order_info['finished_time'] = $order_info['finished_time'] ? local_date("Y-m-d H:i:s",$order_info['finished_time']):'';
    	
    	if($ajax)
    	{
    		$this->json_result($order_info,'success');
    	}
    	else 
    	{
    		$this->assign("order",$order_info);
    		$this->display('behalf.order.details.html');
    	}
    }
    
    /*三级菜单*/
    function _get_member_submenu()
    {
    	$array = array(
    			array(
    					'name' => 'all_orders',
    					'url' => 'index.php?module=behalf&amp;act=order_list&amp;type=all_orders',
    			),
    			array(
    					'name' => 'pending',
    					'url' => 'index.php?module=behalf&amp;act=order_list&amp;type=pending',
    			),
    			array(
    					'name' => 'accepted',
    					'url' => 'index.php?module=behalf&amp;act=order_list&amp;type=accepted',
    			),
    			array(
    					'name' => 'shipped',
    					'url' => 'index.php?module=behalf&amp;act=order_list&amp;type=shipped',
    			),
    			array(
    					'name' => 'finished',
    					'url' => 'index.php?module=behalf&amp;act=order_list&amp;type=finished',
    			),
    			array(
    					'name' => 'canceled',
    					'url' => 'index.php?module=behalf&amp;act=order_list&amp;type=canceled',
    			),
    			array(
    					'name' => 'refund',
    					'url' => 'index.php?module=behalf&amp;act=order_list&amp;type=refund',
    			),
    			array(
    					'name' => 'applyfee',
    					'url' => 'index.php?module=behalf&amp;act=order_list&amp;type=applyfee',
    			),
    			array(
    					'name' => 'refuse',
    					'url' => 'index.php?module=behalf&amp;act=order_list&amp;type=refuse',
    			),
    	);
    	return $array;
    }
    
    
    
    
    
    /**
     * 市场列表
     */
    function _get_markets()
    {
    	$markets = $this->_market_mod->get_list(1);
    	$this->assign("markets",$markets);
    }
    
    /**
     * 获取可用快递
     */
    function _get_related_delivery()
    {
    	$related_delivery=$this->_behalf_mod->getRelatedData('has_delivery',$this->visitor->get('user_id'));
    	$this->assign("related_delivery",$related_delivery);
    }
    
    /**
     * 允许代发设置与否    
     */
    function _allow_behalf_setting($fuc_name)
    {
    	$allowed = false;
    	$behalfs_menu = Conf::get('behalfs_menu');
    	if(!$behalfs_menu)
    	{
    		$this->json_error('not_allow_setting_behalf');
    		return false;
    	}
    	foreach ($behalfs_menu as $menu)
    	{
    		if($fuc_name == $menu)
    			$allowed = true;
    	}
    
    	if(!$allowed)
    	{
    		$this->json_error('not_allow_setting_behalf');
    		return false;
    	}
    
    	return $allowed;
    }
    
    function _curmenu($item)
    {
    	$_member_submenu = $this->_get_member_submenu();
    	foreach ($_member_submenu as $key => $value)
    	{
    		$_member_submenu[$key]['text'] = $value['text'] ? $value['text'] : Lang::get($value['name']);
    	}
    	$this->assign('_member_submenu', $_member_submenu);
    	$this->assign('_curmenu', $item);
    }
    
    
    /**
     * 检测商品编码是否存在
     */
    function check_goodsno(){
    	$goods_no = trim($_POST['goods_no']);
    	$goods_info = $this->_goods_warehouse_mod->get(array(
    			'conditions'=>"goods_no='{$goods_no}'"
    	));
    	if(empty($goods_info))
    	{
    		echo ecm_json_encode(array('valid'=>false,'message'=>Lang::get('goods_unexisted')));
    		return;
    	}
    	$result = $this->_goods_warehouse_mod->get(array(
    			'conditions'=>"goods_no='{$goods_no}' AND ".db_create_in(array(BEHALF_GOODS_UNFORMED,BEHALF_GOODS_PREPARED,BEHALF_GOODS_TOMORROW,BEHALF_GOODS_READY),'goods_status')
    	));
    	if($result)
    	{
    		$order_info = $this->_order_mod->get($result['order_id']);
    		if($order_info['status'] == ORDER_ACCEPTED)
    		{
    			echo ecm_json_encode(array('valid'=>true));
    		}
    		else 
    		{
    			echo ecm_json_encode(array('valid'=>false,'message'=>Lang::get('goods_order_not_accepted')));
    		}
    	}
    	else 
    	{
    		echo ecm_json_encode(array('valid'=>false,'message'=>Lang::get('goods_not_action')));
    	}
    	
    }
    
    /**
     * 用于检测 导航栏商品编码
     */
    function check_header_goodsno()
    {
    	$goods_no = isset($_GET['goods_no']) && $_GET['goods_no'] ? trim($_GET['goods_no']) : '';
    	if(!preg_match('/^\d{14,20}$/', $goods_no))
    	{
    		$this->json_error('find_fail');
    		return;
    	}
    	$goods_info = $this->_goods_warehouse_mod->get(array('conditions'=>"goods_no='{$goods_no}'"));
    	if(!$goods_info)
    	{
    		$this->json_error('find_fail');
    		return;
    	}
    	$this->json_result(1,'success');
    }
    
    /**
     * 左侧导航js
     */
 /*    function left_nav_js()
    {
    	header('Content-Encoding:' . CHARSET);
    	header("Content-Type: application/x-javascript\n");
    	header("Expires: " . date(DATE_RFC822, strtotime("+1 hour")) . "\n");
    	
    	if($this->visitor->get('pass_behalf'))	
    	{	// 导航栏配置文件
    		echo <<<EOT
    		var outlookbar=new outlook();
    		var t;
    		
    		t=outlookbar.addtitle('常用操作','管理首页',1);
    		outlookbar.additem('欢迎页面',t,'index.php?module=behalf&act=defaultmain');
    		outlookbar.additem('生成拿货单',t,'index.php?module=behalf&act=gen_taker_list');
    		outlookbar.additem('出入库管理',t,'index.php?module=behalf&act=manage_goods_warehouse');
    		outlookbar.additem('发货统计',t,'index.php?module=behalf&act=stat_shipped_order');
    		outlookbar.additem('面单打印',t,'index.php?module=behalf&act=mb_print');
    		
    		t=outlookbar.addtitle('基本设置','系统设置',1);
    		outlookbar.additem('查看个人资料',t,'index.php?module=behalf&act=defaultmain&m=look');
    		outlookbar.additem('修改个人资料',t,'index.php?module=behalf&act=set_behalf');
    		//outlookbar.additem('设置可发快递',t,'javascript:;');
    		//outlookbar.additem('设置快递费用',t,'javascript:;');
    		//outlookbar.additem('管理拿货市场',t,'javascript:;');
    		//outlookbar.additem('管理支付方式',t,'javascript:;');
    		
    		t=outlookbar.addtitle('账号管理','系统设置',1);
    		outlookbar.additem('设置面单账号',t,'index.php?module=behalf&act=set_mbaccount');
    		
    		t=outlookbar.addtitle('配货管理','系统设置',1);
    		//outlookbar.additem('设置配货市场',t,'index.php?module=behalf&act=set_markettaker');
    		outlookbar.additem('管理拿货人员',t,'index.php?module=behalf&act=manage_goodstaker');
    		
    		t=outlookbar.addtitle('订单管理','订单管理',1);
    		outlookbar.additem('订单列表',t,'index.php?module=behalf&act=order_list');
    		
    		t=outlookbar.addtitle('配货管理','订单管理',1);
    		outlookbar.additem('生成拿货单',t,'index.php?module=behalf&act=gen_taker_list');
    		outlookbar.additem('管理拿货单',t,'index.php?module=behalf&act=manage_taker_list');
    		outlookbar.additem('出入库管理',t,'index.php?module=behalf&act=manage_goods_warehouse');
    		
    		t=outlookbar.addtitle('订单统计','订单管理',1);
    		outlookbar.additem('发货统计',t,'index.php?module=behalf&act=stat_shipped_order');
    		outlookbar.additem('入库统计',t,'index.php?module=behalf&act=stat_enter_warehouse');
    		
    		t=outlookbar.addtitle('面单打印','打印管理',1);
    		outlookbar.additem('面单打印',t,'index.php?module=behalf&act=mb_print');
    		outlookbar.additem('面单模板',t,'index.php?module=behalf&act=mb_template');
    		
    		t=outlookbar.addtitle('普通打印','打印管理',1);
    		outlookbar.additem('普通打印',t,'index.php?module=behalf&act=common_print');
    		outlookbar.additem('普通模板',t,'index.php?module=behalf&act=common_template');
    		
    		t=outlookbar.addtitle('标签打印','打印管理',1);
    		outlookbar.additem('标签打印',t,'index.php?module=behalf&act=tag_print');
    		
    		t=outlookbar.addtitle('市场管理','其他管理',1);
    		outlookbar.additem('市场列表',t,'index.php?module=behalf&act=market_list');
EOT;
    	}
    	else 
    	{		
    		echo <<<EOT
    		var outlookbar=new outlook();
    		var t;
    				
    		t=outlookbar.addtitle('常用操作','管理首页',1);
    		outlookbar.additem('欢迎页面',t,'index.php?module=behalf&act=defaultmain');
    		outlookbar.additem('入库管理',t,'index.php?module=behalf&act=manage_goods_warehouse');
    		outlookbar.additem('入库统计',t,'index.php?module=behalf&act=stat_enter_warehouse');
EOT;
    	}   
    		
    } */

    /**
     *  重写
     *  要求登录才能访问
     */
    function _run_action()
    {        
        /* 只有登录的用户才可访问 */
        if (!$this->visitor->has_login && in_array(MODULE, array('behalf')))
        {
        
            if (!IS_AJAX)
            {
                header('Location:index.php?app=member&act=login&ret_url=' . rawurlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']));
        
                return;
            }
            else
            {
                $this->json_error('login_please','user_not_login');//user_not_login 在页面知道是用户没登录 by tiq 2015-04-26
                return;
            }
        }
        
        $member_info =ms()->user->_local_get($this->visitor->get('user_id'));
        //echo $this->visitor->get('pass_behalf')."#".$member_info['behalf_goods_taker'];
        /*只有已审核的代发能访问 和 拿货员*/
        if(!$this->visitor->get('pass_behalf') && !$member_info['behalf_goods_taker'])
        {
        	header('Location:index.php?app=member');
        	
        	return;
        }
        
        include_once MODULE_ABSPATH.'/lib/init.lib.php'; 
        
        parent::_run_action();
    }
    
    /**
     * 调整收货地址
     */
    function adjust_consignee()
    {
    	$bh_id = $this->visitor->get('has_behalf');
    	$thisdelivery = $this->_behalf_mod->getRelatedData('has_delivery',$bh_id);
    	
    	if (!IS_POST)
    	{
    		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    		if (!$order_id)
    		{
    			echo Lang::get('no_such_order');
    			return;
    		}
    		header('Content-Type:text/html;charset=' . CHARSET);
    		$consignee  = $this->_orderextm_mod->get(array('conditions' => "order_id={$order_id} "));
    		$this->_import_css_js();
    		$this->assign('regions', $this->_region_mod->get_options(0));
    		$this->assign('consignee', $consignee);
    		$this->assign('deliverys', $thisdelivery);
    		$this->display('behalf.order.adjust_consignee.html');
    	}
    	else
    	{
    		$data = $_POST;
    		$dl_id = isset($_POST['dl_id']) && $_POST['dl_id'] ? intval($_POST['dl_id']) :0;
    		$data['dl_id'] = $dl_id;
    		$dl_name = '';
    		foreach ($data as $key=>$value)
    		{
    			if(empty($value))
    				unset($data[$key]);
    		}
    		foreach ($thisdelivery as $vdelivery)
    		{
    			if($dl_id == $vdelivery['dl_id'])
    				$dl_name = $vdelivery['dl_name'];
    		}
    		$this->_check_region($data);
    		
    		//start transaction
    		$trans = $this->_start_transaction();
    		
    		$affect_rows1 = $this->_orderextm_mod->edit($data['order_id'], $data);
    		$affect_rows1 === false && $trans = false;
    
    		$affect_rows2 = db()->query("UPDATE ".$this->_goods_warehouse_mod->table." SET delivery_id='{$dl_id}', delivery_name='{$dl_name}' WHERE order_id={$data['order_id']}");
    		$affect_rows2 === false && $trans = false;
    		
    		if(!$affect_rows1 && !$affect_rows2) {
    			$trans = false;
    		}
    		    
    		$order_info = $this->_order_mod->get($data['order_id']);
    		
    		
    		$affect_rows = $this->_orderlog_mod->add(array(
		    				'order_id'  => $data['order_id'],
		    				'operator'  => addslashes($this->visitor->get('user_name')),
		    				'order_status' => order_status($order_info['status']),
		    				'changed_status' => order_status($order_info['status']),
		    				'remark'    => Lang::get('adjust_consignee'),
		    				'log_time'  => gmtime(),
		    		));
    	   !$affect_rows && $trans = false;
    	   
    	   $success = $this->_end_transaction($trans);
    
    	   if($success)
    	   {
    	   		$this->pop_warning('ok','behalf_member_adjust_consignee');
    	   }
    	   else
    	   {
    	   		$this->pop_warning('caozuo_fail');
    	   } 
    	}
    
    }
    
    /**
     *    待发货的订单发货
     *
     *    @author    tiq
     *    @return    void
     */
    function shipped()
    {
    	list($order_id, $order_info)    = $this->_get_valid_order_info(array(ORDER_ACCEPTED, ORDER_SHIPPED));
    	if (!$order_id)
    	{
    		$this->pop_warning('caozuo_fail');
    		return;
    	}
    	$behalf_delivery = $this->_orderextm_mod->get($order_info['order_id']);
    
    	//分润
    	$fr_order = $this->_order_mod->findAll(array(
    			'conditions'=>'order_id='.$order_id.' AND status='.ORDER_ACCEPTED,
    			'include'=>array('has_ordergoods')
    	));
    	
    
    	if (!IS_POST)
    	{
	    	/* 显示发货表单 */
	    	header('Content-Type:text/html;charset=' . CHARSET);
    		$thisdelivery = $this->_behalf_mod->getRelatedData('has_delivery',$behalf_delivery['bh_id']);
    		$this->_import_css_js();
    		$this->assign('behalf_delivery',$behalf_delivery);
    		$this->assign("deliverys",$thisdelivery);
    		$this->assign('order', $order_info);
    		$this->display('behalf.order.shipped.html');
    	}
    	else
    	{
	    	if (empty($_POST['invoice_no']))
	    	{
	    		$this->pop_warning('invoice_no_empty');
	    		return;
	    	}
    
    		$edit_data = array('status' => ORDER_SHIPPED, 'invoice_no' => trim($_POST['invoice_no']));
    		$is_edit = true;
    		//开启事务
    		$trans = $this->_start_transaction();
    		$tran_reason = '';
    		
            if (empty($order_info['invoice_no']) || $edit_data['invoice_no'] == $order_info['invoice_no'])
            {
                /*商付通v2.2.1 更新商付通定单状态 开始*/
                if($order_info['payment_code']=='sft' || $order_info['payment_code']=='chinabank' || $order_info['payment_code']=='alipay' || $order_info['payment_code']=='tenpay' || $order_info['payment_code']=='tenpay2')
    			{
               		 $my_moneylog=& m('my_moneylog')->edit('order_id='.$order_id,array('caozuo'=>20));
               		 if(!$my_moneylog) {
               		     $trans = false;//不成功，则回滚
               		     $tran_reason = 'sft_update_fail';
               		 }
                }
                /*商付通v2.2.1  更新商付通定单状态 结束*/
                //不是修改发货单号
                $edit_data['ship_time'] = gmtime();
                $is_edit = false;
    		
    		    		
	            //分润
	            if(!empty($fr_order))
	            {
	                $behalf_discount = 0;
	                if(!empty($fr_order[$order_id]['order_goods']))
	                {
	                	foreach ($fr_order[$order_id]['order_goods'] as $goods)
	                	{//不能缺货
	                		if($goods['oos_value'])
	                		{
	                			$behalf_discount += floatval($goods['behalf_to51_discount']);
	               			}
	                	}
	                }
	                //快递费分润，8块分0.5
	                if($behalf_delivery['shipping_fee'] > 0)
	                {
	              		 $shipping_fee = intval($behalf_delivery['shipping_fee']);
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
	    				$my_money_result=$fakemoneyapp->to_user_withdraw($this->visitor->get('user_id'),FR_USER,$behalf_discount, $fr_reason,$order_id,$fr_order[$order_id]['order_sn']);
	    				if($my_money_result !== true){
	    				    $trans = false;
	    				    $tran_reason = 'behalf_discount_pay_fail';
	    				}
	                }
	    
	             }
             
            }
    
             $affect_rows = $this->_order_mod->edit(intval($order_id), $edit_data);
             if(!$affect_rows){
                 $trans = false;
                 $tran_reason = 'order_update_fail';
             }
             
             //商品仓库更新
             $affect_rows = $this->_goods_warehouse_mod->edit("order_id = '{$order_id}' AND goods_status = '".BEHALF_GOODS_READY."'",array('goods_status'=>BEHALF_GOODS_SEND));
             //!$affect_rows && $trans = false;
           
             if(!empty($_POST['delivery']) && $behalf_delivery['dl_id'] != $_POST['delivery'])
             {
                //如果修改了快递
                $affect_rows = $this->_orderextm_mod->edit($order_id, array('dl_id' => intval($_POST['delivery'])));
                if(!$affect_rows){
                    $trans = false;
                    $tran_reason = 'order_extm_update_fail';
                }
             }
    
             #TODO 发邮件通知
             /*记录订单操作日志 */
             //$order_log =& m('orderlog');
             $affect_rows = $this->_orderlog_mod->add(array(
			                	'order_id'  => $order_id,
			                	'operator'  => addslashes($this->visitor->get('user_name')),
			                    'order_status' => order_status($order_info['status']),
			                    'changed_status' => order_status(ORDER_SHIPPED),
			                    'remark'    => $is_edit ? Lang::get('edit_invoice_no'). $_POST['remark']:Lang::get('shipped_order'),
			                    'log_time'  => gmtime(), 
			                ));
    		if(!$affect_rows){
    		    $trans = false;
    		    $tran_reason = 'order_log_add_fail';
    		}
    		
    		$this->_end_transaction($trans);
    		
    		/* 如果匹配到的话，修改第三方订单状态 */
    		if($trans)
    		{
    			$ordervendor_mod = &m('ordervendor');
    			$ordervendor_mod->edit("ecm_order_id={$order_id}", array(
    					'status' => VENDOR_ORDER_SHIPPED,
    			));
    			
    			/* 发送给买家订单已发货通知 */
    			$buyer_info   = ms()->user->_local_get($order_info['buyer_id']);
    			$order_info['invoice_no'] = $edit_data['invoice_no'];
    			$mail = get_mail('tobuyer_shipped_notify', array('order' => $order_info));
    			if($buyer_info['phone_mob'])
    			{
    				$com = $this->_order_mod->get_delivery_bybehalf($order_info['order_id'],$order_info['bh_id']);
    				$order_info['dl_name'] = $com;
    				$order_info['consignee'] = $behalf_delivery['consignee'];
    				$smail = get_mail('sms_order_notify', array('order' => $order_info));
    				$this->sendSaleSms($buyer_info['phone_mob'],  addslashes($smail['message']));
    			}
    			
    			$this->pop_warning('ok','behalf_order_shipped');
    		}
    		else 
    		{
    			$this->pop_warning($tran_reason);
    		}
        }
    }
    
    /**
     * 调整订单费用
     */
    function adjust_fee()
    {
    	list($order_id, $order_info)    = $this->_get_valid_order_info(array(ORDER_SUBMITTED, ORDER_PENDING));
    	
    	if (!$order_id)
    	{
    		echo Lang::get('no_such_order');    
    		return;
    	}
    	//$model_order    =&  m('order');
    	//$model_orderextm =& m('orderextm');
    	//$model_delivery = & m('delivery');
    	$shipping_info   = $this->_orderextm_mod->get($order_id);
    	if (!IS_POST)
    	{
    		header('Content-Type:text/html;charset=' . CHARSET);
    		$this->_import_css_js();
    		$this->assign('order', $order_info);
    		$this->assign('shipping', $shipping_info);
    		$this->display('behalf.order.adjust_fee.html');
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
    
    		//开启事务
    		$trans = $this->_start_transaction();
    		
    		if ($shipping_fee != $shipping_info['shipping_fee'])
    		{
    			/* 若运费有变，则修改运费 */
    			$affect_row = $this->_orderextm_mod->edit($order_id, array('shipping_fee' => $shipping_fee));
    			!$affect_row && $trans = false;
    		}
    		$affect_row = $this->_order_mod->edit($order_id, $data);
    		!$affect_row && $trans = false;
    
    		/* if ($model_order->has_error())
    		{
    			$this->pop_warning($model_order->get_error());
    
    			return;
    		} */
    		/* 记录订单操作日志 */
    		//$order_log =& m('orderlog');
    		$affect_row = $this->_orderlog_mod->add(array(
    				'order_id'  => $order_id,
    				'operator'  => addslashes($this->visitor->get('user_name')),
    				'order_status' => order_status($order_info['status']),
    				'changed_status' => order_status($order_info['status']),
    				'remark'    => Lang::get('adjust_fee'),
    				'log_time'  => gmtime(),
    		));
    		!$affect_row && $trans = false;
    		
    		$this->_end_transaction($trans);
    		if($trans){
    			$this->pop_warning('ok','behalf_member_adjust_fee');
    		}
    		else{
    			$this->pop_warning('caozuo_fail');
    		}
    		
    	}
    }
    /**
     * 申请补邮
     */
    function apply_fee()
    {
    	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    	$bh_id = $this->visitor->get('has_behalf');
    	if (!$order_id)
    	{
    		echo Lang::get('no_such_order');
    		return;
    	}    	
    	
    	/* 只有已付款,已发货,已完成的订单可以申请补邮 */
    	$order_info     = $this->_order_mod->get("order_id={$order_id} AND bh_id={$bh_id} AND status " . db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED)));
    
    	if (empty($order_info))
    	{
    		echo Lang::get('no_such_order');
    		return;
    	}
    	if (!IS_POST)
    	{
    		header('Content-Type:text/html;charset=' . CHARSET);
    		$refund_info = $this->_orderrefund_mod->get("order_id='{$order_id}' AND sender_id='{$bh_id}' AND receiver_id='{$order_info['buyer_id']}' AND status='0' AND type='2' AND closed='0'");
     		if($refund_info)
     		{
     			echo Lang::get('exist_orderrefund');
     			return;
     		}
     		$this->_import_css_js();
    		$this->assign('order', $order_info);
    		$this->display('behalf.order.apply_fee.html');
    	}
    	else
    	{
    		//status 0:申请，1：已同意，2：已拒绝  closed 0:未关闭 1：已关闭
    		$refund_result=$this->_orderrefund_mod->get("order_id={$order_id} AND receiver_id={$order_info['buyer_id']} AND type=2 AND status=0 AND closed=0");
    		if(!empty($refund_result))
    		{
    			$this->pop_warning(Lang::get('exist_apply'));
    			return ;
    		} 
    
    		$refund_amount = isset($_POST['refund_amount'])?floatval(trim($_POST['refund_amount'])):0;
    		if($refund_amount > 100 || $refund_amount <= 0)
    		{
    			echo Lang::get('apply_fee_incorrect');
    			return;
    		}
    		if(empty($_POST['apply_fee_reason']))
    		{
    			echo Lang::get('fill_apply_fee_reason');
    			return;
    		}
    		
    		$data=array(
    				'order_id'=>$order_info['order_id'],
    				'order_sn'=>$order_info['order_sn'],
    				'sender_id'=>$this->visitor->get('user_id'),
    				'sender_name'=>$this->visitor->get('user_name'),
    				'receiver_id'=>$order_info['buyer_id'],
    				'receiver_name'=>$order_info['buyer_name'],
    				'refund_reason'=>html_filter($_POST['apply_fee_reason']),
    				'refund_intro'=>html_filter($_POST['refund_intro']),
    				'apply_amount'=>$refund_amount,
    				'refund_amount'=>0,
    				'create_time'=>gmtime(),
    				'pay_time'=>0,
    				'status'=>0,
    				'closed'=>0,
    				'type'=>2,//代发申请补邮
    		);
    		
    		if(!$data['refund_reason'] || !$data['refund_intro'])
    		{
    			echo Lang::get('refund_reason_intro_unexist');
    			return;
    		}
    		
    		$affect_rows = $this->_orderrefund_mod->add($data);
    		
    		
    		if($affect_rows)
    		{
    			$refund_message = Lang::get('apply_fee_message').$order_info['order_sn'];
    			/* 连接用户系统 */
    			$ms =& ms();
    			$msg_id = $ms->pm->send($bh_id, array($order_info['buyer_id']), '', $refund_message);
    			
    			/* 发送给买家订单补收差价通知 */
    			$seller_info   = $ms->user->_local_get($order_info['buyer_id']);
    			$this->sendSaleSms($seller_info['phone_mob'], $refund_message);
    			
    			$this->pop_warning('ok');
    		}
    		else 
    		{
    			$this->pop_warning('caozuo_fail');
    		}
    
    	}
    
    }
    
    /**
     * 查看补差
     */
    function apply_fee_look()
    {
    	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    	$bh_id = $this->visitor->get('has_behalf');
    	if (!$order_id)
    	{
    		echo Lang::get('no_such_order');
    		return;
    	}
    	
    	header('Content-Type:text/html;charset=' . CHARSET);
    	$refund_info = $this->_orderrefund_mod->get("order_id='{$order_id}' AND sender_id='{$bh_id}'  AND status='0' AND type='2' AND closed='0'");
    	
    	$this->_import_css_js();
    	$this->assign('refund', $refund_info);
    	$this->display('behalf.order.apply_fee.look.html');
    	
    }
    
    /**
     * 卖家留言
     */
    function sell_message()
    {
    	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    	if (!$order_id)
    	{
    		echo Lang::get('no_such_order');
    		return;
    	}
    	
    	$order_info = $this->_order_mod->get("order_id={$order_id} ");
    	if (!IS_POST)
    	{
    		header('Content-Type:text/html;charset=' . CHARSET);
    		$this->assign('order', $order_info);
    		$this->display('behalf.order.seller_message.html');
    	}
    	else
    	{
    		/* 卖家留言*/
    		$seller_message = isset($_POST['seller_message']) ? trim($_POST['seller_message']) : '';
    		/* 标志*/
    		if(!empty($seller_message)) $seller_message_flag=2;
    		else $seller_message_flag=0;
    
    		$data = array(
    				'seller_message'  => html_filter($seller_message),
    				'seller_message_flag'  => $seller_message_flag,
    		);
    
    		$this->_order_mod->edit($order_id, $data);
    
    		if ($this->_order_mod->has_error())
    		{
    			$this->json_error($this->_order_mod->get_error());
    
    			return;
    		}
    
    		$this->json_result('ok','behalf_member_seller_message');
    	}
    }
    
    /**
     * 显示代发备忘录
     */
    function show_ordernote()
    {
    	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    	if (!$order_id)
    	{
    		echo Lang::get('no_such_order');
    		return;
    	}
    	$model_ordernote = & m('behalfordernote');
    	$order_info = $model_ordernote->get("order_id={$order_id} ");
    	if (!IS_POST)
    	{
    		header('Content-Type:text/html;charset=' . CHARSET);
    		$this->assign('ordernote', $order_info);
    		$this->assign('order_id',$order_id);
    		$this->display('behalf.order.ordernote.html');
    	}
    }
    
    /**
     *    取消订单
     *
     *    @author    tiq
     *    @return    void
     */
    function cancel_order()
    {
    	/* 取消的和完成的订单不能再取消 */
    	$order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
    	$bh_id = $this->visitor->get('has_behalf');
    	if (!$order_id)
    	{
    		echo Lang::get('no_such_order');
    		return;
    	}
    	
    	$exist_order_refunds = $this->_exist_order_refunds($bh_id,$order_id);
        if($exist_order_refunds)
        {
            echo Lang::get('exist_order_refunds');
            return;
        }
    	
    	$status = array(ORDER_SUBMITTED, ORDER_PENDING, ORDER_ACCEPTED, ORDER_SHIPPED);
    	
    	/* 只有已发货的货到付款订单可以收货 */
    	$order_info     = $this->_order_mod->get("order_id={$order_id} AND bh_id={$bh_id} AND status " . db_create_in($status));
    	
    	if (!$order_info)
    	{
    		echo Lang::get('no_such_order');
    		return;
    	}
    	if (!IS_POST)
    	{
    		header('Content-Type:text/html;charset=' . CHARSET);
    		$this->_import_css_js();
    		$this->assign('order', $order_info);
    		$this->display('behalf.order.cancel.html');
    	}
    	else
    	{
    		//开启事务
    		$success = $this->_start_transaction();
    		
    		$id = $order_info['order_id'];
    		
    		$affect_rows =	$this->_order_mod->edit($order_info['order_id'], array('status' => ORDER_CANCELED));
    		!$affect_rows && $success = false;//回滚
    
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
    				$buy_money_dj=$buy_money_row['money_dj'];//买家的钱
    
    				$sell_money_row=$my_money_mod->getrow("select * from ".DB_PREFIX."my_money where user_id='$sell_user_id'");
    				$sell_money=$sell_money_row['money'];//卖家的冻结资金
    				$sell_money_dj=$sell_money_row['money_dj'];//卖家的冻结资金
    
    				$new_buy_money = $buy_money+$money;
    				$new_sell_money = $sell_money_dj-$money;
    				//更新数据
    				$affect_rows = $my_money_mod->edit('user_id='.$buy_user_id,array('money'=>$new_buy_money));
    				!$affect_rows && $success = false;//回滚
    				$affect_rows = $my_money_mod->edit('user_id='.$sell_user_id,array('money_dj'=>$new_sell_money));
    				!$affect_rows && $success = false;//回滚
    				//更新商付通log为 定单已取消
    				$change_buyer = array('caozuo'=>30, 'admin_time' => gmtime(), 'moneyleft' => $new_buy_money + $buy_money_dj);
    				$change_seller = array('caozuo'=>30, 'admin_time' => gmtime(), 'moneyleft' => $sell_money + $new_sell_money);
    				//                    $my_moneylog_mod->edit('order_id='.$id,array('caozuo'=>30));
    				Log::write('dualven:behalf:'.var_export($change_buyer,true));
    				$affect_rows = $my_moneylog_mod->edit('order_id='.$id.' and user_id='.$buy_user_id, $change_buyer);
    				!$affect_rows && $success = false;//回滚
    				$affect_rows = $my_moneylog_mod->edit('order_id='.$id.' and user_id='.$sell_user_id, $change_seller);
    				!$affect_rows && $success = false;//回滚
    			}
    			/*商付通v2.2.1  更新商付通定单状态 结束*/
    			
    			//退还分润,必须是已发货或已完成，且退货有商品
    			if(in_array($order_info['status'], array(ORDER_SHIPPED,ORDER_FINISHED)))
    			{
    				$refund_results=$this->_orderrefund_mod->find(array(
    						'conditions'=>"order_id={$id} AND sender_id={$order_info['buyer_id']} AND receiver_id={$bh_id} AND status=0 AND closed=0 AND type=1"
    				));
    				if(count($refund_results) == 1)
    				{
    					$refund_result = current($refund_results);
    					if($refund_result['goods_ids'])
    					{
    						//计算返款
    						$rec_ids = explode(',', $refund_result['goods_ids']);
    						$rec_goods = $this->_ordergoods_mod->find(array(
    								'conditions'=> "order_id={$id} AND ".db_create_in($rec_ids,'rec_id')
    						));
    						$behalf_discount = 0;
    						if($rec_goods)
    						{
    							foreach ($rec_goods as $goods)
    							{
    								if($goods['oos_value'] && $goods['behalf_to51_discount'] > 0)
    								{
    									$behalf_discount += $goods['behalf_to51_discount'];
    									$affect_rows = $this->_ordergoods_mod->edit($goods['rec_id'],array('zwd51_tobehalf_discount'=>$goods['behalf_to51_discount']));
    									!$affect_rows && $success = false;//回滚
    								}
    							}
    						}
    
    						if($behalf_discount > 0)
    						{
    							include_once(ROOT_PATH.'/app/fakemoney.app.php');
    							$fakemoneyapp = new FakeMoneyApp();
    							$fr_reason = Lang::get('behalf_to_51_tk_reason').local_date('Y-m-d H:i:s',gmtime());
    							//给用户转账
    							$my_money_result=$fakemoneyapp->to_user_withdraw(FR_USER,$bh_id,$behalf_discount, $fr_reason,$order_info['order_id'],$order_info['order_sn']);
    							$my_money_result !== true && $success = false; //回滚
    						}
    					}
    				}
    			}
    
    			/* 加回订单商品库存 */
    			$cancel_reason = (!empty($_POST['remark'])) ? $_POST['remark'] : $_POST['cancel_reason'];
    			$cancel_reason .= " ".Lang::get('order_sn').":".$order_info['order_sn'].";".Lang::get('reback_money_success');
    			/* 记录订单操作日志 */
    			$affect_rows = $this->_orderlog_mod->add(array(
	    					'order_id'  => $id,
	    					'operator'  => addslashes($this->visitor->get('user_name')),
	    					'order_status' => order_status($order_info['status']),
	    					'changed_status' => order_status(ORDER_CANCELED),
	    					'remark'    => $cancel_reason,
	    					'log_time'  => gmtime(),
	    		));
    			!$affect_rows && $success = false;//回滚
    			//提交或回滚
    			$this->_end_transaction($success);
    			
    			if($success)
    			{
	    			/* 连接用户系统 */
	    			//$ms =& ms();
	    			//$buyer_info   = $ms->user->_local_get($order_info['buyer_id']);
	    			//$msg_id = $ms->pm->send($this->visitor->get('user_id'), array($order_info[$id]['buyer_id']), '', Lang::get('order_cancel_notice').$cancel_reason);
	    			/*短信通知*/
	    			//$this->sendSaleSms($buyer_info['phone_mob'], Lang::get('order_cancel_notice').$cancel_reason);
	    
	    			/* 如果是关联到淘宝订单的话, 需要同时修改淘宝订单的状态 */
	    			$ordervendor_mod = &m('ordervendor');
	    			$ordervendor_mod->edit("ecm_order_id={$id}", array(
	    					'status' => VENDOR_ORDER_UNHANDLED,
	    					'ecm_order_id' => 0));
	    		
	    			$this->pop_warning('ok', 'behalf_member_cancel_order');
    			}
    			else 
    			{
    				$this->pop_warning('caozuo_fail');
    			}
    	} //end post else
    
    }
    /**
     * 订单是否存在退款申请
     */
    private function _exist_order_refunds($bh_id,$order_id)
    {
        $exist_refunds = $this->_orderrefund_mod->get("receiver_id='{$bh_id}' AND order_id='{$order_id}' AND type='1' AND closed='0' AND status='0'");
        return empty($exist_refunds)?false:true;
    }

    

    /**
     * 处理退货退款请求
     */
    function applied_refund()
    {
    	//2015-11-22 暂停关闭，存在并发性能问题
    	 $bh_id = $this->visitor->get('has_behalf');
    	//利用php文件锁解决并发问题
    	$lock_file = ROOT_PATH."/data/applied_refund.lock";
    	if(!file_exists($lock_file))
    	{
    		file_put_contents($lock_file, 1);
    	}
    
    	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    	if (!$order_id)
    	{
    		echo Lang::get('no_such_order');
    		return;
    	}
    	//$model_order    =&  m('order');
    	//$model_ordergoods =& m('ordergoods');
    	//$model_orderrefund = & m('orderrefund');
    	//对文件加锁
    	$fp = fopen($lock_file, 'a+');
    	if(!$fp)
    	{
    		echo 'fail to open lock file!';
    		return;
    	}
    	flock($fp, LOCK_EX);
    
    	/* 只有已付款和已经发货、已完成的订单可以申请退货退款 */
    	$order_info     = $this->_order_mod->get("order_id={$order_id}  AND bh_id={$bh_id} AND status " . db_create_in(array(ORDER_ACCEPTED, ORDER_SHIPPED,ORDER_FINISHED)));
    	$order_info_status = $order_info['status'];//记录订单变化状态
    	if(!empty($order_info))
    	{
    		$refund_results=$this->_orderrefund_mod->find(array(
    				'conditions'=>'order_id='.$order_info['order_id'].' AND sender_id='.$order_info['buyer_id'].' AND receiver_id='.$order_info['bh_id']." AND status='0' AND closed='0' AND type='1'",
    		));
    	}
    	else
    	{
    		$refund_results = array();
    	}
    
    	/*文件解锁*/
    	flock($fp, LOCK_UN);
    	fclose($fp);
    
    
    	if(count($refund_results) != 1)
    	{
    		echo count($refund_results)>1?Lang::get('feifashenqi_1'):Lang::get('feifashenqi_0');
    		return ;
    	}
    	$refund_result = array();
    	foreach ($refund_results as $value)
    	{
    		if($value['status'] == 0 && $value['closed'] == 0)
    			$refund_result = $value;
    	}
    	if(!$refund_result || $refund_result['apply_amount'] <= 0)
    	{
    		echo Lang::get('feifashenqi_3');
    		return ;
    	}
    	if (!IS_POST)
    	{
    		header('Content-Type:text/html;charset=' . CHARSET);
    		$this->_import_css_js();
    		$this->assign('order', $order_info);
    		$this->assign('refund',$refund_result);
    		$this->assign("show_refund",count($refund_results) > 1 ?false:true);
    		$this->display('behalf.order.applied_refund.html');
    	}
    	else
    	{
    		$refund_agree = isset($_POST['agree'])?intval(trim($_POST['agree'])):0;
    		$zf_pass = isset($_POST['zf_pass'])?trim($_POST['zf_pass']):'';
    		if(!in_array($refund_agree, array(1,2)))
    		{
    			//$this->pop_warning("feifacaozuo");
    			echo Lang::get("feifacaozuo");
    		    return;
    		}
    		if(empty($zf_pass) && $refund_agree==1)
    		{
    			//$this->pop_warning("passwd_again");
    			echo Lang::get("passwd_again");
    			return;
    		}
    		//$refund_result = $refund_result;
    
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
    		//同意退款转账
    		if($refund_agree == 1)
    		{
    		    /*for debug*/
    		    //echo 'start<br>';
    		    
    			$data=array(
    					'order_id'=>$order_info['order_id'],
    					'order_sn'=>$order_info['order_sn'],
    					'refund_amount'=>$refund_result['apply_amount'],
    					'pay_time'=>gmtime(),
    					'status'=>$refund_agree,
    					'closed'=>0
    			);
    			$refund_message = Lang::get('refund_message').$order_info['order_sn'].','.$refund_result['apply_amount'];
    			 
    			$affect_rows = $this->_orderrefund_mod->edit($refund_result['id'],$data);
    			if(!$affect_rows){
    			    $db_transaction_success = false;
    			    $db_transaction_reason = 'update_refund_failed';
    			}
    			//退货统计
    			if($refund_result['goods_ids'] && $refund_result['goods_ids_flag'])
    			{
    			    $whgoods_ids = explode(',', $refund_result['goods_ids']);
    			    $whgoods_ids = array_unique($whgoods_ids);
    			    $whgoods_ids = array_filter($whgoods_ids);
    			    
    			    $reback_goods = $this->_goods_warehouse_mod->find(array('conditions'=>db_create_in($whgoods_ids,'id')));
    			    $reback_goods_ids = array();//允许重复goods_id
    			    if($reback_goods)
    			    {    foreach ($reback_goods as $rbgoods)
        			    {
        			        $reback_goods_ids[] = $rbgoods['goods_id'];
        			    }
    			    }
    			}
    			
    			//退货则标记为退货状态,主要是考虑 待发货的 订单，不用返回 分润，因为发货时才分润
    			if($refund_result['goods_ids'] && $refund_result['goods_ids_flag'] && $order_info['status'] == ORDER_ACCEPTED)
    			{
    			    
    			    $affect_rows = $this->_goods_warehouse_mod->edit(db_create_in($whgoods_ids,'id'),array('goods_status'=>BEHALF_GOODS_REBACK));
    			    if(!$affect_rows){
    			        $db_transaction_success = false;
    			        $db_transaction_reason = 'update_goodswarecase_fail';
    			    }
    			    //统计退货
    			    if($reback_goods_ids)
    			    {
    			        foreach ($reback_goods_ids as $rbgoods_id)
    			        {
    			            $goods_statistics = $this->_goods_statistics_mod->get($rbgoods_id);
    			            if($goods_statistics)
    			            {
    			                $this->_goods_statistics_mod->edit("{$rbgoods_id}",'backs=backs+1');
    			            }
    			            else
    			            {
    			                $this->_goods_statistics_mod->add(array('goods_id'=>$rbgoods_id,'backs'=>1));
    			            }
    			        }
    			    }
    			}
    			
    			
    			    			
    			include_once(ROOT_PATH.'/app/fakemoney.app.php');
    			$fakemoneyapp = new FakeMoneyApp();
    			 
    			//退还分润,必须是已发货或已完成，且退货有商品
    			if(in_array($order_info['status'], array(ORDER_SHIPPED,ORDER_FINISHED)) && $refund_result['goods_ids'])
    			{
    				//计算返款
    				$rec_ids = explode(',', $refund_result['goods_ids']);  
    				$rec_ids = array_unique($rec_ids);
    				
    				$behalf_discount = 0;
    				
    				if(!$refund_result['goods_ids_flag'])//如果是goods_id
    				{
        				$rec_goods = $this->_ordergoods_mod->find(array(
        				    'conditions'=> 'order_id='.$order_id.' AND '.db_create_in($rec_ids,'rec_id'),
        				));
        				if($rec_goods)
        				{
        					foreach ($rec_goods as $goods)
        					{
        						//商品仓库退货标记,如果只退 同一规格的 一件，如果同一订单有多件，则都会 标记！需要 修正
        						$affect_rows = $this->_goods_warehouse_mod->edit("order_id ='{$goods['order_id']}' AND goods_id='{$goods['goods_id']}' AND goods_spec_id='{$goods['spec_id']}' ",array('goods_status'=>BEHALF_GOODS_REBACK));
        						if(!$affect_rows){
        						    $db_transaction_success = false;
        						    $db_transaction_reason = 'update_goodswarecase_fail';
        						}
        						
        						if($goods['oos_value'] && $goods['behalf_to51_discount'] > 0)
        						{
        							$behalf_discount += $goods['behalf_to51_discount'];
        							$affect_rows = $this->_ordergoods_mod->edit($goods['rec_id'],array('zwd51_tobehalf_discount'=>$goods['behalf_to51_discount']));
        							if(!$affect_rows){
        							    $db_transaction_success = false;
        							    $db_transaction_reason = 'update_ordergoods_fail';
        							}
        							
        						}
        					}
        				}
    				}
    				else //如果是goods_warehouse的id 
    				{

    				    $warehouse_goods = $this->_goods_warehouse_mod->find(array(
    				        'conditions'=> 'order_id='.$order_id.' AND '.db_create_in($rec_ids,'id'),
    				    ));
    				    if($warehouse_goods)
    				    {
    				        foreach ($warehouse_goods as $whgoods)
    				        {
    				            //商品仓库退货标记,如果只退 同一规格的 一件，如果同一订单有多件，则都会 标记！需要 修正
    				            $affect_rows = $this->_goods_warehouse_mod->edit("id ='{$whgoods['id']}' ",array('goods_status'=>BEHALF_GOODS_REBACK,'refund_id'=>$refund_result['id']));
    				            if(!$affect_rows){
    				                $db_transaction_success = false;
    				                $db_transaction_reason = 'update_goodswarecase_fail';
    				            }
    				    
    				            if($whgoods['goods_status'] == BEHALF_GOODS_SEND && $whgoods['behalf_to51_discount'] > 0)
    				            {
    				                $behalf_discount += $whgoods['behalf_to51_discount'];
    				                $affect_rows = $this->_goods_warehouse_mod->edit($whgoods['id'],array('zwd51_tobehalf_discount'=>$whgoods['behalf_to51_discount']));
    				                if(!$affect_rows){
    				                    $db_transaction_success = false;
    				                    $db_transaction_reason = 'update_ordergoods_fail';
    				                }
    				                 
    				            }
    				        }
    				    }
    				    //统计退货
    				    if($reback_goods_ids)
    				    {
    				        foreach ($reback_goods_ids as $rbgoods_id)
    				        {
    				            $goods_statistics = $this->_goods_statistics_mod->get($rbgoods_id);
    				            if($goods_statistics)
    				            {
    				                $this->_goods_statistics_mod->edit("{$rbgoods_id}",'backs=backs+1');
    				            }
    				            else
    				            {
    				                $this->_goods_statistics_mod->add(array('goods_id'=>$rbgoods_id,'backs'=>1));
    				            }
    				        }
    				    }
    				    
    				}
    				
    				
    				if($behalf_discount > 0)
    				{
    					$fr_reason = Lang::get('behalf_to_51_tk_reason').local_date('Y-m-d H:i:s',gmtime());
    					//给用户转账
    					$my_money_result=$fakemoneyapp->to_user_withdraw(FR_USER,$this->visitor->get('user_id'),$behalf_discount, $fr_reason,$order_info['order_id'],$order_info['order_sn']);
    					if($my_money_result !== true)
    					{
    						//echo "fenrun reback! <br>";
    						$db_transaction_success = false;
    						$db_transaction_reason = 'fr_to_user_withdraw_fail';
    
    					}
    				}
    			}
    			/*for debug*/
                //echo 'fr_over#';
    
    			include_once(ROOT_PATH.'/app/my_money.app.php');
    			$my_moneyapp = new My_moneyApp();
    
    			//给用户转账
    			$my_money_result=$my_moneyapp->to_user_withdraw($order_info['buyer_name'],$refund_result['apply_amount'], $order_id,$order_info['order_sn'],$zf_pass);
    			if($my_money_result !== true)
    			{
    				//echo "pay user.<br>";
    				$db_transaction_success = false;
    				$db_transaction_reason = 'to_user_withdraw_fail';
    				
    			}
    			/*for debug*/
                //echo 'zz_over#';
    			//全额退款时，才解冻订单全部资金，自动关闭订单。未发货=订单总价，已发货=订单商品价格
    			if($refund_result['apply_amount'] == $order_info['goods_amount'] || $refund_result['apply_amount'] == $order_info['order_amount'] )
    			{
    				if($order_info['status'] != ORDER_FINISHED)
    				{
    					
    					//这是相当于收货了，订单资金解冻
    					$my_money_result=$my_moneyapp->jd_behalf_refund($this->visitor->get('user_id'),$order_info['order_amount'], $order_info['order_sn']);
    					
    					if($my_money_result !== true)
    					{
    						//echo "jd money.<br>";
    						$db_transaction_success = false;
    						$db_transaction_reason = "jd_failed";
    					}
    				}
                   
    				$affect_rows = $this->_order_mod->edit($order_info['order_id'], array('status' => ORDER_CANCELED));
    				if (empty($affect_rows))
    				{
    					//echo "cancel order.<br>";
    					$db_transaction_success = false;
    					$db_transaction_reason = 'update_order_status_fail';
    				}
    
    				//商付通 更新状态
    				$my_moneylog_mod =& m('my_moneylog');
    				$affect_rows = $my_moneylog_mod->edit('order_id='.$order_info['order_id'],array('caozuo'=>80));
    				if(!$affect_rows){
    				    $db_transaction_success = false;
    				    $db_transaction_reason = 'update_moneylog_fail';
    				}
    				//商付通 结束
    				$order_info_status = ORDER_CANCELED;
    			}
    			/*for debug*/
                //echo 'tk_over#';
    			//这是已完成订单申请的退款，前面手动冻结，现在解冻
    			if($order_info['status'] == ORDER_FINISHED)
    			{
    				$affect_result	= $fakemoneyapp->manuRefro($order_info['bh_id'], $refund_result['apply_amount']);
    				if($affect_result === false)
    				{
    					//$db_transaction_success = false;
    					//$db_transaction_reason = 'jd_failed';
    				}
    			}
    
    
    		}
           
    		if($refund_agree == 2)
    		{
    			$data=array(
    					'order_id'=>$order_info['order_id'],
    					'order_sn'=>$order_info['order_sn'],
    					'refuse_reason'=>html_filter(trim($_POST['refuse_reason'])),
    					'status'=>$refund_agree,
    					'closed'=>isset($_POST['reapplay_refund'])&& !empty($_POST['reapplay_refund'])?1:0
    			);
    			$refund_message = Lang::get('refund_message_disagree').$order_info['order_sn'].','.$refund_result['apply_amount'];
    
    			$affect_rows = $this->_orderrefund_mod->edit($refund_result['id'],$data);
    			if(empty($affect_rows))
    			{
    				//echo "refuse requent.<br>";
    				$db_transaction_success = false;
    				$db_transaction_reason = 'refuse_update_refund_fail';
    				 
    				
    			}
    		}
    
    		
    	 $affect_rows = $this->_orderlog_mod->add(array(
    				'order_id'  => $order_info['order_id'],
    				'operator'  => addslashes($this->visitor->get('user_name')),
    				'order_status' => order_status($order_info['status']),
    				'changed_status' => order_status($order_info_status),
    				'remark'    => $refund_message,
    				'log_time'  => gmtime(),
    		));
    	 
    	 if(!$affect_rows){
    	     $db_transaction_success = false;
    	     $db_transaction_reason = 'add_orderlog_fail';
    	 }
    	 /*for debug*/
    	 //echo 'endlog';
    	 /*for debug*/
    	 //$db_transaction_success = false;
    	 
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
    	 	$msg_id = $ms->pm->send($this->visitor->get('user_id'), array($order_info['buyer_id']), '', $refund_message);
    	 	
    	 	/* 如果是关联到第三方订单的话, 需要同时修改淘宝订单的状态 */
    	 	$ordervendor_mod = &m('ordervendor');
    	 	$ordervendor_mod->edit("ecm_order_id=".$order_info['order_id'], array(
    	 			'status' => VENDOR_ORDER_UNHANDLED,
    	 			'ecm_order_id' => 0));
    	 	
    	 	echo Lang::get('refund_ok');
    	 	return;
    	 }
    	 else 
    	 {
    	 	echo Lang::get($db_transaction_reason) ;
    	 	return ;
    	 }
    	 
    	echo 'end';
    		
    	}
    }
    
    
    
    /**
     * @param data
     */
    private function _check_region($data)
    {
    	$regionArr = $this->_region_mod->get_layer($data['region_id']);
    	$region_name ='';
    
    	if(!$data['region_id'])
    	{
    		$this->pop_warning('region_illeage');
    		return;
    	}
    	if(!$this->_region_mod->isleaf($data['region_id']))
    	{
    		$this->pop_warning('region_illeage');
    		return;
    	}
    	foreach ($regionArr as $region)
    	{
    		if(strpos($data['region_name'],$region['region_name'])===false)
    		{
    			$this->pop_warning($region['region_name']);
    			return;
    		}
    		$region_name .= $region['region_name'].' ';
    	}
    	if(!preg_match('/^1[34578][0-9]{9}$/',$data['phone_mob']))
    	{
    		$this->pop_warning('phone_illeage');
    		return;
    	}
    	if(!empty($data['zipcode']))
    	{
    		if(!preg_match('/\d{6}/',$data['zipcode']))
    		{
    			$this->pop_warning('zipcode is error!');
    			return;
    		}
    	}
    
    
    	$data['region_name'] = $region_name;
    	return $data;
    }
    
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
    	
    	/* 只有已发货的货到付款订单可以收货 */
    	$order_info     = $this->_order_mod->get(array(
    			'conditions'    => "order_id={$order_id} " . " AND status " . db_create_in($status) . $ext,
    	));
    	if (empty($order_info))
    	{
    		return array();
    	}
    
    	return array($order_id, $order_info);
    }
    
    /**
     * 代发备忘录
     */
    function save_ordernote()
    {
    	$order_id = isset($_GET['order_id']) && $_GET['order_id'] ? trim($_GET['order_id']) :0;
    	$content = trim($_POST['content']);
    	if(!$order_id || empty($content))
    	{
    		$this->json_error('caozuo_fail');
    		return;
    	}
    	$model_ordernote = & m('behalfordernote');
    	$note_info = $model_ordernote->get($order_id);
    	if(empty($note_info))
    	{
    		$affect_rows = $model_ordernote->add(array('order_id'=>$order_id,'content'=>html_filter($content),'create_time'=>gmtime(),'login_id'=>$this->visitor->get('user_id')));
    		if($affect_rows)
    		{
    			$this->json_result(1,'caozuo_success');
    			return;
    		}
    		else 
    		{
    			$this->json_error('caozuo_fail');
    			return;
    		}
    	}
    	else 
    	{
    		if($this->visitor->get('user_id') != $note_info['login_id'])
    		{
    			$this->json_error('cannot_modify_others');
    			return;
    		}
    		$affect_rows = $model_ordernote->edit("order_id ='{$order_id}' ",array('content'=>html_filter($content)));
    		if($affect_rows)
    		{
    			$this->json_result(1,'caozuo_success');
    			return;
    		}
    		else
    		{
    			$this->json_error('caozuo_fail');
    			return;
    		}
    	}
    }
    
    public function mb_print()
    {
    	$this->_behalf_printer->mb_print();
    }
    
    public function common_print()
    {
    	$this->_behalf_printer->common_print();
    }
    
    public function get_invoice_no()
    {
    	$result = $this->_behalf_printer->get_invoice_no();
    	if($result === true)
    	{
    		$this->json_result(1,'modeb_success');
    	}
    	else 
    	{
    		$this->json_error($result);
    	}
    }
    /**
     * 显示模板
     */
    public function getMailCounter()
    {
        $this->_assign_leftmenu('setting');
        $this->display('behalf.system.getMailCounter.html');
    }
    /**
     * ajax
     */
    public function getMailCounter_ajax()
    {
        $result = $this->_behalf_printer->getMailCounter();
        if($result === false)
        {
           //$this->json_error(false); 
           echo ecm_json_encode(false);
        }
        else
        {
           //$this->json_result($result,'modeb_success');
           echo $result;
           
        }
    }
    
    public function async_shipped()
    {
    	$result = $this->_behalf_printer->async_shipped();
    	if($result === true)
    	{
    		$this->json_result(1,'success');
    	}
    	else
    	{
    		$this->json_error($result);
    	}
    }
    
    public function save_invoiceno()
    {
    	$result = $this->_behalf_printer->save_invoiceno();
    	if($result === false)
    	{
    		return ;
    	}
    	else
    	{
    		return $result;
    	}
    }
    
    public function check_invoiceno()
    {
    	$result = $this->_behalf_printer->check_invoiceno();
    	echo ecm_json_encode($result);
    }
    
    /**
     * 
     */
    public function member_list()
    {
    	$this->_behalf_client->member_list();
    	
    }
    public function store_black_list()
    {
    	$this->_behalf_client->store_black_list();
    	
    }
    /**
     * 统计所有下单数，及分润！
     */
    public function stat_all_fr()
    {
        $shipped_orders = 0;//正在发货的单数
        $finished_orders = 0;//已完成的单数
        
        $fr_deliverys = 0;//邮费分润
        $fr_store = 0; //商品优惠分润总金额
        $fr_storeback = 0; //退还分润
        
        $order_list = $this->_order_mod->findAll(array(
           'conditions'=>"order_alias.bh_id = {$this->visitor->get('has_behalf')} AND order_alias.status ".db_create_in(array(
               ORDER_SHIPPED,ORDER_FINISHED
           )), 
           'include'=>array('has_ordergoods')
        ));
        if($order_list)
        {
           foreach ($order_list as $order)
           {
               if($order['status'] == ORDER_SHIPPED)
               {
                   $shipped_orders ++ ;
               }
               elseif ($order['status'] == ORDER_FINISHED)
               {
                   $finished_orders ++ ;
               }
               $fr_deliverys += floatval($order['behalf_discount']) ;//快递 和 优惠和
               
               foreach ($order['order_goods'] as $goods)
               {
                   if($goods['oos_value'])
                   {
                       $fr_store += floatval($goods['behalf_to51_discount']);
                       $fr_storeback += floatval($goods['zwd51_tobehalf_discount']);
                   }
                   
               }
               
           }
        }
        
        $data = array(
          'total_orders'=>$shipped_orders + $finished_orders,
          'shipped_orders'=>$shipped_orders,
          'finished_orders'=>$finished_orders,
          'fr_deliverys'=>$fr_deliverys - $fr_store,
          'goods_fr'=>$fr_store,
          'reback_fr'=>$fr_storeback,
          'fr_result'=>$fr_store - $fr_storeback
        );
        $this->json_result($data,'success');
        //dump($data);
    }
    
    /**
     *   主动退还订单缺货款
     *   订单为 待发货， 已发货，已完成 情况下 
     */
    function refund_lackgoods()
    {
        $order_id = $_GET['order_id'] ? intval($_GET['order_id']):0;
        //查询款项信息
        $mod_ordercompensationbehalf = & m('ordercompensationbehalf');
        $compensation_info = $mod_ordercompensationbehalf->get("order_id='".$order_id."' AND type='lack'");
        if($compensation_info)
        {
            echo Lang::get('compensation_lack_exist');
            return;
        }
        //查询订单信息
        $order_info = $this->_order_mod->findAll(array(
           'conditions'=>"order_alias.order_id = {$order_id}"." AND ".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED),"order_alias.status"),
           'include'=>array('has_goodswarehouse')
        ));
        if(empty($order_info))
        {
            echo 'empty order!';
            return;
        }
        //统计需退款项
        $total_amount = 0;//缺货总款项
        $total_count = 0;//缺货总件数
        $order_info = current($order_info);
        if($order_info['gwh'])
        {
            foreach ($order_info['gwh'] as $goods)
            {
                //备货中 和 已备货 在退款时 不属于 缺货了
                if(in_array($goods['goods_status'],array(BEHALF_GOODS_TOMORROW,BEHALF_GOODS_UNFORMED,BEHALF_GOODS_UNSALE)))
                {
                    $total_amount += floatval($goods['goods_price']);
                    $total_count++;
                }
            }
        }
        //缺货款为0，退出
        if(empty($total_amount))
        {
            echo Lang::get('order_lack_amount_0');
            return;
        }
        
        if(!IS_POST)
        {
            $this->assign('buyer_name',$order_info['buyer_name']);
            $this->assign('total_amount',number_format($total_amount + $total_count*2,2));
            $this->assign('order_id',$order_id);
            $this->display('behalf.order.compensation.lack.html');
        }
        else 
        {
            $pay_amount = $_POST['pay_amount'] ? floatval($_POST['pay_amount']) :0;
            //$pay_amount = $total_amount;
            //限制转账金额
            if($pay_amount > ($total_amount + $total_count * 2) || $pay_amount > 500)
            {
                echo sprintf(Lang::get('pay_amount_too_much'),$pay_amount);
                return;
            }
            
            $zf_pass = trim($_POST['zf_pass']);
            
            //开启事务
            $db_transaction_begin = db()->query("START TRANSACTION");
            if($db_transaction_begin === false)
            {
                //$this->pop_warning('fail_caozuo');
                echo 'fail_to_transaction';
                return;
            }
            $db_transaction_success = true;//默认事务执行成功，不用回滚
            
            include_once(ROOT_PATH.'/app/my_money.app.php');
    		$my_moneyapp = new My_moneyApp();
    		
    		$result_pay = $my_moneyapp->to_user_withdraw($order_info['buyer_name'],$pay_amount, $order_id,$order_info['order_sn'],$zf_pass);
    		$result_pay !== true && $db_transaction_success = false;
    		
    		$affect_rows = $mod_ordercompensationbehalf->add(array(
    		   'order_id'=>$order_info['order_id'],
    		   'order_sn'=>$order_info['order_sn'],
    		    'bh_id'=>$this->visitor->get('user_id'),
    		    'create_time'=>gmtime(),
    		    'pay_amount'=>$pay_amount,
    		    'type'=>'lack'
    		));
    		
    		!$affect_rows && $db_transaction_success=false;
    		
    		if($db_transaction_success === false)
    		{
    		    db()->query("ROLLBACK");//回滚
    		    if($result_pay !== true ){echo $result_pay;}
    		    else{echo 'server is busy now,try again later!';}
    		    
    		    return ;
    		}
    		else
    		{
    		    db()->query("COMMIT");//提交
    		    $this->pop_warning('ok');
    		}
    		
    		
        }
       
        
    }
    /**
     * 主动赔偿运费，因为发错货
     * 订单为 已发货 和 已完成 情况下
     */
    function compensate_fee()
    {
        $order_id = $_GET['order_id'] ? intval($_GET['order_id']):0;
        //查询款项信息
        $mod_ordercompensationbehalf = & m('ordercompensationbehalf');
        $compensation_info = $mod_ordercompensationbehalf->get("order_id='".$order_id."' AND type='deli'");
        if($compensation_info)
        {
            echo Lang::get('compensation_deli_exist');
            return;
        }
        //查询订单信息
        $order_info = $this->_order_mod->get(array(
           'conditions'=>"order_alias.order_id = {$order_id}"." AND ".db_create_in(array(ORDER_SHIPPED,ORDER_FINISHED),"order_alias.status")
        ));
        if(empty($order_info))
        {
            echo 'empty order!';
            return;
        }
        
        
        if(!IS_POST)
        {
            $this->assign('buyer_name',$order_info['buyer_name']);
            $this->assign('order_id',$order_id);
            $this->display('behalf.order.compensation.deli.html');
        }
        else 
        {
            $pay_amount = $_POST['pay_amount'] ? floatval($_POST['pay_amount']) :0;
            
            if($pay_amount > 16)
            {
                echo sprintf(Lang::get('pay_amount_too_much'),$pay_amount);
                return;
            }
            
            $zf_pass = trim($_POST['zf_pass']);
            
            //开启事务
            $db_transaction_begin = db()->query("START TRANSACTION");
            if($db_transaction_begin === false)
            {
                //$this->pop_warning('fail_caozuo');
                echo 'fail_to_transaction';
                return;
            }
            $db_transaction_success = true;//默认事务执行成功，不用回滚
            
            include_once(ROOT_PATH.'/app/my_money.app.php');
    		$my_moneyapp = new My_moneyApp();
    		
    		$result_pay = $my_moneyapp->to_user_withdraw($order_info['buyer_name'],$pay_amount, $order_id,$order_info['order_sn'],$zf_pass);
    		$result_pay !== true && $db_transaction_success = false;
    		
    		$affect_rows = $mod_ordercompensationbehalf->add(array(
    		   'order_id'=>$order_info['order_id'],
    		   'order_sn'=>$order_info['order_sn'],
    		    'bh_id'=>$this->visitor->get('user_id'),
    		    'create_time'=>gmtime(),
    		    'pay_amount'=>$pay_amount,
    		    'type'=>'deli'
    		));
    		
    		!$affect_rows && $db_transaction_success=false;
    		
    		if($db_transaction_success === false)
    		{
    		    db()->query("ROLLBACK");//回滚
    		    if($result_pay !== true ){echo $result_pay;}
    		    else{echo 'server is busy now,try again later!';}
    		    
    		    return ;
    		}
    		else
    		{
    		    db()->query("COMMIT");//提交
    		    $this->pop_warning('ok');
    		}
    		
    		
        }
    }
}
?>
