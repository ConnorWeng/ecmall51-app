<?php

/* 定义like语句转换为in语句的条件 */
define('MAX_ID_NUM_OF_IN', 10000); // IN语句的最大ID数
define('MAX_HIT_RATE', 0.05);      // 最大命中率（满足条件的记录数除以总记录数）
define('MAX_STAT_PRICE', 10000);   // 最大统计价格
define('PRICE_INTERVAL_NUM', 5);   // 价格区间个数
define('MIN_STAT_STEP', 50);       // 价格区间最小间隔
define('NUM_PER_PAGE', 40);        // 每页显示数量
define('ENABLE_SEARCH_CACHE', true); // 启用商品搜索缓存
define('SEARCH_CACHE_TTL', 3600);  // 商品搜索缓存时间

class Behalf_realzoneApp extends MallbaseApp {
    /* 搜索商品 */

    function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    function index() {     	
//        $t00 = time();
        // tyioocom 过滤非法参数
        if (!$this->_check_query_param_by_orders()) {
            header('Location:index.php');
            exit;
        }

        $cache_server = & cache_server();
        $isfirst = false;
        $indexkey = 'index_lists_brz_';
        $data = $cache_server->get($indexkey);
        if (!$data || empty($data)) {
            $isfirst = true;
        }

        $param = $this->_get_query_param(); // 查询参数  新增color(颜色) dim(尺寸) by tanaiquan 2015-04-13

        if (isset($param['cate_id']) && $param['layer'] === false) {
            $this->show_warning('no_such_category');
            return;
        }
        //添加市场判断,防止不存在的市场id去查询
        if (isset($param['mkid']) && $param['mlayer'] === false) {
            $this->show_warning('no_such_category');
            return;
        }

        $behalf_area_stores = $this->_get_behalf_realzone_stores();
        if(empty($behalf_area_stores))
        {
            $conditions_store_ids = " AND g.store_id = '0' ";
        }
        else 
        {
            $conditions_store_ids = " AND g.store_id ".db_create_in($behalf_area_stores);
        }
        /* 按分类、品牌、地区、价格区间统计商品数量 */
        $stats = $this->_get_group_by_info($param, ENABLE_SEARCH_CACHE);
        // vdump($stats);

        $this->assign('categories', $stats['by_category']);
        //$this->assign('category_count', count($stats['by_category']));

        $this->assign('markets', $stats['by_market']);
        $this->assign('floors', $stats['by_market_floor']);

        //$this->assign('brands', $stats['by_brand']);
        //$this->assign('brand_count', count($stats['by_brand']));

        $this->assign('price_intervals', $stats['by_price']);

        //$this->assign('regions', $stats['by_region']);
        //$this->assign('region_count', count($stats['by_region']));

        /* 排序 */
        $orders = $this->_get_orders();
        $this->assign('orders', $orders);
        if ($isfirst || count($_GET) > 2) {

//            echo ' come the initial process' . 'the count is ' . count($_GET);
            /* 商品列表 */
            $conditions = $this->_get_goods_conditions2($param);
            //add
            $conditions .= $conditions_store_ids; 
            
            $goods_mod = & m('goods');

//         if (isset($param['keyword']))
//        {
//            $conditions_tt = $this->_get_conditions_by_keyword($param['keyword'], ENABLE_SEARCH_CACHE);
//        }
            // echo '<br>tt:'.$conditions_tt. '<br>'.$conditions;
//            $tb001 = $this->getMillisecond();
            $goods_list = $goods_mod->get_list2(array(
                'conditions' => $conditions,
                'order' => isset($_GET['order']) && isset($orders[trim(str_replace('asc', '', str_replace('desc', '', $_GET['order'])))]) ? $_GET['order'] : 'sales desc', // tyioocom
                'fields' => 's.praise_rate,s.im_qq,s.im_ww,', // tyioocom
                'limit' => $page['limit'],
                'conditions_tt' => $param['keyword'],
                    ), null, false, true, $total_found,$backkey);
            
//            $tb002 = $this->getMillisecond();
//            echo 'get goodslist:' . ($tb002 - $tb001);
//            $tb003 = $this->getMillisecond();
            /* 推荐商品类型，相当于广告宝贝。当用户进入默认搜款页时，还没有加入其它搜索条件;则加入一组51网推荐的商品 */
            /*
            if (count($_GET) == 2) {
                $recom_mod = & m('recommend');
                $recom_goods_list = $recom_mod->get_my_recommended_goods(SEARCH_RECOMMEND_GOODSTYPE);
            }*/

            /* 合并搜索结果和51推荐商品,商品数大于默认一页的数目 */
           /* if (!empty($recom_goods_list)) {
                //添加zwd51_recommended用于表示为51网推荐
                foreach ($recom_goods_list as $rkey => $rgoods) {
                    $recom_goods_list[$rkey]['zwd51_recommended'] = 1;
                }

                if (empty($goods_list)) {
                    $goods_list = $recom_goods_list;
                } else {
                    foreach ($goods_list as $key => $goods) {
                            array_push($recom_goods_list, $goods);
                    }
                    $goods_list = $recom_goods_list;
                }
            }*/

            /* 当搜索结果为0，也没有推荐商品。 则按上架时间推荐一组给用户 */
            if (!$goods_list) {
                $goods_list = $goods_mod->get_list2(array(
                    'conditions' => 'if_show=1 AND closed=0 '.$conditions_store_ids,
                    'order' => 'sort_order asc',
                    'fields' => 's.praise_rate,s.im_qq,s.im_ww,',
                    'limit' => $page['limit'],
                        ), null, false, true, $total_found,$backkey);
                $this->assign('goods_list_order', 1);
            }

            $goods_list = $this->_format_goods_list($goods_list);

            /* 当用户登录时，如果商品为已收藏，则标记为已收藏 */
            if ($this->visitor->get('user_id')) {
                $goods_list = $this->_add_collect_flag_goods_list($goods_list);
            }
            if (count($_GET) == 2) {
                $cache_server->set($indexkey, $goods_list, 7200);
                $cache_server->set($indexkey . '_total_found', $total_found, 7200);
            }
        } else {
            $goods_list = $data;
            $total_found = $cache_server->get($indexkey . '_total_found');
        }
        $this->assign('goods_list', $goods_list);
        /* 分页信息 */
        $page = $this->_get_page(NUM_PER_PAGE);
        if ($total_found) {
            $page['item_count'] = $total_found;
        } else {
            $page['item_count'] = $stats['total_count'];
        }
        $this->_format_page($page);
        $this->assign('page_info', $page);

        /* 商品展示方式 */
        $display_mode = ecm_getcookie('goodsDisplayMode');
        if (empty($display_mode) || !in_array($display_mode, array('list', 'squares'))) {
            $display_mode = 'squares'; // 默认格子方式
        }
        $this->assign('display_mode', $display_mode);

        /* 取得导航 */
        $this->assign('navs', $this->_get_navs());

        $this->import_resource(array(
            'script' => 'jquery.plugins/jquery.infinitescroll.js,jquery.plugins/jquery.masonry.js,jBox/jquery.jBox.src.js',
            'style' => 'jBox/jbox.css',
        ));

        /* 当前位置 */
        $cate_id = isset($param['cate_id']) ? $param['cate_id'] : 0;
        $this->_curlocal($this->_get_goods_curlocal($cate_id));

        /* 配置seo信息 */
        $this->_config_seo($this->_get_seo_info('goods', $cate_id));

//        $t01 = time();
//       echo 'index new goods ;'.($t01-$t00). '<br>';	
        if($backkey && !empty($backkey)){
             //$param['keyword'][0] = '对不起您要的关键字没有找到,帮您搜索:'.$backkey;
             $this->assign("kw_search_tips",'对不起您要的关键字没有找到,帮您搜索:'.$backkey);
        }
       
        $this->assign('filters', $this->_get_filter($param));
        $this->assign("current_local_plaction",Lang::get('behalf_realzone'));
        //$this->assign('apply_btn',array('text'=>Lang::get('apply_join_behalf_realzone'),'url'=>'index.php?app=behalf_realzone&act=apply'));
        $this->display('behalf.realzone.goods.html');
//        $tb004 = $this->getMillisecond();
//        echo '<br> after display ' . ($tb004 - $tb003);
    }
    private function _get_behalf_realzone_stores()
    {
        /* 筛选条件 */
        $model_storerealzone = & m('storerealityzone');
        $results = $model_storerealzone->getCol("SELECT store_id FROM ".$model_storerealzone->table." WHERE state='1'");

        return $results;
    }

    
    /**
     * 申请加入代发区
     */
    function apply()
    {
       
        $user_id = $this->visitor->get('user_id');
        /* 只有登录的用户才可申请 */
        if (!$this->visitor->has_login)
        {
            $this->login();
            return;
        }
        
        /*没有店铺不能再申请 */
        $store_mod =& m('store');
        $store = $store_mod->get($user_id);
        if ($store['state'] != '1')
        {
            $this->show_warning('store_not_open');
            return;
        }
        
        $storerealzone_model = & m('storerealityzone');
        $storerealzone = $storerealzone_model->get($user_id);
        
        if ($$storerealzone['state'] == 1)
        {
            $this->show_warning('user_has_behalf_area');
            return;
        }
        elseif($$storerealzone['state'] === '0')
        {
            $this->show_warning('user_has_application');
            return;
        }
        
        $my_money = & m('my_money')->getRow("select * from " . DB_PREFIX . "my_money where user_id='{$user_id}'");
        
        if(!IS_POST)
        {            
            $this->assign('my_money', $my_money);
            
            $this->_config_seo('title', Lang::get('title_step1') . ' - ' . Conf::get('site_title'));
            $this->display('behalf_goods.apply.step1.html');
        }
        else 
        {
            if($my_money['money'] < 1000)
            {
                $this->show_warning('user_money_notenough');
                return;
            }
            //开始数据库事务
            $db_transaction_begin = db()->query("START TRANSACTION");
            if($db_transaction_begin === false)
            {
                $this->pop_warning('fail_caozuo');
                return;
            }
            $db_transaction_success = true;//默认事务执行成功，不用回滚
            
            include_once(ROOT_PATH.'/app/fakemoney.app.php');
            $fakemoneyapp = new FakeMoneyApp();
            
            $result_dj = $fakemoneyapp->manuFro($user_id,1000);
            
            $result_dj == false && $db_transaction_success=false;
            
            $affect_rows = $storerealzone_model->add(array('store_id'=>$user_id,'state'=>'1','category'=>'freeze'));
            $affect_rows == false && $db_transaction_success=false;
            
            if($db_transaction_success === false)
            {
                db()->query("ROLLBACK");//回滚
            }
            else
            {
                db()->query("COMMIT");//提交
            }
            
            //db()->query("END");
            if($db_transaction_success === false)
            {
                $this->pop_warning("caozuo_fail");
                return;
            }
           
            $this->show_message('apply_ok','index', 'index.php?app=behalf_goods');
        }
        
     
        
    }

  

