<?php

/**
 *    我的收藏控制器
 *
 *    @author    Garbin
 *    @usage    none
 */
class My_favoriteApp extends MemberbaseApp
{
    /**
     *    收藏列表
     *
     *    @author    Garbin
     *    @return    void
     */
    function index()
    {
        $type = empty($_GET['type'])    ? 'goods' : trim($_GET['type']);
        if ($type == 'goods')
        {
            $this->_list_collect_goods();
        }
        elseif ($type == 'store')
        {
            /* 收藏店铺 */
            $this->_list_collect_store();
        }
        elseif ($type == 'behalf')
        {
                /* 收藏代发 */
                $this->_list_collect_behalf();
        }
        elseif ($type == 'sbehalf')
        {
                /* 签约代发 */
                $this->_list_collect_sbehalf();
        }
    }

    /**
     *    收藏项目
     *
     *    @author    Garbin
     *    @return    void
     */
    function add()
    {
        $type = empty($_GET['type'])    ? 'goods' : trim($_GET['type']);
        $item_id = empty($_GET['item_id'])  ? 0 : intval($_GET['item_id']);
        $keyword = empty($_GET['keyword'])  ? '' : trim($_GET['keyword']);
        if (!$item_id)
        {
            $this->show_warning('no_such_collect_item');

            return;
        }
        if ($type == 'goods')
        {
            $this->_add_collect_goods($item_id, $keyword);
        }
        elseif ($type == 'store')
        {
            $this->_add_collect_store($item_id, $keyword);
        }
        elseif($type == 'behalf')
        {
                $this->_add_collect_behalf($item_id, $keyword); //收藏代发
        }
        elseif($type == 'sbehalf')
        {
                $this->_add_collect_sbehalf($item_id, $keyword);//签约代发
        }
    }
    /**
     *    删除收藏的项目
     *
     *    @author    Garbin
     *    @return    void
     */
    function drop()
    {
        $type = empty($_GET['type'])    ? 'goods' : trim($_GET['type']);
        $item_id = empty($_GET['item_id'])  ? 0 : trim($_GET['item_id']);
        if (!$item_id)
        {
            $this->show_warning('no_such_collect_item');

            return;
        }
        if ($type == 'goods')
        {
            $this->_drop_collect_goods($item_id);
        }
        elseif ($type == 'store')
        {
            $this->_drop_collect_store($item_id);
        }
        elseif ($type == 'behalf')
        {
                $this->_drop_collect_behalf($item_id);
        }
        elseif ($type == 'sbehalf')
        {
                $this->_drop_collect_sbehalf($item_id);
        }
    }

    /**
     *    列表收藏的商品
     *
     *    @author    Garbin
     *    @return    void
     */
    function _list_collect_goods()
    {
        $conditions = $this->_get_query_conditions(array(array(
                'field' => 'goods_name',         //可搜索字段title
                'equal' => 'LIKE',          //等价关系,可以是LIKE, =, <, >, <>
            ),
        ));
        $model_goods =& m('goods');
        $page   =   $this->_get_page();    //获取分页信息
        $collect_goods = $model_goods->find(array(
            'join'  => 'be_collect,belongs_to_store,has_default_spec',
            'fields'=> 'this.*,store.store_name,store.store_id,collect.add_time,goodsspec.price,goodsspec.spec_id,store.mk_name,store.dangkou_address',
            'conditions' => 'collect.user_id = ' . $this->visitor->get('user_id') . $conditions,
            'count' => true,
            'order' => 'collect.add_time DESC',
            'limit' => $page['limit'],
        ));
        foreach ($collect_goods as $key => $goods)
        {
            empty($goods['default_image']) && $collect_goods[$key]['default_image'] = Conf::get('default_goods_image');
        }
        $page['item_count'] = $model_goods->getCount();   //获取统计的数据
        $this->_format_page($page);
        $this->assign('filtered', $conditions? 1 : 0); //是否有查询条件
        $this->assign('collect_goods', $collect_goods);
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                            LANG::get('my_favorite'), 'index.php?app=my_favorite',
                            LANG::get('collect_goods'));

        $this->import_resource(array(
            'script' => array(
                array(
                    'path' => 'dialog/dialog.js',
                    'attr' => 'id="dialog_js"',
                ),
                array(
                    'path' => 'jquery.ui/jquery.ui.js',
                    'attr' => '',
                ),
                array(
                    'path' => 'jquery.ui/i18n/' . i18n_code() . '.js',
                    'attr' => '',
                ),
                array(
                    'path' => 'jquery.plugins/jquery.validate.js',
                    'attr' => '',
                ),
            ),
            'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
        ));

