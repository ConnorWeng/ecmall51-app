<?php
/**
 * 代发客户关系管理
 * @author tanaiquan
 * @ 2015-12-30
 */
class BehalfClientModule extends BehalfBaseModule
{
	function __construct()
	{
		$this->BehalfClientModule();
	}
	
	function BehalfClientModule()
	{
		parent::__construct();	
	}
	
	function _run_action()
	{
		parent::_run_action();
	}
	
	/**
	 *  下过单的会员
	 */
	public function member_list()
	{
		$bh_id = $this->visitor->get('has_behalf');
		/* $sql = "SELECT buyer_id, count(buyer_id) as orders FROM ".$this->_order_mod->table." WHERE bh_id='{$bh_id}' AND status ".db_create_in(array(ORDER_FINISHED))." GROUP BY buyer_id ";
		$buyers = $this->_order_mod->getAll($sql);
		
		$member_ids = array();
		if($buyers)
		{
		    foreach ($buyers as $buyer)
		    {
		        $member_ids[$buyer['buyer_id']] = $buyer['orders'];
		    }
		    arsort($member_ids,SORT_NUMERIC);
		} */
		//dump($member_ids);
		
		$order = in_array($_GET['order'],array('orders','pay_time'))?$_GET['order']:'orders';
		
		$page = $this->_get_page();
		
		$model_member = & m('member');
		/* $members = $model_member->find(array(
			'conditions'=>db_create_in(array_keys($member_ids),'user_id'),
			'limit'=>$page['limit'],
			'count'=>true	
		)); */
		
		/* $sql = "SELECT m.user_name,m.real_name,m.phone_mob,m.im_qq,m.im_aliww,m.user_id,count(o.buyer_id) as orders FROM "
		    .$this->_order_mod->table ." as o LEFT JOIN ".$model_member->table 
		    ." as m ON o.buyer_id = m.user_id WHERE o.bh_id='{$bh_id}' AND o.status "
		    .db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED))." GROUP BY buyer_id ORDER BY orders DESC LIMIT ".$page['limit']; */
		/* $sql = "SELECT m.user_name,m.real_name,m.phone_mob,m.im_qq,m.im_aliww,m.user_id,o.orders FROM "
		    ."((SELECT *,count(buyer_id) as orders FROM ".$this->_order_mod->table."  WHERE bh_id='{$bh_id}' AND status "
		        .db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED))." GROUP BY buyer_id) as a "
		         ."left join (SELECT buyer_id,max(pay_time) FROM ".$this->_order_mod->table."  WHERE bh_id='{$bh_id}' AND status "
		        .db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED))." GROUP BY buyer_id) as b ON a.buyer_id = b.buyer_id AND "
		            ."a.pay_time=b.pay_time) as o "
		    ." LEFT JOIN ".$model_member->table 
		    ." as m ON o.buyer_id = m.user_id  ORDER BY o.orders DESC LIMIT ".$page['limit']; */
		 $sql ="SELECT m.user_name,m.real_name,m.phone_mob,m.im_qq,m.im_aliww,m.user_id,o.orders,o.pay_time FROM "
		     ."(SELECT a.buyer_id,b.orders,b.pay_time FROM ".$this->_order_mod->table." a "
		        ." inner join (SELECT t.buyer_id,max(pay_time) pay_time,count(t.buyer_id) as orders FROM ".$this->_order_mod->table." t  WHERE bh_id='{$bh_id}' AND status "
		       .db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED))." GROUP BY t.buyer_id) b ON a.buyer_id = b.buyer_id AND "
		           ."a.pay_time=b.pay_time )"
		    ." as o LEFT JOIN ".$model_member->table 
		    ." as m ON o.buyer_id = m.user_id  ORDER BY o.$order DESC LIMIT ".$page['limit'];
		$members = $this->_order_mod->getAll($sql); 
		
		//dump($members);
		/* dump($buyers);
		
		$members = $this->_order_mod->find(array(
			'conditions'=>"order_alias.bh_id='{$bh_id}' AND order_alias.status=".ORDER_FINISHED,
			'fields'=>'member.phone_mob,member.im_qq,member.im_aliww,buyer_id',
		    'join'=>'belongs_to_user',
			'limit'=>$page['limit'],
			//'count'=>true	
		)); 
		
		dump($members); */
		
		
		
		$sql = "SELECT count(buyer_id) as c FROM ".$this->_order_mod->table 
		    ." WHERE bh_id='{$bh_id}' AND status ".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED))." GROUP BY buyer_id ";
		$item_count = $this->_order_mod->getCol($sql);
		
		if($members)
		{
		    foreach ($members as $key=>$vm)
		    {
		        
		        if(time() - $vm['pay_time'] > 10*24*60*60)
		        {
		            $members[$key]['red'] = 1;
		        }
		    }
		} 
		//dump($members);
		//统计超过10天未下单的用户
		
		
		
		$page ['item_count'] = count($item_count);
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
		/* $page ['item_count'] = $model_member->getCount ();
		$this->_format_page ( $page );
		$page['start_number'] = (intval($page['curr_page'])-1)*intval($page['pageper'])+1;
		if($page['start_number'] + $page['pageper'] <= $page['item_count'])
		{
			$page['end_number'] = intval($page['start_number']) + intval($page['pageper']) -1;
		}
		else
		{
			$page['end_number'] = intval($page['start_number']) - 1 + floor(intval($page['item_count']) % intval($page['pageper']));
		} */
		$this->_assign_leftmenu('client_manage');
		$this->_import_css_js('dt');
		$this->assign ( 'page_info', $page );
		$this->assign('members',$members);
		$this->display('behalf.client.members.list.html');
		//return $members;
	}
	
	/**
	 * 档口黑名单
	 */
	public function store_black_list()
	{
	   // $rows = isset($_GET['length']) && $_GET['length'] ? intval(trim($_GET['length'])):10;
	   // $page = $this->_get_page($rows);
	    $bh_id = $this->visitor->get('has_behalf');
	    $store_id = $_GET['sid'];
	    //加入黑名单
	    if($_GET['type'] == 'add')
	    {
	       if(!empty($store_id) && is_numeric($store_id))
	       {
	          $this->_behalf_mod->createRelation("has_blacklist_stores",$bh_id,array($store_id));    
	       }
	       
	    }
	    //解除黑名单
	    if($_GET['type'] == 'dismiss')
	    {	        
	        $this->_behalf_mod->unlinkRelation("has_blacklist_stores",$bh_id,array($store_id));
	    }
	    
	   // $black_list = $this->_behalf_mod->getRelatedData("has_blacklist_stores",$bh_id,array('limit'=>$page['limit']));
	    
	    
	    //$page ['item_count'] = $this->_behalf_mod->getCount ();
	    /* $this->_format_page ( $page );
	    $page['start_number'] = (intval($page['curr_page'])-1)*intval($page['pageper'])+1;
	    if($page['start_number'] + $page['pageper'] <= $page['item_count'])
	    {
	        $page['end_number'] = intval($page['start_number']) + intval($page['pageper']) -1;
	    }
	    else
	    {
	        $page['end_number'] = intval($page['start_number']) - 1 + floor(intval($page['item_count']) % intval($page['pageper']));
	    } */
	    
	    
	    
	    $this->_assign_leftmenu('client_manage');
	    $this->_import_css_js('dt');
	    //$this->assign ( 'page_info', $page );
	   // $this->assign('black_list',$black_list);
	   // $this->assign ( 'page_info', $page );
	    $this->display('behalf.client.store.black_list.html');
	}
}

?>