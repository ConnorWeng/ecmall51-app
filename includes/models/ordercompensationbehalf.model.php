<?php

/* 订单代发主动退缺货钱和赔偿运费 */
class OrdercompensationbehalfModel extends BaseModel
{
    var $table  = 'order_compensation_behalf';
    var $prikey = 'id';
    var $_name  = 'ordcombehalf';
    var $_relation = array(
        /* // 一个代发只能属于一个订单
        'belongs_to_order' => array(
            'model'         => 'order',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'order_id',
            'reverse'       => 'has_orderbehalfs',
        ), */
    );
}

?>