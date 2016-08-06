<?php

/* 精品区*/
class StorebrandareaModel extends BaseModel
{
    var $table  = 'store_brandarea';
    var $prikey = 'store_id';
    var $alias  = 'sbrandarea';
    var $_name  = 'storebrandarea';
    var $_relation = array(
        
        'belongs_to_store' => array(
            'model'         => 'store',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'store_id',
            'reverse'       => 'has_storebrandarea',
        ), 
    );
}

?>