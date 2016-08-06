<?php

/* 精品区*/
class StorebrandareagoodsModel extends BaseModel
{
    var $table  = 'store_brandarea_goods';
    var $prikey = 'goods_id';
    var $alias  = 'gbrandarea';
    var $_name  = 'storebrandareagoods';
    var $_relation = array(
        
        'belongs_to_goods' => array(
            'model'         => 'goods',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'goods_id',
            'reverse'       => 'has_storebrandareagoods',
        ), 
    );
}

?>