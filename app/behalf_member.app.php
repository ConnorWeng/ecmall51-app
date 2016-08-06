<?php

/**
 *    代发用户中心控制器
 *
 *    @author    tiq
 *    @usage    none
 */
class Behalf_memberApp extends MemberbaseApp
{

    function index()
    {
        /*获取市场列表*/
        $this->_get_markets();
        /* 获取订单列表 */
        $this->_get_orders();
        /*统计代发相关*/
        $this->_get_statics();
        /*获取可用快递*/
        $this->_get_related_delivery();
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                         LANG::get('basic_information'), 'index.php?app=behalf_member',
                         LANG::get('behalf_order_list'));

        /* 当前用户中心菜单 */
        $type = (isset($_GET['type']) && $_GET['type'] != '') ? trim($_GET['type']) : 'all_orders';
        $this->_curitem('behalf_manage');
        $this->_curmenu($type);
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('behalf_manage'));
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
                    'path' => 'jquery.ui/jquery-ui-timepicker-addon.min.js',
                    'attr' => '',
                      ),
                array(
                    'path' => 'jquery.ui/jquery-ui-timepicker-zh-CN.js',
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
                /* array(
                   'path' => 'jquery.plugins/popModal/jquery.webui-popover.min.js',
                   'attr' => '',,jquery.plugins/popModal/jquery.webui-popover.min.css
                   ), */
                              ),
            'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
                                     ));
        /* 显示订单列表 */
        $this->display('behalf_member.index.html');
    }
    /**
     *    查看订单详情
     *
     *    @author    Garbin
     *    @return    void
     */
    function view()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

        $model_order =& m('order');
        $order_info  = $model_order->findAll(array(
            'conditions'    => "order_alias.order_id={$order_id} ",
            'join'          => 'has_orderextm',
                                                   ));
        $order_info = current($order_info);
        if (!$order_info)
        {
            $this->show_warning('no_such_order');
            return;
        }

        $member_contact = $this->_get_member_profile($order_info['buyer_id']);
        $order_info['im_qq'] = $member_contact['im_qq'];
        $order_info['im_aliww'] = $member_contact['im_aliww'];
        $order_info['phone_mob'] = $member_contact['phone_mob'];

        /* 团购信息 */
        if ($order_info['extension'] == 'groupbuy')
        {
            $groupbuy_mod = &m('groupbuy');
            $group = $groupbuy_mod->get(array(
                'join' => 'be_join',
                'conditions' => 'order_id=' . $order_id,
                'fields' => 'gb.group_id',
                                              ));
            $this->assign('group_id',$group['group_id']);
        }

        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                         LANG::get('behalf_manage'), 'index.php?app=behalf_member',
                         LANG::get('view_order'));

        /* 当前用户中心菜单 */
        $this->_curitem('behalf_manage');
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('detail'));

        /*给出当前店铺的信息*/
        /* $store_model = & m('store');
           $store = $store_model->get('store_id='.$order_info['seller_id']); */

        /* 调用相应的订单类型，获取整个订单详情数据 */
        $order_type =& ot($order_info['extension']);
        $order_detail = $order_type->get_order_detail($order_id, $order_info);
        $spec_ids = array();
        foreach ($order_detail['data']['goods_list'] as $key => $goods)
        {
            empty($goods['goods_image']) && $order_detail['data']['goods_list'][$key]['goods_image'] = Conf::get('default_goods_image');
            $spec_ids[] = $goods['spec_id'];

        }

        // 查出最新的相应的货号
        $model_spec =& m('goodsspec');
        $spec_info = $model_spec->find(array(
            'conditions'    => $spec_ids,
            'fields'        => 'sku',
                                             ));
        ////商家编码
        $model_goodsattr =& m('goodsattr');
        foreach ($order_detail['data']['goods_list'] as $key => $goods)
        {
            $order_detail['data']['goods_list'][$key]['sku'] = $spec_info[$goods['spec_id']]['sku'];
            if(!$order_detail['data']['goods_list'][$key]['sku'])
            {
                $order_detail['data']['goods_list'][$key]['sku'] = getHuoHao($goods['goods_name']);
                if(!$order_detail['data']['goods_list'][$key]['sku'])
                {
                    $goods_AttrModel = &m('goodsattr');
                    $attrs = $goods_AttrModel->get(array(
                        'conditions' => "goods_id = ".$goods['goods_id']." AND attr_id = 13021751",
                                                         ));
                    $order_detail['data']['goods_list'][$key]['sku'] = $attrs['attr_value'];
                }
            }
            if(empty($goods['attr_value']))
            {
                $goods_seller_bm = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$goods['goods_id']} AND attr_id=1");
                $order_detail['data']['goods_list'][$key]['goods_seller_bm'] = $goods_seller_bm;
            }
            else
            {
                $order_detail['data']['goods_list'][$key]['goods_seller_bm'] = $goods['attr_value'];
            }
        }



        //tiq
        /*store,goods infos*/
        $data = $stores = array();
        $goods_model = & m('goods');
        $store_model = & m('store');
        foreach ($order_detail['data']['goods_list'] as $key => $goods)
        {
            if(!empty($goods['goods_id']))
            {
                $result = $goods_model->get(array(
                    'fields'=>'store_id',
                    'conditions'=>'goods_id='.$goods['goods_id'],
                                                  ));
                if($result['store_id'] &&!in_array($result['store_id'], $stores))
                {
                    $stores[] = $result['store_id'];
                    $data[$result['store_id']]['store_info'] = $store_model->get($result['store_id']);
                    $data[$result['store_id']]['goods_list'][] = $goods;
                }
                else
                {
                    $data[$result['store_id']]['goods_list'][] = $goods;
                }

            }

        }

        //
        $model_orderrefund =& m('orderrefund');
        $refunds = $model_orderrefund->get(array(
                'conditions'=>'order_id='.$order_info['order_id'].' AND receiver_id='.$this->visitor->get('user_id').' AND closed=0 AND type=1',
        ));
        $apply_fees = $model_orderrefund->get(array(
                'conditions'=>'order_id='.$order_info['order_id'].' AND sender_id='.$this->visitor->get('user_id').' AND closed=0 AND type=2',
        ));

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


        $this->assign('merge_sgoods',$data);
        //$this->assign('store',$store);
        $this->assign('order', $order_info);
        $this->assign("refunds",$refunds);
        $this->assign("apply_fees",$apply_fees);
        $this->assign($order_detail['data']);
        $this->display('behalf_member.view.html');
    }
    /**
     * 设置代发
     */
    function set_behalf()
    {
        $user_id = $this->visitor->get('user_id');
        $behalf_mod =& m('behalf');
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),  'index.php?app=member',
                             LANG::get('basic_information'),'index.php?app=behalf_member',
                             LANG::get('my_behalf'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_behalf');

            /* 当前所处子菜单 */
            $this->_curmenu('basic_information');
            /*获得代发对应的快递*/
            $behalf = $behalf_mod->get($user_id);

            $region_mod =& m('region');
            $this->assign('regions', $region_mod->get_options(0));

            $this->assign("behalf",$behalf);
            $this->import_resource('jquery.plugins/jquery.validate.js,mlselection.js');
            $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('my_delivery'));
            $this->display('my_behalf.index.html');
        }
        else
        {
            if(!$this->_allow_behalf_setting('set_behalf')) return;
            
            $data = $_POST;
            $data['max_orders'] = abs(intval($data['max_orders']));
            foreach ($data as $key=>$value)
            {
                if(empty($value) && $data['max_orders']) 
                    unset($data[$key]);
            }
            /* 检查名称是否已存在 */
            if (!$behalf_mod->unique(trim($data['bh_name']),$data['bh_id']))
            {
                $this->show_warning('name_exist');
                return;
            }

            $image = $this->_upload_image($data['bh_id']);
            $data = array_merge($data,$image);
            if ($this->has_error())
            {
                $this->show_warning($this->get_error());

                return;
            }
            $behalf_mod->edit($data['bh_id'], $data);
            if($behalf_mod->has_error())
            {
                $this->show_warning($behalf_mod->get_error());
            }
            $this->show_message('edit_delivery_successed');
        }

    }
    /**
     * 设置代发包含哪些快递
     */
    function set_delivery()
    {
        $user_id = $this->visitor->get('user_id');
        $behalf_mod =& m('behalf');
        $delivery_mod =& m('delivery');
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),  'index.php?app=member',
                             LANG::get('basic_information'),'index.php?app=behalf_member',
                             LANG::get('my_delivery'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_delivery');

            /* 当前所处子菜单 */
            $this->_curmenu('basic_information');
            /*获得代发对应的快递*/
            $behalf = $behalf_mod->get($user_id);
            $deliveries = $delivery_mod->find();
            $behalf_deliveries = $behalf_mod->getRelatedData('has_delivery',$user_id);
            $exist_deliveries = array();
            foreach ($behalf_deliveries as $value)
            {
                $exist_deliveries[] = $value['dl_id'];
            }
            $this->assign("behalf",$behalf);
            $this->assign("deliveries",$deliveries);
            $this->assign("exist_deliveries",$exist_deliveries);
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js',
                                         ));
            $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('my_delivery'));
            $this->display('behalf.deliveryform.html');
        }
        else
        {
            if(!$this->_allow_behalf_setting('set_delivery'))  return;
            
            $data = $_POST;
            extract($data);
            if(!empty($data))
            {
                $behalf_mod->unlinkRelation('has_delivery',$user_id);
                $behalf_mod->createRelation('has_delivery',$user_id,$deliveries);
                if($behalf_mod->has_error())
                {
                    $this->show_warning($behalf_mod->get_error());
                }
            }
            $this->show_message('edit_delivery_successed');
        }
    }
    /**
     *  设置快递为一个固定的费用
     */
    function set_delivery_fee()
    {
        $user_id = $this->visitor->get('user_id');
        $behalf_mod =& m('behalf');
        $delivery_mod =& m('delivery');
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),  'index.php?app=member',
                             LANG::get('basic_information'),'index.php?app=behalf_member',
                             LANG::get('my_behalf_delivery_fee'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_behalf_delivery_fee');

            /* 当前所处子菜单 */
            $this->_curmenu('basic_information');
            /*获得代发对应的快递*/
            $behalf = $behalf_mod->get($user_id);
            $behalf_deliveries = $behalf_mod->getRelatedData('has_delivery',$user_id);

            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js',
                                         ));
            $this->assign("behalf",$behalf);
            $this->assign('deliveries',$behalf_deliveries);
            $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('my_behalf_delivery_fee'));
            $this->display('behalf.dl_feeform.html');
        }
        else
        {
            if(!$this->_allow_behalf_setting('set_delivery_fee')) return;
            
            $data = $_POST;
            extract($data);
            if(!empty($data))
            {
                /* $behalf_mod->unlinkRelation('has_delivery',$user_id);
                   $behalf_mod->createRelation('has_delivery',$user_id,$deliveries); */
                $deliveries_fees = array();
                foreach ($dl_ids as $key=>$dl_id)
                {
                    $deliveries_fees[$key]['dl_id'] = intval($dl_id);
                    $deliveries_fees[$key]['first_amount'] = abs(intval($dl1_quantity[$key])) > 1 ? abs(intval($dl1_quantity[$key])):1;
                    $deliveries_fees[$key]['first_price'] = abs(floatval($dl1_fee[$key]));
                    $deliveries_fees[$key]['step_amount'] = abs(intval($dl2_quantity[$key])) > 1 ? abs(intval($dl2_quantity[$key])):1;
                    $deliveries_fees[$key]['step_price'] = abs(floatval($dl2_fee[$key]));
                }

                $behalf_mod->unlinkRelation('has_delivery',$user_id);
                $behalf_mod->createRelation('has_delivery',$user_id,$deliveries_fees);
                //$behalf_mod->updateRelation('has_delivery',$user_id,$deliveries_ids,$deliveries_fees);
                if($behalf_mod->has_error())
                {
                    $this->show_warning($behalf_mod->get_error());
                }
            }
            $this->show_message('edit_delivery_successed');
        }
    }
    /**
     * 设置拿货范围
     */
    function set_behalf_market()
    {
        $user_id = $this->visitor->get('user_id');
        $behalf_mod =& m('behalf');
        $market_mod =& m('market');
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),  'index.php?app=member',
                             LANG::get('basic_information'),'index.php?app=behalf_member',
                             LANG::get('my_behalf_market'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_behalf_market');

            /* 当前所处子菜单 */
            $this->_curmenu('basic_information');
            /*获得代发对应的快递*/
            $behalf = $behalf_mod->get($user_id);
            $markets = $market_mod->get_list(1);
            $behalf_markets = $behalf_mod->getRelatedData('has_market',$user_id);
            $exist_markets = array();
            foreach ($behalf_markets as $value)
            {
                $exist_markets[] = $value['mk_id'];
            }

            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js',
                                         ));
            $this->assign("behalf",$behalf);
            $this->assign('markets',$markets);
            $this->assign('exist_markets',$exist_markets);
            $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('my_behalf_market'));
            $this->display('behalf.marketform.html');
        }
        else
        {
            if(!$this->_allow_behalf_setting('set_behalf_market'))  return;
            
            $data = $_POST;
            extract($data);
            if(!empty($data))
            {
                $behalf_mod->unlinkRelation('has_market',$user_id);
                $behalf_mod->createRelation('has_market',$user_id,$markets);
                if($behalf_mod->has_error())
                {
                    $this->show_warning($behalf_mod->get_error());
                }
            }
            else 
            {
                $behalf_mod->unlinkRelation('has_market',$user_id);
                if($behalf_mod->has_error())
                {
                    $this->show_warning($behalf_mod->get_error());
                }
            }
            $this->show_message('edit_delivery_successed');
        }
    }
    /**
     * 电子面单账号设置
     */
    function set_behalf_account()
    {
        $user_id = $this->visitor->get('user_id');
        $behalf_mod =& m('behalf');
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),  'index.php?app=member',
                LANG::get('basic_information'),'index.php?app=behalf_member',
                LANG::get('set_behalf_account'));
        
            /* 当前用户中心菜单 */
            $this->_curitem('set_behalf_account');
        
            /* 当前所处子菜单 */
            $this->_curmenu('set_behalf_account');
            
            $behalf = $behalf_mod->get($user_id);
            
            $this->assign('infos',Conf::get('behalf_modeb_account_'.$user_id));
            $this->assign("behalf",$behalf); 
            $this->display('behalf.modeb.account.html');
        }
        else
        {
            if(!$this->_allow_behalf_setting('set_behalf_account'))  return;
            $data = array();
            $data['behalf_modeb_account_'.$user_id] = array();
            $yto_account = empty($_POST['yto_account'])?'':trim($_POST['yto_account']);
            $yto_pass = empty($_POST['yto_pass'])?'':trim($_POST['yto_pass']);
            $zto_account = empty($_POST['zto_account'])?'':trim($_POST['zto_account']);
            $zto_pass = empty($_POST['zto_pass'])?'':trim($_POST['zto_pass']);
            
            if(!empty($yto_pass) && !empty($yto_account))
            {
                $data['behalf_modeb_account_'.$user_id]['yto_account'] = $yto_account;
                $data['behalf_modeb_account_'.$user_id]['yto_pass']= $yto_pass;
            }
            if(!empty($zto_pass) && !empty($zto_account))
            {
                $data['behalf_modeb_account_'.$user_id]['zto_account'] = $zto_account;
                $data['behalf_modeb_account_'.$user_id]['zto_pass']= $zto_pass;
            }
            
            $model_setting = &af('settings');
            $setting = $model_setting->getAll();
           /*  if($setting['behalf_modeb_account_'.$user_id])
            {
                $data['behalf_modeb_account_'.$user_id] = $setting['behalf_modeb_account_'.$user_id];
            }
            else
            {
                $data['behalf_modeb_account_'.$user_id] = array();
            } */
            
            
            
            
            $model_setting->setAll($data);
            $this->show_message('edit_behalf_account_successed');
        }
    }
    /**
     * 设置用户中心代发收费
     */
    function  set_fee()
    {
        $user_id = $this->visitor->get('user_id');
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),  'index.php?app=member',
                             LANG::get('basic_information'),'index.php?app=behalf_member',
                             LANG::get('my_behalf_fee'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_behalf_fee');

            /* 当前所处子菜单 */
            $this->_curmenu('basic_information');

            $gcategory_mod =& m('gcategory');
            $gcategories = $gcategory_mod->get_list(0);
            $category_behalf_mod =& m('category_behalf');
            $category_behalvies = $category_behalf_mod->getAll("select * from ".DB_PREFIX."category_behalf"." where bh_id = {$user_id} ");
            //合并
            $my_gcategories = array();
            foreach ($gcategories as $key=>$value)
            {
                $gdata = array();
                $gdata['cate_id'] = $key;
                $gdata['cate_name'] = $value['cate_name'];
                foreach ($category_behalvies as $cb)
                {
                    if($key == $cb['cate_id'])
                    {
                        $gdata['bh_fee'] = $cb['bh_fee'];
                    }
                }
                if(empty($gdata['bh_fee']))
                {
                    $gdata['bh_fee'] = 0;
                }
                $my_gcategories[] = $gdata;
            }



            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js',
                                         ));
            $this->assign('my_gcategories',$my_gcategories);
            $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('my_behalf_fee'));
            $this->display('behalf.feeform.html');
        }
        else
        {
            $data = array(
            );

            $data = $_POST;

            extract($data);

            if(count($cids) == count($fees))
            {
                for($i=0;$i<count($cids);$i++)
                {
                    $idata = array();
                    $idata['cate_id'] = intval($cids[$i]);
                    $idata['bh_id'] = intval($user_id);
                    $idata['bh_fee'] = floatval($fees[$i]);
                    $model_cid_behalf = & m('category_behalf');
                    $model_cid_behalf->drop("cate_id = {$idata['cate_id']} and bh_id = {$idata['bh_id']}");
                    $model_cid_behalf->add($idata);
                    if($model_cid_behalf->has_error())
                    {
                        $this->show_warning($model_cid_behalf->get_error());
                    }
                }
            }

            $this->show_message('edit_behalffee_successed');
        }
    }
    /**
     *    收到货款
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function received_pay()
    {
    	return;//2015-08-03 21:50
        list($order_id, $order_info)    = $this->_get_valid_order_info(ORDER_PENDING);
        if (!$order_id)
        {
            echo Lang::get('no_such_order');

            return;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->display('behalf_member.received_pay.html');
        }
        else
        {
            $model_order    =&  m('order');
            $model_order->edit(intval($order_id), array('status' => ORDER_ACCEPTED, 'pay_time' => gmtime()));
            if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());

                return;
            }

            #TODO 发邮件通知
            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                'order_id'  => $order_id,
                'operator'  => addslashes($this->visitor->get('user_name')),
                'order_status' => order_status($order_info['status']),
                'changed_status' => order_status(ORDER_ACCEPTED),
                'remark'    => $_POST['remark'],
                'log_time'  => gmtime(),
                                  ));

            /* 发送给买家邮件，提示等待安排发货 */
            $model_member =& m('member');
            $buyer_info   = $model_member->get($order_info['buyer_id']);
            $mail = get_mail('tobuyer_offline_pay_success_notify', array('order' => $order_info));
            $this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));

            $new_data = array(
                'status'    => Lang::get('order_accepted'),
                'actions'   => array(
                    'cancel',
                    'shipped'
                                     ), //可以取消可以发货
                              );

            /* 如果匹配到的话，修改第三方订单状态 */
            $ordervendor_mod = &m('ordervendor');
            $ordervendor_mod->edit("ecm_order_id={$order_id}", array(
                'status' => VENDOR_ORDER_ACCEPTED,
                                                                     ));

            $this->pop_warning('ok');
        }

    }
    /**
     *    调整费用
     *
     *    @author    Garbin
     *    @return    void
     */
    function adjust_fee()
    {
        list($order_id, $order_info)    = $this->_get_valid_order_info(array(ORDER_SUBMITTED, ORDER_PENDING));
        if (!$order_id)
        {
            echo Lang::get('no_such_order');

            return;
        }
        $model_order    =&  m('order');
        $model_orderextm =& m('orderextm');
        //$model_delivery = & m('delivery');
        $shipping_info   = $model_orderextm->get($order_id);
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->assign('shipping', $shipping_info);
            //$this->assign("deliverys",$model_delivery->findAll());
            $this->display('behalf_member.adjust_fee.html');
        }
        else
        {
            /* 配送费用 */
            $shipping_fee = isset($_POST['shipping_fee']) ? abs(floatval($_POST['shipping_fee'])) : 0;
            /* 折扣金额 */
            $goods_amount     = isset($_POST['goods_amount'])     ? abs(floatval($_POST['goods_amount'])) : 0;
            /* 订单实际总金额 */
            $order_amount = round($goods_amount + $shipping_fee, 2);
            if ($order_amount <= 0)
            {
                /* 若商品总价＋配送费用扣队折扣小于等于0，则不是一个有效的数据 */
                $this->pop_warning('invalid_fee');

                return;
            }
            $data = array(
                'goods_amount'  => $goods_amount,    //修改商品总价
                'order_amount'  => $order_amount,     //修改订单实际总金额
                'pay_alter' => 1    //支付变更
                          );

            if(!empty($_POST['delivery']) && $shipping_info['dl_id'] != $_POST['delivery'])
            {
                //如果修改了快递
                $model_orderextm->edit($order_id, array('dl_id' => intval($_POST['delivery'])));
            }
            if ($shipping_fee != $shipping_info['shipping_fee'])
            {
                /* 若运费有变，则修改运费 */
                $model_orderextm->edit($order_id, array('shipping_fee' => $shipping_fee));
            }
            $model_order->edit($order_id, $data);

            if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());

                return;
            }
            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                'order_id'  => $order_id,
                'operator'  => addslashes($this->visitor->get('user_name')),
                'order_status' => order_status($order_info['status']),
                'changed_status' => order_status($order_info['status']),
                'remark'    => Lang::get('adjust_fee'),
                'log_time'  => gmtime(),
                                  ));

            /* 发送给买家邮件通知，订单金额已改变，等待付款 */
            /* $model_member =& m('member');
               $buyer_info   = $model_member->get($order_info['buyer_id']);
               $mail = get_mail('tobuyer_adjust_fee_notify', array('order' => $order_info));
               $this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));

               $new_data = array(
               'order_amount'  => price_format($order_amount),
               ); */

            $this->pop_warning('ok','behalf_member_adjust_fee');
        }
    }

    /**
     *    待发货的订单发货
     *
     *    @author    Garbin
     *    @return    void
     */
    function shipped()
    {
        list($order_id, $order_info)    = $this->_get_valid_order_info(array(ORDER_ACCEPTED, ORDER_SHIPPED));
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }
        $model_order    =&  m('order');
        $model_orderextm =& m('orderextm');
        //$model_orderbehalf=& m('orderbehalfs');
        $model_ordergoods=& m('ordergoods');
        $model_storediscount=& m('storediscount');
        $behalf_delivery = $model_orderextm->get($order_info['order_id']);
        
        //分润
        $fr_order = $model_order->findAll(array(
           'conditions'=>'order_id='.$order_id.' AND status='.ORDER_ACCEPTED,
            'include'=>array('has_ordergoods') 
        ));
        //dump($fr_order[$order_id]);
        //判断 订单是否缺货，如果 缺货，不能发货，2015-07-31 20：00注释 
       /*  $orders = $model_order->findAll(array(
        	'conditions'=>'order_id='.$order_id,
        	'include'=>array('has_ordergoods'),	
        )); 
        $order = current($orders);
        if(empty($order['order_goods']))
        {
        	echo Lang::get('order_goods_empty');
        	return;
        }
        foreach ($order['order_goods'] as $order_goods)
        {
        	if(empty($order_goods['oos_value']))
        	{
        		echo Lang::get('order_goods_isempty');
        		return;
        	}
        } */
        
        if (!IS_POST)
        {
            /* 显示发货表单 */
            header('Content-Type:text/html;charset=' . CHARSET);
            /* 获取物流公司名称，用于填写订单物流*/
            /* $mod_delivery =& m('delivery');
               $deliveries = $mod_delivery->find();
               if($deliveries)
               {
               foreach ($deliveries as $dl_id=>$dl)
               {
               $deliveries[$dl_id] = $dl['dl_name'];
               }
               }

               $this->assign('deliveries',$deliveries); */

            $mod_delivery =& m('delivery');
            //$dl_id = empty($behalf_delivery)?0:$behalf_delivery['dl_id'];
            //$dl =$mod_delivery->get($dl_id);
            $model_behalf = & m('behalf');
            $thisdelivery = $model_behalf->getRelatedData('has_delivery',$behalf_delivery['bh_id']);
            $thisdelivery_ids = array();
            foreach ($thisdelivery as $value)
            {
                $thisdelivery_ids[] = $value['dl_id'];
            }

            $this->assign('behalf_delivery',$behalf_delivery);
            $this->assign("deliverys",$mod_delivery->findAll(array('conditions'=>db_create_in($thisdelivery_ids,'dl_id'))));
            $this->assign('order', $order_info);
            $this->display('behalf_member.shipped.html');
        }
        else
        {
            if (empty($_POST['invoice_no']))
            {
                $this->pop_warning('invoice_no_empty');
                return;
            }
            /* if (empty($_POST['logistics']))
               {
               $this->pop_warning('logistics_empty');
               return;
               } */
            /* if(exist_invoiceno(trim($_POST['invoice_no'])))
            {
                $this->pop_warning('invoice_no_exist');
                return;
            }  */

            $edit_data = array('status' => ORDER_SHIPPED, 'invoice_no' => trim($_POST['invoice_no']));
            $is_edit = true;
            if (empty($order_info['invoice_no']) || $edit_data['invoice_no'] == $order_info['invoice_no'])
            {
                /*商付通v2.2.1 更新商付通定单状态 开始*/
                if($order_info['payment_code']=='sft' || $order_info['payment_code']=='chinabank' || $order_info['payment_code']=='alipay' || $order_info['payment_code']=='tenpay' || $order_info['payment_code']=='tenpay2')
                {
                    $my_moneylog=& m('my_moneylog')->edit('order_id='.$order_id,array('caozuo'=>20));
                }
                /*商付通v2.2.1  更新商付通定单状态 结束*/
                //不是修改发货单号
                $edit_data['ship_time'] = gmtime();
                $is_edit = false;
           
	            //分润
	            if(!empty($fr_order))
	            {
	                $behalf_discount = 0;
	                if(!empty($fr_order[$order_id]['order_goods']))
	                {
	                    foreach ($fr_order[$order_id]['order_goods'] as $goods)
	                    {
	                        //不能缺货
	                        if($goods['oos_value'])
	                        {
	                            $behalf_discount += $goods['behalf_to51_discount'];
	                        }
	                    } 
	                }
	                //快递费分润，8块分1
	                if($behalf_delivery['shipping_fee'] > 0)
	                {
	                    $shipping_fee = intval($behalf_delivery['shipping_fee']);
	                    $behalf_discount += intval($shipping_fee/8);
	                }
	                
	                if($behalf_discount > 0) 
	                {
	                    $edit_data['behalf_discount'] = $behalf_discount;//写入订单
	                    //转账
	                    include_once(ROOT_PATH.'/app/fakemoney.app.php');
	                    $fakemoneyapp = new FakeMoneyApp();
	                    $fr_reason = Lang::get('behalf_to_51_fr_reason').local_date('Y-m-d H:i:s',gmtime());
	                    //给用户转账
	                    $my_money_result=$fakemoneyapp->to_user_withdraw($this->visitor->get('user_id'),FR_USER,$behalf_discount, $fr_reason,$order_id,$fr_order[$order_id]['order_sn']);
	                    if($my_money_result !== true)
	                    {
	                        $this->pop_warning($my_money_result);
	                        return;
	                    }
	                }
	                
	            }
            
             }
            
            $affect_rows = $model_order->edit(intval($order_id), $edit_data);
            Log::write('MYorder_id is :'.$order_id.var_export($edit_data,true));
            if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());

                return;
            }
            if(!empty($_POST['delivery']) && $behalf_delivery['dl_id'] != $_POST['delivery'])
            {
                //如果修改了快递
                $model_orderextm->edit($order_id, array('dl_id' => intval($_POST['delivery'])));
                //$model_orderbehalf->edit($order_id,array('dl_id' => intval($_POST['delivery'])));
            }

            #TODO 发邮件通知
            /*记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                'order_id'  => $order_id,
                'operator'  => addslashes($this->visitor->get('user_name')),
                'order_status' => order_status($order_info['status']),
                'changed_status' => order_status(ORDER_SHIPPED),
                'remark'    => $_POST['remark'],
                'log_time'  => gmtime(),
                                  ));


            /* 发送给买家订单已发货通知 */
            $model_member =& m('member');
            $buyer_info   = $model_member->get($order_info['buyer_id']);
            $order_info['invoice_no'] = $edit_data['invoice_no'];
            $mail = get_mail('tobuyer_shipped_notify', array('order' => $order_info));
            //$this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));
            if($buyer_info['phone_mob']){
                $com = $model_order->get_delivery_bybehalf($order_info['order_id'],$order_info['bh_id']);
                $order_info['dl_name'] = $com;
                $order_info['consignee'] = $behalf_delivery['consignee'];
                $smail = get_mail('sms_order_notify', array('order' => $order_info));
                $this->sendSaleSms($buyer_info['phone_mob'],  addslashes($smail['message']));
            }
            /* $new_data = array(

               'status'    => Lang::get('order_shipped'),
               'actions'   => array(
               'cancel',
               'edit_invoice_no'
               ), //可以取消可以发货
               ); */
            /* if ($order_info['payment_code'] == 'cod')
               {
               $new_data['actions'][] = 'finish';
               } */

            /* 如果匹配到的话，修改第三方订单状态 */
            $ordervendor_mod = &m('ordervendor');
            $ordervendor_mod->edit("ecm_order_id={$order_id}", array(
                'status' => VENDOR_ORDER_SHIPPED,
                                                                     ));

            $this->pop_warning('ok','behalf_order_shipped');
        }
    }
    function test(){

        list($order_id, $order_info)    = $this->_get_valid_order_info(array(ORDER_ACCEPTED, ORDER_SHIPPED));
        if (!$order_id)
        {
            echo Lang::get('no_such_order');

            return;
        }
        $model_order    =&  m('order');
        $model_orderextm =& m('orderextm');
        $behalf_delivery = $model_orderextm->get($order_info['order_id']);

        if(true){

            $com = $model_order->get_delivery_bybehalf($order_info['order_id'],$order_info['bh_id']);
            $order_info['dl_name'] = $com;
            $smail = get_mail('sms_order_notify', array('order' => $order_info));
            $this->sendSaleSms('15900402562',  addslashes($smail['message']));
        }
    }
    function generate_behalf_list()
    {
        $order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
        }
        $status = array(ORDER_ACCEPTED);
        $order_ids = explode(',', $order_id);
        /*防止恶意篡改浏览器order_id*/

        $model_order    =&  m('order');
        /* 只有未发货的订单可以生成拿货单 */
        $orders  = $model_order->findAll(array(
            'conditions'    => "order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) ." AND order_alias.bh_id > 0",
            'join'          => 'has_orderextm,belongs_to_store',
            'include'       =>  array(
                'has_ordergoods',       //取出商品
                                      ),

                                               ));
        $model_goodsspec = & m('goodsspec');
        $model_goodsattr =& m('goodsattr');
        $model_goods =&m('goods');
        $model_store = &m('store');
        foreach ($orders as $key1 => $order)
        {
            foreach ($order['order_goods'] as $key2 => $goods)
            {
                empty($goods['goods_image']) && $orders[$key1]['order_goods'][$key2]['goods_image'] = Conf::get('default_goods_image');
                $goods_spec = $model_goodsspec->find(array(
                    'conditions' => ' goods_id = '.$goods['goods_id'].' AND spec_id = '.$goods['spec_id'],
                                                           ));
                $goods_spec = array_values($goods_spec);
                $orders[$key1]['order_goods'][$key2]['sku']=$goods_spec[0]['sku'];
                if(!$orders[$key1]['order_goods'][$key2]['sku'])
                {
                    $orders[$key1]['order_goods'][$key2]['sku'] = getHuoHao($goods['goods_name']);
                    if(!$orders[$key1]['order_goods'][$key2]['sku'])
                    {
                        $goods_AttrModel = &m('goodsattr');
                        $attrs = $goods_AttrModel->get(array(
                            'conditions' => "goods_id = ".$goods['goods_id']." AND attr_id = 13021751",
                                                             ));
                        $orders[$key1]['order_goods'][$key2]['sku'] = $attrs['attr_value'];
                    }
                }
                //如果是合并的订单
                if(!$order['store_id'] && $order['bh_id'])
                {
                    $query_goods = $model_goods->find(array(
                        'conditions'=>'g.goods_id = '.$goods['goods_id'],
                        'fields'=>'g.goods_id,g.store_id,store_name,dangkou_address,mk_name,tel',
                        'join'=>'belongs_to_store',
                                                            ));
                    $orders[$key1]['order_goods'][$key2]['store_id'] = $query_goods[$goods['goods_id']]['store_id'];
                    $orders[$key1]['order_goods'][$key2]['store_name'] = $query_goods[$goods['goods_id']]['store_name'];
                    $orders[$key1]['order_goods'][$key2]['dangkou_address'] = $query_goods[$goods['goods_id']]['dangkou_address'];
                    $orders[$key1]['order_goods'][$key2]['mk_name'] = $query_goods[$goods['goods_id']]['mk_name'];
                    $orders[$key1]['order_goods'][$key2]['tel'] = $query_goods[$goods['goods_id']]['tel'];
                    $temp_order = $model_order->get($order['order_id']);
                    $orders[$key1]['add_time'] = $temp_order['add_time'];
                }
                /*$orders[$key1]['order_goods'][$key2]['spec_1'] = $goods_spec[0]['spec_1'];
                  $orders[$key1]['order_goods'][$key2]['spec_2'] = $goods_spec[0]['spec_2']; */
                ////商家编码
                $result = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$goods['goods_id']} AND attr_id=1");
                $orders[$key1]['order_goods'][$key2]['goods_seller_bm'] = $result;

            }
        }
        //按照传入的订单顺序排序order by order_ids
        $order_result = array();
        foreach ($order_ids as $value)
        {
            if($orders[$value])
            {
                $order_result[$value] = $orders[$value];
            }
        }
        //如果是合并的代发订单，则要按照店铺拆开
        $order_last_result = array();
        foreach ($order_result as $key=>$value)
        {
            if($value['store_id'])
            {
                //不是多店铺订单
                $order_last_result[]=$value;
            }
            else
            {
                //按店铺分类
                $temp_store_ids = array();
                foreach ($value['order_goods'] as $order_g)
                {
                    $temp_store_ids[]=$order_g['store_id'];
                }
                $temp_store_ids = array_filter($temp_store_ids);
                $temp_store_ids = array_unique($temp_store_ids);
                foreach ($temp_store_ids as $store_id_t)
                {
                    $temp_order = $value;
                    $temp_order_goods = array();
                    foreach ($temp_order['order_goods'] as $order_g_t)
                    {
                        if($order_g_t['store_id'] == $store_id_t)
                        {
                            $temp_order_goods[]=$order_g_t;
                            $temp_order['seller_id'] = $order_g_t['store_id'];
                            $temp_order['seller_name'] = $order_g_t['store_name'];
                            $temp_order['dangkou_address'] = $order_g_t['dangkou_address'];
                            $temp_order['mk_name'] = $order_g_t['mk_name'];
                            $temp_order['tel'] = $order_g_t['tel'];
                        }
                    }
                    $temp_order['order_goods'] = $temp_order_goods;
                    $order_last_result[] = $temp_order;
                }
            }
        }
        //dump($order_last_result);
        //排序
        if(in_array(trim($_GET['gorder']),array('store','market')))
        {
            //提取反序的数组
            $nahuo_order = array();
            if(trim($_GET['gorder']) == 'store')
            {
                foreach ($order_last_result as $value)
                {
                    $nahuo_order[] = $value['seller_name'];
                }
                //$nahuo_order = array_unique($nahuo_order);
                array_multisort($nahuo_order,SORT_ASC,$order_last_result);
            }
            if(trim($_GET['gorder']) == 'market')
            {
                foreach ($order_last_result as $value)
                {
                    $nahuo_order[] = $value['mk_name'];
                }
                //$nahuo_order = array_unique($nahuo_order);
                array_multisort($nahuo_order,SORT_ASC,$order_last_result);
            }
        }
        $this->assign('orders', $order_last_result);
        $this->display("behalf_member.goods.list.html");
        //dump($orders);



    }

    /**
     *    取消订单
     *
     *    @author    Garbin
     *    @return    void
     */
    function cancel_order()
    {
        echo Lang::get('kill_function');
        return;
        /* 取消的和完成的订单不能再取消 */
        //list($order_id, $order_info)    = $this->_get_valid_order_info(array(ORDER_SUBMITTED, ORDER_PENDING, ORDER_ACCEPTED, ORDER_SHIPPED));
        $order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
        }
        $status = array(ORDER_SUBMITTED, ORDER_PENDING, ORDER_ACCEPTED, ORDER_SHIPPED);
        $order_ids = explode(',', $order_id);
        if ($ext)
        {
            $ext = ' AND ' . $ext;
        }

        $model_order    =&  m('order');
        $model_ordergoods=& m('ordergoods');
        $model_orderrefund = & m('orderrefund');
        /* $model_order_behalf = & m('orderbehalfs');
           $behalf_order_info = $model_order_behalf->find(array(
           'conditions'=>"order_id" . db_create_in($order_ids),
           ));
           if(!$behalf_order_info)
           {
           echo Lang::get('no_such_order');
           return;
           }
           foreach ($behalf_order_info as $key=>$value)
           {
           if($this->visitor->get('user_id') != $value['bh_id'])
           {
           echo Lang::get('no_such_order');
           return;
           }
           }*/
        /* 只有已发货的货到付款订单可以收货 */
        $order_info     = $model_order->find(array(
            'conditions'    => "order_id" . db_create_in($order_ids) . " AND status " . db_create_in($status) . $ext,
                                                   ));
        $ids = array_keys($order_info);
        if (!$order_info)
        {
            echo Lang::get('no_such_order');
            return;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('orders', $order_info);
            $this->assign('order_id', count($ids) == 1 ? current($ids) : implode(',', $ids));

            $this->display('behalf_member.cancel.html');
        }
        else
        {
            $model_order    =&  m('order');
            $ordervendor_mod = &m('ordervendor');
            foreach ($ids as $val)
            {
                $id = intval($val);
                $model_order->edit($id, array('status' => ORDER_CANCELED));
                if ($model_order->has_error())
                {
                    //$_erros = $model_order->get_error();
                    //$error = current($_errors);
                    //$this->json_error(Lang::get($error['msg']));
                    //return;
                    continue;
                }
                
                /*商付通v2.2.1  更新商付通定单状态 开始*/
                $my_money_mod =& m('my_money');
                $my_moneylog_mod =& m('my_moneylog');
                $my_moneylog_row=$my_moneylog_mod->getrow("select * from ".DB_PREFIX."my_moneylog where order_id='$id' and (caozuo='10' or caozuo='20') and s_and_z=1");
                $money=$my_moneylog_row['money'];//定单价格
                $buy_user_id=$my_moneylog_row['buyer_id'];//买家ID
                $sell_user_id=$my_moneylog_row['seller_id'];//卖家ID
                if($my_moneylog_row['order_id']==$id)
                {
                    $buy_money_row=$my_money_mod->getrow("select * from ".DB_PREFIX."my_money where user_id='$buy_user_id'");
                    $buy_money=$buy_money_row['money'];//买家的钱
                    $buy_money_dj=$buy_money_row['money_dj'];//买家的钱

                    $sell_money_row=$my_money_mod->getrow("select * from ".DB_PREFIX."my_money where user_id='$sell_user_id'");
                    $sell_money=$sell_money_row['money'];//卖家的冻结资金
                    $sell_money_dj=$sell_money_row['money_dj'];//卖家的冻结资金

                    $new_buy_money = $buy_money+$money;
                    $new_sell_money = $sell_money_dj-$money;
                    //更新数据
                    $my_money_mod->edit('user_id='.$buy_user_id,array('money'=>$new_buy_money));
                    $my_money_mod->edit('user_id='.$sell_user_id,array('money_dj'=>$new_sell_money));
                    //更新商付通log为 定单已取消
                    $change_buyer = array('caozuo'=>30, 'admin_time' => gmtime(), 'moneyleft' => $new_buy_money + $buy_money_dj);
                    $change_seller = array('caozuo'=>30, 'admin_time' => gmtime(), 'moneyleft' => $sell_money + $new_sell_money);
//                    $my_moneylog_mod->edit('order_id='.$id,array('caozuo'=>30));
                    Log::write('dualven:behalf:'.var_export($change_buyer,true));
                      $my_moneylog_mod->edit('order_id='.$id.' and leixing=20 and user_id='.$buy_user_id, $change_buyer);
                      $my_moneylog_mod->edit('order_id='.$id.' and leixing=10 and user_id='.$sell_user_id, $change_seller);
                }
                /*商付通v2.2.1  更新商付通定单状态 结束*/
                $order_this = $model_order->get($id);
                //退还分润,必须是已发货或已完成，且退货有商品
                if(in_array($order_this['status'], array(ORDER_SHIPPED,ORDER_FINISHED)))
                {
                    $refund_results=$model_orderrefund->find(array(
                        'conditions'=>'order_id='.$id.' AND sender_id='.$order_this['buyer_id'].' AND receiver_id='.$order_this['bh_id'].'',
                    ));
                    if(count($refund_results) == 1)
                    {
                        $refund_result = current($refund_results);
                        if($refund_result['status'] == 0 && $refund_result['closed'] == 0 && $refund_result['goods_ids'])
                        {
                            //计算返款
                            $rec_ids = explode(',', $refund_result['goods_ids']);
                            $rec_goods = $model_ordergoods->find(array(
                                'conditions'=> 'order_id='.$id.' AND '.db_create_in($rec_ids,'rec_id'),
                            ));
                            $behalf_discount = 0;
                            if($rec_goods)
                            {
                                foreach ($rec_goods as $goods)
                                {
                                    if($goods['oos_value'] && $goods['behalf_to51_discount'] > 0)
                                    {
                                        $behalf_discount += $goods['behalf_to51_discount'];
                                        $model_ordergoods->edit($goods['rec_id'],array('zwd51_tobehalf_discount'=>$goods['behalf_to51_discount']));
                                        if($model_ordergoods->has_error())
                                        {
                                            continue;
                                        }
                                    }
                                }
                            }
                            
                            if($behalf_discount > 0)
                            {
                                include_once(ROOT_PATH.'/app/fakemoney.app.php');
                                $fakemoneyapp = new FakeMoneyApp();
                                $fr_reason = Lang::get('behalf_to_51_tk_reason').local_date('Y-m-d H:i:s',gmtime());
                                //给用户转账
                                $my_money_result=$fakemoneyapp->to_user_withdraw(FR_USER,$this->visitor->get('user_id'),$behalf_discount, $fr_reason,$order_this['order_id'],$order_this['order_sn']);
                                if($my_money_result !== true)
                                {
                                    $this->pop_warning($my_money_result);
                                    return;
                                }
                            }
                        }
                    }
                }

                /* 加回订单商品库存 */
                //$model_order->change_stock('+', $id);
                $cancel_reason = (!empty($_POST['remark'])) ? $_POST['remark'] : $_POST['cancel_reason'];
                $cancel_reason .= " ".Lang::get('order_sn').":".$order_info[$val]['order_sn'];
                /* 记录订单操作日志 */
                $order_log =& m('orderlog');
                $order_log->add(array(
                    'order_id'  => $id,
                    'operator'  => addslashes($this->visitor->get('user_name')),
                    'order_status' => order_status($order_info[$id]['status']),
                    'changed_status' => order_status(ORDER_CANCELED),
                    'remark'    => $cancel_reason,
                    'log_time'  => gmtime(),
                                      ));

                /* 连接用户系统 */
                $ms =& ms();
                $buyer_info   = $ms->user->_local_get($order_info[$id]['buyer_id']);
                $msg_id = $ms->pm->send($this->visitor->get('user_id'), array($order_info[$id]['buyer_id']), '', Lang::get('order_cancel_notice').$cancel_reason);
                /*短信通知*/
                $this->sendSaleSms($buyer_info['phone_mob'], Lang::get('order_cancel_notice').$cancel_reason);

                /* 发送给买家订单取消通知 */
                $mail = get_mail('tobuyer_cancel_order_notify', array('order' => $order_info[$id], 'reason' => $_POST['remark']));
                //$this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));

                $new_data = array(
                    'status'    => Lang::get('order_canceled'),
                    'actions'   => array(), //取消订单后就不能做任何操作了
                                  );

                /* 如果是关联到淘宝订单的话, 需要同时修改淘宝订单的状态 */
                $ordervendor_mod->edit("ecm_order_id={$id}", array(
                'status' => VENDOR_ORDER_UNHANDLED,
                'ecm_order_id' => 0));
            }
            $this->pop_warning('ok', 'behalf_member_cancel_order');
        }

    }

    /**
     * 停用停用停用停用停用停用停用停用停用停用停用停用停用停用停用停用停用停用
     * //此函数停用，转到 applied_refund中自动退款 和 关闭订单
     * 强制关闭订单，不退款，
     * @author tanaiquan
     * @date 2015-06-25
     */
    function fclose_order()
    {
        //此函数停用
        return;
        /* 取消的和完成的订单不能再取消 */
        $order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
        }
        $status = array(ORDER_SUBMITTED, ORDER_PENDING, ORDER_ACCEPTED, ORDER_SHIPPED,ORDER_FINISHED);
        $order_ids = explode(',', $order_id);
        if ($ext)
        {
            $ext = ' AND ' . $ext;
        }

        $model_order    =&  m('order');

        /* 只有已发货的货到付款订单可以收货 */
        $order_info     = $model_order->find(array(
                'conditions'    => "order_id" . db_create_in($order_ids) . " AND status " . db_create_in($status) . ' AND bh_id='.$this->visitor->get('user_id'),
        ));
        $ids = array_keys($order_info);
        if (!$order_info)
        {
            echo Lang::get('no_such_order');

            return;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('orders', $order_info);
            $this->assign('order_id', count($ids) == 1 ? current($ids) : implode(',', $ids));
            $this->display('behalf_member.fclose.html');
        }
        else
        {
            $model_order    =&  m('order');
            $ordervendor_mod = &m('ordervendor');
            $model_orderrefund=& m('orderrefund');
            foreach ($ids as $val)
            {
                $id = intval($val);
                $order_info = current($order_info);
                //如果是有退款成功的订单，则从冻结资金中减去相应资金
                $orderfund = $model_orderrefund->get(array(
                    'conditions'=>'order_id='.$id.' AND receiver_id='.$this->visitor->get('user_id').' AND type=1 AND status=1',
                ));
                if($orderfund && $order_info['status']!= ORDER_FINISHED)
                {
                    //dump($orderfund['apply_amount'] == $order_info['goods_amount'] || $orderfund['apply_amount'] == $order_info['order_amount'] );
                    //全额退款时，才解冻订单全部资金。未发货=订单总价，已发货=订单商品价格
                    if($orderfund['apply_amount'] == $order_info['goods_amount'] || $orderfund['apply_amount'] == $order_info['order_amount'] )
                    {
                        include_once(ROOT_PATH.'/app/my_money.app.php');
                        $my_moneyapp = new My_moneyApp();
                        $my_money_result=$my_moneyapp->jd_behalf_refund($this->visitor->get('user_id'),$order_info['order_amount'], $order_info['order_sn']);
                        if($my_money_result !== true)
                        {
                            $this->pop_warning($my_money_result);
                            return;
                        }
                    }
                    else
                    {
                        $this->pop_warning('hack attack');
                        return;
                    }
                }

                $model_order->edit($id, array('status' => ORDER_CANCELED));
                if ($model_order->has_error())
                {
                    //$_erros = $model_order->get_error();
                    //$error = current($_errors);

                    //$this->json_error(Lang::get($error['msg']));

                    //return;
                    continue;
                }
                /*商付通v2.2.1  更新商付通定单状态 开始*/
                $my_moneylog_mod =& m('my_moneylog');
                $my_moneylog_mod->edit('order_id='.$id,array('caozuo'=>80));
                /*商付通v2.2.1  更新商付通定单状态 结束*/

                /* 加回订单商品库存 */
                //$model_order->change_stock('+', $id);
                $cancel_reason = (!empty($_POST['remark'])) ? $_POST['remark'] : $_POST['cancel_reason'];
                /* 记录订单操作日志 */
                $order_log =& m('orderlog');
                $order_log->add(array(
                        'order_id'  => $id,
                        'operator'  => addslashes($this->visitor->get('user_name')),
                        'order_status' => order_status($order_info[$id]['status']),
                        'changed_status' => order_status(ORDER_CANCELED),
                        'remark'    => $cancel_reason,
                        'log_time'  => gmtime(),
                ));

                /* 发送给买家订单取消通知 */
                $model_member =& m('member');
                $buyer_info   = $model_member->get($order_info[$id]['buyer_id']);
                $mail = get_mail('tobuyer_cancel_order_notify', array('order' => $order_info[$id], 'reason' => $_POST['remark']));
                $this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));

                $new_data = array(
                        'status'    => Lang::get('order_canceled'),
                        'actions'   => array(), //取消订单后就不能做任何操作了
                );

                /* 如果是关联到淘宝订单的话, 需要同时修改淘宝订单的状态 */
                $ordervendor_mod->edit("ecm_order_id={$id}", array(
                        'status' => VENDOR_ORDER_UNHANDLED,
                        'ecm_order_id' => 0));
            }
            $this->pop_warning('ok', 'behalf_member_cancel_order');
        }

    }
    
    /**
     * 统计代发相关
     * 
     */
    function _get_statics()
    {
        !$_GET['type'] && $_GET['type'] = 'all_orders';
        if($_GET['type'] != 'shipped')
        {
            return;
        }
        $status = order_status_translator($_GET['type']);
        if(!empty($status))
        {
            $status_condition = " AND status=".$status;
        }
        
        
        $today_now = gmtime();
        $today_start = mktime(0,0,0,date('m',$today_now),date('d',$today_now),date('Y',$today_now));
        $today_start = gmstr2time(date('Y-m-d',$today_start));
        $yesterday_start = $today_start-24*60*60;
        $yesterday_end = $today_start - 1;
        
        $behalf_shipped_statics = array(
            'today'=>array(
                'date_now'=>$today_start,//注意linux与window区别
                'order_count'=>0,
                'order_amounts' =>0,//订单总资金
                'goods_amounts' => 0 //订单商品总资金
            ),
            'yesterday'=>array(
                'date_now'=>$yesterday_start,
                'order_count'=>0,
                'order_amounts' =>0,//订单总资金
                'goods_amounts' => 0 //订单商品总资金
            )
        );//存放代发相关统计
        
       
        //统计代发今天发了多少订单，多少钱的货物
        $model_order =& m('order');
        $orders = $model_order->find(array(
           'conditions'=>'bh_id='.$this->visitor->get('user_id').' AND ship_time >='.$today_start." AND ship_time <=".$today_now.$status_condition ,
            'count'=>true,
        ));
        $behalf_shipped_statics['today']['order_count'] = $model_order->getCount();//订单总数       
        if($orders)
        {
            foreach ($orders as $order)
            {
                $behalf_shipped_statics['today']['order_amounts'] += floatval($order['order_amount']);
                $behalf_shipped_statics['today']['goods_amounts'] += floatval($order['goods_amount']);
            }
        }
        $yes_orders = $model_order->find(array(
            'conditions'=>'bh_id='.$this->visitor->get('user_id').' AND ship_time >='.$yesterday_start." AND ship_time <=".$yesterday_end.$status_condition ,
            'count'=>true,
        ));
        $behalf_shipped_statics['yesterday']['order_count'] = $model_order->getCount();//订单总数
        if($yes_orders)
        {
            foreach ($yes_orders as $yorder)
            {
                $behalf_shipped_statics['yesterday']['order_amounts'] += floatval($yorder['order_amount']);
                $behalf_shipped_statics['yesterday']['goods_amounts'] += floatval($yorder['goods_amount']);
            }
        }
        
        $this->assign('behalf_shipped_statics',$behalf_shipped_statics);        
    }


    /**
     *    获取订单列表
     *
     *    @author    Garbin
     *    @return    void
     */
    function _get_orders()
    {
        $page = $this->_get_page();
        $model_order =& m('order');
        $model_goodsattr =& m('goodsattr');
        $model_ordergoods =& m('ordergoods');
        $model_goods=& m('goods');
        $model_orderrefund=& m('orderrefund');
        !$_GET['type'] && $_GET['type'] = 'all_orders';

        $conditions = '';

        // 团购订单
        /* if (!empty($_GET['group_id']) && intval($_GET['group_id']) > 0)
           {
           $groupbuy_mod = &m('groupbuy');
           $order_ids = $groupbuy_mod->get_order_ids(intval($_GET['group_id']));
           $order_ids && $conditions .= ' AND order_alias.order_id' . db_create_in($order_ids);
           } */

        $conditions .= $this->_get_query_conditions(array(
            array(      //按订单状态搜索
                'field' => 'status',
                'name'  => 'type',
                'handler' => 'order_status_translator',
                        ),
            array(      //按买家名称搜索
                'field' => 'buyer_name',
                'equal' => 'LIKE',
                        ),
            array(      //按支付时间搜索,起始时间
                'field' => 'order_alias.pay_time',
                'name'  => 'add_time_from',
                'equal' => '>=',
                'handler'=> 'gmstr2time',
                        ),
            array(      //按下单时间搜索,结束时间
                'field' => 'order_alias.pay_time',
                'name'  => 'add_time_to',
                'equal' => '<=',
                'handler'=> 'gmstr2time',
                        ),
            array(      //按订单号
                'field' => 'order_sn',
                        ),
            array(      //按订单号
                'field' => 'invoice_no',
                        ),
            array(
                //按档口
                'field' => 'seller_name',
                'equal' => 'LIKE',
                  ),

                                                          ));
        //dump($conditions."bb");

        /*代发订单*/
        /* $order_behalf = & m('orderbehalfs');
           $order_behalvies = $order_behalf->find("bh_id=".$this->visitor->get('has_behalf'));
           $orderids = array();
           foreach ($order_behalvies as $ob)
           {
           $orderids[] = $ob['order_id'];
           } */

        /**/
        $order_order =  'order_alias.pay_time DESC , order_alias.add_time DESC';
        /* if(!empty($_GET['order']))
           {
           if($_GET['order'] == 'store')
           $order_order =  'CONVERT(order_alias.seller_name USING gbk ) COLLATE gbk_chinese_ci ASC';
           if($_GET['order'] == 'market')
           $order_order =  's.mk_id ASC';
           }  */
        /*市场中的店铺*/
        if(!empty($_GET['market']))
        {
            $mk_id = intval($_GET['market']);
            $market_mod = & m('market');
            $mk_ids = array();
            $mk_ids[] = $mk_id;
            $son_ids = $market_mod->get_list($mk_id);
            foreach ($son_ids as $sid)
            {
                $mk_ids[] = $sid['mk_id'];
            }
            $mk_stores = $market_mod->getRelatedData('has_store',$mk_ids);
            $mk_storeids = array();
            foreach ($mk_stores as $mst)
            {
                $mk_storeids[] = $mst['store_id'];
            }
            $store_conditions = '';
            if(!empty($mk_storeids))
            {
                $store_conditions .= ' AND order_alias.seller_id IN ('.implode(',', $mk_storeids).') ';
            }
            else
            {
                $store_conditions .= ' AND order_alias.seller_id is NULL';
            }
            $this->assign("query_mkid",$mk_id);
        }	//dump($conditions);
        //商品名称查询
        if($_GET['goods_name'])
        {
            //找出代发所有订单
            $query_goods_name = trim($_GET['goods_name']);
            $query_goods_name_orders = $model_order->find(array(
                'conditions'=>"bh_id=".$this->visitor->get('has_behalf'),
                'fields'=>'order_id',
                                                                ));
            if(!empty($query_goods_name_orders))
            {
                $query_goods_name_order_ids = array();
                foreach ($query_goods_name_orders as $value)
                {
                    $query_goods_name_order_ids[] = $value['order_id'];
                }
                //找出 有传入关键字的订单
                $query_order_goods = $model_ordergoods->find(array(
                    'conditions'=>db_create_in($query_goods_name_order_ids,'order_id')." AND goods_name like '%".$query_goods_name."%'",
                    'fields'=>'order_id',
                                                                   ));
                $query_goods_name_order_result = array();
                foreach ($query_order_goods as $value)
                {
                    if(!in_array($value['order_id'], $query_goods_name_order_result))
                        $query_goods_name_order_result[] = $value['order_id'];
                }
                $this->assign("query_goods_name",$query_goods_name);
                if($query_goods_name_order_result)
                {
                    $query_goods_condition = " AND ".db_create_in($query_goods_name_order_result,'order_alias.order_id');
                }
                else
                {
                    return;
                }
                //dump($query_goods_name_order_result);
            }
        }
        if($_GET['oos'])
        {
            $query_oos = intval(trim($_GET['oos'])) == 1?1:0;
            if($query_oos)
            {
                $query_oos_orders = $model_order->find(array(
                'conditions'=>"bh_id=".$this->visitor->get('has_behalf')." AND status".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED)),
                'fields'=>'order_id',
                                                                ));
                if($query_oos_orders)
                {
                    $query_oos_order_ids = array();
                    foreach ($query_oos_orders as $value)
                    {
                        $query_oos_order_ids[] = $value['order_id'];
                    }
                    //找出 有传入关键字的订单
                    $query_order_goods = $model_ordergoods->find(array(
                        'conditions'=>db_create_in($query_oos_order_ids,'order_id')." AND oos_value = 0",
                        'fields'=>'order_id',
                    ));
                    if($query_order_goods)
                    {
                        $query_oos_order_result = array();
                        foreach ($query_order_goods as $value)
                        {
                            if(!in_array($value['order_id'], $query_oos_order_result))
                            {
                                $query_oos_order_result[] = $value['order_id'];
                            }
                        }
                        $this->assign('query_oos',$query_oos);
                        if($query_oos_order_result)
                        {
                            $query_oos_condition = " AND ".db_create_in($query_oos_order_result,'order_alias.order_id');
                        }
                    }
                    else
                    {
                        return;
                    }
                    
                }
                else 
                {
                    return;
                }
            }
        }
        //商家编码查询
        if($_GET['goods_seller_bm'])
        {
            //找出代发所有订单
            $query_goods_seller_bm = trim($_GET['goods_seller_bm']);
            $query_goods_seller_bm_orders = $model_order->find(array(
                'conditions'=>"bh_id=".$this->visitor->get('has_behalf'),
                'fields'=>'order_id',
                                                                     ));
            if(!empty($query_goods_seller_bm_orders))
            {
                $query_goods_seller_bm_orders_ids = array();
                foreach ($query_goods_seller_bm_orders as $value)
                {
                    $query_goods_seller_bm_orders_ids[] = $value['order_id'];
                }
                //找出 有传入关键字的订单
                ////商家编码
                /* $goods_AttrModel = & m('goodsattr');
                   $attrs = $goods_AttrModel->find(array(
                   'conditions' => "attr_value like '%".$query_goods_seller_bm."%' AND attr_id = 1",
                   'fields'=>'goods_id',
                   )); */
                $attrs = $model_goods->get_Mem_list(array(
                    'order'=>'views desc',
                    'fields'=>'g.goods_id,',
                    'limit'=>20,
                    'conditions_tt'=>array($query_goods_seller_bm)
                                                          ),null,false,true,$total_found);

                $query_goods_seller_bm_goods_ids = array();
                foreach ($attrs as $value)
                {
                    if(!in_array($value['goods_id'], $query_goods_seller_bm_goods_ids))
                        $query_goods_seller_bm_goods_ids[] = $value['goods_id'];
                }
                //dump($attrs);

                $query_goods_seller_bm_order_goods = $model_ordergoods->find(array(
                    'conditions'=>db_create_in($query_goods_seller_bm_goods_ids,'goods_id'),
                    'fields'=>'order_id',
                                                                                   ));
                $query_goods_seller_bm_order_result = array();
                foreach ($query_goods_seller_bm_order_goods as $value)
                {
                    if(!in_array($value['order_id'], $query_goods_seller_bm_order_result))
                        $query_goods_seller_bm_order_result[] = $value['order_id'];
                }
                $this->assign("query_goods_seller_bm",$query_goods_seller_bm);
                if($query_goods_seller_bm_order_result)
                {
                    $query_goods_seller_bm_condition = " AND ".db_create_in($query_goods_seller_bm_order_result,'order_alias.order_id');
                }
                else
                {
                    return;
                }
                //dump($query_goods_name_order_result);
            }
        }

        //已拒绝
        if(isset($_GET['type']) && 'refuse'==trim($_GET['type']))
        {
        	
        	
            $orderrefund_result1 = $model_orderrefund->find(array(
                    'conditions'=>'receiver_id='.$this->visitor->get('user_id').' AND status=2 AND type=1',
                    'fields'=>'order_id,apply_amount',
            		'order'=>'apply_amount DESC'
            ));
            if($orderrefund_result1)
            {
            	$orderrefund_ids1 = array();
            	foreach ($orderrefund_result1 as $value)
            	{
            		if(!in_array($value['order_id'], $orderrefund_ids1))
            			$orderrefund_ids1[] = $value['order_id'];
            	}
            	$orderrefund_ids1 = array_unique($orderrefund_ids1);
            	
                $orderrefund_ids3 = array();
            	$orderrefund_result3 = $model_orderrefund->find(array(
            			'conditions'=>'receiver_id='.$this->visitor->get('user_id')." AND ".db_create_in($orderrefund_ids1,'order_id').' AND status <> 2 AND type=1',
            			'fields'=>'order_id,apply_amount',
            			//'order'=>'apply_amount DESC'
            	));
            	
            	if($orderrefund_result3)
            	{
            		foreach ($orderrefund_result3 as $value3)
            		{
            			if(!in_array($value3['order_id'], $orderrefund_ids3))
            				$orderrefund_ids3[] = $value3['order_id'];
            		}
            		
            		$orderrefund_ids3 = array_unique($orderrefund_ids3);
            		 
            		foreach ($orderrefund_ids1 as $idkey=>$orids1)
            		{
            			if(in_array($orids1, $orderrefund_ids3))
            			{
            				unset($orderrefund_ids1[$idkey]);
            			}
            		}
            	}
            	 
            	
            }
            
           /*  $orderrefund_result1 = array_values($orderrefund_result1);
            $refuse_start = (intval($page['curr_page'])-1)*intval($page['pageper']);
            $refuse_end = $refuse_start + intval($page['pageper']);
            $orderrefund_ids2 = array(); */
            if($orderrefund_result1)
            {
            	/*  */
                
                
                          
               
                
                /* $orderrefund_ids1 = array_unique($orderrefund_ids1);
                $items_count_refuse = count($orderrefund_ids1);
                for( $i = $refuse_start; $i < $refuse_end;$i++)
                {
               		 $orderrefund_ids2[] = $orderrefund_ids1[$i];
                }    */             
                //$order_order = '';
                $query_refunds_condition =" AND ".db_create_in($orderrefund_ids1,'order_alias.order_id')." AND ".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED),'order_alias.status');
            }
            else{
                return;
            }
        }
        //待退款
        if(isset($_GET['type']) && 'refund'==trim($_GET['type']))
        {
            $orderrefund_result = $model_orderrefund->find(array(
                    'conditions'=>'receiver_id='.$this->visitor->get('user_id').' AND status=0 AND closed=0 AND type=1',
                    'fields'=>'order_id',
            ));
            if($orderrefund_result)
            {
                $orderrefund_ids = array();
                foreach ($orderrefund_result as $value)
                {
                    if(!in_array($value['order_id'], $orderrefund_ids))
                        $orderrefund_ids[] = $value['order_id'];
                }
                $query_refunds_condition =" AND ".db_create_in($orderrefund_ids,'order_alias.order_id')." AND ".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED),'order_alias.status');
            }
            else{
                return;
            }
        }
        //待补差
        if(isset($_GET['type']) && 'applyfee'==trim($_GET['type']))
        {
            $orderrefund_result = $model_orderrefund->find(array(
                    'conditions'=>'sender_id='.$this->visitor->get('user_id').' AND status=0 AND closed=0 AND type=2',
                    'fields'=>'order_id',
            ));
            if($orderrefund_result)
            {
                $orderrefund_ids = array();
                foreach ($orderrefund_result as $value)
                {
                    if(!in_array($value['order_id'], $orderrefund_ids))
                        $orderrefund_ids[] = $value['order_id'];
                }
                $query_refunds_condition =" AND ".db_create_in($orderrefund_ids,'order_alias.order_id')." AND ".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED),'order_alias.status');
            }
            else{
                return;
            }
        }
        //查找快递
        if(isset($_GET['exp_delivery']) && !empty($_GET['exp_delivery']))
        {
            $query_dl_condition = ' AND dl_id='.trim($_GET['exp_delivery']);
            $this->assign('query_dl',$_GET['exp_delivery']);
        }
        //dump("order_alias.bh_id = ".$this->visitor->get('has_behalf').$query_goods_condition.$query_goods_seller_bm_condition.$query_refunds_condition.$query_dl_condition.$query_oos_condition."{$conditions}");
        /* 查找订单 */
        $orders = $model_order->findAll(array(
            'conditions'    => "order_alias.bh_id = ".$this->visitor->get('has_behalf').$query_goods_condition.$query_goods_seller_bm_condition.$query_refunds_condition.$query_dl_condition.$query_oos_condition."{$conditions}",
            'fields' => 'order_alias.*,orderextm.shipping_fee',
            'count'         => true,
            'join'          => 'has_orderextm',
            'limit'         => $page['limit'],
            'order'         => $order_order,
            'include'       =>  array(
                'has_ordergoods',       //取出商品
                'has_orderrefund'
                                      ),
                                              ));
        //dump($orders);
        foreach ($orders as $key1 => $order)
        {
        	if(!empty($order['order_goods']))
        	{
	            foreach ($order['order_goods'] as $key2 => $goods)
	            {
	                empty($goods['goods_image']) && $orders[$key1]['order_goods'][$key2]['goods_image'] = Conf::get('default_goods_image');
	                ////商家编码
	                if(empty($goods['attr_value']))
	                {
	                    $result = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$goods['goods_id']} AND attr_id=1");
	                    $orders[$key1]['order_goods'][$key2]['goods_seller_bm'] = $result;
	                }
	                else
	                {
	                    $orders[$key1]['order_goods'][$key2]['goods_seller_bm'] = $goods['attr_value'];
	                }
	                $store = $model_goods->find(array(
	                    'conditions'=>'g.goods_id='.$goods['goods_id'],
	                    'fields'=>'s.*',
	                    'join'=>'belongs_to_store',
	                                                  ));
	                if(!empty($store))
	                {
	                    $store = current($store);
	                    $orders[$key1]['order_goods'][$key2]['tel']=$store['tel'];
	                    $orders[$key1]['order_goods'][$key2]['im_qq']=$store['im_qq'];
	                    $orders[$key1]['order_goods'][$key2]['im_ww']=$store['im_ww'];
	                }
	            }
        	}
            $orders[$key1]['refunds'] = $model_orderrefund->get(array(
                'conditions'=>'order_id='.$order['order_id'].' AND receiver_id='.$this->visitor->get('user_id').' AND type=1',
            ));
            $orders[$key1]['apply_fee'] = $model_orderrefund->get(array(
                    'conditions'=>'order_id='.$order['order_id'].' AND receiver_id='.$order['buyer_id'].' AND closed=0 AND type=2',
            ));
        }
        //dump($orders);
        //找出所有待发货的订单order_id
        $order_accepted = $model_order->findAll(array(
            'conditions'    => "order_alias.bh_id = ".$this->visitor->get('has_behalf').$query_goods_condition.$query_goods_seller_bm_condition.$query_refunds_condition.$query_dl_condition.$query_oos_condition."{$conditions}"." AND order_alias.status ".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED)),
            'fields' => 'order_alias.order_id',
            'join'          => 'has_orderextm',
            'order'         => $order_order,
                                                      ));
        $order_accepted_str="";
        if($order_accepted)
        {
            foreach ($order_accepted as $key=>$value)
            {
                $order_accepted_str .= $key.",";
            }
            $order_accepted_str = rtrim($order_accepted_str,",");
        }
        //找出所有已发货的订单order_id
        /* $order_shipped = $model_order->findAll(array(
            'conditions'    => "order_alias.bh_id = ".$this->visitor->get('has_behalf').$query_goods_condition.$query_goods_seller_bm_condition.$query_refunds_condition.$query_dl_condition."{$conditions}"." AND order_alias.status=".ORDER_SHIPPED,
            'fields' => 'order_alias.order_id',
            'join'          => 'has_orderextm',
            'order'         => $order_order,
                                                      ));
        $order_shipped_str="";
        if($order_shipped)
        {
            foreach ($order_shipped as $key=>$value)
            {
                $order_shipped_str .= $key.",";
            }
            $order_shipped_str = rtrim($order_shipped_str,",");
        } */
        //$delivery_bm = array();//test
        foreach ($orders as $key=>$value)
        {
            $member_info = $this->_get_member_profile($value['buyer_id']);
            $orders[$key]['im_qq'] = $member_info['im_qq'];
            $orders[$key]['im_aliww'] = $member_info['im_aliww'];
            $orders[$key]['delivery_bm'] = $model_order->get_delivery_bm_bybehalf($value['order_id']);
            //$delivery_bm[] = $orders[$key]['delivery_bm'];//test
            $orders[$key]['dl_name'] = $model_order->get_delivery_bybehalf($value['order_id'],$value['bh_id']);
        }
        // dump($orders);
        //dump($delivery_bm);//test
        $page['item_count'] = $model_order->getCount();
        /* if(isset($_GET['type']) && 'refuse'==trim($_GET['type']))
        {
        	$page['item_count'] = $items_count_refuse;
        } */
        $this->_format_page($page);
        $this->assign('types', array('all' => Lang::get('all_orders'),
                                     'pending' => Lang::get('pending_orders'),
                                     'submitted' => Lang::get('submitted_orders'),
                                     'accepted' => Lang::get('accepted_orders'),
                                     'shipped' => Lang::get('shipped_orders'),
                                     'finished' => Lang::get('finished_orders'),
                                     'canceled' => Lang::get('canceled_orders')));
        $this->assign('type', $_GET['type']);
        $this->assign('orders', $orders);
        $this->assign('page_info', $page);
        $this->assign("order_accepted_str",$order_accepted_str);
        //$this->assign('order_shipped_str',$order_shipped_str);
    }

    function _get_markets()
    {
        $market_mod = & m('market');
        $markets = $market_mod->get_list(1);
        $this->assign("markets",$markets);
    }
    /**
     * 获取可用快递
     */
    function _get_related_delivery()
    {
        $model_behalf  =& m('behalf');
        $related_delivery=$model_behalf->getRelatedData('has_delivery',$this->visitor->get('user_id'));
        $this->assign("related_delivery",$related_delivery);
    }

    function _run_action()
    {
        /* 只有登录的用户才可访问 */
        if (!$this->visitor->has_login && !in_array(ACT, array('login', 'register', 'check_user')))
        {
            if (!IS_AJAX)
            {
                header('Location:index.php?app=member&act=login&ret_url=' . rawurlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']));

                return;
            }
            else
            {
                $this->json_error('login_please');
                return;
            }
        }
        $referer = $_SERVER['HTTP_REFERER'];
        if (strpos($referer, 'act=login') === false)
        {
            $ret_url = $_SERVER['HTTP_REFERER'];
            $ret_text = 'go_back';
        }
        else
        {
            $ret_url = SITE_URL . '/index.php';
            $ret_text = 'back_index';
        }

        /* 检查是否是店铺管理员 */
        if (!$this->visitor->get('pass_behalf'))
        {
            $this->show_warning(
                'not_behalfadmin',
                'apply_now', 'index.php?app=bhapply',
                $ret_text, $ret_url
                                );

            return;
        }

        /* 检查是否被授权 */
        /* $privileges = $this->_get_privileges();
           if (!$this->visitor->i_can('do_action', $privileges))
           {
           $this->show_warning('no_permission', $ret_text, $ret_url);

           return;
           }
        */
        /* 检查店铺开启状态 */
        /* $state = $this->visitor->get('state');
           if ($state == 0)
           {
           $this->show_warning('apply_not_agree', $ret_text, $ret_url);

           return;
           }
           elseif ($state == 2)
           {
           $this->show_warning('store_is_closed', $ret_text, $ret_url);

           return;
           } */

        /* 检查附加功能 */
        /* if (!$this->_check_add_functions())
           {
           $this->show_warning('not_support_function', $ret_text, $ret_url);
           return;
           } */

        parent::_run_action();
    }

    /*三级菜单*/
    function _get_member_submenu()
    {
        $array = array(
            array(
                'name' => 'all_orders',
                'url' => 'index.php?app=behalf_member&amp;type=all_orders',
                  ),
            array(
                'name' => 'pending',
                'url' => 'index.php?app=behalf_member&amp;type=pending',
                  ),
            /* array(
                'name' => 'submitted',
                'url' => 'index.php?app=behalf_member&amp;type=submitted',
                  ), */
            array(
                'name' => 'accepted',
                'url' => 'index.php?app=behalf_member&amp;type=accepted',
                  ),
            array(
                'name' => 'shipped',
                'url' => 'index.php?app=behalf_member&amp;type=shipped',
                  ),
            array(
                'name' => 'finished',
                'url' => 'index.php?app=behalf_member&amp;type=finished',
                  ),
            array(
                'name' => 'canceled',
                'url' => 'index.php?app=behalf_member&amp;type=canceled',
                  ),
            array(
                'name' => 'refund',
                'url' => 'index.php?app=behalf_member&amp;type=refund',
                ),
            array(
                'name' => 'applyfee',
                'url' => 'index.php?app=behalf_member&amp;type=applyfee',
                ),
            array(
                'name' => 'refuse',
                'url' => 'index.php?app=behalf_member&amp;type=refuse',
                ),
                       );
        return $array;
    }

    /**
     *    获取有效的订单信息
     *
     *    @author    Garbin
     *    @param     array $status
     *    @param     string $ext
     *    @return    array
     */
    function _get_valid_order_info($status, $ext = '')
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id)
        {

            return array();
        }
        if (!is_array($status))
        {
            $status = array($status);
        }

        if ($ext)
        {
            $ext = ' AND ' . $ext;
        }

        $model_order    =&  m('order');
        /* 只有已发货的货到付款订单可以收货 */
        $order_info     = $model_order->get(array(
            'conditions'    => "order_id={$order_id} " . " AND status " . db_create_in($status) . $ext,
                                                  ));
        if (empty($order_info))
        {

            return array();
        }

        return array($order_id, $order_info);
    }

    /* 上传图片 */
    function _upload_image($bh_id)
    {
        import('uploader.lib');
        $uploader = new Uploader();
        $uploader->allowed_type(IMAGE_FILE_TYPE);
        $uploader->allowed_size(SIZE_STORE_CERT); // 400KB

        $data = array();
        $file = $_FILES['bh_logo'];
        if ($file['error'] == UPLOAD_ERR_OK)
        {
            if (empty($file))
            {
                continue;
            }
            $uploader->addFile($file);
            if (!$uploader->file_info())
            {
                $this->_error($uploader->get_error());
                return false;
            }

            $uploader->root_dir(ROOT_PATH);
            $dirname   = 'data/files/mall/behalf_logos';
            $filename  = 'bh_' . $bh_id . '_1';
            $data['bh_logo'] = $uploader->save($dirname, $filename);
        }
        return $data;
    }
    /**
     * 生成拿货单
     */
    function sum_accepted_order()
    {
        //dump($_POST);
        //接收order_ids
        $order_id = isset($_POST['oaids']) ? trim($_POST['oaids']) : '';
        $order_status = isset($_POST['status'])?trim($_POST['status']):'';
        
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }
        //可生成待发货的 或 2天内已发货的  
        $pay_time_conditions = '';
        if($order_status == 'shipped')
        {
            $status = array(ORDER_SHIPPED);
            $pay_time_conditions = ' AND order_alias.pay_time > '.(gmtime()-60*60*48);
        }
        elseif ($order_status == 'accepted')
        {
            $status = array(ORDER_ACCEPTED);
        }
        else
        {
            echo Lang::get('no_such_order');
            return;
        }
        
        $order_ids = explode(',', $order_id);
        $order_ids = array_filter($order_ids);

        $model_order    =&  m('order'); //订单
        $model_goodsattr =& m('goodsattr'); //商品属性
        $model_storediscount=& m('storediscount'); //店铺拿货优惠
        $model_behalf = & m('behalf'); //代发
        $model_store = & m('store');
        $model_market =& m('market');
        //市场数据array('市场'=>array(各楼层),...)
        $markets = $model_market->get_list(1);
        foreach ($markets as $mkey=>$market)
        {
            $markets[$mkey]['children'] = $model_market->get_list($market['mk_id']);
        }
        //$model_goods =& m('goods');
        /* 只有未发货的订单可以生成拿货单 */
        $orders  = $model_order->findAll(array(
            'conditions'    =>"order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) ." AND order_alias.bh_id=".$this->visitor->get('has_behalf').$pay_time_conditions,
            'join'          => 'has_orderextm,belongs_to_store',
            'fields'=>'order_alias.order_id,order_alias.order_sn,order_alias.add_time,order_alias.postscript,order_alias.goods_amount,order_extm.dl_id',
            'include'       =>  array( 'has_ordergoods'),//取出订单商品
        ));
        
        //代发所有可用的快递
        $deliverys = $model_behalf->getRelatedData('has_delivery',$this->visitor->get('user_id'));       
        
        if($orders && $deliverys)
        {   //订单 快递id->快递名称
            foreach ($orders as $okey=>$oorder)
            {
                foreach ($deliverys as $delivery)
                {
                    if($delivery['dl_id'] == $oorder['dl_id'])
                    {
                        $orders[$okey]['dl_name'] = $delivery['dl_name'];
                    }
                }
            }    
        } 
        if($orders)
        {  //统计 每个订单商品数量, 包括了 订单的 缺货商品
            foreach ($orders as $okey=>$oorder)
            {
                if($oorder['order_goods'])
                {
                    $temp_order_goodsquantity = 0;
                    foreach ($oorder['order_goods'] as $ogoods)
                    {
                        $temp_order_goodsquantity += $ogoods['quantity'];
                    }
                }
                $orders[$okey]['goodsquantity'] = $temp_order_goodsquantity;
            }
        }
        
        $goods_list = array(); //拿货单商品列表，key=goods_id+spec_id
        $market_list = array(); //存放拿货单 各市场名称、ID、商品总数及该市场总资金，以mk_id为key
        //$goods_list_order_field_mk = array();
        //$goods_list_order_field_dk = array();
        $order_count = 0;//拿货单 订单总数
        $goods_count = 0;//拿货单 商品总数
        $goods_amount_total = 0.0;//拿货单 商品总资金
        if(is_array($orders) && !empty($orders))
        {
            //计算订单总数，goods_list中加入商品信息
        	$order_count = count($orders); //计算 订单总数
            foreach ($orders as $order)
            {
                $take_time = gmtime() - $order['pay_time'];//拿货 警示时间,黄色、红色
                $take_time = intval($take_time/3600);
                $goods_amount_total += floatval($order['goods_amount']);//计算 拿货单 商品总资金 
                if(is_array($order['order_goods']))
                {
                    foreach ($order['order_goods'] as $goods)
                    {   
                    	$goods_count += $goods['quantity'];
                        $print_arr = array();//用于循环打印标签,现已废弃
                        if(!array_key_exists($goods['goods_id']."_".$goods['spec_id'], $goods_list))
                        {
                            //商品信息
                            $goods_list[$goods['goods_id']."_".$goods['spec_id']] =  $goods;
                        }
                        else
                        {
                            //商品规格量垒加
                            $goods_list[$goods['goods_id']."_".$goods['spec_id']]['quantity'] = intval($goods_list[$goods['goods_id']."_".$goods['spec_id']]['quantity']) + intval($goods['quantity']);
                            //商品订单信息（可提取）
                            //$goods_list[$goods['goods_id']."_".$goods['spec_id']]['order_sn_and_count'][] = array($order['order_sn'],$goods['quantity'],$goods['order_id'],$take_time,$print_arr,$order['postscript'],$order['dl_name'],$order['goodsquantity']);
                        }
                        //商品订单信息(order_sn,goods_quantity本商品规格数量,order_id,take_time,print_arr,postscript,dl_name,order_goodsquantity订单商品总数)
                        $goods_list[$goods['goods_id']."_".$goods['spec_id']]['order_sn_and_count'][] = array($order['order_sn'],$goods['quantity'],$goods['order_id'],$take_time,$print_arr,$order['postscript'],$order['dl_name'],$order['goodsquantity']);
                    }
                }
            }
        }
        //dump($goods_list);
        //商品编码中文转拼音，计算代发拿货优惠
        foreach ($goods_list as $key=>$value)
        {
            if($value['attr_value'])
            {
                $goods_list[$key]['sku'] = $value['attr_value'];
            }
            else
            {
                $result = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$value['goods_id']} AND attr_id=1");
                $goods_list[$key]['sku'] = $result;
            }

            if($goods_list[$key]['sku'])
            {
                //商品编码转换为 拼音首字母
                $goods_sku = explode('_',trim($goods_list[$key]['sku']));
                $result = ecm_iconv("UTF-8", "GBK",$goods_sku[0]);
                $result = strtoupper(GetPinyin($result,1,1));
                $goods_sku[0] = $result;
                //去掉价格
                foreach ($goods_sku as $pkey=>$pvalue)
                {
                    $goods_sku[$pkey] = trim($pvalue);
                    if(preg_match('/P\d+/i', trim($pvalue)))
                       unset($goods_sku[$pkey]);
                }
               
                $goods_sku = array_filter($goods_sku);//去掉空项
                $goods_list[$key]['tag_sku'] = implode("_", $goods_sku);
                $goods_list[$key]['single_sku'] = trim($goods_sku[2]);
            }
            
            $goods_discount = $model_storediscount->get_goods_discount($value['store_id'],$value['price']);//计算 代发 去店铺拿货折扣后的优惠
            $goods_list[$key]['goods_discount'] = $goods_discount * 2;
            //市场信息
            $store_info = $model_store->get($value['store_id']);
            //$goods_list[$key]['mk_id'] = $store_info['mk_id'];
            $goods_list[$key]['mk_name'] =  strtoupper(GetPinyin(ecm_iconv("UTF-8", "GBK",$store_info['mk_name']),1,1))."-".$store_info['address']; 
            //各市场统计
            $illegal_goods = 0;//商品应该属于各市场
            $market_id = 0 ;//店铺属于市场第二层的id
            foreach ($markets as $floors)
            {
                if(in_array($store_info['mk_id'], array_keys($floors['children'])))
                {
                    $market_id = $floors['mk_id'];
                }
            }
            if(!empty($market_id)) //属于某个市场，开始加入统计
            {
                if(in_array($market_id, array_keys($market_list)))
                {
                   $market_list[$market_id]['goods_count'] += intval($value['quantity']);
                   $market_list[$market_id]['goods_amount'] += intval($value['quantity'])*floatval($value['price']);
                }
                else
                {
                    $market_list[$market_id] = array(
                        'mk_id'=>$market_id,
                        'mk_name'=>$model_market->getOne("SELECT mk_name from ".$model_market->table." WHERE mk_id=".$market_id),
                        'goods_count'=>intval($value['quantity']),
                        'goods_amount'=>intval($value['quantity'])*floatval($value['price'])
                    );
                    //echo $market_id."<br>";
                }
            }
            else
            {
                $illegal_goods += 1 ;
            }
           $goods_list[$key]['mk_class'] = "market".$market_id;//在页面class
        }
        
        $goods_list = array_msort($goods_list, array('mk_name'=>'SORT_ASC'));
       
        //dump($goods_list);
        //print_r($market_list);
        //标签打印
        $goods_tag_list = array();//拿货单标签打印列表
        foreach ($goods_list as $ords)
        {
        	foreach ($ords['order_sn_and_count'] as $gods)
        	{
        		for($i=0;$i<$gods[1];$i++)
        		{
        			$tmp_ord = array();
        			$tmp_ord['tag_sku'] = $ords['tag_sku'];
        			$tmp_ord['specification'] = $ords['specification'];
        			$tmp_ord['order_sn'] = $gods[0];
        			$tmp_ord['order_dlname'] = $gods[6];
        			$tmp_ord['order_goodsquantity'] = $gods[7];
        			$tmp_ord['goods_discount'] = $ords['goods_discount'];
        			$goods_tag_list[] = $tmp_ord;
        		}        		
        	}
        }

        $this->assign("illegal_goods",$illegal_goods);//没统计进入各市场的商品
        $this->assign('market_list',$market_list);//拿货单 各市场统计
        $this->assign('goods_amount_total',$goods_amount_total);//商品总资金
        $this->assign('order_status',$order_status);//回显 待发货还是已发货拿货单
        $this->assign('order_count',$order_count);//订单总数
        $this->assign('goods_count',$goods_count);//商品总数
        $this->assign('tag',$this->visitor->get('user_id'));
        $this->assign('goods_list',$goods_list); //拿货单列表
        $this->assign('goods_tag_list',$goods_tag_list);  //拿货音标签打印列表      
        $this->display("behalf_member.accepted.order.html");
        
    }
    

    /**
     * 生成配货单，即按支付时间列出有5个以上商品的待发货订单
     */
    function gen_preparegoods_order()
    {
        $order_id = isset($_POST['gpids']) ? trim($_POST['gpids']) : '';
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
        }
        $status = array(ORDER_ACCEPTED);
        $order_ids = explode(',', $order_id);
        $order_ids = array_filter($order_ids);

        $model_order    =&  m('order');
        $model_goodsattr =& m('goodsattr');
        //$model_goods =& m('goods');
        /* 只有未发货的订单可以生成拿货单 */
        $orders  = $model_order->findAll(array(
            'conditions'    =>"order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) ." AND order_alias.bh_id=".$this->visitor->get('has_behalf'),
            'join'          => 'has_orderextm,belongs_to_store',
            'fields'=>'order_alias.order_id,order_alias.order_sn,order_alias.add_time',
            'order'=>'order_alias.pay_time DESC',
            'include'       =>  array(
                'has_ordergoods',       //取出商品
                                      ),

                                               ));
       //筛选订单
       foreach ($orders as $key=>$order)
       {
            if(count($order['order_goods']) <= 5)
            {
                unset($orders[$key]);
            }
            $quantity = 0;
            foreach ($order['order_goods'] as $goods)
            {
                $quantity += $goods['quantity'];
            }
            $orders[$key]['quantity'] = $quantity;
       }

      //  dump($orders);
        $this->assign('orders',$orders);
        $this->display("behalf_member.preparegoods.order.html");
    }

    /**
     * 导出淘宝快递打印单
     */
    function export_delivery_order()
    {
        // 目标编码
        $to_charset = (CHARSET == 'utf-8') ? substr(LANG, 0, 2) == 'sc' ? 'gbk' : 'big5' : '';

        if (!IS_POST)
        {
            if (CHARSET == 'utf-8')
            {
                $this->assign('note_for_export', sprintf(LANG::get('note_for_export'), $to_charset));

                /* 当前页面信息 */
                $this->_curlocal(LANG::get('member_center'), 'index.php?app=member',
                                 LANG::get('my_category'), 'index.php?app=my_category',
                                 LANG::get('export'));
                $this->_curitem('gcategory_manage');
                $this->_curmenu('export');
                $this->_config_seo('title', Lang::get('member_center') . Lang::get('my_category'));
                header("Content-Type:text/html;charset=" . CHARSET);
                $this->display('behalf_order.export.html');

                return;
            }
        }
        else
        {
            if (!$_POST['if_convert'])
            {
                $to_charset = '';
            }
        }

        $order_id = isset($_POST['oeids']) ? trim($_POST['oeids']) : '';
        $exp_delivery = isset($_POST['exp_delivery'])?intval($_POST['exp_delivery']):0;
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }
        $status = array(ORDER_ACCEPTED);
        $order_ids = explode(',', $order_id);
        $order_ids = array_filter($order_ids);



        $model_order    =&  m('order');
        $model_goodsattr =& m('goodsattr');
        /* 只有未发货的订单可以生成快递打印单 */
        $orders  = $model_order->findAll(array(
            'conditions'    =>"order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) ." AND order_alias.bh_id=".$this->visitor->get('has_behalf'),
            'join'          => 'has_orderextm',
            'order'=>'order_alias.pay_time DESC',
            'include'       =>  array(
                'has_ordergoods',       //取出商品
                                      ),

                                               ));
        //dump($orders);

        if(!empty($exp_delivery))
        {
            foreach ($orders as $key=>$value)
            {
                if($value['dl_id'] != $exp_delivery)
                    unset($orders[$key]);
            }
        }
        $csvTaoBaoOrders = array();
        $csvTaoBaoOrders['csv_taobao_orders_title']=Lang::get('export_taobao_order');
        if(is_array($orders))
        {
            foreach ($orders as $orderKey=>$orderValue)
            {
                $csvTaoBaoOrders[$orderKey]['order_sn']=$orderValue['order_sn'];//订单编号
                $csvTaoBaoOrders[$orderKey]['buyer_name']=$orderValue['buyer_name'];//买家会员名
                $csvTaoBaoOrders[$orderKey]['buyer_alipay']=Lang::get('not_offer');//买家支付宝账号
                $csvTaoBaoOrders[$orderKey]['buyer_goods_amount']=$orderValue['goods_amount'];//买家应付货款
                $csvTaoBaoOrders[$orderKey]['buyer_shipping_fee']=$orderValue['shipping_fee'];//买家应付邮费
                $csvTaoBaoOrders[$orderKey]['buyer_pay_scores']=0;//买家支付积分
                $csvTaoBaoOrders[$orderKey]['buyer_order_amount']=$orderValue['order_amount'];//总金额
                $csvTaoBaoOrders[$orderKey]['buyer_return_scores']=0;//返点积分
                $csvTaoBaoOrders[$orderKey]['buyer_real_order_amount']=$orderValue['order_amount'];//买家实际支付金额
                $csvTaoBaoOrders[$orderKey]['buyer_real_pay_scores']=0;//买家实际支付积分
                $csvTaoBaoOrders[$orderKey]['order_status']=order_status($orderValue['status']);//订单状态
                $csvTaoBaoOrders[$orderKey]['postscript']=$orderValue['postscript'];//买家留言
                $csvTaoBaoOrders[$orderKey]['consignee']=$orderValue['consignee'];//收货人姓名
                if($orderValue['region_name'])
                {
                    mb_internal_encoding("UTF-8");
                    $region_name = $orderValue['region_name'];
                    if(strpos($orderValue['region_name'],'中国') == 0 && strpos($orderValue['region_name'],'中国') !== false )
                      $region_name = mb_substr($orderValue['region_name'], 2);
                }
                $csvTaoBaoOrders[$orderKey]['consignee_region']=trim($region_name.$orderValue['address']."(".$orderValue['zipcode'].")");//收货人地址
                $csvTaoBaoOrders[$orderKey]['logistics']=$this->_getDeliveryNameById($orderValue['dl_id']);//运送方式
                $csvTaoBaoOrders[$orderKey]['phone_tel']=$orderValue['phone_tel'];//联系电话
                $csvTaoBaoOrders[$orderKey]['phone_mob']=$orderValue['phone_mob'];//联系手机
                $csvTaoBaoOrders[$orderKey]['add_time']=date("Y-m-d H:i",$orderValue['add_time']);//订单创建时间
                $csvTaoBaoOrders[$orderKey]['pay_time']=date("Y-m-d H:i",$orderValue['pay_time']);//订单付款时间
                $goods_title = "";
                $goods_quantity=0;
                $seller_bm =array();
                foreach ($orderValue['order_goods'] as $orderGoods)
                {
                    $goods_title .= $orderGoods['goods_name'].",";
                    $goods_quantity += $orderGoods['quantity'];
                    ////商家编码
                    $result = $orderGoods['attr_value'];
                    if(empty($result))
                    {
                        $result = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$orderGoods['goods_id']} AND attr_id=1");
                    }
                    if(!empty($result))
                    {

                        //转拼音首字母
                        $result = ecm_iconv("UTF-8", "GBK",$result);
                        $result = GetPinyin($result,1,1);
                        //去掉价格
                        $result = preg_replace('/_P\d+_/i', '_', $result);
                        $seller_bm[] = $result."(".$orderGoods['specification'].' '.Lang::get('goods_quantity_1').":".$orderGoods['quantity'].")";
                    }
                }
                $csvTaoBaoOrders[$orderKey]['goods_name']=implode(',', $seller_bm);//宝贝标题
                $csvTaoBaoOrders[$orderKey]['goods_category']=count($orderValue['order_goods']);//宝贝种类
                $csvTaoBaoOrders[$orderKey]['invoice_no']=$orderValue['invoice_no'];//物流单号
                $csvTaoBaoOrders[$orderKey]['invoice_dept']=$csvTaoBaoOrders[$orderKey]['logistics'];//物流公司
                $csvTaoBaoOrders[$orderKey]['order_remark']=$region_name;//订单备注
                $csvTaoBaoOrders[$orderKey]['goods_quantity']=$goods_quantity;//宝贝总数量
                $csvTaoBaoOrders[$orderKey]['store_id']=$orderValue['seller_id'];//店铺Id
                $csvTaoBaoOrders[$orderKey]['store_name']=$orderValue['seller_name'];//店铺名称
                $csvTaoBaoOrders[$orderKey]['order_close_reason']="";//订单关闭原因
                $csvTaoBaoOrders[$orderKey]['seller_service_fee']=0;//卖家服务费
                $csvTaoBaoOrders[$orderKey]['buyer_service_fee']=0;//买家服务费
                $csvTaoBaoOrders[$orderKey]['invoice_title']="";//发票抬头
                $csvTaoBaoOrders[$orderKey]['is_mob_order']="";//是否手机订单
                $csvTaoBaoOrders[$orderKey]['phase_order_info']="";//分阶段订单信息
                $csvTaoBaoOrders[$orderKey]['order_money']="";//定金排名
                $csvTaoBaoOrders[$orderKey]['modify_sku']="";//修改后的sku
                $csvTaoBaoOrders[$orderKey]['modify_consignee_region']="";//修改后的收货地址
                $csvTaoBaoOrders[$orderKey]['except_info']="";//异常信息
            }
        }
        $this->export_to_csv($csvTaoBaoOrders, 'Zwd51ToTaoBaoOrders', "gbk");
    }

    /**
     * 打印快递单
     */
    function print_delivery_order()
    {
        $order_id = isset($_POST['opids']) ? trim($_POST['opids']) : '';
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }
        $status = array(ORDER_ACCEPTED);
        $order_ids = explode(',', $order_id);
        $order_ids = array_filter($order_ids);
         
        $model_order    =&  m('order');
        $model_behalf  =& m('behalf');
        /* 只有未发货的订单可以生成快递打印单 */
        $orders  = $model_order->findAll(array(
            'conditions'    =>"order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) ." AND order_alias.bh_id=".$this->visitor->get('has_behalf'),
            'join'          => 'has_orderextm',
            'include'       =>  array(
                'has_ordergoods',       //取出商品
            ), 
             
        ));
       if(!empty($orders))
        {
            $model_goodsattr =& m('goodsattr');
            $model_delivery =& m('delivery');
            foreach ($orders as $key=>$value)
            {
                //$orders[$key]['region_name'] = $this->trimall($value['region_name']);
                $goods_count = 0;
                foreach ($value['order_goods'] as $orderGoods)
                {
                    //$goods_title .= $orderGoods['goods_name'].",";
                    //$goods_quantity += $orderGoods['quantity'];
                    ////商家编码
                    $result = $orderGoods['attr_value'];
                    if(empty($result))
                    {
                        $result = $model_goodsattr->getOne("SELECT attr_value FROM {$model_goodsattr->table} WHERE goods_id={$orderGoods['goods_id']} AND attr_id=1");
                    }
                    if(!empty($result))
                    {
                
                        //转拼音首字母
                        $result = ecm_iconv("UTF-8", "GBK",$result);
                        $result = GetPinyin($result,1,1);
                        //去掉价格
                        $result = preg_replace('/_P\d+_/i', '_', $result);
                        $orders[$key]['goods_info'] .= $result."(".$orderGoods['specification'].' '.Lang::get('goods_quantity_1').":".$orderGoods['quantity'].")";
                    }
                    
                    $goods_count += $orderGoods['quantity'];
                }
                $orders[$key]['goods_count'] = $goods_count;
                $orders[$key]['goods_info'] = Lang::get('order_goods_quantity1').$goods_count.Lang::get('order_goods_quantity2').$orders[$key]['goods_info'];
                $orders[$key]['dl_name']= $model_delivery->getDLName($orders[$key]['dl_id']);
            }
        } 
        //dump($orders);
        $this->assign("behalf",$model_behalf->get($this->visitor->get('has_behalf')));
        $this->assign("orders",$orders);
        $this->assign('ids',$order_id);
        $this->display("behalf_member.print.html");
    }
    
    /**
     * 获取快递单号
     */
    function  get_invoice_no()
    {
        
        $order_id = isset($_POST['ids']) ? trim($_POST['ids']) : '';
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }
        $status = array(ORDER_ACCEPTED);
        $order_ids = explode(',', $order_id);
        $order_ids = array_filter($order_ids);
        
        $model_order    =&  m('order');
        $model_ordermodeb =& m('ordermodeb');
        $model_behalf  =& m('behalf');
        $model_delivery =& m('delivery');
        //
        $dl_id = $model_delivery->get(array(
           'conditions'=>"dl_desc like 'yuantong'", 
        ));
        
       
        
        /* 只有未发货的订单可以生成快递打印单 */
        $orders  = $model_order->findAll(array(
            'conditions'    =>"order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) ." AND order_alias.bh_id=".$this->visitor->get('has_behalf'),
            'join'          => 'has_orderextm',
            'include'       =>  array(
                'has_ordergoods',       //取出商品
            ),
             
        ));
        
        if(!empty($orders))
        {
            $behalf =$model_behalf->get($this->visitor->get('has_behalf'));
            import('createOrderModeB');
            $orderMB = new CreateOrderModeB('K200225829','3dv20UFA','http://service.yto56.net.cn/CommonOrderModeBServlet.action');
         
            foreach ($orders as $key=>$value)
            {
                //不是圆通，不获取订单快递号
                if($dl_id['dl_id'] != $value['dl_id'])
                    continue;
                if(!empty($value['invoice_no']))
                    continue;
                $order = array();
                $order['order_sn'] = $value['order_sn'];
                $order['sender_name'] = $behalf['owner_name'];
                $order['sender_code'] = $behalf['zipcode'];
                $order['sender_mob'] = $behalf['bh_tel'];
                $tmp = $this->turnspace($behalf['region_name']);
                $region_arr = explode(',', $tmp);
                $region_arr = array_filter($region_arr);
                $order['sender_prov'] = $region_arr[1];
                $city = $region_arr[2];
                for($i=3;$i<count($region_arr);$i++)
                {
                    $city .= ','.$region_arr[$i];
                }
                $order['sender_city'] = $city;
                $order['sender_address'] = $behalf['bh_address'];
                
                $order['receiver_name'] = $value['consignee'];
                $order['receiver_code'] = $value['zipcode'];
                $order['receiver_phone'] = $value['phone_tel'];
                $order['receiver_mob'] = $value['phone_mob'];
                $tmp = $this->turnspace($value['region_name']);
                $region_arr = explode(',', $tmp);
                $region_arr = array_filter($region_arr);
                $order['receiver_prov'] = $region_arr[1];
                $city = $region_arr[2];
                for($i=3;$i<count($region_arr);$i++)
                {
                    $city .= ','.$region_arr[$i];
                }
                $order['receiver_city'] = $city;
                $order['receiver_address'] = $value['address'];

                $order['goods_amount'] = $value['goods_amount'];
                $order['order_amount'] = $value['order_amount'];
                $order['order_goods'] =  $value['order_goods'];
                //dump($order);  
                $orderMB->setOrder($order,'yto');
                $ret_xml = $orderMB->getOrderModeB();
                $xml = simplexml_load_string($ret_xml);
                //record modeb
                $modeb = $model_ordermodeb->get($value['order_id']);
                if(empty($modeb))
                {
                    $model_ordermodeb->add(array('order_id'=>$value['order_id'],'md_content'=>$ret_xml));
                }
                else
                {
                    $model_ordermodeb->edit($value['order_id'],array('md_content'=>$ret_xml));
                }
                if($xml->success)
                {
                    $invoice_no = $xml->orderMessage->mailNo;
                    $model_order->edit($value['order_id'],array('invoice_no'=>strval($invoice_no)));
                    //dump($ret_xml);
                    
                }
            }
        }
        echo ecm_json_encode(true);
    }
    
    /**
     * 打印并同步发货
     */
    function print_async_shipped()
    {
        //停用
        return ;
        $order_id = isset($_POST['ids']) ? trim($_POST['ids']) : '';
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }
        $status = array(ORDER_ACCEPTED);
        $order_ids = explode(',', $order_id);
        $order_ids = array_filter($order_ids);
        
        $model_order    =&  m('order');
        $order_log =& m('orderlog');
        $model_member =& m('member');
        $ordervendor_mod = &m('ordervendor');
        /* 只有未发货的订单可以生成快递打印单 */
        $orders  = $model_order->find(array(
            'conditions'    =>"order_alias.order_id" . db_create_in($order_ids) . " AND order_alias.status " . db_create_in($status) ." AND order_alias.bh_id=".$this->visitor->get('has_behalf'),
        ));
        
        if($orders)
        {
            foreach ($orders as $order)
            {
                if(!empty($order['invoice_no']) && $order['status'] == ORDER_ACCEPTED)
                {
                    $edit_data = array('status' => ORDER_SHIPPED,'ship_time'=>gmtime());
                    /*商付通v2.2.1 更新商付通定单状态 开始*/
                    if($order['payment_code']=='sft' || $order['payment_code']=='chinabank' || $order['payment_code']=='alipay' || $order['payment_code']=='tenpay' || $order['payment_code']=='tenpay2')
                    {
                        $my_moneylog=& m('my_moneylog')->edit('order_id='.$order['order_id'],array('caozuo'=>20));
                    }
                    /*商付通v2.2.1  更新商付通定单状态 结束*/
                    $affect_rows = $model_order->edit($order['order_id'], $edit_data);
                    if ($model_order->has_error())
                    {
                        $this->pop_warning($model_order->get_error());  
                        continue;
                    }
                    #TODO 发邮件通知
                    /*记录订单操作日志 */                    
                    $order_log->add(array(
                        'order_id'  => $order['order_id'],
                        'operator'  => addslashes($this->visitor->get('user_name')),
                        'order_status' => order_status($order['status']),
                        'changed_status' => order_status(ORDER_SHIPPED),
                        'remark'    => $_POST['remark'],
                        'log_time'  => gmtime(),
                    ));
                    /* 发送给买家订单已发货通知 */
                    $buyer_info   = $model_member->get($order['buyer_id']);
                    //$mail = get_mail('tobuyer_shipped_notify', array('order' => $order_info));
                    //$this->_mailto($buyer_info['email'], addslashes($mail['subject']), addslashes($mail['message']));
                    if($buyer_info['phone_mob'])
                    {
                        $com = $model_order->get_delivery_bybehalf($order['order_id'],$order['bh_id']);
                        $order['dl_name'] = $com;
                        $smail = get_mail('sms_order_notify', array('order' => $order));
                        $this->sendSaleSms($buyer_info['phone_mob'],  addslashes($smail['message']));
                    }
                    /* 如果匹配到的话，修改第三方订单状态 */
                    $ordervendor_mod->edit("ecm_order_id={$order['order_id']}", array(
                        'status' => VENDOR_ORDER_SHIPPED,
                    ));
                    
                }
            }
        }
        
        echo ecm_json_encode(true);
        
    }
    
    /**
     *  获取订单失败原因
     */
    function get_failinfo()
    {
        $ret_str = 'nothing!';
        $order_id = isset($_POST['id']) && $_POST['id'] ? intval($_POST['id']) :0;
        if(empty($order_id))
            return $ret_str;
        $model_ordermodeb =& m('ordermodeb');
        
        $failinfo = $model_ordermodeb->get($order_id);
        
        if(empty($failinfo))
            return $ret_str;
        else 
           return $failinfo['md_content'];        
    }
    

    function _getDeliveryNameById($dl_id)
    {
        $model_delivery = & m('delivery');
        $delivery = $model_delivery->get($dl_id);
        return $delivery['dl_name'];
    }

    /**
     * 获取用户联系方式
     * @param 用户id $user_id
     * @return object
     */
    function _get_member_profile($user_id)
    {
        $ms =& ms();    //连接用户系统
        $mprofile = $ms->user->_local_get(array(
            'conditions'=>'user_id='.$user_id,
            'fields'=>'im_qq,im_aliww,phone_mob',
                                                ));
        return $mprofile;
    }

    /**
     * 卖家留言
     */
    function sell_message()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id)
        {
            echo Lang::get('no_such_order');

            return;
        }
        $model_order    =&  m('order');
        $order_info     = $model_order->get(array(
            'conditions'    => "order_id={$order_id} ",
                                                  ));
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->display('behalf_member.seller_message.html');
        }
        else
        {
            /* 卖家留言*/
            $seller_message = isset($_POST['seller_message']) ? trim($_POST['seller_message']) : 0;
            /* 标志*/
            !empty($seller_message) && $seller_message_flag=2;
            empty($seller_message) && $seller_message_flag=0;

            $data = array(
                'seller_message'  => html_filter($seller_message),
                'seller_message_flag'  => $seller_message_flag,
                          );

            $model_order->edit($order_id, $data);

            if ($model_order->has_error())
            {
                $this->pop_warning($model_order->get_error());

                return;
            }

            $this->pop_warning('ok','behalf_member_seller_message');
        }
    }

    /**
     * 调整收货地址
     */
    function adjust_consignee()
    {
        $model_orderextm    =&  m('orderextm');
        $model_order = & m('order');
        $model_behalf = & m('behalf');
        //$model_orderbehalf=& m('orderbehalfs');
       
        $thisdelivery = $model_behalf->getRelatedData('has_delivery',$this->visitor->get('has_behalf'));
        
        if (!IS_POST)
        {
        	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        	if (!$order_id)
        	{
        		echo Lang::get('no_such_order');
        		return;
        	}
            header('Content-Type:text/html;charset=' . CHARSET);
            $consignee     = $model_orderextm->get(array(
                    'conditions'    => "order_id={$order_id} ",
            ));

            $region_mod =& m('region');
            $this->assign('regions', $region_mod->get_options(0));
            $this->assign('consignee', $consignee);
            $this->assign('deliverys', $thisdelivery);
            $this->display('behalf_member.adjust_consignee.html');
        }
        else
        {

            $data = $_POST;
            $dl_id = isset($_POST['dl_id']) && $_POST['dl_id'] ? intval($_POST['dl_id']) :0;
            $data['dl_id'] = $dl_id;
            $dl_name = '';
            foreach ($data as $key=>$value)
            {
                if(empty($value))
                    unset($data[$key]);
            }
            foreach ($thisdelivery as $vdelivery)
            {
            	if($dl_id == $vdelivery['dl_id'])
            		$dl_name = $vdelivery['dl_name'];
            }
            $this->_check_region($data);
            $model_orderextm->edit($data['order_id'], $data);
            
            $model_goods_warehouse = & m('goodswarehouse');  
            db()->query("UPDATE ".$model_goods_warehouse->table." SET delivery_id='{$dl_id}', delivery_name='{$dl_name}' WHERE order_id={$data['order_id']}");

            if ($model_orderextm->has_error())
            {
                $this->pop_warning($model_orderextm->get_error());
                return;
            }
            
            if($model_goods_warehouse->has_error())
            {
            	$this->pop_warning($model_goods_warehouse->get_error());
            }

            $order_info = $model_order->get($data['order_id']);
           /*  if($dl_id && $dl_id != $consignee['dl_id'])
            {
                //如果修改了快递
                $model_orderbehalf->edit($order_id,array('dl_id' => $dl_id));
            } */
            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                'order_id'  => $data['order_id'],
                'operator'  => addslashes($this->visitor->get('user_name')),
                'order_status' => order_status($order_info['status']),
                'changed_status' => order_status($order_info['status']),
                'remark'    => Lang::get('adjust_consignee'),
                'log_time'  => gmtime(),
                                  ));

            $this->pop_warning('ok','behalf_member_adjust_consignee');
        }

    }
    
    /**
     * @param data
     */
    private function _check_region($data)
    {
        $model_region =& m('region');
        $regionArr = $model_region->get_layer($data['region_id']);
        $region_name ='';
    
        if(!$data['region_id'])
        {
            $this->pop_warning('region_illeage');
            return;
        }
        if(!$model_region->isleaf($data['region_id']))
        {
            $this->pop_warning('region_illeage');
            return;
        }
        foreach ($regionArr as $region)
        {
            if(strpos($data['region_name'],$region['region_name'])===false)
            {
                $this->pop_warning($region['region_name']);
                return;
            }
            $region_name .= $region['region_name'].' ';
        }
        if(!preg_match('/^1[34578][0-9]{9}$/',$data['phone_mob']))
        {
            $this->pop_warning('phone_illeage');
            return;
        }
        if(!empty($data['zipcode']))
        {
            if(!preg_match('/\d{6}/',$data['zipcode']))
            {
                $this->pop_warning('zipcode is error!');
                return;
            }
        }
    
    
        $data['region_name'] = $region_name;
        return $data;
    }

    /**
     * 处理退货退款请求
     */
    function applied_refund()
    {
        //2015-11-22 暂停关闭，存在并发性能问题
       
        //利用php文件锁解决并发问题
        $lock_file = ROOT_PATH."/data/applied_refund.lock";
        if(!file_exists($lock_file))
        {
            file_put_contents($lock_file, 1);
        }
        
        
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }
        $model_order    =&  m('order');
        $model_ordergoods =& m('ordergoods');
        $model_orderrefund = & m('orderrefund');
        //对文件加锁
        $fp = fopen($lock_file, 'a+');
        if(!$fp)
        {
            echo 'fail to open lock file!';
            return;
        }
        flock($fp, LOCK_EX);
        
        /* 只有已付款和已经发货、已完成的订单可以申请退货退款 */
        $order_info     = $model_order->get("order_id={$order_id}  AND bh_id=" . $this->visitor->get('user_id')." AND status " . db_create_in(array(ORDER_ACCEPTED, ORDER_SHIPPED,ORDER_FINISHED)));
        $order_info_status = $order_info['status'];//记录订单变化状态
        if(!empty($order_info))
        {
            $refund_results=$model_orderrefund->find(array(
                    'conditions'=>'order_id='.$order_info['order_id'].' AND sender_id='.$order_info['buyer_id'].' AND receiver_id='.$order_info['bh_id']." AND status='0' AND closed='0'",
            ));
        }
        else
        {
            $refund_results = array();
        }
        
        /*文件解锁*/
        flock($fp, LOCK_UN);
        fclose($fp);
        
        
        if(count($refund_results) != 1)
        {
            echo count($refund_results)>1?Lang::get('feifashenqi_1'):Lang::get('feifashenqi_0');
            return ;
        }
        $refund_result = array();
        foreach ($refund_results as $value)
        {
            if($value['status'] == 0 && $value['closed'] == 0)
                $refund_result = $value;
        }
        if(!$refund_result || $refund_result['apply_amount'] <= 0)
        {
            echo Lang::get('feifashenqi_3');
            return ;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->assign('refund',$refund_result);
            $this->assign("show_refund",count($refund_results) > 1 ?false:true);
            $this->display('behalf_member.applied_refund.html');
        }
        else
        { 
            $refund_agree = isset($_POST['agree'])?intval(trim($_POST['agree'])):0;
            $zf_pass = isset($_POST['zf_pass'])?trim($_POST['zf_pass']):'';
            if(!in_array($refund_agree, array(1,2)))
            {
            	$this->pop_warning("feifacaozuo");
                return;
            }
            if(empty($zf_pass) && $refund_agree==1)
            {
            	$this->pop_warning("passwd_again");
                return;
            }
            //$refund_result = $refund_result;
            
            //开始数据库事务
            $db_transaction_begin = db()->query("START TRANSACTION");
            if($db_transaction_begin === false)
            {
            	$this->pop_warning('fail_caozuo');
            	return;
            }
            $db_transaction_success = true;//默认事务执行成功，不用回滚
            $db_transaction_reason = '';//回滚的原因
            
            //开始转账
            if($refund_agree == 1)
            {
            	$data=array(
            			'order_id'=>$order_info['order_id'],
            			'order_sn'=>$order_info['order_sn'],
            			'refund_amount'=>$refund_result['apply_amount'],
            			'pay_time'=>gmtime(),
            			'status'=>$refund_agree,
            			'closed'=>0
            	);
            	$refund_message = Lang::get('refund_message').$order_info['order_sn'].','.$refund_result['apply_amount'];
            	
            	$affect_rows = $model_orderrefund->edit($refund_result['id'],$data);
            	if(empty($affect_rows) || $model_orderrefund->has_error())
            	{
            		echo "refund write. <br>";
            		$db_transaction_success = false;
            		$db_transaction_reason = 'write_db_failed';
            		
            		//$this->pop_warning('write_db_failed');
            		//return;
            	}
            	
            	include_once(ROOT_PATH.'/app/fakemoney.app.php');
            	$fakemoneyapp = new FakeMoneyApp();
            	
                //退还分润,必须是已发货或已完成，且退货有商品
                if(in_array($order_info['status'], array(ORDER_SHIPPED,ORDER_FINISHED)) && $refund_result['goods_ids'])
                {
                    //计算返款
                    $rec_ids = explode(',', $refund_result['goods_ids']);
                    $rec_goods = $model_ordergoods->find(array(
                       'conditions'=> 'order_id='.$order_id.' AND '.db_create_in($rec_ids,'rec_id'),
                    ));
                    $behalf_discount = 0;
                    if($rec_goods)
                    {
                        foreach ($rec_goods as $goods)
                        {
                            if($goods['oos_value'] && $goods['behalf_to51_discount'] > 0)
                            {
                                $behalf_discount += $goods['behalf_to51_discount'];
                                $model_ordergoods->edit($goods['rec_id'],array('zwd51_tobehalf_discount'=>$goods['behalf_to51_discount']));
                                if($model_ordergoods->has_error())
                                {
                                    continue;
                                }
                            }
                        }
                    }
                    if($behalf_discount > 0)
                    {
                        $fr_reason = Lang::get('behalf_to_51_tk_reason').local_date('Y-m-d H:i:s',gmtime());
                        //给用户转账
                        $my_money_result=$fakemoneyapp->to_user_withdraw(FR_USER,$this->visitor->get('user_id'),$behalf_discount, $fr_reason,$order_info['order_id'],$order_info['order_sn']);
                        if($my_money_result !== true)
                        {
                        	echo "fenrun reback! <br>";
                            $db_transaction_success = false;
                            $db_transaction_reason = $my_money_result;
                            
                            //$this->pop_warning($my_money_result);
                            //return;
                        }
                     }
                }
                
                
                include_once(ROOT_PATH.'/app/my_money.app.php');
                $my_moneyapp = new My_moneyApp();

                //给用户转账
                $my_money_result=$my_moneyapp->to_user_withdraw($order_info['buyer_name'],$refund_result['apply_amount'], $order_id,$order_info['order_sn'],$zf_pass);
                if($my_money_result !== true)
                {
                	echo "pay user.<br>";
                	$db_transaction_success = false;
                	$db_transaction_reason = $my_money_result;
                	
                    //$this->pop_warning($my_money_result);
                    //return;
                }
                
                //全额退款时，才解冻订单全部资金，自动关闭订单。未发货=订单总价，已发货=订单商品价格
                if($refund_result['apply_amount'] == $order_info['goods_amount'] || $refund_result['apply_amount'] == $order_info['order_amount'] )
                {          
                    if($order_info['status'] != ORDER_FINISHED)
                    {
                    	//这是相当于收货了，订单资金解冻
                    	$my_money_result=$my_moneyapp->jd_behalf_refund($this->visitor->get('user_id'),$order_info['order_amount'], $order_info['order_sn']);
                    	if($my_money_result !== true)
                    	{
                    		//echo "jd money.<br>";
                    		//$db_transaction_success = false;
                    		//$db_transaction_reason = "jd_failed";
                    		
                    		//$this->pop_warning($my_money_result);
                    		//return;
                    	}
                    } 

                    $affect_rows = $model_order->edit($order_info['order_id'], array('status' => ORDER_CANCELED));
                    if (empty($affect_rows) || $model_order->has_error())
                    {
                    	echo "cancel order.<br>";
                    	$db_transaction_success = false;
                    	$db_transaction_reason = 'write_db_failed';
                    
                    	//$this->pop_warning($model_order->get_error());
                    	//return;
                    }
                    
                    //商付通 更新状态
                    $my_moneylog_mod =& m('my_moneylog');
                    $my_moneylog_mod->edit('order_id='.$order_info['order_id'],array('caozuo'=>80));
                    //商付通 结束
                    $order_info_status = ORDER_CANCELED;
                }
                
                //这是已完成订单申请的退款，前面手动冻结，现在解冻
                if($order_info['status'] == ORDER_FINISHED)
                {
                   $affect_result	= $fakemoneyapp->manuRefro($order_info['bh_id'], $refund_result['apply_amount']);
                   if($affect_result === false)
                   {
                   		//$db_transaction_success = false;
                   		//$db_transaction_reason = 'jd_failed';
                   }
                }

                
            }

            if($refund_agree == 2)
            {
                $data=array(
                        'order_id'=>$order_info['order_id'],
                        'order_sn'=>$order_info['order_sn'],
                        'status'=>$refund_agree,
                        'closed'=>isset($_POST['reapplay_refund'])&& !empty($_POST['reapplay_refund'])?1:0
                );
                $refund_message = Lang::get('refund_message_disagree').$order_info['order_sn'].','.$refund_result['apply_amount'];
                
                $affect_rows = $model_orderrefund->edit($refund_result['id'],$data);
                if(empty($affect_rows) || $model_orderrefund->has_error())
                {
                	echo "refuse requent.<br>";
                	$db_transaction_success = false;
                	$db_transaction_reason = 'write_db_failed';
                	
                	//$this->pop_warning($model_orderrefund->get_error());
                	//return;
                }
            }
            
            
            
            /* 连接用户系统 */
            $ms =& ms();
            $msg_id = $ms->pm->send($this->visitor->get('user_id'), array($order_info['buyer_id']), '', $refund_message);
            


            /* $new_data = array(
                    'status'    => Lang::get('apply_refund'),
                    'actions'   => array(), //取消订单后就不能做任何操作了
            ); */
            /* 记录订单操作日志 */
            $order_log =& m('orderlog');
            $order_log->add(array(
                    'order_id'  => $order_info['order_id'],
                    'operator'  => addslashes($this->visitor->get('user_name')),
                    'order_status' => order_status($order_info['status']),
                    'changed_status' => order_status($order_info_status),
                    'remark'    => $refund_message,
                    'log_time'  => gmtime(),
            ));

            /* 如果是关联到第三方订单的话, 需要同时修改淘宝订单的状态 */
            $ordervendor_mod = &m('ordervendor');
            $ordervendor_mod->edit("ecm_order_id=".$order_info['order_id'], array(
                'status' => VENDOR_ORDER_UNHANDLED,
                'ecm_order_id' => 0));
            
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
            	$this->pop_warning($db_transaction_reason);
            	return;
            }
            /* 发送给买家订单转账通知 */
            //$model_member =& m('member');
            //$seller_info   = $model_member->get($order_info['buyer_id']);
            //$mail = get_mail('tobuyer_apply_refund_notify', array('order' => $order_info, 'reason' =>$refund_message ));
            //$this->_mailto($seller_info['email'], addslashes($mail['subject']), $refund_message);
            /*短信通知,事务成功 了再发*/
            if($db_transaction_success !== false) {
            	//$this->sendSaleSms($seller_info['phone_mob'], $refund_message);老吴要求2015-11-29
            }
            
            $this->pop_warning('ok');
        }
    }
        

    /**
     * 申请补邮
     */
    function apply_fee()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id)
        {
            echo Lang::get('no_such_order');
            return;
        }
        $model_order    =&  m('order');
        $model_orderrefund = & m('orderrefund');
        /* 只有已付款,已发货,已完成的订单可以申请补邮 */
        $order_info     = $model_order->get("order_id={$order_id} AND bh_id=" . $this->visitor->get('user_id') . " AND status " . db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED,ORDER_FINISHED)));

        if (empty($order_info))
        {
            echo Lang::get('no_such_order');
            return;
        }
        if (!IS_POST)
        {
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->assign('order', $order_info);
            $this->display('behalf_member.apply_fee.html');
        }
        else
        {
            //status 0:申请，1：已同意，2：已拒绝  closed 0:未关闭 1：已关闭
            $refund_result=$model_orderrefund->find(array(
                    'conditions'=>'order_id='.$order_info['order_id'].' AND receiver_id='.$order_info['buyer_id'].' AND status=0 AND closed=0',
            ));
            if(!empty($refund_result))
            {
                $this->pop_warning(Lang::get('exist_apply'));
                return ;
            }

            $refund_amount = isset($_POST['refund_amount'])?floatval(trim($_POST['refund_amount'])):0;
            if($refund_amount > 100)
            {
                echo "hack attacked";
                return;
            }
            if(!isset($_POST['apply_fee_reason']) || empty($_POST['apply_fee_reason']))
            {
                echo "hack attacked";
                return;
            }
            if(empty($order_info['bh_id']))
            {
                echo 'hack attacked';
                return;
            }
            $data=array(
                    'order_id'=>$order_info['order_id'],
                    'order_sn'=>$order_info['order_sn'],
                    'sender_id'=>$this->visitor->get('user_id'),
                    'sender_name'=>$this->visitor->get('user_name'),
                    'receiver_id'=>$order_info['buyer_id'],
                    'receiver_name'=>$order_info['buyer_name'],
                    'refund_reason'=>html_filter($_POST['apply_fee_reason']),
                    'refund_intro'=>html_filter($_POST['refund_intro']),
                    'apply_amount'=>$refund_amount,
                    'refund_amount'=>0,
                    'create_time'=>gmtime(),
                    'pay_time'=>0,
                    'status'=>0,
                    'closed'=>0,
                    'type'=>2,//代发申请补邮
            );
            $model_orderrefund=& m('orderrefund');
            $model_orderrefund->add($data);
            if($model_orderrefund->has_error())
            {
                $this->pop_warning($model_orderrefund->get_error());
                return;
            }

            $refund_message = Lang::get('apply_fee_message').$order_info['order_sn'];

            /* 连接用户系统 */
            $ms =& ms();
            $msg_id = $ms->pm->send($this->visitor->get('user_id'), array($order_info['buyer_id']), '', $refund_message);
            
            /* 发送给买家订单补收差价通知 */
            $model_member =& m('member');
            $seller_info   = $model_member->get($order_info['buyer_id']);
           /*  $mail = get_mail('tobuyer_apply_fee_notify', array('order' => $order_info, 'reason' => $_POST['apply_fee_reason']));
            $this->_mailto($seller_info['email'], addslashes($mail['subject']), $refund_message); */
            /*短信通知*/
            $this->sendSaleSms($seller_info['phone_mob'], $refund_message);
            $new_data = array(
                    'status'    => Lang::get('apply_refund'),
                    'actions'   => array(), //取消订单后就不能做任何操作了
            );

            $this->pop_warning('ok');
        }

    }

    function gen_barcode($goods_list)
    {
        /* if(empty($goods_list))
        {
            $this->show_warning('no goods');
            return;
        }
        import('barcode/barcode.lib');
        
        $barcode = new barcodeprocessor();
        $barcode->delfiles($this->visitor->get('user_id'));
        $barcode->setUid($this->visitor->get('user_id'));
        foreach($goods_list as $goods)
        {
            foreach($goods['order_sn_and_count'] as $vv)
            {
                $barcode->setText($vv[0]);
                $barcode->generate();
            }
        } */
        // $barcode->delfiles(5);
    }
    
    /**
     * out of stock 缺货通知
     */
    function oos_notice()
    {
    	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    	if (!$order_id)
    	{
    		echo Lang::get('no_such_order');    	
    		return;
    	}
    	$model_order    =&  m('order');
    	$order_info     = $model_order->findAll(array(
    			'conditions'    => "order_id={$order_id} AND ".db_create_in(array(ORDER_ACCEPTED,ORDER_SHIPPED),'status'),//待发货
    			'include'       =>  array(
    					'has_ordergoods',       //取出商品
    			),
    	));
    	if(count($order_info) != 1)
    	{
    		echo Lang::get('no_such_order');
    		return;
    	}
    	if (!IS_POST)
    	{
    		header('Content-Type:text/html;charset=' . CHARSET);
    		$this->assign('order', current($order_info));
    		$this->display('behalf_member.oos_message.html');
    	}
    	else
    	{
    		//释放ids=rec_ids,oos=rec_ids,reasons,order_id
    		$model_goods_statistics = & m('goodsstatistics');
    		extract($_POST);
    		$model_order_goods =& m('ordergoods');
    		$order_goods = $model_order_goods->find(array(
    			'conditions'=>db_create_in($ids,'rec_id') .' AND order_id='.$order_id,	
    		));
    		if(count($ids) != count($order_goods))
    		{
    			$this->pop_warning('no_such_order');
    			return;
    		}
    		if(!empty($oos))
    		{
    			foreach ($oos as $value)
    			{
    				if(!in_array($value, $ids))
    				{
    					$this->pop_warning('no_such_order');
    					return;
    				}
    			}
    		}
    		foreach ($ids as $key=>$value)
    		{
    			$data=array();
    			if(!empty($oos) && in_array($value, $oos))
    			{
    				$data['oos_value'] = 0;
    				$model_goods_statistics->edit($order_goods[$value]['goods_id'],'oos=oos+1');
    			}
    			else 
    			{
    				$data['oos_value'] = 1;
    			}
    			$data['oos_reason']='['.local_date('Y-m-d H:i',gmtime()).']'.html_filter(trim($reasons[$key]));
    			$model_order_goods->edit($value,$data);
    			if($model_order_goods->has_error())
    			{
    				$this->pop_warning($model_order_goods->get_error());    				
    			}
    		}
    		//message
    		$order = current($order_info);
    	    $message = Lang::get('behalf_notice').''.local_date('Y-m-d H:i',gmtime()).' '.Lang::get('your_order').
    	    '['.$order['order_sn'].'] ';
    		if(empty($oos))
    		{
    			$message .= Lang::get('order_goods_control');
    		}
    		else
    		{
    			$message .= Lang::get('list_oos');
    		}
    		
    		/* 连接用户系统 */
    		$ms =& ms();
    		$msg_id = $ms->pm->send($this->visitor->get('user_id'), array($order['buyer_id']), '', $message);
    		/*短信通知*/
    		//$this->sendSaleSms($seller_info['phone_mob'], $refund_message);
    		    		
    		
    		/* 发送给买家订单转账通知 */    		
    		$buyer_info   = $ms->user->_local_get($order['buyer_id']);    		
    		$this->_mailto($buyer_info['email'], Lang::get('behalf_notice1'), $message);
    		
    		$this->pop_warning('ok','behalf_member_oos_notice');
    		
    	}
    }
    
    function trimall($str)//删除空格
    {
        $qian=array(" ","　","\t","\n","\r");
        $hou=array("","","","","");
        return str_replace($qian,$hou,$str);
    }
    function turnspace($str)//转换为,
    {
        $qian=array(" ","　","\t","\n","\r");
        $hou=array(",",",",",",",",",");
        return str_replace($qian,$hou,$str);
    }
    
    function _allow_behalf_setting($fuc_name)
    {
        $allowed = false;
        $behalfs_menu = Conf::get('behalfs_menu');
        if(!$behalfs_menu)
        {
            $this->show_warning('not_allow_setting_behalf');
            return false;
        }        
        foreach ($behalfs_menu as $menu)
        {
            if($fuc_name == $menu)
                $allowed = true;
        }
        
        if(!$allowed)
        {           
            $this->show_warning('not_allow_setting_behalf');
            return false;
        }
        
        return $allowed;
    }
    
    

}

?>
