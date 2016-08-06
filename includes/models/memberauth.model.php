<?php

class MemberauthModel extends BaseModel {
    var $table = 'member_auth';
    var $prikey = 'user_id';
    var $_relation = array(
        'belongs_to_member' => array(
            'model' => 'member',
            'type' => BELONGS_TO,
            'foreign_key' => 'user_id'));
}

?>