    function _format_goods_list($goods_list) {
        $store_mod = & m('store');
        $sgrade_mod = & m('sgrade');
       // $image_mod = & m('goodsimage');

        $step = intval(Conf::get('upgrade_required'));
        $step < 1 && $step = 5;

        $sgrades = $sgrade_mod->get_options();

        foreach ($goods_list as $key => $goods) {
            $goods_list[$key]['credit_image'] = $this->_view->res_base . '/images/' . $store_mod->compute_credit($goods['credit_value'], $step);
            empty($goods['default_image']) && $goods_list[$key]['default_image'] = Conf::get('default_goods_image');
            $goods_list[$key]['grade_name'] = $sgrades[$goods['sgrade']];

            /* 加载商品小图 tyioocom */
            /* $goods_list[$key]['_images'] = array_values($image_mod->find(array(
                        'conditions' => "goods_id=" . $goods['goods_id'],
                        'order' => 'sort_order',
                        'fields' => 'thumbnail,image_url',
                        'limit' => 4
                    ))); */
        }
        return $goods_list;
    }

    /**
     *  当用户登录了，标记其已收藏的商品
     * @param unknown $goods_list
     */
    function _add_collect_flag_goods_list($goods_list) {
        $mod_member = & m('member');
        $collect_goods = $mod_member->getRelatedData('collect_goods', $this->visitor->get('user_id'));
        $collect_goods_ids = array();
        foreach ($collect_goods as $goods) {
            $collect_goods_ids[] = $goods['goods_id'];
        }
        foreach ($goods_list as $goods_id => $goods) {
            if (in_array($goods['goods_id'], $collect_goods_ids)) {
                //加入collect_goods标记
                $goods_list[$goods_id]['collect_goods'] = 1;
            }
        }
        return $goods_list;
    }

    function _get_ultimate_store() {
        $store = array();
        $brand_name = trim($_GET['brand']);
        $cate_id = intval($_GET['cate_id']);
        $keyword = trim($_GET['keyword']);

        $conditions = '';
        if (!empty($brand_name)) {
            $brand_mod = &m('brand');
            $brand = $brand_mod->get(array('conditions' => "brand_name='" . $brand_name . "'", 'fields' => 'brand_id,brand_logo'));
            if ($brand) {
                $conditions = ' AND brand_id=' . $brand['brand_id'];
            } else {
                $conditions = ' AND brand_id="" ';
            }
        } elseif (!empty($keyword)) {
            $conditions = " AND keyword='" . $keyword . "' ";
        } elseif (!empty($cate_id)) {
            $conditions = ' AND cate_id=' . $cate_id;
        }
        import('init.lib');
        $init = new Init_SearchApp();

        return $init->_get_ultimate_store($conditions, $brand);
    }

    /* 列表页排行，推荐商品 */

    function _get_list_goods($param, $type = 'recommend') {
        $conditions = $recommended = '';
        $goods_mod = & m('goods');
        if (isset($param['cate_id']) && $param['cate_id'] > 0) {
            $gcategory_mod = & bm('gcategory');
            $cate_ids = implode(",", $gcategory_mod->get_descendant_ids($param['cate_id']));
            $conditions .= " AND cate_id IN (" . $cate_ids . ")";
        }
        if ($type == 'search_rec') {
            $order = 'sales desc,goods_id desc';
            $limit = 5;
            $conditions .= " AND goods_name lIKE '%" . $param['keyword'][0] . "%' ";
        } elseif ($type == "owner_rec") {
            $order = 'views desc,goods_id desc';
            $limit = 5;
        } else {
            $order = 'recommended desc,goods_id desc';
            $recommended = ' AND recommended=1 ';
            $limit = 6;
        }
        $data = $goods_mod->find(array(
            "conditions" => "if_show=1 AND closed=0 " . $recommended . $conditions,
            "order" => $order,
            "join" => "has_goodsstatistics",
            "fields" => "g.goods_id,default_image,price,goods_name,sales",
            "limit" => $limit
                ));

        // 如果按照商品的条件，得到的商品数为空，为了保持页面的美观，随机读取最新的商品
        if (empty($data)) {
            $data = $goods_mod->find(array(
                'conditions' => 'if_show=1 AND closed=0 ',
                "order" => $order,
                "join" => "has_goodsstatistics",
                "fields" => "g.goods_id,default_image,price,goods_name,sales",
                "limit" => $limit
                    ));
        }

        return $data;
    }

    /* 搜索店铺 */
    
