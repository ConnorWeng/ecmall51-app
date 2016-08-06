<?php

/* 订单代发orderbehalfs */
class OrderbehalfsModel extends BaseModel
{
    var $table  = 'order_behalfs';
    var $prikey = 'rec_id';
    var $_name  = 'orderbehalfs';
    var $_relation = array(
        // 一个代发只能属于一个订单
        'belongs_to_order' => array(
            'model'         => 'order',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'order_id',
            'reverse'       => 'has_orderbehalfs',
        ),
    );
}

?>