<?php

/* 商品数据模型 */

class GoodsModel extends BaseModel {

    var $table = 'goods';
    var $prikey = 'goods_id';
    var $alias = 'g';
    var $_name = 'goods';
    var $temp; // 临时变量
    var $_relation = array(
        // 一个商品对应一条商品统计记录
        'has_goodsstatistics' => array(
            'model' => 'goodsstatistics',
            'type' => HAS_ONE,
            'foreign_key' => 'goods_id',
            'dependent' => true
        ),
        // 一个商品对应一条精品区商品记录
        'has_storebrandareagoods' => array(
            'model' => 'storebrandareagoods',
            'type' => HAS_ONE,
            'foreign_key' => 'goods_id',
            'dependent' => true
        ),
        // 一个商品对应多个规格
        'has_goodsspec' => array(
            'model' => 'goodsspec',
            'type' => HAS_MANY,
            'foreign_key' => 'goods_id',
            'dependent' => true
        ),
        // 一个商品对应多个文件
        'has_uploadedfile' => array(
            'model' => 'uploadedfile',
            'type' => HAS_MANY,
            'foreign_key' => 'item_id',
            'ext_limit' => array('belong' => BELONG_GOODS),
            'dependent' => true
        ),
        // 一个商品对应一个默认规格
        'has_default_spec' => array(
            'model' => 'goodsspec',
            'type' => HAS_ONE,
            'refer_key' => 'default_spec',
            'foreign_key' => 'spec_id',
        ),
        // 一个商品对应多个属性
        'has_goodsattr' => array(
            'model' => 'goodsattr',
            'type' => HAS_MANY,
            'foreign_key' => 'goods_id',
            'dependent' => true
        ),
        // 一个商品对应多个图片
        'has_goodsimage' => array(
            'model' => 'goodsimage',
            'type' => HAS_MANY,
            'foreign_key' => 'goods_id',
            'dependent' => true
        ),
        // 一个商品只能属于一个店铺
        'belongs_to_store' => array(
            'model' => 'store',
            'type' => BELONGS_TO,
            'foreign_key' => 'store_id',
            'reverse' => 'has_goods',
        ),
        // 商品和分类是多对多的关系
        'belongs_to_gcategory' => array(
            'model' => 'gcategory',
            'type' => HAS_AND_BELONGS_TO_MANY,
            'middle_table' => 'category_goods',
            'foreign_key' => 'goods_id',
            'reverse' => 'has_goods',
        ),
        // 商品和会员是多对多的关系（会员收藏商品）
        'be_collect' => array(
            'model' => 'member',
            'type' => HAS_AND_BELONGS_TO_MANY,
            'middle_table' => 'collect',
            'foreign_key' => 'item_id',
            'ext_limit' => array('type' => 'goods'),
            'reverse' => 'collect_goods',
        ),
        // 商品和推荐类型是多对多的关系 todo
        'be_recommend' => array(
            'model' => 'recommend',
            'type' => HAS_AND_BELONGS_TO_MANY,
            'middle_table' => 'recommended_goods',
            'foreign_key' => 'goods_id',
            'reverse' => 'recommend_goods',
        ),
        //商品和商品咨询是一对多关系
        'be_questioned' => array(
            'model' => 'goodsqa',
            'type' => HAS_MANY,
            'foreign_key' => 'item_id',
            'ext_limit' => array('type' => 'goods'),
            'dependent' => true, // 依赖
        ),
        //商品和团购活动是一对多关系
        'has_groupbuy' => array(
            'model' => 'groupbuy',
            'type' => HAS_MANY,
            'foreign_key' => 'goods_id',
            'dependent' => true, // 依赖
        ),
    );
    var $_autov = array(
        'goods_name' => array(
            'required' => true,
            'filter' => 'trim',
        ),
    );
    function getSphinxAddress(){
        $cache_server = & cache_server();
        $current = $cache_server->get('currentSphinx');
        if($current){
            return $current;
        }else{
            return FIRST_SPHINX;
        }
    }
    function getGoods(&$keyword = "", $reCondition = null, $orderStr, $start = 0, &$total_found, &$ids) {
        $page = empty($_REQUEST['page']) ? 1 : intval($_REQUEST['page']);
        $start = ($page - 1) * NUM_PER_PAGE;
//        echo 'start: '.$start;
//        print_r($keyword);
//        echo 'recondition: '.$reCondition. '<br>';
//        print_r($reCondition);
//        echo 'orderStr : '. $orderStr.'<br>';
        if (USESPHINX == 1) {
            $goods_name = '';
            if ($keyword) {
                foreach ($keyword as $word) {
                    $goods_name .= '#' . $word;
                }
            }
            $cl = new SphinxClient ();
            $cl->SetServer($this->getSphinxAddress(), SPHINXPORT);
            $cl->SetArrayResult(true);
            $cl->SetMatchMode(SPH_MATCH_ALL);
            if (!empty($reCondition)) {
                foreach ($reCondition as $key => $value) {
                    if ($value['type'] == "equal") {
                        $cl->SetFilter($key, array($value['value']));
                    } else if ($value['type'] == 'range') {
                        $cl->SetFilterRange($key, $value['min'], $value['max'], $value['exclude']);
                    } else if ($value['type'] == "in") {
                        $cl->SetFilter($key, $value['value']);
                    } else if ($value['type'] == "keywords") {
                        $goods_name .= '#' . $value['value'];
                    }
                }
            }
//            $cl->SetFilter('mk_id', $filterArr);
//            $cl->SetFilterRange('mk_id',1,100);
            $cl->SetSortMode(SPH_SORT_EXTENDED, $orderStr);
//            Log::write('goodsModel:'.$orderStr);
            $cl->SetLimits($start, NUM_PER_PAGE, NUM_PER_PAGE * $page + NUM_PER_PAGE);
            if (defined('OEM')) {
                $res = $cl->Query($goods_name, "goods_" . OEM);
            } else {
                $res = $cl->Query($goods_name, "goods");
            }
            if ($res && $res['total'] > 0) {
//                print_r($res);
                foreach ($res['matches'] as $record) {
                    $ids[] = $record['id'];
                }

                $conditions = 'g.goods_id' . db_create_in($ids);
                $total_found = $res['total_found'];
                $keyword = "";
//                echo $conditions;
            } else if ($res && $res['total'] == 0 && $res['words']) {//尝试减少一个关键字
                $goods_name = '';
                $tsize = count($res['words']);
                $tsize--;
                foreach ($res['words'] as $tkey => $tvalue) {
                    if (--$tsize < 0)
                        break;
                    $goods_name.= $tkey;
                }
                if (defined('OEM')) {
                    $res = $cl->Query($goods_name, "goods_" . OEM);
                } else {
                    $res = $cl->Query($goods_name, "goods");
                }
                if ($res && $res['total'] > 0) {
//                print_r($res);
                    foreach ($res['matches'] as $record) {
                        $ids[] = $record['id'];
                    }

                    $conditions = 'g.goods_id' . db_create_in($ids);
                    $total_found = $res['total_found'];
                    $keyword = $goods_name;
//                echo $conditions;
                } else {
                    $conditions = '0=1';
                    $total_found = 0;
                    $keyword = "";
                }
            } else {
                $conditions = '0=1';
                $total_found = 0;
                $keyword = "";
            }
        }

        return $conditions;
    }

