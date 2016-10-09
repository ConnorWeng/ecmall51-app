<?php

class Mobile_goodsApp extends Mobile_frontendApp {
    function __construct() {
    }

    function index() {
        $store_id = $this->_make_sure_numeric('store_id', 0);
        $this->_index($store_id);
    }

    function _index($store_id) {
        $order_by = 'add_time DESC';
        $page_per = 25;
        $page = $this->_get_page($page_per);
        $conditions = $store_id == 0 ? '' : "store_id = {$store_id}";
        $goods_mod =& m('goods');
        $goods_list = $goods_mod->find(array(
            'fields' => 'goods_id, goods_name, default_image, price, store_id',
            'index_key' => false,
            'conditions' => $conditions,
            'order' => $order_by,
            'limit' => $page['limit']));
        echo ecm_json_encode($goods_list);
    }

    function search() {
        $keywords = explode(' ', $_REQUEST['keywords']);
        $page_per = 25;
        $page = $this->_get_page($page_per);
        $goods_mod =& m('goods');
        $goods = $goods_mod->get_Mem_list(array(
            'order' => 'views desc',
            'fields' => 'g.goods_id,',
            'limit' => $page['limit'],
            'conditions_tt' => $keywords), null, false, true, $total_found);
        $goodsspec_mod =& m('goodsspec');
        $result = array();
        foreach ($goods as $key => $good) {
            $goodsspec = $goodsspec_mod->get_spec_list($good['goods_id']);
            $result = array_merge($result, $goodsspec);
        }
        echo ecm_json_encode($result);
    }

    function describe() {
        $goods_id = $this->_make_sure_numeric('goods_id', -1);
        if ($goods_id === -1) {
            $this->_ajax_error(400, PARAMS_NOT_PROVIDED, 'goods id must be provided');
        } else {
            $this->_describe($goods_id);
        }
    }

    function _describe($goods_id) {
        $conditions = "goods_id = {$goods_id}";
        $goods_mod =& m('goods');
        $good = $goods_mod->get(array(
            'fields' => 'description',
            'conditions' => $conditions));
        echo ecm_json_encode($good);
    }

    function specs() {
        $goods_id = $this->_make_sure_numeric('goods_id', -1);
        if ($goods_id === -1) {
            $this->_ajax_error(400, PARAMS_NOT_PROVIDED, 'goods id must be provided');
        } else {
            $this->_specs($goods_id);
        }
    }

    function _specs($goods_id) {
        $goods_mod =& m('goods');
        $goods_info = $goods_mod->get_info($goods_id);
        $result = array(
            'specs' => $goods_info['_specs'],
            'spec_qty' => $goods_info['spec_qty'],
            'spec_name_1' => $goods_info['spec_name_1'],
            'spec_pid_1' => $goods_info['spec_pid_1'],
            'spec_name_2' => $goods_info['spec_name_2'],
            'spec_pid_2' => $goods_info['spec_pid_2'],
        );
        echo ecm_json_encode($result);
    }
}

?>