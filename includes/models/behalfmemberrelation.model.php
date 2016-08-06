<?php

class BehalfmemberrelationModel extends BaseModel {
    var $table = 'behalf_member_relation';
    var $alias  = 'bh_mem_relation';
    var $prikey = 'id';
    
    var $_relation = array(
        'belongs_to_order' => array(
            'type'          => BELONGS_TO,
            'reverse'       => 'has_behalfmemberrelation',
            'model'         => 'order',
            'foreign_key'   => 'buyer_id',
        ),
    );
}