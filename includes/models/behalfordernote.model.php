<?php

class BehalfordernoteModel extends BaseModel {
    var $table = 'behalf_ordernote';
    var $alias  = 'bh_ordernote';
    var $prikey = 'order_id';
    
    var $_relation = array(
        'belongs_to_order' => array(
            'type'          => BELONGS_TO,
            'reverse'       => 'has_ordernote',
            'model'         => 'order',
            'foreign_key'   => 'order_id',
        ),
    );
}