    function get_Mem_Goods($keyword = "", $reCondition = null, $orderStr, $limit = 1, &$total_found) {
        $start = 0;
        if (USESPHINX == 1) {
            $goods_name = '';
            if ($keyword) {
                foreach ($keyword as $word) {
                    $goods_name .= '#' . $word;
                }
            }
            $cl = new SphinxClient ();
            $cl->SetServer($this->getSphinxAddress(), SPHINXPORT);
            $cl->SetArrayResult(true);
            $cl->SetMatchMode(SPH_MATCH_ALL);
            if (!empty($reCondition)) {
                foreach ($reCondition as $key => $value) {
                    if ($value['type'] == "equal") {
                        $cl->SetFilter($key, array($value['value']));
                    } else if ($value['type'] == 'range') {
                        $cl->SetFilterRange($key, $value['min'], $value['max'], $value['exclude']);
                    } else if ($value['type'] == "in") {
                        $cl->SetFilter($key, $value['value']);
                    } else if ($value['type'] == "keywords") {
                        $goods_name .= '#' . $value['value'];
                    }
                }
            }
            $cl->SetSortMode(SPH_SORT_EXTENDED, $orderStr);
            $cl->SetLimits($start, $limit, $limit);
            if (defined('OEM')) {
                $res = $cl->Query($goods_name, "goods_" . OEM);
            } else {
                $res = $cl->Query($goods_name, "goods");
            }
            if ($res && $res['total'] > 0) {
                foreach ($res['matches'] as $record) {
                    $ids[] = $record['id'];
                }

                $conditions = 'g.goods_id' . db_create_in($ids);
                $total_found = $res['total_found'];
            } else {
                $conditions = '0=1';
                $total_found = 0;
            }
        }
        return $conditions;
    }