    function store() {
        /* 取得导航 */
        $this->assign('navs', $this->_get_navs());
    
        $cache_server = & cache_server();
        $isfirst = false;
        $indexkey = 'store_lists_brz_';
        $data = $cache_server->get($indexkey);
        if (!$data || empty($data)) {
            $isfirst = true;
        }
        if ($isfirst || count($_GET) > 2) {
    
    
            /* 取得该分类及子分类cate_id */
            $cate_id = empty($_GET['cate_id']) ? 0 : intval($_GET['cate_id']);
            $cate_ids = array();
            $condition_id = '';
            if ($cate_id > 0) {
                $scategory_mod = & m('scategory');
                $cate_ids = $scategory_mod->get_descendant($cate_id);
            }
    
            /* 店铺分类检索条件 */
            $condition_id = implode(',', $cate_ids);
            $condition_id && $condition_id = ' AND cate_id IN(' . $condition_id . ')';
    
            //mkid  如果有商场id,得出它的层级
            $market_param = array();
            //取得所有市场
            $market_mod = & m('market');
            $store_markets = $market_mod->get_list(1);
            $condition_market = '';
            $condition_service = '';
            $default_enter_store = false;
            if (isset($_GET['mkid']) && intval($_GET['mkid']) > 0) {
                $market_param['mkid'] = $mkid = intval($_GET['mkid']);
            } elseif (count($_GET) == 2) {
                //默认第一个市场
                //is_array($store_markets) && $market_param['mkid'] =$mkid = key($store_markets);
                $default_enter_store = true;
            }
            $market_param['mlayer'] = $market_mod->get_layer($mkid, true);
            //如果不是市场，则剔除
            if ($market_param['mlayer'] != 2) {
                unset($market_param['mkid']);
            }
    
            if (isset($_GET['fid']) && intval($_GET['fid']) > 0) {
                $market_param['fid'] = $fid = intval($_GET['fid']);
                $market_param['flayer'] = $market_mod->get_layer($fid, true);
                //如果不是楼层，则剔除
                if ($market_param['flayer'] != 3) {
                    unset($market_param['fid']);
                }
            }
            if (isset($_GET['service_detail']) && intval($_GET['service_detail']) == 1) {
                $market_param['service_detail'] = intval($_GET['service_detail']);
            }
            if (isset($_GET['service_send']) && intval($_GET['service_send']) == 1) {
                $market_param['service_send'] = intval($_GET['service_send']);
            }
            if (isset($_GET['service_cash']) && intval($_GET['service_cash']) == 1) {
                $market_param['service_cash'] = intval($_GET['service_cash']);
            }
            if (isset($_GET['service_pattern']) && intval($_GET['service_pattern']) == 1) {
                $market_param['service_pattern'] = intval($_GET['service_pattern']);
            }
            /* 查询市场，非楼层 */
            if (isset($market_param['mkid']) && (intval($market_param['mlayer']) == 2)) {
                $store_market_floors = $market_mod->get_list($market_param['mkid']);
            }
            if (OEM == 'nc') {
                $f_markets = $market_mod->get_sm_list(1);
                if (count($f_markets) != 1) {
                    $this->show_warning('no_single');
                }
                $f_markets = array_values($f_markets);
                $store_market_floors = $market_mod->get_list($f_markets[0]['mk_id']);
            }
    
            if (isset($market_param['service_send'])) {
                $condition_service .= " AND serv_sendgoods = 1"; //代发
            }
            if (isset($market_param['service_cash'])) {
                $condition_service .= " AND serv_refund = 1";  //退现
            }
            if (isset($market_param['service_pattern'])) {
                $condition_service .= " AND serv_exchgoods = 1"; //换款
            }
    
            if ($market_param['mkid'] > 1) {
                $mids = $market_mod->get_list($market_param['mkid']);
                $mids_arr = array($market_param['mkid']);
                foreach ($mids as $v) {
                    $mids_arr[] = $v['mk_id'];
                }
                $condition_market = " AND mk_id " . db_create_in($mids_arr);
            }
            if (($market_param['mkid'] > 1) && ($market_param['fid'] > 1)) {
                $condition_market = " AND mk_id = " . $market_param['fid'];
            }
            if (OEM == 'nc' && ($market_param['fid'] > 1)) {
                $condition_market = " AND mk_id = " . $market_param['fid'];
            }
            //            var_dump($_GET['keyword']);
            /* 其他检索条件 */
            $conditions = $this->_get_query_conditions(array(
                /* array(//店铺名称
                 'field' => 'store_name',
                    'equal' => 'LIKE',
                    'assoc' => 'AND',
                    'name' => 'keyword',
                    'type' => 'string',
                ), */
                /*  array(//档口地址
                 'field' => 'dangkou_address',
                    'equal' => 'LIKE',
                    'assoc' => 'OR',
                    'name' => 'keyword',
                    'type' => 'string',
                ), */
                array(//地区名称
                    'field' => 'region_name',
                    'equal' => 'LIKE',
                    'assoc' => 'AND',
                    'name' => 'region_name',
                    'type' => 'string',
                ),
                array(//地区id
                    'field' => 'region_id',
                    'equal' => '=',
                    'assoc' => 'AND',
                    'name' => 'region_id',
                    'type' => 'string',
                ),
                array(//店铺等级id
                    'field' => 'sgrade',
                    'equal' => '=',
                    'assoc' => 'AND',
                    'name' => 'sgrade',
                    'type' => 'string',
                ),
                array(//是否推荐
                    'field' => 'recommended',
                    'equal' => '=',
                    'assoc' => 'AND',
                    'name' => 'recommended',
                    'type' => 'string',
                ),
                array(//好评率
                    'field' => 'praise_rate',
                    'equal' => '>',
                    'assoc' => 'AND',
                    'name' => 'praise_rate',
                    'type' => 'string',
                ),
                array(//商家用户名
                    'field' => 'user_name',
                    'equal' => 'LIKE',
                    'assoc' => 'AND',
                    'name' => 'user_name',
                    'type' => 'string',
                ),
            ));
    
            if($_GET['keyword'])
            {
                $conditions .= " AND ( store_name like '%".trim($_GET['keyword'])."%' OR dangkou_address like '%".trim($_GET['keyword'])."%' OR address like '%".trim($_GET['keyword'])."%' )";
            }
    
            //  tyioocom  safe care
            $orders = array(
                'sales desc',
                'sales asc',
                'price desc',
                'price asc',
                'add_time desc',
                'add_time asc',
                'comments desc',
                'comments asc',
                'credit_value desc',
                'credit_value asc',
                'views desc',
                'views asc',
            );
            $step = intval(Conf::get('upgrade_required'));
            //echo "step:".$step;
            $step < 1 && $step = 5;
            $level_1 = $step * 5;
            $level_2 = $level_1 * 6;
            $level_3 = $level_2 * 6;
            if ($_GET['credit_value']) {
                switch (intval($_GET['credit_value'])) {
                    case 1;
                    $credit_condition = ' AND credit_value<' . $level_1 . ' ';
                    break;
                    case 2;
                    $credit_condition = ' AND credit_value<' . $level_2 . ' AND credit_value>=' . $level_1 . ' ';
                    break;
                    case 3;
                    $credit_condition = ' AND credit_value<' . $level_3 . ' AND credit_value>=' . $level_2 . ' ';
                    break;
                    case 4;
                    $credit_condition = ' AND credit_value>=' . $level_3 . ' ';
                    break;
                }
            }
            $model_store = & m('store');
            
            $model_storerealzone = & m('storerealityzone');
            $brz_stores_ids = $model_storerealzone->getCol("SELECT store_id FROM ".$model_storerealzone->table);
            $conditions .=" AND ".db_create_in($brz_stores_ids,'s.store_id');
            //$regions = $model_store->list_regions();
            //$page   =   $this->_get_page(200);   //获取分页信息
            //if (!$default_enter_store) {  //不是默认进入批发市场
                $stores = $model_store->find(array(
                    'conditions' => 'state = ' . STORE_OPEN . $credit_condition . $condition_id . $condition_service . $condition_market .$conditions,
                    //'limit'   =>$page['limit'],
                    //'fields'  =>'store_name,user_name,sgrade,store_logo,recommended,praise_rate,credit_value,s.im_qq,im_ww,business_scope,region_name,serv_sendgoods,serv_refund,serv_exchgoods,serv_golden,dangkou_address,mk_name,shop_http,see_price',
                    'fields' => 'store_name,dangkou_address,mk_id,mk_name,credit_value,recommended,serv_refund',
                    'order' => empty($_GET['order']) || !in_array($_GET['order'], $orders) ? 'credit_value DESC' : $_GET['order'], //  tyioocom $orders
                    'join' => 'has_scategory', //belongs_to_user,
                    //'count'   => true   //允许统计
                ));
             //print_r($stores);   
               
           /*  } else {   //默认进入批发市场，且没有点击市场
                $stores = $model_store->find(array(
                    'conditions' => 'state = ' . STORE_OPEN . $credit_condition . $condition_id . $condition_service . $condition_market . $conditions,
                    'limit' => "0,200",
                    //'fields'  =>'store_name,user_name,sgrade,store_logo,recommended,praise_rate,credit_value,s.im_qq,im_ww,business_scope,region_name,serv_sendgoods,serv_refund,serv_exchgoods,serv_golden,dangkou_address,mk_name,shop_http,see_price',
                    'fields' => 'store_name,dangkou_address,mk_id,mk_name,credit_value,recommended,serv_refund',
                    'order' => empty($_GET['order']) || !in_array($_GET['order'], $orders) ? 'credit_value DESC' : $_GET['order'], //
                    //'join' => 'has_scategory', //belongs_to_user,
                    //'count'   => true   //允许统计
                ));
            } */
            //dump($stores);
            $model_goods = &m('goods');
            //$order_mod=&m('order');
            $sgrade_mod = &m('sgrade');
    
            foreach ($stores as $key => $store) {
                /* $goods_list = $model_goods->find(array(
                 'conditions'=>'store_id='. $store['store_id'],
                 'order'     =>'add_time desc',
                 'limit'     =>10,
                 'fields'=>'goods_name,default_image,price'
                 ));
    
                $stores[$key]['goods_list'] = array_chunk($goods_list,5); */
    
                /* $goods_list = $model_goods->find(array(
                 'conditions'=>'store_id='. $store['store_id']." AND realpic = 1",
                 'order'     =>'add_time desc',
                 'limit'     =>1,
                 'fields'=>'goods_name,default_image,price'
                 ));
                if(!empty($goods_list))
                {
                $stores[$key]['realpic'] = 1;
                } */
    
    
                /* $order=$order_mod->find(array('conditions'=>'status=40 AND seller_id='.$store['store_id'],'fields'=>'order_id'));
                 $stores[$key]['store_sold']=count($order);
    
    
                 $sgrade=$sgrade_mod->get(array('conditions'=>'grade_id='.$store['sgrade'],'fields'=>'grade_name'));
                 $stores[$key]['sgrade_name']=$sgrade['grade_name']; */
    
                //店铺logo
                //empty($store['store_logo']) && $stores[$key]['store_logo'] = Conf::get('default_store_logo');
                //商品数量
                //$stores[$key]['goods_count'] = $model_goods->get_count_of_store($store['store_id']);
                //等级图片
                $stores[$key]['credit_image'] = $this->_view->res_base . '/images/' . $model_store->compute_credit($store['credit_value'], $step);
            }
    
            /* 有实拍的店铺过滤 */
            if (isset($market_param['service_detail'])) {
                foreach ($stores as $key => $store) {
                    if (!isset($store['realpic'])) {
                        unset($stores[$key]);
                    }
                }
            }
            //展示给前台
            $storelist = array();
            //如果是有楼层
            $floorlist = array();
            if ($store_market_floors && $stores) {//进入了某个市场
                foreach ($store_market_floors as $mkey => $mvalue) {
                    $storelist[$mkey]['mkid'] = $mvalue['mk_id'];
                    $storelist[$mkey]['mk_name'] = $mvalue['mk_name'];
                    $floorlist[] = $mkey;
                }
                foreach ($stores as $skey => $svalue) {
                    if (in_array($svalue['mk_id'], $floorlist)) {
                        $storelist[$svalue['mk_id']]['stores'][] = $svalue;
                    }
                }
            } elseif (isset($market_param['fid'])) {
                $storelist = array();
                $queryfloor = $market_mod->get($market_param['fid']);
                $storelist[$queryfloor['mk_id']]['mk_id'] = $queryfloor['mk_id'];
                $storelist[$queryfloor['mk_id']]['mk_name'] = $queryfloor['mk_name'];
                $storelist[$queryfloor['mk_id']]['stores'] = $stores;
            } 
           else {
                $storelist[0]['mk_id'] = 1;
                //$storelist[0]['mk_name'] = $default_enter_store ? Lang::get('site51_200st_shops') : '';
                $storelist[0]['stores'] = $stores;
            }
            /*
             //查询市场 并且有楼层 ，不是默认进入
             if ($store_market_floors && $stores && !$default_enter_store) {
             $storelist = array();
             $floorlist = array();
             foreach ($store_market_floors as $mkey => $mvalue) {
             $storelist[$mkey]['mkid'] = $mvalue['mk_id'];
             $storelist[$mkey]['mk_name'] = $mvalue['mk_name'];
             $floorlist[] = $mkey;
             }
    
             foreach ($stores as $skey => $svalue) {
             if (in_array($svalue['mk_id'], $floorlist)) {
             $storelist[$svalue['mk_id']]['stores'][] = $svalue;
             }
             }
             } elseif ($default_enter_store) {
             //默认进入
             $storelist = array();
             $storelist[0]['mk_id'] = 1;
             $storelist[0]['mk_name'] = Lang::get('site51_200st_shops');
             $storelist[0]['stores'] = $stores;
             }
    
             if (!$store_market_floors && $stores && !$default_enter_store && $condition_id) {
             //默认进入
             $storelist = array();
             $cate_query = $scategory_mod->get($cate_id);
             $storelist[0]['mk_id'] = $cate_id;
             $storelist[0]['mk_name'] = $cate_query['cate_name'];
             $storelist[0]['stores'] = $stores;
             }
             //兼容南城网
             if (isset($market_param['fid']) && $stores) {
             $storelist = array();
             $queryfloor = $market_mod->get($market_param['fid']);
             $storelist[$queryfloor['mk_id']]['mk_id'] = $queryfloor['mk_id'];
             $storelist[$queryfloor['mk_id']]['mk_name'] = $queryfloor['mk_name'];
             $storelist[$queryfloor['mk_id']]['stores'] = $stores;
             } */
            if (count($_GET) == 2) {
                $cache_server->set($indexkey, $storelist, 7200);
                $cache_server->set($indexkey . "_floor", $store_market_floors, 7200);
                $cache_server->set($indexkey . "_market", $store_markets, 7200);
            }
        } else {
            $storelist = $data;
            $store_markets = $cache_server->get($indexkey . "_market");
            $store_market_floors = $cache_server->get($indexkey . "_floor");
        }
        //dump($storelist);
        //$page['item_count']=$model_store->getCount();   //获取统计数据
        //$this->_format_page($page);
        $this->assign('sgrades', $this->get_sgrade());
    
        /* 当前位置 */
        $this->_curlocal($this->_get_store_curlocal($cate_id));
        $scategorys = $this->_list_scategory();
    
    
        //$this->assign('tencent_maps', ecm_json_encode(Lang::get('tencent_maps')));
        $this->assign('markets', $store_markets);
        $this->assign('floors', $store_market_floors);
    
        $this->assign('markets', $store_markets);
        $this->assign('floors', $store_market_floors);
    //print_r($storelist);
        $this->assign('stores', $storelist);
        //$this->assign('regions', $regions);
        //$this->assign('cate_id', $cate_id);
        $this->assign('scategorys', $scategorys);
        //$this->assign('page_info', $page);
        $this->import_resource(array(
            'style' => 'jquery.plugins/popModal/jquery.webui-popover.min.css',
            'script' => 'jquery.plugins/scrollIt.min.js',
        ));
        /* 配置seo信息 */
        $this->_config_seo($this->_get_seo_info('store', $cate_id));
        $this->display('behalf.realzone.store.html');
    }

