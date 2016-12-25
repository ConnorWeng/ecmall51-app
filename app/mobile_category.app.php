<?php

class Mobile_categoryApp extends Mobile_frontendApp {
    private $_gcategory_mod = null;

    function __construct($gcategory_mod = null) {
        $this->_gcategory_mod = $gcategory_mod;
        if ($this->_gcategory_mod === null) {
            $this->_gcategory_mod =& bm('gcategory');
        }
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
        $categories = $this->_remove_index_key($this->_gcategory_mod->get_children($cate_id, true));
        echo ecm_json_encode($categories);
    }
}

?>