    function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    /**
     * 取得商品列表
     *
     * @param array $params     这个参数跟find函数的参数相同
     * @param int   $scate_ids  店铺商品分类id
     * @param bool  $desc       是否查描述
     * @param bool  $no_picture 没有图片时是否使用no_picture作为默认图片
     * @return array
     */
    function get_list2($params = array(), $scate_ids = array(), $desc = false, $no_picture = true, &$total_found,&$backkey) {
        if (!defined('USESPHINX') || USESPHINX != 1) {
            return $this->get_list($params, $scate_ids, $desc, $no_picture);
        }
        is_int($scate_ids) && $scate_ids > 0 && $scate_ids = array($scate_ids);

        extract($this->_initFindParams($params));

        $gs_mod = & m('goodsspec');
        $store_mod = & m('store');
        $gstat_mod = & m('goodsstatistics');
        $goods_attr_mod = & m('goodsattr');
        $cg_table = DB_PREFIX . 'category_goods';

        /* tyioocom */
        $fields .= "g.default_spec,g.goods_id, g.store_id, g.type, g.goods_name, g.cate_id, g.cate_name, g.brand, g.spec_qty, g.spec_name_1, g.spec_name_2, g.if_show, g.closed, g.add_time, g.recommended, g.default_image,g.sort_order,g.realpic, " .
                "gs.spec_id, gs.spec_1, gs.spec_2, gs.color_rgb, g.price, gs.stock, " .
                "s.store_name, s.region_id, s.region_name, s.credit_value, s.sgrade, s.serv_sendgoods, s.serv_refund, s.serv_exchgoods,s.mk_name,s.dangkou_address,s.serv_realpic, " .
                "gst.views, gst.sales, gst.comments";
        $desc && $fields .= ", g.description";

        /* 条件(WHERE) */
        $conditions = $this->_getConditions($conditions, true);
        $pos = strpos($conditions, "WHERE");
        $conditions = substr($conditions, $pos + 6);
        $conditions .= "  AND s.store_id IS NOT NULL ";
//        echo 'condi::'.$conditions.'<br>';
        $this->_getMyConditions($conditions, $g_con, $s_con, $gs_con, $gst_con);
        $reCondition = $this->_getOneCondition($conditions);
//        print_r($reCondition);
        $conditions = ' where ' . $conditions;
//         echo '<br>ori fields: '.$fields.'<br>';

        if ($scate_ids) {
            $sql = "SELECT DISTINCT goods_id FROM {$cg_table} WHERE cate_id " . db_create_in($scate_ids);
            $goods_ids = $gs_mod->getCol($sql);
            $conditions .= " AND g.goods_id " . db_create_in($goods_ids);
        }

        /* 排序(ORDER BY) */
        if ($order) {
            $order = ' ORDER BY ' . $this->getRealFields($order) . ', g.add_time desc ';
        }
        if ($limit == false) {
            $limit = '1';
        }
        $this->_getMyFields($fields, $g_f, $s_f, $gs_f, $gst_f);
//        $this->_getMySorts($order, $g_s, $s_s, $gs_s, $gst_s, $g_f, $s_f, $gs_f, $gst_f);
        $mysort = $this->_getOneSort($order, $g_f, $s_f, $gs_f, $gst_f);
//        echo $order.'-'.$mysort;
//        echo 'g_f:'.$g_f.'<br>'.'s_f:'.$s_f.'<br>'.$gs_f.'<br>'.$gst_f.'<br>';
//        echo 'order:'.$g_s.'-'.$s_s.'-'.$gs_s.'-'.$gss_s;
        $tables = "(select g.* from {$this->table} g $g_con and g.store_id in (select s.store_id from {$store_mod->table} s $s_con) Limit $limit) g " .
//                "LEFT JOIN (select gs.* from {$gs_mod->table} gs $gs_con )gs ON g.default_spec = gs.spec_id " .
                "LEFT JOIN (select s.* from {$store_mod->table} s $s_con) s ON g.store_id = s.store_id " .
                "LEFT JOIN (select gst.* from {$gstat_mod->table} gst $gst_con) gst ON g.goods_id = gst.goods_id ";




        /* 分页(LIMIT) */
        $limit && $limit = ' LIMIT ' . $limit;
        if ($count) {
//            $this->_updateLastQueryCount("SELECT COUNT(*) as c FROM {$tables}{$conditions}");
        }
        $fields = $g_f . ',' . $s_f . ',' . $gst_f;
//       echo '<br>My fields: '.$fields.'<br>';
        /* 完整的SQL */
        $this->temp = $tables . $conditions;
        $sql = "SELECT {$fields} FROM {$tables}{$conditions}{$order} ";
//        echo $sql;
//             $goods_list = $index_key ? $this->db->getAllWithIndex($sql, $index_key) : $this->db->getAll($sql);

        $sqlg = "select " . $g_f . " from ecm_goods g where " . $this->getGoods($conditions_tt, $reCondition, $mysort, 0, $total_found, $ids);
//        echo "sqlg".$sqlg;
        $backkey = $conditions_tt;
        $t0 = time();
        $goods_list = $index_key ? $this->db->getAllWithIndex($sqlg, $index_key) : $this->db->getAll($sqlg);
        $t1 = time();
//        echo 't1-t0=:'. ($t1-$t0). '<br>';
        // 用no_picture替换商品图片
        if ($no_picture) {
            foreach ($goods_list as $key => $goods) {
                $goods['default_image'] || $goods_list[$key]['default_image'] = Conf::get('default_goods_image');
                $gssql = "select " . $gs_f . " from {$gs_mod->table} gs  where   gs.spec_id=" . $goods['default_spec'];
                $row = $this->getRow($gssql);
                if ($row != false) {
                    foreach ($row as $kk => $pp) {
                        $goods_list[$key][$kk] = $pp;
                    }
                }
                $ssql = "select " . $s_f . " from {$store_mod->table} s  where  s.store_id=" . $goods['store_id'];
                $row = $this->getRow($ssql);
                if ($row != false) {
                    foreach ($row as $kk => $pp) {
                        $goods_list[$key][$kk] = $pp;
                    }
                }
                $gstsql = "select " . $gst_f . " from {$gstat_mod->table} gst where   gst.goods_id=" . $goods['goods_id'];
                $row = $this->getRow($gstsql);
                if ($row != false) {
                    foreach ($row as $kk => $pp) {
                        $goods_list[$key][$kk] = $pp;
                    }
                }
                $gattrsql = "select attr_value from {$goods_attr_mod->table} gattr where  gattr.gattr_id = 1 and  gattr.goods_id=" . $goods['goods_id'];
                $row = $this->getRow($gattrsql);
                if ($row != false) {
                    foreach ($row as $kk => $pp) {
                        $goods_list[$key][$kk] = $pp;
                    }
                }
            }
        } else {
            foreach ($goods_list as $key => $goods) {
                $gssql = "select gs.* from {$gs_mod->table} gs $gs_con  and  gs.spec_id=" . $goods['default_spec'];
//                  echo $gssql;
                $row = $this->getRow($gssql);
                if ($row != false) {
                    foreach ($row as $kk => $pp) {
                        $goods_list[$key][$kk] = $pp;
                    }
                    $ssql = "select " . $s_f . " from {$store_mod->table} s  where  s.store_id=" . $goods['store_id'];
                    $row = $this->getRow($ssql);
                    if ($row != false) {
                        foreach ($row as $kk => $pp) {
                            $goods_list[$key][$kk] = $pp;
                        }
                    }
                    $gstsql = "select " . $gst_f . " from {$gstat_mod->table} gst where   gst.goods_id=" . $goods['goods_id'];
                    $row = $this->getRow($gstsql);
                    if ($row != false) {
                        foreach ($row as $kk => $pp) {
                            $goods_list[$key][$kk] = $pp;
                        }
                    }
                    $gattrsql = "select attr_value from {$goods_attr_mod->table} gattr where  gattr.attr_id = 1 and  gattr.goods_id=" . $goods['goods_id'];
                    $row = $this->getRow($gattrsql);
                    if ($row != false) {
                        foreach ($row as $kk => $pp) {
                            $goods_list[$key][$kk] = $pp;
                        }
                    }
                }
            }
        }
        $new_goods_list = array();
        if ($ids) {
            foreach ($ids as $id) {
               $goods_list[$id] && $new_goods_list[$id] = $goods_list[$id];
            }
        }
        return $new_goods_list;
    }