        //当前用户中心菜单项
        $this->_curitem('my_favorite');

        $this->_curmenu('collect_goods');
        $this->assign('page_info', $page);          //将分页信息传递给视图，用于形成分页条
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('collect_goods'));
        $this->display('my_favorite.goods.index.html');
    }

    /**
     *    列表收藏的店铺
     *
     *    @author    Garbin
     *    @return    void
     */
    function _list_collect_store()
    {
        $conditions = $this->_get_query_conditions(array(array(
                'field' => 'store_name',         //可搜索字段title
                'equal' => 'LIKE',          //等价关系,可以是LIKE, =, <, >, <>
            ),
        ));
        $model_store =& m('store');
        $model_goods=& m('goods');
        $page   =   $this->_get_page();    //获取分页信息
        $collect_store = $model_store->find(array(
            'join'  => 'be_collect,belongs_to_user',
            'fields'=> 'this.*,member.user_name,collect.add_time',
            'conditions' => 'collect.user_id = ' . $this->visitor->get('user_id') . $conditions,
            'count' => true,
            'order' => 'collect.add_time DESC',
            'limit' => $page['limit'],
        ));
        if(is_array($collect_store))
        {
            foreach ($collect_store as $key=>$store)
            {
                $collect_store[$key]['recently_goods'] = $model_goods->find(array(
                        'fields'=>'goods_id,default_image,goods_name',
                        'conditions'=>'store_id='.$store['store_id'],
                        'order'=>'add_time DESC',
                        'limit'=> 5,
                ));
                if($collect_store[$key]['recently_goods'])
                {
                    foreach ($collect_store[$key]['recently_goods'] as $key1=>$value)
                    {
                        empty($value['default_image']) && $collect_store[$key]['recently_goods'][$key1]['default_image'] = Conf::get('default_goods_image');
                    }
                }
            }
        }
        $page['item_count'] = $model_store->getCount();   //获取统计的数据
        $this->_format_page($page);
        $step = intval(Conf::get('upgrade_required'));
        $step < 1 && $step = 5;
        foreach ($collect_store as $key => $store)
        {
            empty($store['store_logo']) && $collect_store[$key]['store_logo'] = Conf::get('default_store_logo');
            $collect_store[$key]['credit_image'] = $this->_view->res_base . '/images/' . $model_store->compute_credit($store['credit_value'], $step);
        }
        $this->assign('collect_store', $collect_store);

        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                        LANG::get('my_favorite'), 'index.php?app=my_favorite',
                        LANG::get('collect_store'));

        $this->import_resource(array(
            'script' => array(
                array(
                    'path' => 'dialog/dialog.js',
                    'attr' => 'id="dialog_js"',
                ),
                array(
                    'path' => 'jquery.ui/jquery.ui.js',
                    'attr' => '',
                ),
                array(
                    'path' => 'jquery.ui/i18n/' . i18n_code() . '.js',
                    'attr' => '',
                ),
                array(
                    'path' => 'jquery.plugins/jquery.validate.js',
                    'attr' => '',
                ),
            ),
            'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
        ));
        //当前用户中心菜单项
        $this->_curitem('my_favorite');

        $this->_curmenu('collect_store');
        $this->assign('page_info', $page);          //将分页信息传递给视图，用于形成分页条
        $this->assign('filtered', $conditions? 1 : 0); //是否有查询条件
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('collect_store'));
        $this->display('my_favorite.store.index.html');
    }

    /**
     *    列表收藏的代发
     *
     *    @author    tiq
     *    @return    void
     */
    function _list_collect_behalf()
    {
        $conditions = $this->_get_query_conditions(array(array(
                        'field' => 'bh_name',         //可搜索字段title
                        'equal' => 'LIKE',          //等价关系,可以是LIKE, =, <, >, <>
        ),
        ));
        $model_behalf =& m('behalf');
        $page   =   $this->_get_page();    //获取分页信息
        $collect_behalf = $model_behalf->find(array(
                        'join'  => 'be_collect,belongs_to_user',
                        'fields'=> 'this.*,member.user_name,collect.add_time',
                        'conditions' => 'collect.user_id = ' . $this->visitor->get('user_id') . $conditions,
                        'count' => true,
                        'order' => 'collect.add_time DESC',
                        'limit' => $page['limit'],
        ));
        $page['item_count'] = $model_behalf->getCount();   //获取统计的数据
        $this->_format_page($page);
        /* $step = intval(Conf::get('upgrade_required'));
        $step < 1 && $step = 5; */
        foreach ($collect_behalf as $key => $behalf)
        {
                empty($behalf['bh_logo']) && $collect_behalf[$key]['bh_logo'] = "/data/system/default_behalf_logo.gif";
        }
        $this->assign('collect_behalf', $collect_behalf);

        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                        LANG::get('my_favorite'), 'index.php?app=my_favorite',
                        LANG::get('collect_behalf'));

        $this->import_resource(array(
                        'script' => array(
                                        array(
                                                        'path' => 'dialog/dialog.js',
                                                        'attr' => 'id="dialog_js"',
                                        ),
                                        array(
                                                        'path' => 'jquery.ui/jquery.ui.js',
                                                        'attr' => '',
                                        ),
                                        array(
                                                        'path' => 'jquery.ui/i18n/' . i18n_code() . '.js',
                                                        'attr' => '',
                                        ),
                                        array(
                                                        'path' => 'jquery.plugins/jquery.validate.js',
                                                        'attr' => '',
                                        ),
                        ),
                        'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
        ));
        //当前用户中心菜单项
        $this->_curitem('my_favorite');

        $this->_curmenu('collect_behalf');
        $this->assign('page_info', $page);          //将分页信息传递给视图，用于形成分页条
        $this->assign('filtered', $conditions? 1 : 0); //是否有查询条件
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('collect_behalf'));
        $this->display('my_favorite.behalf.index.html');
    }
    /**
     *    列表签约的代发
     *
     *    @author    tiq
     *    @return    void
     */
    function _list_collect_sbehalf()
    {
        $conditions = $this->_get_query_conditions(array(array(
                        'field' => 'bh_name',         //可搜索字段title
                        'equal' => 'LIKE',          //等价关系,可以是LIKE, =, <, >, <>
        ),
        ));
        $model_behalf =& m('behalf');
        $page   =   $this->_get_page();    //获取分页信息
        $collect_behalf = $model_behalf->find(array(
                        'join'  => 'be_signed,belongs_to_user',
                        'fields'=> 'this.*,member.user_name,collect.add_time',
                        'conditions' => 'collect.user_id = ' . $this->visitor->get('user_id') . $conditions,
                        'count' => true,
                        'order' => 'collect.add_time DESC',
                        'limit' => $page['limit'],
        ));

        /*系统设置的默认已签约的所有代发*/
        $model_setting = &af('settings');
        $setting = $model_setting->getAll(); //载入系统设置数据
        $default_signed_behalfs_ids = $setting['default_signed_behalfs'];

        if(!empty($default_signed_behalfs_ids))
        {
                $default_signed_behalfs = $model_behalf->find(array(
                                'join'  => 'belongs_to_user',
                                'fields'=> 'this.*,member.user_name',
                                'conditions' => db_create_in($default_signed_behalfs_ids,'behalf.bh_id'),
                ));
                foreach ($default_signed_behalfs as $key => $behalf)
                {
                        empty($behalf['bh_logo']) && $default_signed_behalfs[$key]['bh_logo'] = "/data/system/default_behalf_logo.gif";
                        /*获取代发的拿货范围*/
                        $markets = $model_behalf->getRelatedData('has_market',$behalf['bh_id']);
                        $default_signed_behalfs[$key]['behalf_markets'] = array_values($markets);
                }
                $this->assign('default_signed_behalfs',$default_signed_behalfs);
        }

        $page['item_count'] = $model_behalf->getCount();   //获取统计的数据
        $this->_format_page($page);
        /* $step = intval(Conf::get('upgrade_required'));
         $step < 1 && $step = 5; */
        foreach ($collect_behalf as $key => $behalf)
        {
                empty($behalf['bh_logo']) && $collect_behalf[$key]['bh_logo'] = "/data/system/default_behalf_logo.gif";
        }
        $this->assign('collect_behalf', $collect_behalf);

        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                        LANG::get('my_favorite'), 'index.php?app=my_favorite',
                        LANG::get('collect_sbehalf'));

        $this->import_resource(array(
                        'script' => array(
                                        array(
                                                        'path' => 'dialog/dialog.js',
                                                        'attr' => 'id="dialog_js"',
                                        ),
                                        array(
                                                        'path' => 'jquery.ui/jquery.ui.js',
                                                        'attr' => '',
                                        ),
                                        array(
                                                        'path' => 'jquery.ui/i18n/' . i18n_code() . '.js',
                                                        'attr' => '',
                                        ),
                                        array(
                                                        'path' => 'jquery.plugins/jquery.validate.js',
                                                        'attr' => '',
                                        ),

                        ),
                        'style' =>  array(
                                        array(
                                                        'path'=>'jquery.ui/themes/ui-lightness/jquery.ui.css',
                                                        'attr' => '',
                                ),
                                        array(
                                                        'path'=>'jquery.plugins/popModal/popModal.min.css',
                                                        'attr' => '',
                                        )
                        ),
        ));
        //当前用户中心菜单项
        $this->_curitem('my_favorite');

        $this->_curmenu('collect_behalf');
        $this->assign('page_info', $page);          //将分页信息传递给视图，用于形成分页条
        $this->assign('filtered', $conditions? 1 : 0); //是否有查询条件
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('collect_sbehalf'));
        $this->display('my_favorite.sbehalf.index.html');
    }

    /**
     *    删除收藏的商品
     *
     *    @author    Garbin
     *    @param     int $item_id
     *    @return    void
     */
    function _drop_collect_goods($item_id)
    {
        $ids = explode(',', $item_id);

        /* 解除“我”与商品ID为$ids的收藏关系 */
        $model_user =& m('member');
        $model_user->unlinkRelation('collect_goods', $this->visitor->get('user_id'), $ids);
        if ($model_user->has_error())
        {
            $this->show_warning($model_user->get_error());

            return;
        }
        $this->show_message('drop_collect_goods_successed');
    }

    /**
     *    删除收藏的店铺
     *
     *    @author    Garbin
     *    @param     int $item_id
     *    @return    void
     */
    function _drop_collect_store($item_id)
    {
        $ids = explode(',', $item_id);

        /* 解除“我”与店铺ID为$ids的收藏关系 */
        $model_user =& m('member');
        $model_user->unlinkRelation('collect_store', $this->visitor->get('user_id'), $ids);
        if ($model_user->has_error())
        {
            $this->show_warning($model_user->get_error());

            return;
        }
        $this->show_message('drop_collect_store_successed');
    }
    /**
     *    删除收藏的代发
     *
     *    @author    tiq
     *    @param     int $item_id
     *    @return    void
     */
    function _drop_collect_behalf($item_id)
    {
        $ids = explode(',', $item_id);

        /* 解除“我”与店铺ID为$ids的收藏关系 */
        $model_user =& m('member');
        $model_user->unlinkRelation('collect_behalf', $this->visitor->get('user_id'), $ids);
        if ($model_user->has_error())
        {
                $this->show_warning($model_user->get_error());

                return;
        }
        $this->show_message('drop_collect_behalf_successed');
    }

    /**
     *    解除签约的代发
     *
     *    @author    tiq
     *    @param     int $item_id
     *    @return    void
     */
    function _drop_collect_sbehalf($item_id)
    {
        $ids = explode(',', $item_id);

        /* 解除“我”与店铺ID为$ids的收藏关系 */
        $model_user =& m('member');
        $model_user->unlinkRelation('collect_sbehalf', $this->visitor->get('user_id'), $ids);
        if ($model_user->has_error())
        {
                $this->show_warning($model_user->get_error());

                return;
        }
        $this->show_message('drop_collect_sbehalf_successed');
    }

    /**
     *    收藏商品
     *
     *    @author    Garbin
     *    @param     int    $goods_id
     *    @param     string $keyword
     *    @return    void
     */
    function _add_collect_goods($goods_id, $keyword)
    {
        /* 验证要收藏的商品是否存在 */
        $model_goods =& m('goods');
        $goods_info  = $model_goods->get($goods_id);

        if (empty($goods_info))
        {
            /* 商品不存在 */
            $this->json_error('no_such_goods');
            return;
        }


        $model_user =& m('member');
        //验证是否已经收藏
        $collect_goods = $model_user->getRelatedData('collect_goods',$this->visitor->get('user_id'),array(
                'fields'=>'goods_id'
        ));
        if(!empty($collect_goods))
        {
            foreach ($collect_goods as $fgoods)
            {
                if($fgoods['goods_id'] == $goods_id)
                {
                    $this->json_error('goods_already_collected');
                    return;
                }
            }
        }


        $model_user->createRelation('collect_goods', $this->visitor->get('user_id'), array(
            $goods_id   =>  array(
                'keyword'   =>  $keyword,
                'add_time'  =>  gmtime(),
            )
        ));

        /* 更新被收藏次数 */
        $model_goods->update_collect_count($goods_id);

        $goods_image = $goods_info['default_image'] ? $goods_info['default_image'] : Conf::get('default_goods_image');
        $goods_url  = SITE_URL . '/' . url('app=goods&id=' . $goods_id);
        $this->send_feed('goods_collected', array(
            'user_id'   => $this->visitor->get('user_id'),
            'user_name'   => $this->visitor->get('user_name'),
            'goods_url'   => $goods_url,
            'goods_name'   => $goods_info['goods_name'],
            'images'    => array(array(
                'url' => SITE_URL . '/' . $goods_image,
                'link' => $goods_url,
            )),
        ));

        /* 收藏成功 */
        $this->json_result('', 'collect_goods_ok');
    }

    /**
     *    收藏店铺
     *
     *    @author    Garbin
     *    @param     int    $store_id
     *    @param     string $keyword
     *    @return    void
     */
    function _add_collect_store($store_id, $keyword)
    {
        /* 验证要收藏的店铺是否存在 */
        $model_store =& m('store');
        $store_info  = $model_store->get($store_id);
        if (empty($store_info))
        {
            /* 店铺不存在 */
            return;
        }
        $model_user =& m('member');
        //验证是否已经收藏
        $collect_stores = $model_user->getRelatedData('collect_store',$this->visitor->get('user_id'),array(
                'fields'=>'store_id'
        ));
        if(!empty($collect_stores))
        {
            foreach ($collect_stores as $fstore)
            {
                if($fstore['store_id'] == $store_id)
                {
                    $this->json_error('store_already_collected');
                    return;
                }
            }
        }

        $model_user->createRelation('collect_store', $this->visitor->get('user_id'), array(
            $store_id   =>  array(
                'keyword'   =>  $keyword,
                'add_time'  =>  gmtime(),
            )
        ));
        $this->send_feed('store_collected', array(
            'user_id'   => $this->visitor->get('user_id'),
            'user_name'   => $this->visitor->get('user_name'),
            'store_url'   => SITE_URL . '/' . url('app=store&id=' . $store_id),
            'store_name'   => $store_info['store_name'],
        ));

        /* 收藏成功 */
        $this->json_result('', 'collect_store_ok');
    }

    /**
     *    收藏代发
     *
     *    @author    tiq
     *    @param     int    $bh_id
     *    @param     string $keyword
     *    @return    void
     */
    function _add_collect_behalf($bh_id, $keyword)
    {
        /* 验证要收藏的代发是否存在 */
        $model_behalf =& m('behalf');
        $behalf_info  = $model_behalf->get($bh_id);
        if (empty($behalf_info))
        {
                /* 代发不存在 */
                return;
        }
        $model_user =& m('member');
        $model_user->createRelation('collect_behalf', $this->visitor->get('user_id'), array(
                        $bh_id   =>  array(
                                        'keyword'   =>  $keyword,
                                        'add_time'  =>  gmtime(),
                        )
        ));
        $this->send_feed('behalf_collected', array(
                        'user_id'   => $this->visitor->get('user_id'),
                        'user_name'   => $this->visitor->get('user_name'),
                        'behalf_url'   => SITE_URL . '/' . url('app=bhstore&id=' . $bh_id),
                        'bh_name'   => $behalf_info['bh_name'],
        ));

        /* 收藏成功 */
        $this->json_result('', 'collect_behalf_ok');
    }

    /**
     *   签约代发
     *
     *    @author    tiq
     *    @param     int    $bh_id
     *    @param     string $keyword
     *    @return    void
     */
    function _add_collect_sbehalf($bh_id, $keyword)
    {
        /* 验证要签约的代发是否存在 */
        $model_behalf =& m('behalf');
        $behalf_info  = $model_behalf->get($bh_id);
        if (empty($behalf_info))
        {
                /* 代发不存在 */
                return;
        }
        $model_user =& m('member');
        $model_user->createRelation('collect_sbehalf', $this->visitor->get('user_id'), array(
                        $bh_id   =>  array(
                                        'keyword'   =>  $keyword,
                                        'add_time'  =>  gmtime(),
                        )
        ));
        $this->send_feed('behalf_collected', array(
                        'user_id'   => $this->visitor->get('user_id'),
                        'user_name'   => $this->visitor->get('user_name'),
                        'behalf_url'   => SITE_URL . '/' . url('app=bhstore&id=' . $bh_id),
                        'bh_name'   => $behalf_info['bh_name'],
        ));

        /* 签约成功 */
        $this->json_result('', 'collect_sbehalf_ok');
    }


    function _get_member_submenu()
    {
        $menus = array(
            array(
                'name'  => 'collect_goods',
                'url'   => 'index.php?app=my_favorite',
            ),
            array(
                'name'  => 'collect_store',
                'url'   => 'index.php?app=my_favorite&amp;type=store',
            ),
                array(
                        'name'  => 'collect_behalf',
                        'url'   => 'index.php?app=my_favorite&amp;type=behalf',
            ),
        );
        return $menus;
    }

    /**
     * 同款比价
     */
    function compareFav()
    {
        $citem_id = empty($_GET['citem_id'])  ? 0 : trim($_GET['citem_id']);
        if (!$citem_id)
        {
                $this->show_warning('no_such_collect_item');
                return;
        }
        $ids = explode(',', $citem_id);
        $goods_mod = & m("goods");
        $goods_result = $goods_mod->find(array(
                        'join'  => 'belongs_to_store',
                'conditions' => db_create_in($ids,'goods_id'),
        ));
        foreach ($goods_result as $key => $goods)
        {
                empty($goods['default_image']) && $goods_result[$key]['default_image'] = Conf::get('default_goods_image');
                $goods_result[$key]['mk_name'] = $this->_get_market_name($goods['mk_id']);
                //
                $goods_name = $goods['goods_name'];
                $pKh='/[A-Z]?\d+/';
                preg_match_all($pKh,$goods_name,$pKuanhao);
                $pKhnum=count($pKuanhao[0]);
                if($pKhnum>0)
                {
                        for($ii=0;$ii < $pKhnum;$ii++)
                        {
                                if(strlen($pKuanhao[0][$ii])==3 || (strlen($pKuanhao[0][$ii])==4 && substr($pKuanhao[0][$ii], 0,3)!= "201"))
                                {
                                        $khn = $pKuanhao[0][$ii];
                                        //echo  $kh."<br>";
                                        break;
                                }
                        }
                }

                $goods_name = str_replace($khn,"",$goods_name);
                $goods_name = str_replace("#","",$goods_name);
                $goods_name = str_replace("*","",$goods_name);
                $goods_name = str_replace("款号","",$goods_name);
                $goods_name = trim($goods_name);
                $goods_result[$key]['goods_name_clean'] = $goods_name;
                //
        }
        $this->assign("qrcode",generateQRfromGoogle(SITE_URL."/index.php?app=my_favorite&act=compareFav&citem_id=".$citem_id));
        $this->assign("goods",$goods_result);
        $this->assign('siteurl',SITE_URL);
        $this->display("my_favorite.compareFav.index.html");
    }

    /**
     * 打印清单
     */
    function printFav()
    {
        $pitem_id = empty($_GET['pitem_id'])  ? 0 : trim($_GET['pitem_id']);
        if (!$pitem_id)
        {
                $this->show_warning('no_such_collect_item');
                return;
        }
        $ids = explode(',', $pitem_id);
        $goods_mod = & m("goods");
        $goods_result = $goods_mod->find(array(
                        'join'  => 'belongs_to_store',
                        'conditions' => db_create_in($ids,'goods_id'),
        ));
        foreach ($goods_result as $key => $goods)
        {
                empty($goods['default_image']) && $goods_result[$key]['default_image'] = Conf::get('default_goods_image');
                $goods_result[$key]['mk_name'] = $this->_get_market_name($goods['mk_id']);
        }
        $this->assign("qrcode",generateQRfromGoogle(SITE_URL."/index.php?app=my_favorite&act=printFav&citem_id=".$pitem_id));
        $this->assign("goods",$goods_result);
        $this->assign('siteurl',SITE_URL);
        $this->display("my_favorite.printFav.index.html");
    }

    /**
     *
     * @param unknown $id
     */
    function _get_market_name($id)
    {
        $market_mod = & m('market');
        $layer = $market_mod->get_layer($id);
        if($layer == 2)
        {
                $market_one = $market_mod->get($id);
        }
        else if($layer == 3)
        {
                $temp_market = $market_mod->get($id);
                $market_one = $market_mod->get($temp_market['parent_id']);
        }
        return $market_one['mk_name'];
    }

    function dataPack() {
        $goodsIds = empty($_GET['item_id'])  ? 0 : trim($_GET['item_id']);
        if (!$goodsIds) {
            $this->show_warning('no_such_collect_item');
            return;
        }
        $time = time();
        $baseDir = ROOT_PATH.'/data/files/data_packs/'.$time.'/';
        if (!mkdir($baseDir, 0777, true)) {
            $this->show_warning("data_packs/{$time} directory not exists");
            return;
        }
        $csvFilename = $baseDir.$time.'.csv';
        $fp = fopen($csvFilename, 'w');
        if (!$fp) {
            $this->show_warning('cannot open csv file: '.$csvFilename);
            return;
        }
        $csvHeaders = iconv('UTF-8', 'GB2312', '宝贝名称,宝贝类目,店铺类目,新旧程度,省,城市,出售方式,宝贝价格,加价幅度,宝贝数量,有效期,运费承担,平邮,EMS,快递,付款方式,支付宝,发票,保修,自动重发,放入仓库,橱窗推荐,开始时间,心情故事,宝贝描述,宝贝图片,宝贝属性,团购价,最小团购件数,邮费模版ID,会员打折,修改时间,上传状态,图片状态,返点比例,新图片,视频,销售属性组合,用户输入ID串,用户输入名-值对,商家编码,销售属性别名,代充类型,宝贝编号,数字ID');
        fputcsv($fp, explode(',', $csvHeaders));
        $goodsIdsArray = explode(',', $goodsIds);
        foreach ($goodsIdsArray as $goodsId) {
            $taobaoItem = json_decode(file_get_contents('http://yjsc.51zwd.com/taobao-upload-multi-store/index.php?g=Taobao&m=Api&a=getTaobaoItem&db='.OEM.'&goodsId='.$goodsId));
            $picStr = $this->handlePic($taobaoItem->item_imgs->item_img, $baseDir.$time.'/');
            if ($picStr) {
                $skus = $this->parseSkus($taobaoItem->skus);
                $line = iconv('UTF-8', 'GB2312', "{$taobaoItem->title},{$taobaoItem->cid},,0,广东,广州,b,{$taobaoItem->price},0,99,14,1,20,20,20,,,0,0,1,0,1,,,{$taobaoItem->desc},,{$taobaoItem->props_name},,,,0,,,,0,{$picStr},,{$skus},,,{$taobaoItem->outer_id},,,,{$taobaoItem->num_iid}");
                fputcsv($fp, explode(',', $line));
            }
        }
        fclose($fp);
        import('zip');
        $zip = new PHPZip();
        $zipFilename = $time.'.zip';
        $zip->downloadZip($baseDir, $zipFilename, true, $baseDir, $baseDir, $zipFilename);
    }

    function handlePic($itemImgs, $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                $this->show_warning("cannot mkdir: {$dir}");
                return false;
            }
        }
        $picStr = '';
        for ($i = 0; $i < count($itemImgs); $i++) {
            $itemImg = $itemImgs[$i];
            $picName = $this->getPicName($itemImg->url);
            $pic = $dir.$picName.'.tbi';
            $content = file_get_contents($itemImg->url);
            file_put_contents($pic, $content);
            $picStr .= $picName.':0:'.$i.':|;';
        }
        return $picStr;
    }

    function getPicName($picUrl) {
        $lastSlashPos = strrpos($picUrl, '/');
        $str = substr($picUrl, $lastSlashPos + 1);
        return substr($str, 0, strlen($str) - 4);
    }

    function parseSkus($skus) {
        $ret = '';
        $skuArray = $skus->sku;
        foreach ($skuArray as $sku) {
            $propsArray = explode(';', $sku->properties_name);
            $ret .= $sku->price.':'.$sku->quantity.'::';
            foreach ($propsArray as $prop) {
                $parts = explode(':', $prop);
                $ret .= $parts[0].':'.$parts[1].';';
            }
        }
        return $ret;
    }
}

?>
