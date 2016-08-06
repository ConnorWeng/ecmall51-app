<?php

class StoreApp extends StorebaseApp
{
    function index()
    {
        /* 店铺信息 */
        $_GET['act'] = 'index';
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }
        $this->set_store($id);
        $store = $this->get_store_data();

        /*
          $market_mod =& m('market');
          if(!empty($store['mk_id']))
          {
          $m_layer = $market_mod->get_layer($store['mk_id']);
          if($m_layer == 3)
          {
          $market_layer3 = $market_mod->find($store['mk_id']);
          $market_layer3 = array_values($market_layer3);
          $market_layer2 = $market_mod->find($market_layer3[0]['parent_id']);
          $market_layer2 = array_values($market_layer2);
          $store['mk_name'] = $market_layer2[0]['mk_name']."-".$market_layer3[0]['mk_name'];
          }
          } */
        //$store['im_wx'] = 'sdfll';
        //微信二维码
        if($store['im_wx'])
        {
            if(file_exists(ROOT_PATH.'/data/qrcode/zwd51_s'.$store['store_id'].'.png'))
            {
                $store['img_wx'] = SITE_URL.'/data/qrcode/zwd51_s'.$store['store_id'].'.png';
            }
            else
            {
                $success = generateQRfromQRCode($store['im_wx'], 's'.$store['store_id']);
                $success && $store['img_wx'] = SITE_URL.'/data/qrcode/zwd51_s'.$store['store_id'].'.png';
            }
        }
        //主营
        if(!$store['business_scope'])
        {
            $mod_store =& m('store');
            $business_scope = $mod_store->getRelatedData('has_scategory',$id);
            if($business_scope)
            {
                $business_scope = array_values($business_scope);
                $store['business_scope'] = $business_scope[0]['cate_name'];
            }
        }
        //dump($store);
        $this->assign('store', $store);

        $this->assign('OEM',OEM);
        $this->assign("siteurl",SITE_URL);
        /* 取得友情链接 */
        $this->assign('partners', $this->_get_partners($id));
        /*取得n天内上新与下架商品数量*/
        //$this->assign("newclose_goods",$this->_get_newclose_goods_num($id,5));
        /* 取得推荐商品 */
        // $this->assign('recommended_goods', $this->_get_recommended_goods($id,5));
        //$this->assign('new_groupbuy', $this->_get_new_groupbuy($id));

        /* 取得最新商品   现为所有商品 */
        $this->assign('new_goods', $this->_get_new_goods($id,$store['goods_count']));
        // 店铺顶部两个广告
        $this->_get_store_advs();
        /* 取得热卖商品 */
        //$this->assign('hot_sale_goods', $this->_get_hot_sale_goods($id));

        $orders = $this->_get_orders();
        $this->assign('orders', $orders);

        /* 当前位置 */
        $this->_curlocal(LANG::get('all_stores'), 'index.php?app=search&amp;act=store', $store['store_name']);

