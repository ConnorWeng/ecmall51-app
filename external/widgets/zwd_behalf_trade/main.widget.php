<?php

/**
 * 代发交易
 *
 * @return  array   $image_list
 */
class Zwd_behalf_tradeWidget extends BaseWidget
{
    var $_name = 'zwd_behalf_trade';
	var $_ttl  = 1800;

    function _get_data()
    {
		$cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
        	mb_internal_encoding('UTF-8');
        	$behalf_mod =& m('behalf');
        	$order_mod =& m('order');
			$order_behalf_mod =& m('orderbehalfs');
			$orderbehalfs = $order_behalf_mod->findAll(array('order'=>'rec_id desc','limit'=>'0,12'));
			foreach($orderbehalfs as $key=>$val)
			{
				$behalf = $behalf_mod->get($val["bh_id"]);
				$orderbehalfs[$key]['bh_id'] = $behalf['bh_name'];				
				$order = $order_mod->get($val['order_id']);
				$orderbehalfs[$key]['buyer_name'] =  mb_substr($order['buyer_name'],0,1)."**".mb_substr($order['buyer_name'],-1,1);
				$orderbehalfs[$key]['order_amount'] = $order['order_amount'];
				$orderbehalfs[$key]['seller_name'] = $order['seller_name'];	
				$orderbehalfs[$key]['seller_id'] = $order['seller_id'];
				if($order['seller_id'] == 0)
				{
					$str_arr =explode(',', $order['seller_name']);
					$orderbehalfs[$key]['seller_name'] = $str_arr[1];
					
					//substr($order['seller_name'], 1,strlen($order['seller_name']));
				}
			} 
			$data = array(
					'behalfs' => $orderbehalfs
			);			
			$cache_server->set($key, $data,$this->_ttl);
        }
        return $data;
    }

    /* function parse_config($input)
    {        
        return $input;
    } */

   
}

?>