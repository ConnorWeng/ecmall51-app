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
            'conditions' => 'state = 1'.$conditions.' and store_id in (5889,7714,12276,104105,7995,90484,9203,91734,122243,10896,12290,5872,97984,5827,6879,6520,5509,13926,7288,5860,5807,16152,5523,6232,7804,13891,7082,6948,12204,9875,7164,130267,5483,139115,9231,99142,16356,5352,11977,5400,10432,8840,6319,9003,100814,12673,8403,11346,9232,5826,102566,12826,7441,6631,19064,121403,13105,6170,23701,5751,6093,14655,10499,6479,8534,10429,87531,5561,5335,5971,5666,11136,17007,14081,7131,5430,10991,10433,11473,6472,5385,5536,5774,5530,7451,7785,80275,13684,14475,12696,13481,13705,6527,12199,24417,5867,8131,8292,99416,5474,5612,11502,25125,11126,113861,9038,5692,6585,139271,22893,138567,16873,5808,135014,20233,21257,90235,10288,19774,13587,125722,14605,99917,93476,122623,106623,12139,13687,100154,90235,6032,5529,20833,7243,8131,113861,11222)',
            'order' => $order_by,
            'limit' => $page['limit']));
        echo ecm_json_encode($shop_list);
    }
}

?>