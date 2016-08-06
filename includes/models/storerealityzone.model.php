<?php

/* 代发区*/
class StorerealityzoneModel extends BaseModel
{
    var $table  = 'store_realityzone';
    var $prikey = 'store_id';
    var $alias  = 'srealityzone';
    var $_name  = 'storerealityzone';
    var $_relation = array(
        
         'belongs_to_store' => array(
            'model'         => 'store',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'store_id',
            'reverse'       => 'has_storerealityzone',
        )
    );
}

?>