    function get_list2_for_mobile($params = array(), $scate_ids = array(), $desc = false, $no_picture = true, &$total_found,&$backkey) {
        if (!defined('USESPHINX') || USESPHINX != 1) {
            return $this->get_list($params, $scate_ids, $desc, $no_picture);
        }
        is_int($scate_ids) && $scate_ids > 0 && $scate_ids = array($scate_ids);

        extract($this->_initFindParams($params));

        $gs_mod = & m('goodsspec');
        $store_mod = & m('store');
        $gstat_mod = & m('goodsstatistics');
        $goods_attr_mod = & m('goodsattr');
        $cg_table = DB_PREFIX . 'category_goods';

        /* tyioocom */
        $fields .= "g.default_spec,g.goods_id, g.store_id, g.type, g.goods_name, g.cate_id, g.cate_name, g.brand, g.spec_qty, g.spec_name_1, g.spec_name_2, g.if_show, g.closed, g.add_time, g.recommended, g.default_image,g.sort_order,g.realpic, " .
                "gs.spec_id, gs.spec_1, gs.spec_2, gs.color_rgb, g.price, gs.stock, " .
                "s.store_name, s.region_id, s.region_name, s.credit_value, s.sgrade, s.serv_sendgoods, s.serv_refund, s.serv_exchgoods,s.mk_name,s.dangkou_address,s.serv_realpic, " .
                "gst.views, gst.sales, gst.comments";
        $desc && $fields .= ", g.description";

        /* 条件(WHERE) */
        $conditions = $this->_getConditions($conditions, true);
        $pos = strpos($conditions, "WHERE");
        $conditions = substr($conditions, $pos + 6);
        $conditions .= "  AND s.store_id IS NOT NULL ";
//        echo 'condi::'.$conditions.'<br>';
        $this->_getMyConditions($conditions, $g_con, $s_con, $gs_con, $gst_con);
        $reCondition = $this->_getOneCondition($conditions);
//        print_r($reCondition);
        $conditions = ' where ' . $conditions;
//         echo '<br>ori fields: '.$fields.'<br>';

        if ($scate_ids) {
            $sql = "SELECT DISTINCT goods_id FROM {$cg_table} WHERE cate_id " . db_create_in($scate_ids);
            $goods_ids = $gs_mod->getCol($sql);
            $conditions .= " AND g.goods_id " . db_create_in($goods_ids);
        }

        /* 排序(ORDER BY) */
        if ($order) {
            $order = ' ORDER BY ' . $this->getRealFields($order) . ', g.add_time desc ';
        }
        if ($limit == false) {
            $limit = '1';
        }
        $this->_getMyFields($fields, $g_f, $s_f, $gs_f, $gst_f);
//        $this->_getMySorts($order, $g_s, $s_s, $gs_s, $gst_s, $g_f, $s_f, $gs_f, $gst_f);
        $mysort = $this->_getOneSort($order, $g_f, $s_f, $gs_f, $gst_f);
//        echo $order.'-'.$mysort;
//        echo 'g_f:'.$g_f.'<br>'.'s_f:'.$s_f.'<br>'.$gs_f.'<br>'.$gst_f.'<br>';
//        echo 'order:'.$g_s.'-'.$s_s.'-'.$gs_s.'-'.$gss_s;
        $tables = "(select g.* from {$this->table} g $g_con and g.store_id in (select s.store_id from {$store_mod->table} s $s_con) Limit $limit) g " .
//                "LEFT JOIN (select gs.* from {$gs_mod->table} gs $gs_con )gs ON g.default_spec = gs.spec_id " .
                "LEFT JOIN (select s.* from {$store_mod->table} s $s_con) s ON g.store_id = s.store_id " .
                "LEFT JOIN (select gst.* from {$gstat_mod->table} gst $gst_con) gst ON g.goods_id = gst.goods_id ";




        /* 分页(LIMIT) */
        $limit && $limit = ' LIMIT ' . $limit;
        if ($count) {
//            $this->_updateLastQueryCount("SELECT COUNT(*) as c FROM {$tables}{$conditions}");
        }
        $fields = $g_f . ',' . $s_f . ',' . $gst_f;
//       echo '<br>My fields: '.$fields.'<br>';
        /* 完整的SQL */
        $this->temp = $tables . $conditions;
        $sql = "SELECT {$fields} FROM {$tables}{$conditions}{$order} ";
//        echo $sql;
//             $goods_list = $index_key ? $this->db->getAllWithIndex($sql, $index_key) : $this->db->getAll($sql);

        $sqlg = "select " . $g_f . " from ecm_goods g where " . $this->getGoods($conditions_tt, $reCondition, $mysort, 0, $total_found, $ids);
//        echo "sqlg".$sqlg;
        $backkey = $conditions_tt;
        $t0 = time();
        $goods_list = $index_key ? $this->db->getAllWithIndex($sqlg, $index_key) : $this->db->getAll($sqlg);
        $t1 = time();
//        echo 't1-t0=:'. ($t1-$t0). '<br>';
        $new_goods_list = array();
        if ($ids) {
            foreach ($ids as $id) {
               $goods_list[$id] && $new_goods_list[$id] = $goods_list[$id];
            }
        }
        return $new_goods_list;
    }

