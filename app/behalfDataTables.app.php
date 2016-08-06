<?php

/**
 *    为代发模块dataTables提供数据
 *
 *    @author    tanaiquan
 */
class BehalfDataTablesApp extends MallbaseApp {

    /**
     * 通过档口名称获取档口信息
     */
    function get_store_bystorename()
    {
        $store_name = $_GET['sname']?trim($_GET['sname']):'';
    
        $start = intval($_GET['start']);
        $page_per = intval($_GET['length']);
    
        $mod_store = & m('store');
        $stores = $mod_store->find(array(
            'conditions' => 'state = ' . STORE_OPEN ." AND store_name like '%".$store_name."%'",
            'limit' => "{$start},{$page_per}",
            'count'=>true,
            //'fields'  =>'store_name,user_name,sgrade,store_logo,recommended,praise_rate,credit_value,s.im_qq,im_ww,business_scope,region_name,serv_sendgoods,serv_refund,serv_exchgoods,serv_golden,dangkou_address,mk_name,shop_http,see_price',
        'order' => 'sort_order asc'
            ));
    
        $total_length = $mod_store->getCount();
    
        echo ecm_json_encode(array(
            "draw" => intval($_GET['draw']),
            "recordsTotal" => $total_length,
            "recordsFiltered" => $total_length,
            "data" => array_values($stores)
        ));
        //$this->json_result(1,$stores);
    }
    
    /**
     * 代发 档口黑名单
     */
    function get_store_blacklist()
    {
        $mod_behalf = & m('behalf');
        
        $start = intval($_GET['start']);
        $page_per = intval($_GET['length']);
        
        $bh_id = $this->visitor->get('has_behalf');
        
        $black_list = $mod_behalf->getRelatedData("has_blacklist_stores",$bh_id,array(
            'limit' => "{$start},{$page_per}",
            'count'=>true
        ));
        
        $total_length = count($mod_behalf->getRelatedData("has_blacklist_stores",$bh_id,array(
            'field'=>'store_id'
        )));
        
        echo ecm_json_encode(array(
            "draw" => intval($_GET['draw']),
            "recordsTotal" => $total_length,
            "recordsFiltered" => $total_length,
            "data" => array_values($black_list)
        ));
    }
    
    
}

?>
