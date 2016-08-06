<?php

class GoodswarehouseModel extends BaseModel {
    var $table = 'goods_warehouse';
    var $alias  = 'gwh';
    var $prikey = 'id';
    var $_relation = array(
        'belongs_to_order' => array(
            'model'         => 'order',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'order_id',
            'reverse'       => 'has_goodswarehouse',
        ),
    );
}