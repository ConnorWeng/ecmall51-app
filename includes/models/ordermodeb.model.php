<?php

/* 订单退款*/
class OrdermodebModel extends BaseModel
{
    var $table  = 'order_modeb';
    var $prikey = 'order_id';
    var $_name  = 'ordermodeb';
    var $_relation = array(
        // 只能属于一个订单
        'belongs_to_order' => array(
            'model'         => 'order',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'order_id',
            'reverse'       => 'has_ordermodeb',
        ),
    );
}

?>