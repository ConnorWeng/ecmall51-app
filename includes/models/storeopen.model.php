<?php

class StoreopenModel extends BaseModel {
    var $table  = 'store_open';
    var $prikey = 'store_id';
    var $alias  = 's';
    var $_name  = 'store';

    var $_relation = array(
        // 一个店铺有多个商品
        'has_goods' => array(
            'model'         => 'goods',
            'type'          => HAS_MANY,
            'foreign_key'   => 'store_id',
            'dependent' => true
        ),
    );
}

?>