    function get_Mem_list($params = array(), $scate_ids = array(), $desc = false, $no_picture = true, &$total_found) {
        if (!defined('USESPHINX') || USESPHINX != 1) {
            return $this->get_list($params, $scate_ids, $desc, $no_picture);
        }
        is_int($scate_ids) && $scate_ids > 0 && $scate_ids = array($scate_ids);

        extract($this->_initFindParams($params));

        $gs_mod = & m('goodsspec');
        $store_mod = & m('store');
        $gstat_mod = & m('goodsstatistics');
        $cg_table = DB_PREFIX . 'category_goods';
        $goods_attr_mod = & m('goodsattr');
        /* tyioocom */
        $fields .= "g.default_spec,g.goods_id, g.store_id, g.type, g.goods_name, g.cate_id, g.cate_name, g.brand, g.spec_qty, g.spec_name_1, g.spec_name_2, g.if_show, g.closed, g.add_time, g.recommended, g.default_image,g.sort_order,g.realpic, " .
                "gs.spec_id, gs.spec_1, gs.spec_2, gs.color_rgb, g.price, gs.stock, " .
                "s.store_name, s.region_id, s.region_name, s.credit_value, s.sgrade, s.serv_sendgoods, s.serv_refund, s.serv_exchgoods,s.serv_realpic, " .
                "gst.views, gst.sales, gst.comments";
        $desc && $fields .= ", g.description";

        /* 条件(WHERE) */
        $conditions = $this->_getConditions($conditions, true);
        $pos = strpos($conditions, "WHERE");
        $conditions = substr($conditions, $pos + 6);
        $conditions .= "  AND s.store_id IS NOT NULL ";

        $reCondition = $this->_getOneCondition($conditions);

        $conditions = ' where ' . $conditions;

        if ($limit == false) {
            $limit = 1;
        }
        $this->_getMyFields($fields, $g_f, $s_f, $gs_f, $gst_f);
        $mysort = $this->_getOneSort($order, $g_f, $s_f, $gs_f, $gst_f);


        $sqlg = "select " . $g_f . " from ecm_goods g where " . $this->get_Mem_Goods($conditions_tt, $reCondition, $mysort, $limit, $total_found);
        $goods_list = $index_key ? $this->db->getAllWithIndex($sqlg, $index_key) : $this->db->getAll($sqlg);
        // 用no_picture替换商品图片
        if ($no_picture) {
            foreach ($goods_list as $key => $goods) {
                $goods['default_image'] || $goods_list[$key]['default_image'] = Conf::get('default_goods_image');
                $gssql = "select " . $gs_f . " from {$gs_mod->table} gs  where   gs.spec_id=" . $goods['default_spec'];
                $row = $this->getRow($gssql);
                if ($row != false) {
                    foreach ($row as $kk => $pp) {
                        $goods_list[$key][$kk] = $pp;
                    }
                }
                $ssql = "select " . $s_f . " from {$store_mod->table} s  where  s.store_id=" . $goods['store_id'];
                $row = $this->getRow($ssql);
                if ($row != false) {
                    foreach ($row as $kk => $pp) {
                        $goods_list[$key][$kk] = $pp;
                    }
                }
                $gstsql = "select " . $gst_f . " from {$gstat_mod->table} gst where   gst.goods_id=" . $goods['goods_id'];
                $row = $this->getRow($gstsql);
                if ($row != false) {
                    foreach ($row as $kk => $pp) {
                        $goods_list[$key][$kk] = $pp;
                    }
                }
                $gattrsql = "select attr_value from {$goods_attr_mod->table} gattr where  gattr.attr_id = 1 and  gattr.goods_id=" . $goods['goods_id'];
                $row = $this->getRow($gattrsql);
                if ($row != false) {
                    foreach ($row as $kk => $pp) {
                        $goods_list[$key][$kk] = $pp;
                    }
                }
            }
        } else {
            foreach ($goods_list as $key => $goods) {
                $gssql = "select gs.* from {$gs_mod->table} gs $gs_con  and  gs.spec_id=" . $goods['default_spec'];
                $row = $this->getRow($gssql);
                if ($row != false) {
                    foreach ($row as $kk => $pp) {
                        $goods_list[$key][$kk] = $pp;
                    }
                    $ssql = "select " . $s_f . " from {$store_mod->table} s  where  s.store_id=" . $goods['store_id'];
                    $row = $this->getRow($ssql);
                    if ($row != false) {
                        foreach ($row as $kk => $pp) {
                            $goods_list[$key][$kk] = $pp;
                        }
                    }
                    $gstsql = "select " . $gst_f . " from {$gstat_mod->table} gst where   gst.goods_id=" . $goods['goods_id'];
                    $row = $this->getRow($gstsql);
                    if ($row != false) {
                        foreach ($row as $kk => $pp) {
                            $goods_list[$key][$kk] = $pp;
                        }
                    }
                    $gattrsql = "select attr_value from {$goods_attr_mod->table} gattr where  gattr.attr_id = 1 and  gattr.goods_id=" . $goods['goods_id'];
                    $row = $this->getRow($gattrsql);
                    if ($row != false) {
                        foreach ($row as $kk => $pp) {
                            $goods_list[$key][$kk] = $pp;
                        }
                    }
                }
            }
        }

        return $goods_list;
    }

    function get_Mem_list_for_mobile($params = array(), $scate_ids = array(), $desc = false, $no_picture = true, &$total_found) {
        if (!defined('USESPHINX') || USESPHINX != 1) {
            return $this->get_list($params, $scate_ids, $desc, $no_picture);
        }
        is_int($scate_ids) && $scate_ids > 0 && $scate_ids = array($scate_ids);

        extract($this->_initFindParams($params));

        $gs_mod = & m('goodsspec');
        $store_mod = & m('store');
        $gstat_mod = & m('goodsstatistics');
        $cg_table = DB_PREFIX . 'category_goods';
        $goods_attr_mod = & m('goodsattr');
        /* tyioocom */
        $fields .= "g.default_spec,g.goods_id, g.store_id, g.type, g.goods_name, g.cate_id, g.cate_name, g.brand, g.spec_qty, g.spec_name_1, g.spec_name_2, g.if_show, g.closed, g.add_time, g.recommended, g.default_image,g.sort_order,g.realpic, " .
                "gs.spec_id, gs.spec_1, gs.spec_2, gs.color_rgb, g.price, gs.stock, " .
                "s.store_name, s.region_id, s.region_name, s.credit_value, s.sgrade, s.serv_sendgoods, s.serv_refund, s.serv_exchgoods,s.serv_realpic, " .
                "gst.views, gst.sales, gst.comments";
        $desc && $fields .= ", g.description";

        /* 条件(WHERE) */
        $conditions = $this->_getConditions($conditions, true);
        $pos = strpos($conditions, "WHERE");
        $conditions = substr($conditions, $pos + 6);
        $conditions .= "  AND s.store_id IS NOT NULL ";

        $reCondition = $this->_getOneCondition($conditions);

        $conditions = ' where ' . $conditions;

        if ($limit == false) {
            $limit = 1;
        }
        $this->_getMyFields($fields, $g_f, $s_f, $gs_f, $gst_f);
        $mysort = $this->_getOneSort($order, $g_f, $s_f, $gs_f, $gst_f);


        $sqlg = "select " . $g_f . " from ecm_goods g where " . $this->get_Mem_Goods($conditions_tt, $reCondition, $mysort, $limit, $total_found);
        $goods_list = $index_key ? $this->db->getAllWithIndex($sqlg, $index_key) : $this->db->getAll($sqlg);

        return $goods_list;
    }

