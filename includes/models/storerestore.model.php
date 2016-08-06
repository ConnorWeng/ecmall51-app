<?php

class StorerestoreModel extends BaseModel
{
    var $table = 'store_restore';
    var $prikey = 'store_id';
    var $alias = 'restore';
    var $_name = 'storerestore';

    var $_relation = array(
        'has_store' => array(
            'model' => 'store',
            'type' => HAS_ONE,
            'foreign_key' => 'store_id',
            'dependent' => true
        ),
    );
}

?>