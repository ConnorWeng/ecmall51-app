<?php

define('REC_NEW', -100); // 新品推荐

/* 推荐类型 recommend */

class RecommendModel extends BaseModel {

    var $table = 'recommend';
    var $prikey = 'recom_id';
    var $_name = 'recommend';
    var $_relation = array(
        // todo 一个推荐类型只能属于一个店铺
        // 推荐类型和商品是多对多的关系
        'recommend_goods' => array(
            'model' => 'goods',
            'type' => HAS_AND_BELONGS_TO_MANY,
            'middle_table' => 'recommended_goods',
            'foreign_key' => 'recom_id',
            'reverse' => 'be_recommend',
        ),
    );
    var $_autov = array(
        'recom_name' => array(
            'required' => true,
            'filter' => 'trim',
        ),
    );

    function get_my_recommended_goods($recom_id, $num = '') {

        $sql = "select goods_id FROM " . DB_PREFIX . "recommended_goods where recom_id=" . $recom_id . " order by sort_order ";
        $ids = $this->db->getAll($sql);
        if(!$ids) return null;
        foreach ($ids as $key => $value) {
            $myids[$key] = $value['goods_id'];
        }

        if (!$num || empty($num))
            $num = count($myids);
        $sqlg = "select * from ecm_goods g where goods_id  " . db_create_in($myids);
        $goods_list = $this->db->getAllWithIndex($sqlg, 'goods_id');

        $new_goods_list = array();
        $s_f = 'mk_name, dangkou_address, store_name, serv_sendgoods , serv_refund,serv_exchgoods,serv_realpic';
        if ($myids) {
            foreach ($myids as $id) {
                if ($goods_list[$id]) {
                    $new_goods_list[$id] = $goods_list[$id];
                    $ssql = "select " . $s_f . " from  ecm_store where  store_id=" . $goods_list[$id]['store_id'];
                    $row = $this->getRow($ssql);
                    if ($row != false) {
                        foreach ($row as $kk => $pp) {
                            $new_goods_list[$id][$kk] = $pp;
                        }
                    }
                }
            }
        }

        return $new_goods_list;
    }

