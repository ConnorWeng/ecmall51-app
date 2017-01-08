<?php

class Mobile_categoryApp extends Mobile_frontendApp {
    private $_gcategory_mod = null;
    private $_cache_server = null;

    function __construct($gcategory_mod = null) {
        $this->_gcategory_mod = $gcategory_mod;
        if ($this->_gcategory_mod === null) {
            $this->_gcategory_mod =& bm('gcategory');
        }
        $this->_cache_server =& cache_server();
    }

    function next_layer() {
        $cate_id = $this->_make_sure_numeric('cate_id', -1);
        if ($cate_id === -1) {
            $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
            return ;
        }
        $this->_next_layer($cate_id);
    }

    function _next_layer($cate_id) {
        $cache_key = 'mobile_category_next_layer_cate_id_'.$cate_id;
        $cached_data = $this->_cache_server->get($cache_key);
        if (!empty($cached_data)) {
            echo $cached_data;
        } else {
            $categories = $this->_remove_index_key($this->_gcategory_mod->get_children($cate_id, true));
            $json = ecm_json_encode($categories);
            $this->_cache_server->set($cache_key, $json, 7200);
            echo $json;
        }
    }
}

?>