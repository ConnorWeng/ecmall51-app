<?php

/* 订单 order */
class OrderModel extends BaseModel
{
    var $table  = 'order';
    var $alias  = 'order_alias';
    var $prikey = 'order_id';
    var $_name  = 'order';
    var $_relation  = array(
        // 一个订单有一个实物商品订单扩展
        'has_orderextm' => array(
            'model'         => 'orderextm',
            'type'          => HAS_ONE,
            'foreign_key'   => 'order_id',
            'dependent'     => true
        ),
    	//一个订单有一个备忘录
        'has_ordernote' => array(
            'model'         => 'behalfordernote',
            'type'          => HAS_ONE,
            'foreign_key'   => 'order_id',
            'dependent'     => true
        ),
    	//一个订单有一个客户关系
        'has_behalfmemberrelation' => array(
            'model'         => 'behalfmemberrelation',
            'type'          => HAS_ONE,
            'foreign_key'   => 'user_id',
            'dependent'     => true
        ),
        // 一个订单有多个订单商品
        'has_ordergoods' => array(
            'model'         => 'ordergoods',
            'type'          => HAS_MANY,
            'foreign_key'   => 'order_id',
            'dependent'     => true
        ),
    	// 一个订单有一个代发
    	'has_orderbehalfs' => array(
    			'model'         => 'orderbehalfs',
    			'type'          => HAS_ONE,
    			'foreign_key'   => 'order_id',
    			'dependent'     => true
    	),    	
        // 一个订单有多个订单日志
        'has_orderlog' => array(
            'model'         => 'orderlog',
            'type'          => HAS_MANY,
            'foreign_key'   => 'order_id',
            'dependent'     => true
        ),
    	'has_orderrefund'=>array(
    		 'model'         => 'orderrefund',
    		 'type'          => HAS_MANY,
    		 'foreign_key'   => 'order_id',
    		 'dependent'     => true
        ),
    	'has_orderstorerefund'=>array(
    		 'model'         => 'orderstorerefund',
    		 'type'          => HAS_MANY,
    		 'foreign_key'   => 'order_id',
    		 'dependent'     => true
        ),
    	'has_ordermodeb'=>array(
    		 'model'         => 'ordermodeb',
    		 'type'          => HAS_ONE,
    		 'foreign_key'   => 'order_id',
    		 'dependent'     => true
        ),
    	'has_goodswarehouse'=>array(
    		 'model'         => 'goodswarehouse',
    		 'type'          => HAS_MANY,
    		 'foreign_key'   => 'order_id',
    		 'dependent'     => true
        ),
        'belongs_to_store'  => array(
            'type'          => BELONGS_TO,
            'reverse'       => 'has_order',
            'model'         => 'store',
        ),
        'belongs_to_user'  => array(
            'type'          => BELONGS_TO,
            'reverse'       => 'has_order',
            'model'         => 'member',
        ),
    );

    /**
     *    修改订单中商品的库存，可以是减少也可以是加回
     *
     *    @author    Garbin
     *    @param     string $action     [+:加回， -:减少]
     *    @param     int    $order_id   订单ID
     *    @return    bool
     */
    function change_stock($action, $order_id)
    {
        if (!in_array($action, array('+', '-')))
        {
            $this->_error('undefined_action');

            return false;
        }
        if (!$order_id)
        {
            $this->_error('no_such_order');

            return false;
        }

        /* 获取订单商品列表 */
        $model_ordergoods =& m('ordergoods');
        $order_goods = $model_ordergoods->find("order_id={$order_id}");
        if (empty($order_goods))
        {
            $this->_error('goods_empty');

            return false;
        }

        $model_goodsspec =& m('goodsspec');
        $model_goods =& m('goods');

        /* 依次改变库存 */
        foreach ($order_goods as $rec_id => $goods)
        {
            $model_goodsspec->edit($goods['spec_id'], "stock=stock {$action} {$goods['quantity']}");
            $model_goods->clear_cache($goods['goods_id']);
        }

        /* 操作成功 */
        return true;
    }
    
    /**
     * 根据 $order_id 和   $bh_id 得到 快递名称 dl_name
     * @param 订单id  $order_id
     * @param 代发id  $bh_id
     */
    function get_delivery_bybehalf($order_id=0,$bh_id=0)
    {
    	if(!$order_id)
    	{
    		return;
    	}
    	if(!$bh_id)
    	{
    		return ;
    	}
    	//$model_orderbehalfs = & m('orderbehalfs');
    	$model_orderextm =& m('orderextm');
    	$orderbehalf = $model_orderextm->get(array(
    		'conditions'=>'order_id='.intval($order_id).' AND bh_id='.intval($bh_id),
    	    'fields'=>'dl_id',	
    	)); 
    	if($orderbehalf)
    	{
    		$model_delivery = & m('delivery');
    		$delivery = $model_delivery->get($orderbehalf['dl_id']);
    		return $delivery['dl_name'];
    	}
    }
    
    /**
     * 根据 $order_id 和   $bh_id 得到 快递公司代码 dl_desc
     * @param 订单id  $order_id
     * @param 代发id  $bh_id
     */
    function get_delivery_bm_bybehalf($order_id=0)
    {
    	if(!$order_id)
    	{
    		return;
    	}
    	$order = $this->get($order_id);
    	$model_delivery = & m('delivery');
    	$model_orderextm=& m('orderextm');
    	if(!empty($order['bh_id']))
    	{
    		/* $model_orderbehalfs = & m('orderbehalfs');
    		$orderbehalf = $model_orderbehalfs->get(array(
    				'conditions'=>'order_id='.intval($order_id).' AND bh_id='.intval($order['bh_id']),
    				'fields'=>'dl_id',
    		));return $order['order_id'];
    		if($orderbehalf)
    		{
    			$delivery = $model_delivery->get($orderbehalf['dl_id']);
    			return trim($delivery['dl_desc']);
    		} */
    		$orderextm = $model_orderextm->get($order_id);
    		if($orderextm)
    		{
    			$delivery = $model_delivery->get(intval($orderextm['dl_id']));
    			return trim($delivery['dl_desc']);
    		}
    	}
    	else
    	{
    		$orderextm = $model_orderextm->get($order_id);
    		if($orderextm)
    		{
    			$delivery = $model_delivery->get(intval($orderextm['shipping_id']));
    			return trim($delivery['dl_desc']);
    		}
    	}
    	return;
    }
}

?>
