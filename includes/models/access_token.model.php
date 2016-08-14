<?php

class Access_tokenModel extends BaseModel {
    var $table  = 'access_token';
    var $prikey = 'user_id';
    var $_name  = 'access_token';

    var $_relation = array(
        'belongs_to_member' => array(
            'model' => 'member',
            'type' => BELONGS_TO,
            'foreign_key' => 'user_id',
            'reverse' => 'has_access_token'));
}

?>