    /* 获取店铺等级 */

    function get_sgrade() {
        $sgrade_mod = &m('sgrade');
        $sgrades = $sgrade_mod->find();
        $result = array();
        foreach ($sgrades as $k => $sgrade) {
            $result[$sgrade['grade_id']] = $sgrade['grade_name'];
        }
        return $result;
    }

    // tyioocom 过滤非法参数
    function _check_query_param_by_orders() {
        $order = $_GET['order'];
        if (empty($order)) {
            return true;
        }
        $orders = array(
            'sales desc',
            'sales asc',
            'price desc',
            'price asc',
            'add_time desc',
            'add_time asc',
            'comments desc',
            'comments asc',
            'credit_value desc',
            'credit_value asc',
            'views desc',
            'views asc',
        );

        if (in_array($order, $orders)) {
            return true;
        } else {
            return false;
        }
    }

    function groupbuy() {
        $conditions = '1=1';
        if (isset($_GET['recommend']) && $_GET['recommend'] != '') {
            $recommend = intval($_GET['recommend']);
            $conditions .=' AND gb.recommended=' . $recommend;
        }

        if ($_GET['state'] == 'on') {
            $conditions .= ' AND gb.state =' . GROUP_ON . ' AND gb.end_time>' . gmtime();
        } elseif ($_GET['state'] == 'end') {
            $conditions .= ' AND (gb.state=' . GROUP_ON . ' OR gb.state=' . GROUP_END . ') AND gb.end_time<=' . gmtime();
        } else {
            $conditions .= $this->_get_query_conditions(array(
                array(//按团购状态搜索
                    'field' => 'gb.state',
                    'name' => 'state',
                    'handler' => 'groupbuy_state_translator',
                )
                    ));
        }
        $conditions .= $this->_get_query_conditions(array(
            array(//活动名称
                'field' => 'group_name',
                'equal' => 'LIKE',
                'assoc' => 'AND',
                'name' => 'keyword',
                'type' => 'string',
            ),
                ));
        $page = $this->_get_page(NUM_PER_PAGE);   //获取分页信息
        $groupbuy_mod = &m('groupbuy');
        $groupbuy_list = $groupbuy_mod->find(array(
            'conditions' => $conditions,
            'fields' => 'gb.*,g.default_image,g.price,default_spec,s.store_name',
            'join' => 'belong_store, belong_goods',
            'limit' => $page['limit'],
            'count' => true, //允许统计
            'order' => isset($_GET['order']) && isset($orders[$_GET['order']]) ? $_GET['order'] : 'group_id desc',
                ));
        if ($ids = array_keys($groupbuy_list)) {
            $quantity = $groupbuy_mod->get_join_quantity($ids);
        }
        foreach ($groupbuy_list as $key => $groupbuy) {
            $groupbuy_list[$key]['quantity'] = empty($quantity[$key]['quantity']) ? 0 : $quantity[$key]['quantity'];
            $groupbuy['default_image'] || $groupbuy_list[$key]['default_image'] = Conf::get('default_goods_image');
            $groupbuy['spec_price'] = unserialize($groupbuy['spec_price']);
            $groupbuy_list[$key]['group_price'] = $groupbuy['spec_price'][$groupbuy['default_spec']]['price'];
            $groupbuy['state'] == GROUP_ON && $groupbuy_list[$key]['lefttime'] = lefttime($groupbuy['end_time']);
            if ($groupbuy['price'] != 0) {
                $groupbuy_list[$key]['discount'] = round($groupbuy['spec_price'][$groupbuy['default_spec']]['price'] / $groupbuy['price'] * 10, 1);
            } else {
                $groupbuy_list[$key]['discount'] = 0;
            }
        }
        $this->assign('state', array(
            'on' => Lang::get('group_on'),
            'end' => Lang::get('group_end'),
            'finished' => Lang::get('group_finished'),
            'canceled' => Lang::get('group_canceled'))
        );
        $this->assign('orders', $orders);
        // 当前位置
        $this->_curlocal(array(array('text' => Lang::get('groupbuy'))));
        $this->_config_seo('title', Lang::get('groupbuy') . ' - ' . Conf::get('site_title'));
        $page['item_count'] = $groupbuy_mod->getCount();   //获取统计数据
        $this->_format_page($page);
        $this->assign('nav_groupbuy', 1); // 标识当前页面是团购列表，用于设置导航状态
        $this->assign('page_info', $page);
        $this->assign('groupbuy_list', $groupbuy_list);
        $this->assign('recommended_groupbuy', $this->_recommended_groupbuy(2));
        $this->assign('last_join_groupbuy', $this->_last_join_groupbuy(2));
        $this->display('search.groupbuy.html');
    }

