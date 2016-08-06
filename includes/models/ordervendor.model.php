<?php

class OrdervendorModel extends BaseModel {
    var $table = 'order_vendor';
    var $prikey = 'order_id';
    var $_relation = array(
        'has_goodsvendor' => array(
            'model' => 'goodsvendor',
            'type' => HAS_MANY,
            'foreign_key' => 'order_id',
            'dependent' => true
        )
    );

    function hide_duplicate_order($seller_id) {
        $sql = "update ecm_order_vendor set vendor = 99 where order_id in (select order_id from (select max(order_id) as order_id from ecm_order_vendor where seller_id = ".$seller_id." and ecm_order_id is null group by order_sn having count(1) > 1) t)";
        $res = $this->db->query($sql);
        return $res;
    }
}