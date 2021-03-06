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
        /* 载入配置项 */
        $setting = & af('settings');
        Conf::load($setting->getAll());

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
        $order_by = 'g.add_time DESC';
        $page_per = MOBILE_PAGE_SIZE;
        $page = $this->_get_page($page_per);
        $conditions = $store_id == 0 ? behalf_open_stores_condition() : "s.store_id = {$store_id}";
        $goods_mod =& m('goods');
        $goods_list = $goods_mod->findAll(array(
            'fields' => 'g.goods_id, g.goods_name, g.default_image, g.price, s.store_id, s.store_name, s.see_price, s.mk_name, s.address, s.business_scope',
            'index_key' => false,
            'join' => 'belongs_to_store_open',
            'include' => array(
                'has_goodsattr' => array(
                    'fields' => 'attr_value',
                    'conditions' => 'attr_id = 1')),
            'conditions' => $conditions,
            'order' => $order_by,
            'limit' => $page['limit']));
        echo ecm_json_encode($this->_transform_goods_list($goods_list));
    }

    function _transform_goods_list($goods_list) {
        $list = array();
        foreach ($goods_list as $good) {
            array_push($list, array(
                'goods_id' => $good['goods_id'],
                'goods_name' => $good['goods_name'],
                'default_image' => $good['default_image'],
                'price' => $good['price'],
                'store_id' => $good['store_id'],
                'shop' => array(
                    'store_id' => $good['store_id'],
                    'store_name' => $good['store_name'],
                    'see_price' => $good['see_price'],
                    'mk_name' => $good['mk_name'],
                    'address' => $good['address'],
                    'business_scope' => $good['business_scope']
                )
            ));
        }
        return $list;
    }

    function search() {
        $keywords = explode(' ', $_REQUEST['keywords']);
        $page_per = MOBILE_PAGE_SIZE;
        $page = $this->_get_page($page_per);
        $goods_mod =& m('goods');
        $goods = $goods_mod->get_Mem_list_for_mobile(array(
            'order' => 'views desc',
            'fields' => 'g.goods_id,',
            'index_key' => false,
            'limit' => $page['limit'],
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
            $goods_list = $this->_goods_mod->get_list2_for_mobile(array(
                'fields' => 'g.goods_id, g.goods_name, g.default_image, g.price, g.store_id, ',
                'include' => array(
                    'has_goodsattr' => array(
                        'fields' => 'attr_value',
                        'conditions' => 'attr_id = 1')),
                'conditions' => 'g.cate_id_'.$layer.' = '.$cate_id.' AND g.if_show = 1 AND g.closed = 0 AND g.default_spec > 0',
                'order' => $order_by,
                'limit' => $page['limit']), null, false, true, $total_found, $backkey);

            $json = ecm_json_encode($this->_remove_index_key($goods_list));
            $this->_cache_server->set($cache_key, $json, 7200);
            echo $json;
        }
    }
}

?>