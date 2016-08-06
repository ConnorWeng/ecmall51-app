<?php

/* 档口订单退款*/
class OrderstorerefundModel extends BaseModel
{
    var $table  = 'order_store_refund';
    var $prikey = 'id';
    var $_name  = 'orderstorerefund';
    var $_relation = array(
        // 一个退款只能属于一个订单
        'belongs_to_order' => array(
            'model'         => 'order',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'order_id',
            'reverse'       => 'has_orderstorerefund',
        ),
    );
}

?>