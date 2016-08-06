<?php

class GoodstakerinventoryModel extends BaseModel {
    var $table = 'goods_taker_inventory';
    var $alias  = 'gti';
    var $prikey = 'id';
    var $_relation = array(
    		
        'belongs_to_behalf' => array(
            'model'         => 'behalf',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'bh_id',
            'reverse'       => 'has_goodstakerinventory',
        ),
        'belongs_to_member' => array(
            'model'         => 'member',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'taker_id',
            'reverse'       => 'has_goodstakerinventory',
        ),
    );
}