    /**
     * 取得某推荐下商品
     * @param   int     $recom_id       推荐类型
     * @param   int     $num            取商品数量
     * @param   bool    $default_image  如果商品没有图片，是否取默认图片
     * @param   int     $mall_cate_id   分类（最新商品用到）
     */
    function get_recommended_goods($recom_id, $num, $default_image = true, $mall_cate_id = 0, $timeslot = array(), $sort_by = '') { // tyioocom
        $goods_list = array();
        $order = '';

        $conditions = "g.if_show = 1 AND g.closed = 0 AND g.default_spec > 0 AND s.state = 1 ";

        // tyioocom 加了时间段
        if (count($timeslot) > 0) {
            $conditions .= "AND g.add_time >='" . $timeslot['begin'] . "' and g.add_time <='" . $timeslot['end'] . "'";
        }

        if ($recom_id == REC_NEW) {
            if (empty($sort_by)) {
                $order = ' g.add_time DESC ';
            } elseif (in_array($sort_by, array('views', 'collects', 'comments', 'sales'))) {
                $order = ' goodsstatistics.' . $sort_by . ' DESC,g.add_time DESC ';
            } elseif ($sort_by == 'add_time') {
                $order = ' g.add_time DESC ';
            }

            /* 最新商品 */
            if ($mall_cate_id > 0) {
                $gcategory_mod = & m('gcategory');
                $conditions .= " AND g.cate_id " . db_create_in($gcategory_mod->get_descendant($mall_cate_id));
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.default_image, gs.price, gs.stock,g.realpic,
            		gs.spec_id, gs.spec_1, gs.spec_2, gs.color_rgb, gs.price, gs.stock,
            		goodsstatistics.sales,goodsstatistics.views, goodsstatistics.sales, goodsstatistics.comments,
            		s.store_id,s.store_name,s.mk_name,s.dangkou_address,s.im_ww,s.im_qq,s.credit_value,s.serv_sendgoods, s.serv_refund, s.serv_exchgoods,s.serv_realpic,s.praise_rate " .
                    "FROM " . DB_PREFIX . "goods AS g " .
                    "LEFT JOIN " . DB_PREFIX . "goods_spec AS gs ON g.default_spec = gs.spec_id " .
                    "LEFT JOIN " . DB_PREFIX . "store AS s ON g.store_id = s.store_id " .
                    "LEFT JOIN " . DB_PREFIX . "goods_statistics AS goodsstatistics ON goodsstatistics.goods_id=g.goods_id " .
                    "WHERE " . $conditions .
                    "ORDER BY  " . $order .
                    "LIMIT {$num}";
        } else {
            if (empty($sort_by)) {
                $order = ' rg.sort_order ';
            } elseif (in_array($sort_by, array('views', 'collects', 'comments', 'sales'))) {
                $order = ' goodsstatistics.' . $sort_by . ' DESC,rg.sort_order ';
            } elseif ($sort_by == 'add_time') {
                $order = ' g.add_time DESC ';
            }

            /* 推荐商品 */
            $sql = "SELECT g.goods_id, g.goods_name, g.default_image, gs.price, gs.stock,g.realpic,
            		gs.spec_id, gs.spec_1, gs.spec_2, gs.color_rgb, gs.price, gs.stock,
            		goodsstatistics.sales,goodsstatistics.views, goodsstatistics.sales, goodsstatistics.comments,
            		s.store_id,s.store_name,s.mk_name,s.dangkou_address,s.im_ww,s.im_qq,s.credit_value,s.serv_sendgoods, s.serv_refund, s.serv_exchgoods,s.serv_realpic,s.praise_rate " .
                    "FROM " . DB_PREFIX . "recommended_goods AS rg " .
                    "   LEFT JOIN " . DB_PREFIX . "goods AS g ON rg.goods_id = g.goods_id " .
                    "   LEFT JOIN " . DB_PREFIX . "goods_spec AS gs ON g.default_spec = gs.spec_id " .
                    "   LEFT JOIN " . DB_PREFIX . "store AS s ON g.store_id = s.store_id " .
                    "	LEFT JOIN " . DB_PREFIX . "goods_statistics AS goodsstatistics ON goodsstatistics.goods_id=g.goods_id " .
                    "WHERE " . $conditions .
                    "AND rg.recom_id = '$recom_id' " .
                    "AND g.goods_id IS NOT NULL " .
                    "ORDER BY  " . $order .
                    "LIMIT {$num}";
        }
        $res = $this->db->query($sql);
        while ($row = $this->db->fetchRow($res)) {
            $default_image && empty($row['default_image']) && $row['default_image'] = Conf::get('default_goods_image');
            $goods_list[] = $row;
        }

        //tiq  取得小图片
        /* $image_mod =& m('goodsimage');
          foreach ($goods_list as $key => $goods)
          {

          empty($goods['default_image']) && $goods_list[$key]['default_image'] = Conf::get('default_goods_image');

          // 加载商品小图 tyioocom
          $goods_list[$key]['_images'] = array_values($image_mod->find(array(
          'conditions' => "goods_id=".$goods['goods_id'],
          'order' => 'sort_order',
          'fields'=>'thumbnail,image_url',
          'limit'=>4
          )));
          }
         */
        return $goods_list;
    }

}

class RecommendBModel extends RecommendModel {

    var $_store_id = 0;

    /*
     * 判断名称是否唯一
     */

    function unique($recom_name, $recom_id = 0) {
        $conditions = "recom_name = '$recom_name'";
        $recom_id && $conditions .= " AND recom_id <> '" . $recom_id . "'";

        return count($this->find(array('conditions' => $conditions))) == 0;
    }

    /* 覆盖基类方法 */

    function add($data, $compatible = false) {
        $data['store_id'] = $this->_store_id;

        return parent::add($data, $compatible);
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

    function get_options() {
        $options = array();
        $recommends = $this->find();
        foreach ($recommends as $recommend) {
            $options[$recommend['recom_id']] = $recommend['recom_name'];
        }

        return $options;
    }

    /**
     * 统计各推荐下商品数
     *
     * @return array(recom_id => count)
     */
    function count_goods() {
        $count = array();
        $sql = "SELECT rg.recom_id, COUNT(*) AS goods_count " .
                "FROM " . DB_PREFIX . "recommended_goods AS rg " .
                "   LEFT JOIN {$this->table} AS r ON rg.recom_id = r.recom_id " .
                "   LEFT JOIN " . DB_PREFIX . "goods AS g ON rg.goods_id = g.goods_id " .
                "WHERE r.store_id = '{$this->_store_id}' " .
                "AND g.goods_id IS NOT NULL " .
                "GROUP BY rg.recom_id";
        $res = $this->db->query($sql);
        while ($row = $this->db->fetchRow($res)) {
            $count[$row['recom_id']] = $row['goods_count'];
        }

        return $count;
    }

}

?>