    // 推荐团购活动
    function _recommended_groupbuy($_num) {
        $model_groupbuy = & m('groupbuy');
        $data = $model_groupbuy->find(array(
            'join' => 'belong_goods,belong_store', // tyioocom 
            'conditions' => 'gb.recommended=1 AND gb.state=' . GROUP_ON . ' AND gb.end_time>' . gmtime(),
            'fields' => 'group_id, goods.default_image, group_name, gb.end_time, spec_price,gb.min_quantity,gb.store_id,s.store_name,gb.group_image', // tyioocm 
            'order' => 'group_id DESC',
            'limit' => $_num,
                ));

        // tyioocom 
        if ($ids = array_keys($data)) {
            $quantity = $model_groupbuy->get_join_quantity($ids);
        }
        // end 

        foreach ($data as $gb_id => $gb_info) {
            $data[$gb_id]['quantity'] = empty($quantity[$gb_id]['quantity']) ? 0 : $quantity[$gb_id]['quantity']; //  tyioocom
            $price = current(unserialize($gb_info['spec_price']));
            empty($gb_info['default_image']) && $data[$gb_id]['default_image'] = Conf::get('default_goods_image');
            $data[$gb_id]['lefttime'] = lefttime($gb_info['end_time']);
            $data[$gb_id]['price'] = $price['price'];
        }
        return $data;
    }

    // 最新参加的团购
    function _last_join_groupbuy($_num) {
        $model_groupbuy = & m('groupbuy');
        $data = $model_groupbuy->find(array(
            'join' => 'be_join,belong_goods,belong_store',
            'fields' => 'gb.group_id,gb.group_name,gb.group_id,groupbuy_log.add_time,gb.spec_price,goods.default_image,gb.end_time,gb.min_quantity,gb.store_id,s.store_name,gb.group_image',
            // tyioocm 
            'conditions' => 'groupbuy_log.user_id > 0',
            'order' => 'groupbuy_log.add_time DESC',
            'limit' => $_num,
                ));
        // tyioocom 
        if ($ids = array_keys($data)) {
            $quantity = $model_groupbuy->get_join_quantity($ids);
        }
        // end 

        foreach ($data as $gb_id => $gb_info) {
            $data[$gb_id]['quantity'] = empty($quantity[$gb_id]['quantity']) ? 0 : $quantity[$gb_id]['quantity']; //  tyioocom
            $price = current(unserialize($gb_info['spec_price']));
            empty($gb_info['default_image']) && $data[$gb_id]['default_image'] = Conf::get('default_goods_image');
            $data[$gb_id]['lefttime'] = lefttime($gb_info['end_time']); //  tyioocom
            $data[$gb_id]['price'] = $price['price'];
        }
        return $data;
    }

    /* 取得店铺分类 */

    function _list_scategory() {
        $scategory_mod = & m('scategory');
        $scategories = $scategory_mod->get_list(-1, true);

        import('tree.lib');
        $tree = new Tree();
        $tree->setTree($scategories, 'cate_id', 'parent_id', 'cate_name');
        return $tree->getArrayList(1);
    }

    function _get_goods_curlocal($cate_id) {
        $parents = array();
        if ($cate_id) {
            $gcategory_mod = & bm('gcategory');
            $parents = $gcategory_mod->get_ancestor($cate_id, true);
        }

        $curlocal = array(
            array('text' => LANG::get('all_categories'), 'url' => "javascript:dropParam('cate_id')"),
        );
        foreach ($parents as $category) {
            $curlocal[] = array('text' => $category['cate_name'], 'url' => "javascript:replaceParam('cate_id', '" . $category['cate_id'] . "')");
        }
        unset($curlocal[count($curlocal) - 1]['url']);

        return $curlocal;
    }

    function _get_store_curlocal($cate_id) {
        $parents = array();
        if ($cate_id) {
            $scategory_mod = & m('scategory');
            $scategory_mod->get_parents($parents, $cate_id);
        }

        $curlocal = array(
            array('text' => LANG::get('all_categories'), 'url' => url('app=category&act=store')),
        );
        foreach ($parents as $category) {
            $curlocal[] = array('text' => $category['cate_name'], 'url' => url('app=search&act=store&cate_id=' . $category['cate_id']));
        }
        unset($curlocal[count($curlocal) - 1]['url']);
        return $curlocal;
    }

    /**
     * 取得查询参数（有值才返回）
     *
     * @return  array(
     *              'keyword'   => array('aa', 'bb'),
     *              'cate_id'   => 2,
     *              'layer'     => 2, // 分类层级
     *              'brand'     => 'ibm',
     *              'region_id' => 23,
     *              'price'     => array('min' => 10, 'max' => 100),
     *          )
     */
    function _get_query_param() {
        static $res = null;
        if ($res === null) {
            $res = array();

            $market_mod = & m('market');

            // keyword
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            if ($keyword != '') {
                //$keyword = preg_split("/[\s," . Lang::get('comma') . Lang::get('whitespace') . "]+/", $keyword);
                $tmp = str_replace(array(Lang::get('comma'), Lang::get('whitespace'), ' '), ',', $keyword);
                $keyword = explode(',', $tmp);
                sort($keyword);
                $res['keyword'] = $keyword;
            }

            // cate_id
            if (isset($_GET['cate_id']) && intval($_GET['cate_id']) > 0) {
                $res['cate_id'] = $cate_id = intval($_GET['cate_id']);
                $gcategory_mod = & bm('gcategory');
                $gcategory = $gcategory_mod->get($cate_id);
                $res['cate_name'] = empty($gcategory['cate_mname']) ? $gcategory['cate_name'] : $gcategory['cate_mname'];
                $res['layer'] = $gcategory_mod->get_layer($cate_id, true);
            }

            //mk_id  如果有商场id,得出它的层级
            if (isset($_GET['mkid']) && intval($_GET['mkid']) > 0) {
                $res['mkid'] = $mkid = intval($_GET['mkid']);
                $res['mlayer'] = $market_mod->get_layer($mkid, true);
            }

            if (isset($_GET['fid']) && intval($_GET['fid']) > 0) {
                $res['fid'] = $fid = intval($_GET['fid']);
                $res['flayer'] = $market_mod->get_layer($fid, true);
            }

            //根据时间查询
            if (isset($_GET['qt'])) {
                $res['qt'] = trim($_GET['qt']);
            }

            //services
            if (isset($_GET['service_detail']) && (intval($_GET['service_detail']) == 1)) {
                $res['service_detail'] = intval($_GET['service_detail']);
            }
            if (isset($_GET['service_send']) && (intval($_GET['service_send']) == 1)) {
                $res['service_send'] = intval($_GET['service_send']);
            }
            if (isset($_GET['service_cash']) && (intval($_GET['service_cash']) == 1)) {
                $res['service_cash'] = intval($_GET['service_cash']);
            }
            if (isset($_GET['service_pattern']) && (intval($_GET['service_pattern']) == 1)) {
                $res['service_pattern'] = intval($_GET['service_pattern']);
            }

            //date
            if (isset($_GET['agt'])) {
                $res['agt'] = $_GET['agt'];
            }

            // brand
            if (isset($_GET['brand'])) {
                $brand = trim($_GET['brand']);
                $res['brand'] = $brand;
            }

            // region_id
            if (isset($_GET['region_id']) && intval($_GET['region_id']) > 0) {
                $res['region_id'] = intval($_GET['region_id']);
            }

            // price
            if (isset($_GET['price'])) {
                $arr = explode('-', $_GET['price']);
                $min = abs(floatval($arr[0]));
                $max = abs(floatval($arr[1]));
                if ($min * $max > 0 && $min > $max) {
                    list($min, $max) = array($max, $min);
                }

                $res['price'] = array(
                    'min' => $min,
                    'max' => $max
                );
            }

            //color 获取颜色  2015-04-13 by tanaiquan
            if (isset($_GET['color'])) {
                $color = trim($_GET['color']);
                $res['color'] = $color;
            }
            // size 获取尺寸 2015-04-13 by tanaiquan
            if (isset($_GET['dim'])) {
                $dim = trim($_GET['dim']);
                $res['dim'] = $dim;
            }
        }

        return $res;
    }

