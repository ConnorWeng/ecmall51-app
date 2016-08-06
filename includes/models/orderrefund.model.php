<?php

/* 订单退款*/
class OrderrefundModel extends BaseModel
{
    var $table  = 'order_refund';
    var $prikey = 'id';
    var $_name  = 'orderrefund';
    var $_relation = array(
        // 一个退款只能属于一个订单
        'belongs_to_order' => array(
            'model'         => 'order',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'order_id',
            'reverse'       => 'has_orderrefund',
        ),
    );
}

?>