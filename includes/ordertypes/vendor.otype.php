<?php

class VendorOrder extends BaseOrder {
    var $_name = 'vendor';

    function get_order_detail($order_id, $order_info) {
        if (!$order_id) {
            return array();
        }
        $data['goods_list'] = $this->_get_goods_list($order_id);
        return array('data' => $data);
    }

    function _get_goods_list($order_id) {
        if (!$order_id) {
            return array();
        }
        $goodsvendor_mod = &m('goodsvendor');
        return $goodsvendor_mod->find("order_id={$order_id}");
    }
}