    /**
     * 取得过滤条件
     */
    function _get_filter($param) {
        static $filters = null;
        if ($filters === null) {
            //echo "filter:".print_r($param)."<br>";
            $filters = array();
            // custom mall
            if (isset($param['cate_id']) && isset($param['cate_name'])) {
                $filters['cate_id'] = array('key' => 'cate_id', 'name' => LANG::get('by_search_category'), 'value' => $param['cate_name']);
            }

            if (isset($param['mkid'])) {
                $market_mod = & m('market');
                $row = $market_mod->get(array(
                    'conditions' => $param['mkid'],
                    'fields' => 'mk_name'
                        ));
                $filters['mkid'] = array('key' => 'mkid', 'name' => LANG::get('mall'), 'value' => $row['mk_name']);
            }
            if (isset($param['fid'])) {
                $market_mod = & m('market');
                $row = $market_mod->get(array(
                    'conditions' => $param['fid'],
                    'fields' => 'mk_name'
                        ));
                $filters['floor_id'] = array('key' => 'fid', 'name' => LANG::get('by_market_floor'), 'value' => $row['mk_name']);
            }

            if (isset($param['agt'])) {
                $filters['agt'] = array('key' => 'agt', 'name' => LANG::get('by_date'), 'value' => $param['agt']);
            }

            if (isset($param['keyword'])) {
                $keyword = join(' ', $param['keyword']);
                $filters['keyword'] = array('key' => 'keyword', 'name' => LANG::get('keyword'), 'value' => $keyword);
            }
            isset($param['brand']) && $filters['brand'] = array('key' => 'brand', 'name' => LANG::get('brand'), 'value' => $param['brand']);

            //service
            if (isset($param['service_detail'])) {
                $filters['service_detail'] = array('key' => 'service_detail', 'name' => LANG::get('serv_detail'), 'value' => LANG::get('having'));
            }
            if (isset($param['service_send'])) {
                $filters['service_send'] = array('key' => 'service_send', 'name' => LANG::get('serv_send'), 'value' => LANG::get('having'));
            }
            if (isset($param['service_cash'])) {
                $filters['service_cash'] = array('key' => 'service_cash', 'name' => LANG::get('serv_cash'), 'value' => LANG::get('having'));
            }
            if (isset($param['service_pattern'])) {
                $filters['service_pattern'] = array('key' => 'service_pattern', 'name' => LANG::get('serv_pattern'), 'value' => LANG::get('having'));
            }

            if (isset($param['region_id'])) {
                // todo 从地区缓存中取
                $region_mod = & m('region');
                $row = $region_mod->get(array(
                    'conditions' => $param['region_id'],
                    'fields' => 'region_name'
                        ));
                $filters['region_id'] = array('key' => 'region_id', 'name' => LANG::get('region'), 'value' => $row['region_name']);
            }
            //返回根据时间查询的值
            if (isset($param['qt'])) {
                if ($param['qt'] == 111)
                    $filters['qt'] = array('key' => 'qt', 'name' => LANG::get('choice_time'), 'value' => LANG::get('all_time'));
                if ($param['qt'] == 1)
                    $filters['qt'] = array('key' => 'qt', 'name' => LANG::get('choice_time'), 'value' => LANG::get('oneday_time'));
                if ($param['qt'] == 7)
                    $filters['qt'] = array('key' => 'qt', 'name' => LANG::get('choice_time'), 'value' => LANG::get('oneweek_time'));
                if ($param['qt'] == 30)
                    $filters['qt'] = array('key' => 'qt', 'name' => LANG::get('choice_time'), 'value' => LANG::get('onemonth_time'));
                if ($param['qt'] == 90)
                    $filters['qt'] = array('key' => 'qt', 'name' => LANG::get('choice_time'), 'value' => LANG::get('threemonth_time'));
                if ($param['qt'] == 180)
                    $filters['qt'] = array('key' => 'qt', 'name' => LANG::get('choice_time'), 'value' => LANG::get('sixmonth_time'));
                if ($param['qt'] == 100)
                    $filters['qt'] = array('key' => 'qt', 'name' => LANG::get('choice_time'), 'value' => LANG::get('oneyear_time'));
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $param['qt'])) {
                    $filters['qt'] = array('key' => 'qt', 'name' => LANG::get('choice_time'), 'value' => $param['qt']);
                }
            }

