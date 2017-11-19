<?php

class Mobile_shopApp extends Mobile_frontendApp {
    function __construct() {
        /* 载入配置项 */
        $setting = & af('settings');
        Conf::load($setting->getAll());
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
        $shop_mod =& m('storeopen');
        $shop_list = $shop_mod->findAll(array(
            'fields' => 'mk_id, mk_name, store_id, floor, address, store_name, see_price, business_scope',
            'index_key' => false,
            'include' => array(
                'has_goods' => array(
                    'fields' => 'g.goods_id, g.goods_name, g.default_image, g.price',
                    'index_key' => false)),
            'conditions' => 'state = 1'.$conditions.' and '.behalf_open_stores_condition(),
            'order' => $order_by,
            'limit' => $page['limit']));
        echo ecm_json_encode($shop_list);
    }
}

?>