    /**
     * 取得商品列表
     *
     * @param array $params     这个参数跟find函数的参数相同
     * @param int   $scate_ids  店铺商品分类id
     * @param bool  $desc       是否查描述
     * @param bool  $no_picture 没有图片时是否使用no_picture作为默认图片
     * @return array
     */
    function get_list($params = array(), $scate_ids = array(), $desc = false, $no_picture = true) {
        is_int($scate_ids) && $scate_ids > 0 && $scate_ids = array($scate_ids);

        extract($this->_initFindParams($params));

        $gs_mod = & m('goodsspec');
        $store_mod = & m('store');
        $gstat_mod = & m('goodsstatistics');
        $cg_table = DB_PREFIX . 'category_goods';

        /* tyioocom */
        $fields .= "g.goods_id, g.store_id, g.type, g.goods_name, g.cate_id, g.cate_name, g.brand, g.spec_qty, g.spec_name_1, g.spec_name_2, g.if_show, g.closed, g.add_time, g.recommended, g.default_image,g.sort_order,g.realpic, " .
                "gs.spec_id, gs.spec_1, gs.spec_2, gs.color_rgb, gs.price, gs.stock, " .
                "s.store_name, s.region_id, s.region_name, s.credit_value, s.sgrade, s.serv_sendgoods, s.serv_refund, s.serv_exchgoods,s.mk_name,s.dangkou_address,s.serv_realpic, " .
                "gst.views, gst.sales, gst.comments";
        $desc && $fields .= ", g.description";
        $tables = "{$this->table} g " .
                "LEFT JOIN {$gs_mod->table} gs ON g.default_spec = gs.spec_id " .
                "LEFT JOIN {$store_mod->table} s ON g.store_id = s.store_id " .
                "LEFT JOIN {$gstat_mod->table} gst ON g.goods_id = gst.goods_id ";

        /* 条件(WHERE) */
        $conditions = $this->_getConditions($conditions, true);
        $conditions .= " AND gs.spec_id IS NOT NULL AND s.store_id IS NOT NULL ";
        if ($scate_ids) {
            $sql = "SELECT DISTINCT goods_id FROM {$cg_table} WHERE cate_id " . db_create_in($scate_ids);
            $goods_ids = $gs_mod->getCol($sql);
            $conditions .= " AND g.goods_id " . db_create_in($goods_ids);
        }

        /* 排序(ORDER BY) */
        if ($order) {
            $order = ' ORDER BY ' . $this->getRealFields($order) . ', s.sort_order ';
        }

        /* 分页(LIMIT) */
        $limit && $limit = ' LIMIT ' . $limit;
        if ($count) {
            $this->_updateLastQueryCount("SELECT COUNT(*) as c FROM {$tables}{$conditions}");
        }

        /* 完整的SQL */
        $this->temp = $tables . $conditions;
        $sql = "SELECT {$fields} FROM {$tables}{$conditions}{$order}{$limit}";
//        echo 'condition: ' . $conditions . '<br>' . $sql;
        $goods_list = $index_key ? $this->db->getAllWithIndex($sql, $index_key) : $this->db->getAll($sql);
        // 用no_picture替换商品图片
        if ($no_picture) {
            foreach ($goods_list as $key => $goods) {
                $goods['default_image'] || $goods_list[$key]['default_image'] = Conf::get('default_goods_image');
            }
        }

        return $goods_list;
    }

    /**
     * 取得商品信息
     *
     * @param int $id 商品id
     * @return array
     */
    function get_info($id) {
        $goods = $this->get(array(
            'conditions' => "goods_id = '$id'",
            'join' => 'belongs_to_store',
            'fields' => 'this.*, store.state'
                ));
        if ($goods) {
            /* 商品规格 */
            $spec_mod = & m('goodsspec');
            $specs = $spec_mod->find(array(
                'conditions' => "goods_id = '$id'",
                'order' => 'spec_id',
                    ));
            $goods['_specs'][] = $specs[$goods['default_spec']];
            unset($specs[$goods['default_spec']]);
            $goods['_specs'] = array_merge($goods['_specs'], array_values($specs));
            /* 商品图片 */
            $image_mod = & m('goodsimage');
            $goods['_images'] = array_values($image_mod->find(array(
                        'conditions' => "goods_id = '$id'",
                        'order' => 'sort_order',
                    )));

            /* 店铺分类 */
            $goods['_scates'] = array_values($this->getRelatedData('belongs_to_gcategory', $id, array(
                        'fields' => 'category_goods.cate_id',
                    )));

            /* 统计情况 */
            $stat_mod = & m('goodsstatistics');
            $goods = array_merge($goods, $stat_mod->get_info($id));
        }

        return $goods;
    }

    function get_basic_info($id) {
        $goods = $this->get(array(
            'conditions' => "goods_id = '$id'",
            'join' => 'belongs_to_store',
            'fields' => 'this.*'
                ));


        return $goods;
    }

    /**
     * 取得店铺商品数量
     *
     * @param int $store_id
     */
    function get_count_of_store($store_id) {
//        static $data = array();
//        if (!isset($data[$store_id])) {
//            $cache_server = & cache_server();
//            $data = $cache_server->get('goods_count_of_store');
//            if ($data === false) {
//                $sql = "SELECT store_id, COUNT(*) AS goods_count FROM {$this->table} WHERE if_show = 1 AND closed = 0 AND default_spec > 0 GROUP BY store_id";
//                $data = array();
//                $res = $this->db->query($sql);
//                while ($row = $this->db->fetchRow($res)) {
//                    $data[$row['store_id']] = $row['goods_count'];
//                }
//                $cache_server->set('goods_count_of_store', $data, 3600);
//            }
//        }
//        return isset($data[$store_id]) ? $data[$store_id] : 0;
        $this->get_Mem_list(array(
            'conditions' => 'g.store_id=' . $store_id,
            'order' => 'add_time desc',
            'fields' => '',
            'limit' => 1,
                ), null, false, true, $total_found);
        return $total_found;
    }