            if (isset($param['price'])) {
                $min = $param['price']['min'];
                $max = $param['price']['max'];
                if ($min <= 0) {
                    $filters['price'] = array('key' => 'price', 'name' => LANG::get('price'), 'value' => LANG::get('le') . ' ' . price_format($max));
                } elseif ($max <= 0) {
                    $filters['price'] = array('key' => 'price', 'name' => LANG::get('price'), 'value' => LANG::get('ge') . ' ' . price_format($min));
                } else {
                    $filters['price'] = array('key' => 'price', 'name' => LANG::get('price'), 'value' => price_format($min) . ' - ' . price_format($max));
                }
            }
            /* 颜色 add by tanaiquan 2015-04-13 */
            if (isset($param['color'])) {
                $filters['color'] = array('key' => 'color', 'name' => LANG::get('color'), 'value' => $param['color']);
            }
            /* 尺寸 add by tanaiquan 2015-04-13 */
            if (isset($param['dim'])) {
                $filters['dim'] = array('key' => 'dim', 'name' => LANG::get('size'), 'value' => $param['dim']);
            }
        }


        return $filters;
    }

    /**
     * 取得查询条件语句
     *
     * @param   array   $param  查询参数（参加函数_get_query_param的返回值说明）
     * @return  string  where语句
     */
    function _get_goods_conditions($param) {
        /* 组成查询条件 */
        $conditions = " g.if_show = 1 AND g.closed = 0 AND g.default_spec > 0 AND s.state = 1"; // 上架且没有被禁售，店铺是开启状态,已经采集完成的
        if (isset($param['keyword'])) {
            $conditions .= $this->_get_conditions_by_keyword($param['keyword'], ENABLE_SEARCH_CACHE);
        }
        if (isset($param['cate_id'])) {
            $conditions .= " AND g.cate_id_{$param['layer']} = '" . $param['cate_id'] . "'";
        }
        //传过来mkid ,并且是第2层才查询
        if (isset($param['mkid']) && isset($param['mlayer']) && ($param['mlayer'] == 2)) {
            $market_mod = & m('market');
            $mids = $market_mod->get_descendant($param['mkid']);
            $conditions .= " AND s.mk_id " . db_create_in($mids);
        }
        //传过来fid ,并且是第三层才查询
        if (isset($param['fid']) && isset($param['flayer']) && ($param['flayer'] == 3)) {
            $conditions .= " AND s.mk_id = '" . $param['fid'] . "'";
        }

        //筛选服务
        if (isset($param['service_detail'])) {
            $conditions .= " AND g.serv_realpic = " . $param['service_detail'];//g.realpic 更改为 g.serv_realpic
        }
        if (isset($param['service_send'])) {
            $conditions .= " AND s.serv_sendgoods = " . $param['service_send'];
        }
        if (isset($param['service_cash'])) {
            $conditions .= " AND s.serv_refund = " . $param['service_cash'];
        }
        if (isset($param['service_pattern'])) {
            $conditions .= " AND s.serv_exchgoods = " . $param['service_pattern'];
        }

        //根据查询时间
        if (isset($param['qt']) && in_array(intval($param['qt']), array(111, 1, 7, 30, 90, 180, 100))) {
            if ($param['qt'] == 111) {
                //全部时间，不用组条件
            } else if ($param['qt'] == 100) {
                $conditions .= " AND g.add_time <= " . time() . " AND g.add_time >= " . (time() - 365 * 24 * 60 * 60);
            } else {
                $conditions .= " AND g.add_time <= " . time() . " AND g.add_time >= " . (time() - $param['qt'] * 24 * 60 * 60);
            }
        }
        //时间形如 2015-04-14
        if (isset($param['qt']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param['qt'])) {
            $query_date_arr = explode("-", $param['qt']);
            $conditions .= " AND g.add_time <= " . gmmktime(23, 59, 59, intval($query_date_arr[1]), intval($query_date_arr[2]), intval($query_date_arr[0]))
                    . " AND g.add_time >= " . gmmktime(0, 0, 0, intval($query_date_arr[1]), intval($query_date_arr[2]), intval($query_date_arr[0]));
        }

        if (isset($param['brand'])) {
            $conditions .= " AND g.brand = '" . $param['brand'] . "'";
        }
        if (isset($param['region_id'])) {
            $conditions .= " AND s.region_id = '" . $param['region_id'] . "'";
        }
        if (isset($param['price'])) {
            $min = $param['price']['min'];
            $max = $param['price']['max'];
            $min > 0 && $conditions .= " AND g.price >= '$min'";
            $max > 0 && $conditions .= " AND g.price <= '$max'";
        }
        //by date
        if (isset($param['agt'])) {
            $timex = $param['agt'];
            $year = ((int) substr($timex, 0, 4)); //取得年份
            $month = ((int) substr($timex, 5, 2)); //取得月份
            $day = ((int) substr($timex, 8, 2)); //取得几号
            $btime = mktime(0, 0, 0, $month, $day, $year);
            $etime = $btime + 86400;
            $btime > 0 && $conditions .= " AND g.add_time >= '$btime'";
            $etime > 0 && $conditions .= " AND g.add_time < '$etime'";
        }

        /* add by tiq 2015-04-13 */
        if (isset($param['color'])) {
            $conditions .= " AND gs.spec_1 = '" . $param['color'] . "'";
        }
        if (isset($param['dim'])) {
            $conditions .= " AND gs.spec_2 = '" . $param['dim'] . "'";
        }

        return $conditions;
    }

    function _get_goods_conditions2($param) {
        if (!defined('USESPHINX') || USESPHINX != 1) {
            return $this->_get_goods_conditions($param);
        }
        /* 组成查询条件 */
        $conditions = " g.if_show = 1 AND g.closed = 0 AND g.default_spec > 0 AND s.state = 1"; // 上架且没有被禁售，店铺是开启状态,
//        if (isset($param['keyword']))
//        {
//            $conditions .= $this->_get_conditions_by_keyword($param['keyword'], ENABLE_SEARCH_CACHE);
//        }
        if (isset($param['cate_id'])) {
            $conditions .= " AND g.cate_id_{$param['layer']} = '" . $param['cate_id'] . "'";
        }
        //传过来mkid ,并且是第2层才查询
        if (isset($param['mkid']) && isset($param['mlayer']) && ($param['mlayer'] == 2)) {
            $market_mod = & m('market');
            $mids = $market_mod->get_descendant($param['mkid']);
            $conditions .= " AND s.mk_id " . db_create_in($mids);
        }
        //传过来fid ,并且是第三层才查询
        if (isset($param['fid']) && isset($param['flayer']) && ($param['flayer'] == 3)) {
            $conditions .= " AND s.mk_id = '" . $param['fid'] . "'";
        }

        //筛选服务
        if (isset($param['service_detail'])) {
            $conditions .= " AND g.serv_realpic = " . $param['service_detail'];//g.realpic 更改为 g.serv_realpic
        }
        if (isset($param['service_send'])) {
            $conditions .= " AND s.serv_sendgoods = " . $param['service_send'];
        }
        if (isset($param['service_cash'])) {
            $conditions .= " AND s.serv_refund = " . $param['service_cash'];
        }
        if (isset($param['service_pattern'])) {
            $conditions .= " AND s.serv_exchgoods = " . $param['service_pattern'];
        }

        //根据查询时间
        if (isset($param['qt']) && in_array($param['qt'], array(111, 1, 7, 30, 90, 180, 100))) {
            if ($param['qt'] == 111) {
                //全部时间，不用组条件
            } else if ($param['qt'] == 100) {
                $conditions .= " AND g.add_time <= " . time() . " AND g.add_time >= " . (time() - 365 * 24 * 60 * 60);
            } else {
                $conditions .= " AND g.add_time <= " . time() . " AND g.add_time >= " . (time() - $param['qt'] * 24 * 60 * 60);
            }
        }
        //时间形如 2015-04-14
        if (isset($param['qt']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param['qt'])) {
            $query_date_arr = explode("-", $param['qt']);
            $conditions .= " AND g.add_time <= " . gmmktime(23, 59, 59, intval($query_date_arr[1]), intval($query_date_arr[2]), intval($query_date_arr[0]))
                    . " AND g.add_time >= " . gmmktime(0, 0, 0, intval($query_date_arr[1]), intval($query_date_arr[2]), intval($query_date_arr[0]));
        }

        if (isset($param['brand'])) {
            $conditions .= " AND g.brand = '" . $param['brand'] . "'";
        }
        if (isset($param['region_id'])) {
            $conditions .= " AND s.region_id = '" . $param['region_id'] . "'";
        }
        if (isset($param['price'])) {
            $min = $param['price']['min'];
            $max = $param['price']['max'];
            $min >= 0 && $conditions .= " AND g.price >= " . $min;
            $max >= 0 && $conditions .= " AND g.price <= " . $max;
        }
        //by date
        if (isset($param['agt'])) {
            $timex = $param['agt'];
            $year = ((int) substr($timex, 0, 4)); //取得年份
            $month = ((int) substr($timex, 5, 2)); //取得月份
            $day = ((int) substr($timex, 8, 2)); //取得几号
            $btime = mktime(0, 0, 0, $month, $day, $year);
            $etime = $btime + 86400;
            $btime > 0 && $conditions .= " AND g.add_time >= '$btime'";
            $etime > 0 && $conditions .= " AND g.add_time < '$etime'";
        }

        /* add by tiq 2015-04-13 */
        if (isset($param['color'])) {
            $conditions .= " AND gs.spec_1 = '" . $param['color'] . "'";
        }
        if (isset($param['dim'])) {
            $conditions .= " AND gs.spec_2 = '" . $param['dim'] . "'";
        }

        return $conditions;
    }

    /**
     * 根据查询条件取得分组统计信息
     *
     * @param   array   $param  查询参数（参加函数_get_query_param的返回值说明）
     * @param   bool    $cached 是否缓存
     * @return  array(
     *              'total_count' => 10,
     *              'by_category' => array(id => array('cate_id' => 1, 'cate_name' => 'haha', 'count' => 10))
     *              'by_brand'    => array(array('brand' => brand, 'count' => count))
     *              'by_region'   => array(array('region_id' => region_id, 'region_name' => region_name, 'count' => count))
     *              'by_price'    => array(array('min' => 10, 'max' => 50, 'count' => 10))
     *          )
     */
    function _get_group_by_info($param, $cached) {
        $data = false;

        if ($cached) {
            $cache_server = & cache_server();
            $key = 'group_by_info_' . var_export($param, true);
            $data = $cache_server->get($key);
        }

        if ($data === false) {
            $data = array(
                'total_count' => 0,
                'by_category' => array(),
                'by_brand' => array(),
                'by_region' => array(),
                'by_price' => array(),
                'by_market_floor' => array(), //添加接收市场数组
                'by_market' => array(), //添加接收市场数组
            );

            /* 如果有 颜色或尺寸，统计商品总数时，说明要查 ecm_goods_spec   add by tanaiquan 2015-04-13 */

            /*  if(isset($param['color']) || isset($param['dim']))
              {
              $goodsspec_mod =& m('goodsspec');
              $left_join_gs = "LEFT JOIN {$goodsspec_mod->table} gs ON g.goods_id = gs.goods_id ";
              } */
            /* end */

            $no_param_conditions = " g.if_show = 1 AND g.closed = 0 AND s.state = 1";

            $goods_mod = & m('goods');
            $store_mod = & m('store');
            $table = " {$goods_mod->table} g LEFT JOIN {$store_mod->table} s ON g.store_id = s.store_id " . $left_join_gs;
            $conditions = $this->_get_goods_conditions($param);
            $sql = "SELECT COUNT(g.goods_id) FROM {$table} WHERE" . $conditions;  //vdump($sql);
            // $total_count = $goods_mod->getOne($sql); 
            if ($total_count > 0)
                $data['total_count'] = $total_count;
            /* 查找市场 */
            $market_mod = & m('market');
            if (OEM == 'nc') {
                $by_markets = $market_mod->get_sm_list(1);
                if (count($by_markets) != 1) {
                    $this->show_warning('no_single');
                }
                $by_markets = array_values($by_markets);
                $data['by_market_floor'] = $market_mod->get_list($by_markets[0]['mk_id']);
            } else {
                $by_markets = $market_mod->get_list(1);

                //传过来market id ,并且是第三层才查询
                if (isset($param['mkid']) && ($param['mlayer'] == 2)) {
                    $by_market_floor = $market_mod->get_list($param['mkid']);
                    $data['by_market_floor'] = $by_market_floor;
                }
            }
            $data['by_market'] = $by_markets;

            //cate_id < 0 转换为0
            $cate_id_param = isset($param['cate_id']) ? $param['cate_id'] : 0;
            $category_mod = & bm('gcategory');
            $gcategories = $category_mod->get_children($cate_id_param, true);
            //显示cate_mname
            foreach ($gcategories as $cate_id => $cate_obj) {
                if ($cate_obj['cate_mname'] && strtolower(trim($cate_obj['cate_mname'])) != 'null') {
                    $gcategories[$cate_id]['cate_name'] = $cate_obj['cate_mname'];
                }
            }
            $data['by_category'] = array_slice($gcategories, 0, 7);

            if ($cached) {
                $cache_server->set($key, $data, SEARCH_CACHE_TTL);
            }
        }

        return $data;
    }

    /**
     * 根据关键词取得查询条件（可能是like，也可能是in）
     *
     * @param   array       $keyword    关键词
     * @param   bool        $cached     是否缓存
     * @return  string      " AND (0)"
     *                      " AND (goods_name LIKE '%a%' AND goods_name LIKE '%b%')"
     *                      " AND (goods_id IN (1,2,3))"
     */
    function _get_conditions_by_keyword($keyword, $cached) {
        $conditions = false;

        if ($cached) {
            $cache_server = & cache_server();
            $key1 = 'query_conditions_of_keyword_' . join("\t", $keyword);
            $conditions = $cache_server->get($key1);
        }

        if ($conditions === false) {
            if (USESPHINX == 0 || !defined('USESPHINX')) {
                /* 组成查询条件 */
                $conditions = array();
                foreach ($keyword as $word) {
                    $conditions[] = "g.goods_name LIKE '%{$word}%'";
                }
                $conditions = join(' AND ', $conditions);

                /* 取得满足条件的商品数 */
                $goods_mod = & m('goods');
                $sql = "SELECT COUNT(*) FROM {$goods_mod->table} g WHERE " . $conditions;
                $current_count = $goods_mod->getOne($sql);
                if ($current_count > 0) {
                    if ($current_count < MAX_ID_NUM_OF_IN) {
                        /* 取得商品表记录总数 */
                        $cache_server = & cache_server();
                        $key2 = 'record_count_of_goods';
                        $total_count = $cache_server->get($key2);
                        if ($total_count === false) {
                            $sql = "SELECT COUNT(*) FROM {$goods_mod->table}";
                            $total_count = $goods_mod->getOne($sql);
                            $cache_server->set($key2, $total_count, SEARCH_CACHE_TTL);
                        }

                        /* 不满足条件，返回like */
                        if (($current_count / $total_count) < MAX_HIT_RATE) {
                            /* 取得满足条件的商品id */
                            $sql = "SELECT goods_id FROM {$goods_mod->table} g WHERE " . $conditions;
                            $ids = $goods_mod->getCol($sql);
                            $conditions = 'g.goods_id' . db_create_in($ids);
                        }
                    }
                } else {
                    /* 没有满足条件的记录，返回0 */
                    $conditions = "0";
                }
            } else if (USESPHINX == 1) {
                $goods_name = '';
                foreach ($keyword as $word) {
                    $goods_name .= $word;
                }
                $cl = new SphinxClient ();
                $cl->SetServer(getSphinxAddress(), SPHINXPORT);
                $cl->SetArrayResult(true);
                $cl->SetLimits(0, 10000);
                $cl->SetMatchMode(SPH_MATCH_ALL);
                if (defined('OEM')) {
                    $res = $cl->Query($goods_name, "goods_" . OEM);
                } else {
                    $res = $cl->Query($goods_name, "goods");
                }
                if ($res['total'] > 0) {
                    foreach ($res['matches'] as $record) {
                        $ids[] = $record['id'];
                    }
                    $conditions = 'g.goods_id' . db_create_in($ids);
                } else {
                    $conditions = '0=1';
                }
            }

            if ($cached) {
                $cache_server->set($key1, $conditions, SEARCH_CACHE_TTL);
            }
        }

        return ' AND (' . $conditions . ')';
    }

    /* 商品排序方式  edit  tyioocom  */

    function _get_orders() {
        return array(
            '' => Lang::get('default_order'),
            'sales' => Lang::get('sales_desc'),
            'price' => Lang::get('price'),
            'views' => Lang::get('views'),
            'add_time' => Lang::get('add_time'),
            //'comments'     => Lang::get('comment'),
            'credit_value' => Lang::get('credit_value'),
        );
    }

    function _get_seo_info($type, $cate_id) {
        $seo_info = array(
            'title' => '',
            'keywords' => '',
            'description' => ''
        );
        $parents = array(); // 所有父级分类包括本身
        switch ($type) {
            case 'goods':
                /* if ($cate_id)
                  {
                  $gcategory_mod =& bm('gcategory');
                  $parents = $gcategory_mod->get_ancestor($cate_id, true);
                  $parents = array_reverse($parents);
                  }
                  $filters = $this->_get_filter($this->_get_query_param());
                  foreach ($filters as $k => $v)
                  {
                  $seo_info['keywords'] .= $v['value']  . ',';
                  } */
                $seo_info['title'] = Lang::get('goods_title');
                $seo_info['keywords'] = Lang::get('goods_keywords');
                $seo_info['description'] = Lang::get('goods_description');
                break;
            case 'newgoods':
                $seo_info['title'] = Lang::get('newgoods_title');
                $seo_info['keywords'] = Lang::get('newgoods_keywords');
                $seo_info['description'] = Lang::get('newgoods_description');
                break;
            case 'store':
                $seo_info['title'] = Lang::get('store_title');
                $seo_info['keywords'] = Lang::get('store_keywords');
                $seo_info['description'] = Lang::get('store_description');
                break;
            /* if ($cate_id)
              {
              $scategory_mod =& m('scategory');
              $scategory_mod->get_parents($parents, $cate_id);
              $parents = array_reverse($parents);
              } */
        }

        /* foreach ($parents as $key => $cate)
          {
          $seo_info['title'] .= $cate['cate_name'] . ' - ';
          $seo_info['keywords'] .= $cate['cate_name']  . ',';
          if ($cate_id == $cate['cate_id'])
          {
          $seo_info['description'] = $cate['cate_name'] . ' ';
          }
          }
          $seo_info['title'] .= Lang::get('searched_'. $type) . ' - ' .Conf::get('site_title');
          $seo_info['keywords'] .= Conf::get('site_title');
          $seo_info['description'] .= Conf::get('site_title'); */
        return $seo_info;
    }

    function _get_query_weekdate() {
        $query_weekdate = array();
        for ($i = 0; $i < 30; $i++) {
            $query_weekdate[] = date("Y-m-d", time() - $i * 86400);
        }
        return $query_weekdate;
    }

    /**
     * 南城市场地图
     */
    function nc_map() {
        $store_mod = & m('store');
        $stores = $store_mod->getAll("SELECT store_id as id,dangkou_address as address,store_name as name FROM " . DB_PREFIX . "store where state > 0");
        //dump($stores);

        $this->assign("stores", ecm_json_encode($stores));
        $this->display('search.nc_map.html');
    }

    /**
     * 获取店铺详情
     */
    function get_store_info() {
        $sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        if ($sid <= 0) {
            $this->json_error('store_not_exist');
            return;
        }
        $mod_store = & m('store');
        $store = $mod_store->get($sid);
        //主营
        if(!$store['business_scope'])
        {
        	$business_scope = $mod_store->getRelatedData('has_scategory',$sid);
        	if($business_scope)
        	{
        		$business_scope = array_values($business_scope);
        		$store['business_scope'] = $business_scope[0]['cate_name'];
        	}
        }
        empty($store['store_logo']) && $store['store_logo'] = Conf::get('default_store_logo');
        //$sgrade_mod = &m('sgrade');
        //$sgrade = $sgrade_mod->get(array('conditions' => 'grade_id=' . $store['sgrade'], 'fields' => 'grade_name'));
        $step = intval(Conf::get('upgrade_required'));
        //echo "step:".$step;
        $step < 1 && $step = 5;
        $store['sgrade_name'] = $sgrade['grade_name'];
        //等级图片
        $template_name = $this->_get_template_name();
        $style_name = $this->_get_style_name();
        $store['credit_image'] = SITE_URL . "/themes/mall/{$template_name}/styles/{$style_name}" . '/images/' . $mod_store->compute_credit($store['credit_value'], $step);


        //dump($store);
        $this->assign("store", $store);
        $this->display("search.get.store.info.html");
    }
    
    function update_behalfarea_stores()
    {
        $model_discount_stores = & m('storediscount');
        $discount_stores = $model_discount_stores->getCol("select distinct store_id from ".$model_discount_stores->table);
        if($discount_stores)
        {
            $count = 0;
            $model_storebehalfarea = & m('storebehalfarea');
            foreach ($discount_stores as $sid)
            {
                $store_info = $model_storebehalfarea->get($sid);
                if(empty($store_info))
                {
                    $model_storebehalfarea->add(array('store_id'=>$sid,'state'=>'1','category'=>'syn_dc'));
                    $count ++ ;
                }
            }
            
            dump('result:'.$count);
        }
    }

}

?>