        $this->_config_seo('title', $store['mk_name'] . ' - '.$store['dangkou_address'] .'_'. $store['store_name']);
        /* 配置seo信息 */
        $this->_config_seo($this->_get_seo_info($store));
        /* 设置最后更新时间 */
        $mod_store =& m('store');
        $this->assign('last_update_date', $mod_store->last_update($store['store_id']));
        $this->display('store.index.html');
    }


    function search()
    {
        /* 店铺信息 */
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }
        $this->set_store($id);
        $store = $this->get_store_data();
        $this->assign('store', $store);

        /*如果是查询上新与下架*/
        if(isset($_GET['gids']) && !empty($_GET['gids']))
        {
            $gids=explode(',', trim($_GET['gids']));
            $this->_assign_newclose_goods($id,$gids);
            $this->assign("flag",trim($_GET['flag']));
        }else
        {
            /* 搜索到的商品 */
            $this->_assign_searched_goods($id);
        }
        /* 当前位置 */
        $this->_curlocal(LANG::get('all_stores'), 'index.php?app=search&amp;act=store',
                         $store['store_name'], 'index.php?app=store&amp;id=' . $store['store_id'],
                         LANG::get('goods_list')
                         );

        $this->_config_seo('title', Lang::get('goods_list') . ' - ' . $store['store_name']);
        $this->display('store.search.html');
    }

    function groupbuy()
    {
        /* 店铺信息 */
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }
        $this->set_store($id);
        $store = $this->get_store_data();
        $this->assign('store', $store);

        /* 搜索团购 */
        empty($_GET['state']) &&  $_GET['state'] = 'on';
        $conditions = '1=1';
        if ($_GET['state'] == 'on')
        {
            $conditions .= ' AND gb.state ='. GROUP_ON .' AND gb.end_time>' . gmtime();
            $search_name = array(
                array(
                    'text'  => Lang::get('group_on')
                      ),
                array(
                    'text'  => Lang::get('all_groupbuy'),
                    'url'  => url('app=store&act=groupbuy&state=all&id=' . $id)
                      ),
                                 );
        }
        else if ($_GET['state'] == 'all')
        {
            $conditions .= ' AND gb.state '. db_create_in(array(GROUP_ON,GROUP_END,GROUP_FINISHED));
            $search_name = array(
                array(
                    'text'  => Lang::get('all_groupbuy')
                      ),
                array(
                    'text'  => Lang::get('group_on'),
                    'url'  => url('app=store&act=groupbuy&state=on&id=' . $id)
                      ),
                                 );
        }

        $page = $this->_get_page(16);
        $groupbuy_mod = &m('groupbuy');
        $groupbuy_list = $groupbuy_mod->find(array(
            'fields'    => 'goods.default_image, gb.group_name, gb.group_id, gb.spec_price, gb.end_time, gb.state',
            'join'      => 'belong_goods',
            'conditions'=> $conditions . ' AND gb.store_id=' . $id ,
            'order'     => 'group_id DESC',
            'limit'     => $page['limit'],
            'count'     => true
                                                   ));
        $page['item_count'] = $groupbuy_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);
        if (empty($groupbuy_list))
        {
            $groupbuy_list = array();
        }
        foreach ($groupbuy_list as $key => $_g)
        {
            empty($groupbuy_list[$key]['default_image']) && $groupbuy_list[$key]['default_image'] = Conf::get('default_goods_image');
            $tmp = current(unserialize($_g['spec_price']));
            $groupbuy_list[$key]['price'] = $tmp['price'];
            if ($_g['end_time'] < gmtime())
            {
                $groupbuy_list[$key]['group_state'] = group_state($_g['state']);
            }
            else
            {
                $groupbuy_list[$key]['lefttime'] = lefttime($_g['end_time']);
            }
        }
        /* 当前位置 */
        $this->_curlocal(LANG::get('all_stores'), 'index.php?app=search&amp;act=store',
                         $store['store_name'], 'index.php?app=store&amp;id=' . $store['store_id'],
                         LANG::get('groupbuy_list')
                         );

        $this->assign('groupbuy_list', $groupbuy_list);
        $this->assign('search_name', $search_name);
        $this->_config_seo('title', $search_name[0]['text'] . ' - ' . $store['store_name']);
        $this->display('store.groupbuy.html');
    }

    function article()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }
        $article = $this->_get_article($id);
        if (!$article)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }
        $this->assign('article', $article);

        /* 店铺信息 */
        $this->set_store($article['store_id']);
        $store = $this->get_store_data();
        $this->assign('store', $store);

        /* 当前位置 */
        $this->_curlocal(LANG::get('all_stores'), 'index.php?app=search&amp;act=store',
                         $store['store_name'], 'index.php?app=store&amp;id=' . $store['store_id'],
                         $article['title']
                         );

        $this->_config_seo('title', $article['title'] . ' - ' . $store['store_name']);
        $this->display('store.article.html');
    }

    /* 信用评价 */
    function credit()
    {
        /* 店铺信息 */
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }
        $this->set_store($id);
        $store = $this->get_store_data();
        $this->assign('store', $store);
        /* 取得评价过的商品 */
        if (!empty($_GET['eval']) && in_array($_GET['eval'], array(1,2,3)))
        {
            $conditions = "AND evaluation = '{$_GET['eval']}'";
        }
        else
        {
            $conditions = "";
            $_GET['eval'] = '';
        }
        $page = $this->_get_page(10);
        $order_goods_mod =& m('ordergoods');
        $goods_list = $order_goods_mod->find(array(
            'conditions' => "seller_id = '$id' AND evaluation_status = 1 AND is_valid = 1 " . $conditions,
            'join'       => 'belongs_to_order',
            'fields'     => 'buyer_id, buyer_name, anonymous, evaluation_time, goods_id, goods_name, specification, price, quantity, goods_image, evaluation, comment',
            'order'      => 'evaluation_time desc',
            'limit'      => $page['limit'],
            'count'      => true,
                                                   ));
        $this->assign('goods_list', $goods_list);

        $page['item_count'] = $order_goods_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);

        /* 按时间统计 */
        $stats = array();
        for ($i = 0; $i <= 3; $i++)
        {
            $stats[$i]['in_a_week']        = 0;
            $stats[$i]['in_a_month']       = 0;
            $stats[$i]['in_six_month']     = 0;
            $stats[$i]['six_month_before'] = 0;
            $stats[$i]['total']            = 0;
        }

        $goods_list = $order_goods_mod->find(array(
            'conditions' => "seller_id = '$id' AND evaluation_status = 1 AND is_valid = 1 ",
            'join'       => 'belongs_to_order',
            'fields'     => 'evaluation_time, evaluation',
                                                   ));
        foreach ($goods_list as $goods)
        {
            $eval = $goods['evaluation'];
            $stats[$eval]['total']++;
            $stats[0]['total']++;

            $days = (gmtime() - $goods['evaluation_time']) / (24 * 3600);
            if ($days <= 7)
            {
                $stats[$eval]['in_a_week']++;
                $stats[0]['in_a_week']++;
            }
            if ($days <= 30)
            {
                $stats[$eval]['in_a_month']++;
                $stats[0]['in_a_month']++;
            }
            if ($days <= 180)
            {
                $stats[$eval]['in_six_month']++;
                $stats[0]['in_six_month']++;
            }
            if ($days > 180)
            {
                $stats[$eval]['six_month_before']++;
                $stats[0]['six_month_before']++;
            }
        }
        $this->assign('stats', $stats);

        /* 当前位置 */
        $this->_curlocal(LANG::get('all_stores'), 'index.php?app=search&amp;act=store',
                         $store['store_name'], 'index.php?app=store&amp;id=' . $store['store_id'],
                         LANG::get('credit_evaluation')
                         );

        $this->_config_seo('title', Lang::get('credit_evaluation') . ' - ' . $store['store_name']);
        $this->display('store.credit.html');
    }

    /* 取得友情链接 */
    function _get_partners($id)
    {
        $partner_mod =& m('partner');
        return $partner_mod->find(array(
            'conditions' => "store_id = '$id'",
            'order' => 'sort_order',
                                        ));
    }

    /* 取得推荐商品 */
    function _get_recommended_goods($id, $num = 12)
    {
        $goods_mod =& bm('goods', array('_store_id' => $id));
        $goods_list = $goods_mod->find(array(
            'conditions' => "closed = 0 AND if_show = 1 AND recommended = 1",
            'fields'     => 'goods_name, default_image, price',
            'limit'      => $num,
                                             ));
        foreach ($goods_list as $key => $goods)
        {
            empty($goods['default_image']) && $goods_list[$key]['default_image'] = Conf::get('default_goods_image');
        }

        return $goods_list;
    }

    function _get_new_groupbuy($id, $num = 12)
    {
        $model_groupbuy =& m('groupbuy');
        $groupbuy_list = $model_groupbuy->find(array(
            'fields'    => 'goods.default_image, this.group_name, this.group_id, this.spec_price, this.end_time',
            'join'      => 'belong_goods',
            'conditions'=> $model_groupbuy->getRealFields('this.state=' . GROUP_ON . ' AND this.store_id=' . $id . ' AND end_time>'. gmtime()),
            'order'     => 'group_id DESC',
            'limit'     => $num
                                                     ));
        if (empty($groupbuy_list))
        {
            $groupbuy_list = array();
        }
        foreach ($groupbuy_list as $key => $_g)
        {
            empty($groupbuy_list[$key]['default_image']) && $groupbuy_list[$key]['default_image'] = Conf::get('default_goods_image');
            $tmp = current(unserialize($_g['spec_price']));
            $groupbuy_list[$key]['price'] = $tmp['price'];
            $groupbuy_list[$key]['lefttime'] = lefttime($_g['end_time']);
        }

        return $groupbuy_list;
    }

    /* 取得最新商品 */
    function _get_new_goods($id, $totalnum)
    {
        /*加入分页功能 start*/
        $page = $this->_get_page(60);
        $page['item_count'] = $totalnum;
        $this->_format_page($page);
        $this->assign('page_info', $page);
        /*end*/
        $orders = $this->_get_orders();
        $goods_mod =& bm('goods', array('_store_id' => $id));
        $goods_list = $goods_mod->get_list(array(
            'conditions' => "g.closed = 0 AND g.if_show = 1 AND g.default_spec > 0 ",
            'fields'     => 'g.goods_name, g.default_image, gs.price,',
            'order'      => isset($_GET['order']) && isset($orders[trim(str_replace('asc','',str_replace('desc','',$_GET['order'])))]) ? $_GET['order'] : 'add_time desc',
            //'order'      => 'add_time desc',
            'limit'      => $page['limit'],
                                                 ));
        foreach ($goods_list as $key => $goods)
        {
            empty($goods['default_image']) && $goods_list[$key]['default_image'] = Conf::get('default_goods_image');
        }
        
        //dump(isset($_GET['order']) && isset($orders[trim(str_replace('asc','',str_replace('desc','',$_GET['order'])))]) ? $_GET['order'] : 'sales desc');
        return $goods_list;
    }
    /* 取得热卖商品 */
    function _get_hot_sale_goods($id, $num = 16)
    {
        $goods_mod =& bm('goods', array('_store_id' => $id));
        $goods_list = $goods_mod->find(array(
            'conditions' => "closed = 0 AND if_show = 1",
            'join'           => 'has_goodsstatistics',
            'fields'     => 'goods_name, default_image, price,sales',
            'order'      => 'sales desc,add_time desc',
            'limit'      => $num,
                                             ));
        foreach ($goods_list as $key => $goods)
        {
            empty($goods['default_image']) && $goods_list[$key]['default_image'] = Conf::get('default_goods_image');
        }
        return $goods_list;
    }

    /*上新与下架结果*/
    function _assign_newclose_goods($id,$gids)
    {
        $goods_mod =& bm('goods', array('_store_id' => $id));
        $search_name = LANG::get('new_and_close_static_result');
        $goods_list = $goods_mod->get_list(array(
            'conditions' =>  db_create_in($gids,'g.goods_id'),
            //'order'      => isset($_GET['order']) && isset($orders[trim(str_replace('asc','',str_replace('desc','',$_GET['order'])))]) ? $_GET['order'] : 'sales desc',
            'order' => empty($_GET['order']) || !isset($orders[$_GET['order']]) ? 'add_time desc' : $_GET['order'],
                                                 ), array());
        foreach ($goods_list as $key => $goods)
        {
            empty($goods['default_image']) && $goods_list[$key]['default_image'] = Conf::get('default_goods_image');
        }
        $this->assign('searched_goods', $goods_list);
        $this->assign('search_name', $search_name);
    }

    /* 搜索到的结果 */
    function _assign_searched_goods($id)
    {
        $goods_mod =& bm('goods', array('_store_id' => $id));
        $search_name = LANG::get('all_goods');

        $conditions = $this->_get_query_conditions(array(
            array(
                'field' => 'goods_name',
                'name'  => 'keyword',
                'equal' => 'like',
                  ),
                                                         ));
        if ($conditions)
        {
            $search_name = sprintf(LANG::get('goods_include'), $_GET['keyword']);
            $sgcate_id   = 0;
        }
        else
        {
            $sgcate_id = empty($_GET['cate_id']) ? 0 : intval($_GET['cate_id']);
        }

        if ($sgcate_id > 0)
        {
            $gcategory_mod =& bm('gcategory', array('_store_id' => $id));
            $sgcate = $gcategory_mod->get_info($sgcate_id);
            $search_name = $sgcate['cate_name'];

            $sgcate_ids = $gcategory_mod->get_descendant_ids($sgcate_id);
        }
        else
        {
            $sgcate_ids = array();
        }

        /* 排序方式 */
        /*  $orders = array(
            'add_time desc' => LANG::get('add_time_desc'),
            'price asc' => LANG::get('price_asc'),
            'price desc' => LANG::get('price_desc'),
            ); */
        $orders = $this->_get_orders();
        $this->assign('orders', $orders);

        $page = $this->_get_page(20);
        $goods_list = $goods_mod->get_list(array(
            'conditions' => 'closed = 0 AND if_show = 1 AND default_spec > 0 ' . $conditions,
            'count' => true,
            'order'      => isset($_GET['order']) && isset($orders[trim(str_replace('asc','',str_replace('desc','',$_GET['order'])))]) ? $_GET['order'] : 'add_time desc',
            //'order' => empty($_GET['order']) || !isset($orders[$_GET['order']]) ? 'add_time desc' : $_GET['order'],
            'limit' => $page['limit'],
                                                 ), $sgcate_ids);
        foreach ($goods_list as $key => $goods)
        {
            empty($goods['default_image']) && $goods_list[$key]['default_image'] = Conf::get('default_goods_image');
        }
        $this->assign('searched_goods', $goods_list);

        $page['item_count'] = $goods_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);

        $this->assign('search_name', $search_name);
    }

    /**
     * 取得文章信息
     */
    function _get_article($id)
    {
        $article_mod =& m('article');
        return $article_mod->get_info($id);
    }

    function _get_seo_info($data)
    {
        $seo_info = $keywords = array();
        $seo_info['title'] = $data['mk_name'] . ' - '.$data['dangkou_address'] .'_'. $data['store_name'];
        $keywords = array(
            str_replace("\t", ' ', $data['region_name']),
            $data['store_name'],
                          );
        //$seo_info['keywords'] = implode(',', array_merge($keywords, $data['tags']));
        $seo_info['keywords'] = implode(',', $keywords);
        $seo_info['description'] = sub_str(strip_tags($data['description']), 10, true);
        return $seo_info;
    }

    /* 商品排序方式  edit  tyioocom  */
    function _get_orders()
    {
        return array(
            ''             => Lang::get('default_order'),
            'views'        => Lang::get('views'),
            'sales'        => Lang::get('sales_desc'),
            'price'        => Lang::get('price'),
            'add_time'     => Lang::get('add_time'),
            'comments'     => Lang::get('comment'),
            'credit_value' => Lang::get('credit_value'),
                     );
    }

    /**
     * 店铺顶部两个广告
     */
    function _get_store_advs()
    {
        $model_setting = &af('settings');
        $setting = $model_setting->getAll(); //载入系统设置数据

        $this->assign("store_advs",array(
                array(
                        'image'=>$setting['store_adv_image1'],
                        'img_url'=>$setting['store_adv_image1_href']
                ),
                array(
                        'image'=>$setting['store_adv_image2'],
                        'img_url'=>$setting['store_adv_image2_href']
                )
        ));
    }

}

?>
