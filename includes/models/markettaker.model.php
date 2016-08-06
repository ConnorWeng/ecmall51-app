<?php

class MarkettakerModel extends BaseModel {
    var $table = 'market_taker';
    var $prikey = 'id';
    var $_relation = array(
    		
        //一个拿货市场属于多个拿货员
    	'belongsto_members' => array(
    				'model'        => 'member',
    				'type'         => HAS_AND_BELONGS_TO_MANY,
    				'middle_table' => 'markettaker_member',    //中间表名称
    				'foreign_key'  => 'mt_id',
    				'reverse'      => 'has_markettakers', //反向关系名称
    		),
        //一个拿货市场属于一个代发
    	'belongsto_behalf' => array(
    				'model'        => 'member',
    				'type'         => BELONGS_TO,
    				'foreign_key'  => 'bh_id',
    				'reverse'      => 'has_markettakers', //反向关系名称
    		),
    );
}