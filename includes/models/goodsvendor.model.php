<?php

class GoodsvendorModel extends BaseModel {
    var $table = 'goods_vendor';
    var $prikey = 'goods_id';
    var $_relation = array(
        'belongs_to_ordervendor' => array(
            'model'         => 'ordervendor',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'order_id',
            'reverse'       => 'has_goodsvendor',
        ),
    );
}