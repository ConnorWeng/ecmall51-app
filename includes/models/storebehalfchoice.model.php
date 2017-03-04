<?php

/* 精选代发区*/
class StorebehalfchoiceModel extends BaseModel
{
    var $table  = 'store_behalfchoice';
    var $prikey = 'store_id';
    var $alias  = 'sbehalfchoice';
    var $_name  = 'storebehalfchoice';
    var $_relation = array(

         'belongs_to_store' => array(
            'model'         => 'store',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'store_id',
            'reverse'       => 'has_storebehalfchoice',
        )
    );
}

?>