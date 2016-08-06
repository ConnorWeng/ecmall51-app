<?php

/* 代发区*/
class StorebehalfareaModel extends BaseModel
{
    var $table  = 'store_behalfarea';
    var $prikey = 'store_id';
    var $alias  = 'sbehalfarea';
    var $_name  = 'storebehalfarea';
    var $_relation = array(
        
         'belongs_to_store' => array(
            'model'         => 'store',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'store_id',
            'reverse'       => 'has_storebehalfarea',
        )
    );
}

?>