    /**
     * 格式化分类名称
     *
     * @param string $cate_name 用tab键隔开的多级分类名称
     * @return 把tab换成换行符，并且分级缩进
     */
    function format_cate_name($cate_name) {
        $arr = explode("\t", $cate_name);
        if (count($arr) > 1) {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = str_repeat("&nbsp;", $i * 4) . htmlspecialchars($arr[$i]);
            }
            $cate_name = join("\n", $arr);
        }

        return $cate_name;
    }

    /**
     *    更新被收藏次数
     *
     *    @author    Garbin
     *    @param     int $goods_id
     *    @return    void
     */
    function update_collect_count($goods_id) {
        $count = $this->db->getOne("SELECT COUNT(*) AS collect_count FROM {$this->_prefix}collect WHERE item_id={$goods_id} AND type='goods'");
        $model_goodsstatistics = & m('goodsstatistics');
        $model_goodsstatistics->edit($goods_id, array('collects' => $count));
    }

    /**
     * 删除商品相关数据：包括商品图片、商品缩略图，要在删除商品之前调用
     *
     * @param   string  $goods_ids  商品id，用逗号隔开
     */
    function drop_data($goods_ids) {
        $image_mod = & m('goodsimage');
        $images = $image_mod->find(array(
            'conditions' => 'goods_id' . db_create_in($goods_ids),
            'fields' => 'image_url, thumbnail',
                ));

        foreach ($images as $image) {
            if (!empty($image['image_url']) && trim($image['image_url']) && substr($image['image_url'], 0, 4) != 'http' && file_exists(ROOT_PATH . '/' . $image['image_url'])) {
                _at(unlink, ROOT_PATH . '/' . $image['image_url']);
            }
            if (!empty($image['thumbnail']) && trim($image['thumbnail']) && substr($image['thumbnail'], 0, 4) != 'http' && file_exists(ROOT_PATH . '/' . $image['thumbnail'])) {
                _at(unlink, ROOT_PATH . '/' . $image['thumbnail']);
            }
        }
    }

    /* 清除缓存 */

    function clear_cache($goods_id) {
        $cache_server = & cache_server();
        $keys = array('page_of_goods_' . $goods_id);
        foreach ($keys as $key) {
            $cache_server->delete($key);
        }
    }

    function edit($conditions, $edit_data) {
        /* 清除缓存 */
        $goods_list = $this->find(array(
            'fields' => 'goods_id',
            'conditions' => $conditions,
                ));
        foreach ($goods_list as $goods) {
            $this->clear_cache($goods['goods_id']);
        }

        // 根据cate_id取得cate_id_1到cate_id_4
        if (is_array($edit_data) && isset($edit_data['cate_id'])) {
            $edit_data = array_merge($edit_data, $this->_get_cate_ids($edit_data['cate_id']));
        }

        return parent::edit($conditions, $edit_data);
    }

    function drop($conditions, $fields = '') {
        /* 清除缓存 */
        $goods_list = $this->find(array(
            'fields' => 'goods_id',
            'conditions' => $conditions,
                ));
        foreach ($goods_list as $goods) {
            $this->clear_cache($goods['goods_id']);
        }
        /* 清除店铺商品数缓存 */
        $cache_server = & cache_server();
        $cache_server->delete('goods_count_of_store');

        return parent::drop($conditions, $fields);
    }

    /**
     * 取得某分类的前4级分类id（存入商品作为冗余数据，方便查询和统计）
     *
     * @param   int     $cate_id    分类id
     * @return  array(
     *              'cate_id_1' => 1,
     *              'cate_id_2' => 2,
     *              'cate_id_3' => 3,
     *              'cate_id_4' => 4,
     *          )
     */
    function _get_cate_ids($cate_id) {
        $res = array(
            'cate_id_1' => 0,
            'cate_id_2' => 0,
            'cate_id_3' => 0,
            'cate_id_4' => 0,
        );

        if ($cate_id > 0) {
            $gcategory_mod = & bm('gcategory');
            $ancestor = $gcategory_mod->get_ancestor($cate_id);
            for ($i = 1; $i <= 4; $i++) {
                $res['cate_id_' . $i] = isset($ancestor[$i - 1]) ? $ancestor[$i - 1]['cate_id'] : 0;
            }
        }

        return $res;
    }

    function getAttrGoods($attr_value) {
        $cl = new SphinxClient ();
        $cl->SetServer(getSphinxAddress(), SPHINXPORT);
        $cl->SetArrayResult(true);
        $cl->SetLimits(0, 60);
        $cl->SetMatchMode(SPH_MATCH_ALL);
        $keywords = $attr_value;
        $res = $cl->Query($keywords, "goods_attr");
        if ($res && $res['total'] > 0) {
            foreach ($res['matches'] as $record) {
                $ids[] = $record['id'];
            }

            $conditions = 'a.gattr_id' . db_create_in($ids);
            $total_found = $res['total_found'];
        } else if ($res && $res['total'] == 0 && $res['words']) {//尝试减少一个关键字
            $goods_name = '';
            $tsize = count($res['words']);
            $tsize--;
            foreach ($res['words'] as $tkey => $tvalue) {
                if (--$tsize < 0)
                    break;
                $goods_name.= $tkey;
            }
            $res = $cl->Query($keywords, "goods_attr");
            if ($res && $res['total'] > 0) {
                foreach ($res['matches'] as $record) {
                    $ids[] = $record['id'];
                }

                $conditions = 'a.gattr_id' . db_create_in($ids);
                $total_found = $res['total_found'];
            } else {
                $conditions = '0=1';
                $total_found = 0;
            }
        } else {
            $conditions = '0=1';
            $total_found = 0;
        }
        $sqlg = 'select g.*, a.goods_id from ecm_goods_attr a left join ecm_goods g on a.goods_id=g.goods_id where ' . $conditions . '  ';
//        echo $sqlg;
        $goods_list = $index_key ? $this->db->getAllWithIndex($sqlg, $index_key) : $this->db->getAll($sqlg);
        return $goods_list;
    }

    /**
     * 每日新款，获取15个今日最新商品，前15个店铺
     * @param 店铺数量 $storenums
     */
    function get_latestGoods_fromStore($storenums) {
        //为扩展用，默认15个店铺的1个新品
//      empty($storenums) && $storenums = 15;
//      $day_start = gmmktime(0, 0, 0, date('m'), date('d'), date('Y'));
//      $day_end = gmmktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
//      //子查询  找出今日 每个店铺中商品 add_time 的最大值
//      $sql="SELECT goods_id,store_id,add_time FROM {$this->table} g WHERE add_time=(SELECT max(add_time) FROM {$this->table} WHERE store_id=g.store_id AND add_time >= $day_start AND add_time <= $day_end ) order by g.add_time DESC limit $storenums";
//       $data = array();
//         $res = $this->db->query($sql);
//         while ($row = $this->db->fetchRow($res)) {
//                $data[] = $row['goods_id'];
//         }
//         $goodslist = array();
//         if($data)
//         {
//          $condition= array(
//                  'conditions'=>db_create_in($data,'g.goods_id'),
//          );
//          $goodslist = $this->get_list2($condition);
//         }
//      return $goodslist;

        $num = 0;
        $page = 1;
        $result = array();
        while ($num < $storenums - 1) {

            $_REQUEST['page'] = $page;

            $goods_list = $this->get_list2(array(
                'conditions' => $conditions,
                'order' => 'add_time desc',
                'fields' => '',
                    ), null, false, true, $total_found,$keyback);
            foreach ($goods_list as $key => $goods) {
                if (!$result[$goods['store_id']]) {
                    $result[$goods['store_id']] = $goods;
                    $num++;
                    if ($num >= $storenums)
                        break;
                }
            }
            $page++;
        }
//        print_r($result);
        return $result;
    }

}

