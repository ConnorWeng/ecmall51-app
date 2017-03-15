<?php

class Mobile_shopApp extends Mobile_frontendApp {
    function __construct() {
    }

    function index() {
        $mk_id = $this->_make_sure_numeric('mk_id', 0);
        $page = $this->_make_sure_numeric('page', 1);
        $keywords = isset($_REQUEST['keywords']) ? $_REQUEST['keywords'] : '';
        $this->_index($mk_id, $page, $keywords);
    }

    // 根据市场、排序方式、页面大小、页面号进行店铺查询
    function _index($mk_id, $page, $keywords) {
        $order_by = 'mk_id, floor, address';
        $page_per = MOBILE_PAGE_SIZE;
        $page = $this->_get_page($page_per);
        $conditions = '';
        if ($keywords) {
            $conditions .= " and (store_name like '%".trim($keywords)."%' OR dangkou_address like '%".trim($keywords)."%' OR address like '%".trim($keywords)."%')";
        }
        $conditions .= $mk_id == 0 ? '' : " and (mk_id in (select mk_id from ecm_market where parent_id = {$mk_id}) or mk_id = {$mk_id})";
        $shop_mod =& m('store');
        $shop_list = $shop_mod->find(array(
            'fields' => 'mk_id, mk_name, store_id, floor, address, store_name, see_price, business_scope',
            'index_key' => false,
            'conditions' => 'state = 1'.$conditions.' and store_id in ('.implode(',', behalf_open_stores()).')',
            'order' => $order_by,
            'limit' => $page['limit']));
        echo ecm_json_encode($shop_list);
    }
}

?>