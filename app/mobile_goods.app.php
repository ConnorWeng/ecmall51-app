<?php

/* 定义like语句转换为in语句的条件 */
define('MAX_ID_NUM_OF_IN', 10000); // IN语句的最大ID数
define('MAX_HIT_RATE', 0.05);      // 最大命中率（满足条件的记录数除以总记录数）
define('MAX_STAT_PRICE', 10000);   // 最大统计价格
define('PRICE_INTERVAL_NUM', 5);   // 价格区间个数
define('MIN_STAT_STEP', 50);       // 价格区间最小间隔
define('NUM_PER_PAGE', 40);        // 每页显示数量
define('ENABLE_SEARCH_CACHE', true); // 启用商品搜索缓存
//define('DISABLE_SEARCH_CACHE', false); //不 启用商品搜索缓存
define('SEARCH_CACHE_TTL', 3600);  // 商品搜索缓存时间

class Mobile_goodsApp extends Mobile_frontendApp {
    private $_goods_mod = null;
    private $_gcategory_mod = null;
    private $_cache_server = null;

    function __construct($goods_mod = null, $gcategory_mod = null) {
        $this->_goods_mod = $goods_mod;
        if ($this->_goods_mod === null) {
            $this->_goods_mod =& m('goods');
        }
        $this->_gcategory_mod = $gcategory_mod;
        if ($this->_gcategory_mod === null) {
            $this->_gcategory_mod =& bm('gcategory');
        }
        $this->_cache_server =& cache_server();
    }

    function index() {
        $store_id = $this->_make_sure_numeric('store_id', 0);
        $this->_index($store_id);
    }

    function _index($store_id) {
        $order_by = 'add_time DESC';
        $page_per = MOBILE_PAGE_SIZE;
        $page = $this->_get_page($page_per);
        $conditions = $store_id == 0 ? 'store_id in (5889,7714,12276,104105,7995,90484,9203,91734,122243,10896,12290,5872,97984,5827,6879,6520,5509,13926,7288,5860,5807,16152,5523,6232,7804,13891,7082,6948,12204,9875,7164,130267,5483,139115,9231,99142,16356,5352,11977,5400,10432,8840,6319,9003,100814,12673,8403,11346,9232,5826,102566,12826,7441,6631,19064,121403,13105,6170,23701,5751,6093,14655,10499,6479,8534,10429,87531,5561,5335,5971,5666,11136,17007,14081,7131,5430,10991,10433,11473,6472,5385,5536,5774,5530,7451,7785,80275,13684,14475,12696,13481,13705,6527,12199,24417,5867,8131,8292,99416,5474,5612,11502,25125,11126,113861,9038,5692,6585,139271,22893,138567,16873,5808,135014,20233,21257,90235,10288,19774,13587,125722,14605,99917,93476,122623,106623,12139,13687,100154,90235,6032,5529,20833,7243,8131,113861,11222)' : "store_id = {$store_id}";
        $goods_mod =& m('goods');
        $goods_list = $goods_mod->findAll(array(
            'fields' => 'goods_id, goods_name, default_image, price, store_id',
            'index_key' => false,
            'include' => array(
                'has_goodsattr' => array(
                    'fields' => 'attr_value',
                    'conditions' => 'attr_id = 1')),
            'conditions' => $conditions,
            'order' => $order_by,
            'limit' => $page['limit']));
        echo ecm_json_encode($goods_list);
    }

    function search() {
        $keywords = explode(' ', $_REQUEST['keywords']);
        $page_per = MOBILE_PAGE_SIZE;
        $page = $this->_get_page($page_per);
        $goods_mod =& m('goods');
        $goods = $goods_mod->get_Mem_list(array(
            'order' => 'views desc',
            'fields' => 'g.goods_id,',
            'index_key' => false,
            'limit' => $page_per,
            'conditions_tt' => $keywords), null, false, true, $total_found);
        echo ecm_json_encode($goods);
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
        $good = $this->_goods_mod->get(array(
            'fields' => 'description',
            'conditions' => $conditions));
        $good['imgs_in_desc'] = $this->_parse_desc_images($good['description']);
        $good['need_move_pics'] = $this->_need_move_pics($good['description']);
        echo ecm_json_encode($good);
    }

    function _parse_desc_images($desc) {
        $pattern = "/(https?:\/\/\w+\.\w+\.com[-\w!\/.]+\.(jpg|png|gif|bmp|webp|jpeg))/Ui";
        preg_match_all($pattern, $desc, $matches);
        return $matches[1];
    }

    function _need_move_pics($desc) {
        $pattern = "/(https?:\/\/\w+\.\w+\.com[-\w!\/.]+\.(jpg|png|gif|bmp|webp|jpeg))/Ui";
        preg_match_all($pattern, $desc, $matches);
        $picNum = count($matches[1]);
        $check = false;
        for ($i=0; $i< $picNum; $i++) {
            $picUrl = $matches[1][$i];
            if (strpos($picUrl, "!!") !== false || strpos($picUrl, "taobaocdn") == false) {
                $check = true;
                break;
            }
        }
        return $check;
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

    function goods_in_cate() {
        $cate_id = $this->_make_sure_numeric('cate_id', -1);
        if ($cate_id === -1) {
            $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
            return ;
        }
        $this->_goods_in_cate($cate_id);
    }

    function _goods_in_cate($cate_id) {
        $page_per = MOBILE_PAGE_SIZE;
        $page = $this->_get_page($page_per);
        $cache_key = 'mobile_goods_goods_in_cate_cate_id_'.$cate_id.'_page_'.$page['curr_page'];
        $cached_data = $this->_cache_server->get($cache_key);
        if (!empty($cached_data)) {
            echo $cached_data;
        } else {
            $layer = $this->_gcategory_mod->get_layer($cate_id, true);
            if ($layer === false) {
                $this->_ajax_error(400, CATEGORY_NOT_FOUND, '分类不存在');
                return ;
            }
            $order_by = 'add_time DESC';
            $goods_list = $this->_goods_mod->get_list2(array(
                'fields' => 'g.goods_id, g.goods_name, g.default_image, g.price, g.store_id, ',
                'include' => array(
                    'has_goodsattr' => array(
                        'fields' => 'attr_value',
                        'conditions' => 'attr_id = 1')),
                'conditions' => 'g.cate_id_'.$layer.' = '.$cate_id.' AND g.if_show = 1 AND g.closed = 0 AND g.default_spec > 0 AND s.state = 1',
                'order' => $order_by,
                'limit' => $page['limit']), null, false, true, $total_found, $backkey);

            $json = ecm_json_encode($this->_remove_index_key($goods_list));
            $this->_cache_server->set($cache_key, $json, 7200);
            echo $json;
        }
    }
}

?>