/* 商品业务模型 business model */

class GoodsBModel extends GoodsModel {

    var $_store_id = 0;

    /*
     * 判断名称是否唯一
     */

    function unique($goods_name, $goods_id = 0) {
        return true;
    }

    /* 覆盖基类方法 */

    function add($data, $compatible = false) {
        // store_id
        $data['store_id'] = $this->_store_id;

        // 根据cate_id取得cate_id_1到cate_id_4
        if (!empty($data['cate_id'])) {
            $data = array_merge($data, $this->_get_cate_ids($data['cate_id']));
        }

        $id = parent::add($data, $compatible);
        $stat_mod = & m('goodsstatistics');
        $stat_mod->add(array(
            'goods_id' => $id
        ));

        /* 清除店铺商品数缓存 */
        $cache_server = & cache_server();
        $cache_server->delete('goods_count_of_store');

        return $id;
    }

    /* 覆盖基类方法 */

    function _getConditions($conditions, $if_add_alias = false) {
        $alias = '';
        if ($if_add_alias) {
            $alias = $this->alias . '.';
        }
        $res = parent::_getConditions($conditions, $if_add_alias);
        return $res ? $res . " AND {$alias}store_id = '{$this->_store_id}'" : " WHERE {$alias}store_id = '{$this->_store_id}'";
    }

    /* 过滤掉不是本店的商品id */

    function get_filtered_ids($ids) {
        $sql = "SELECT goods_id FROM {$this->table} WHERE store_id = '{$this->_store_id}' AND goods_id " . db_create_in($ids);

        return $this->db->getCol($sql);
    }

    /* 取得商品数 */

    function get_count() {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE store_id = '{$this->_store_id}'";

        return $this->db->getOne($sql);
    }

    /**
     * 获取商品订单缺货率、退货率
     * 缺货率=缺货次数/总购买次数
     * 退货率=退货次数/总购买次数
     * @param $goods_id
     * @author tanaiquan
     */
    function get_goods_rates($goods_id)
    {
        $result = array(
            'lack_rate'=>0,
            'back_rate'=>0,
            'praise_rate'=>$this->recount_praise_rate($goods_id)
        );
        $stat_mod = & m('goodsstatistics');
        $goods_stat = $stat_mod->get($goods_id);
        if($goods_stat['sales'] > 0)
        {
            $result['lack_rate'] = round($goods_stat['oos']/$goods_stat['sales'],4) * 100;
            $result['back_rate'] = round($goods_stat['backs']/$goods_stat['sales'],4) * 100;

            $goods_stat['oos'] > $goods_stat['sales'] && $result['lack_rate'] = 100.00;
            $goods_stat['backs'] > $goods_stat['sales'] && $result['back_rate'] = 100.00;
        }

        return $result;
    }

    function recount_praise_rate($goods_id)
    {
        $praise_rate = 0.00;
        $model_ordergoods =& m('ordergoods');

        /* 找出所有is_valid为1的商品中的商品评价记录总数 */
        $info  = $model_ordergoods->get(array(
            'join'          => 'belongs_to_order',
            'conditions'    => "goods_id={$goods_id} AND evaluation_status=1 AND is_valid=1",
            'fields'        => 'COUNT(*) as evaluation_count',
            'index_key'     => false,   /* 不需要索引 */
        ));
        $evaluation_count = $info['evaluation_count'];
        if (!$evaluation_count)
        {
            return $praise_count;
        }

        /* 找出所有的evaluation为3的记录总数 */
        $info = $model_ordergoods->get(array(
            'join'          => 'belongs_to_order',
            'conditions'    => "goods_id={$goods_id} AND evaluation_status=1 AND is_valid=1 AND evaluation=3",
            'fields'        => 'COUNT(*) as praise_count',
            'index_key'     => false,   /* 不需要索引 */
        ));
        $praise_count = $info['praise_count'];
        /* 计算好评数占总数的百分比 */
        $praise_rate = round(($praise_count / $evaluation_count), 4) * 100;

        return $praise_rate;
    }

}

?>