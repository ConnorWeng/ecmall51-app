<?php

/* 会员 member vip*/
class MembervipModel extends BaseModel
{
    var $table  = 'member_vip';
    var $prikey = 'user_id';
    var $_name  = 'vip';

    /* 与其它模型之间的关系 */
    var $_relation = array(
        // 一个vip属于一个会员
        'belongs_to_user' => array(
            'model'         => 'member',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'user_id',
            'reverse'       => 'has_vip',
        )




    );



}

?>