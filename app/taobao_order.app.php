<?php

class Taobao_orderApp extends StoreadminbaseApp {
    function index() {
        $this->_check_member_fill_contact();
        $orders = $this->_get_orders();
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                         LANG::get('order_manage'), 'index.php?app=taobao_order&vendor='.$_GET['vendor'],
                         LANG::get('order_list'));
        $this->_curitem($_GET['vendor'] == 1 ? 'order_manage_import' : 'order_manage_taobao');
        $this->_curmenu('all_orders');
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('order_manage'));
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
        $this->assign('vendor', $_GET['vendor']);
        if ($_GET['vendor'] == 0 && count($orders) == 0) {
            $this->_syncWithTaobao();
        }
        $this->display('taobao_order.index.html');
    }

    function deleteOrder() {
        $order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
        if (!$order_id) {
            $this->show_warning('no_such_order');
            return;
        }
        $order_ids = explode(',', $order_id);
        $conditions = 'seller_id='.$this->visitor->get('user_id').' and vendor = 1 and status = 50 and order_id'.db_create_in($order_ids);
        $ordervendor_mod = &m('ordervendor');
        $affected_rows = $ordervendor_mod->drop($conditions);
        if ($affected_rows > 0) {
            $goods_conditions = 'order_id'.db_create_in($order_ids);
            $goodsvendor_mod = &m('goodsvendor');
            $goodsvendor_mod->drop($goods_conditions);
            $this->show_message('delete_order_succeed', 'back_excel_order', 'index.php?app=taobao_order&vendor=1');
        } else {
            $this->show_warning('delete_order_failed');
            Log::write('[delete_order_failed] conditions: '.$conditions);
        }
    }

    function syncAllBackToTaobao() {
        $summary = array(
            'success' => 0,
            'fail' => 0,
            'fail_details' => '');
        $ordervendor_mod =& m('ordervendor');
        $sql = 'select v.order_sn tid, o.*, e.* from ecm_order_vendor v, ecm_order o, ecm_order_extm e where v.status=53 and v.vendor=0 and v.seller_id='.$this->visitor->get('user_id').' and v.ecm_order_id=o.order_id and o.order_id = e.order_id';
        $order_infos = $ordervendor_mod->getAll($sql);
        if (is_array($order_infos) && count($order_infos) > 0) {
            foreach($order_infos as $order_info) {
                $result = $this->_syncBackToTaobao($order_info);
                if ($result === true) {
                    $summary['success'] = $summary['success'] + 1;
                } else {
                    $summary['fail'] = $summary['fail'] + 1;
                    $summary['fail_details'] = $summary['fail_details'].'订单 '.$order_info['tid'].' '.$result.'<br/>';
                }
            }
        }
        if ($summary['success'] > 0 && $summary['fail'] === 0) {
            $this->show_message('操作成功');
        } else if ($summary['success'] === 0 && $summary['fail'] === 0) {
            $this->show_warning('没有需要发货的淘宝订单');
        } else {
            $this->show_warning($summary['fail_details']);
        }
    }

    function syncBackToTaobao() {
        $ecm_order_id = $_GET['ecm_order_id'];
        $tid = $_GET['order_sn'];
        $order_model =& m('order');
        $order_info  = $order_model->get(array(
            "conditions" => "order_alias.order_id={$ecm_order_id} AND buyer_id=" . $this->visitor->get('user_id'),
            'join' => 'has_orderextm'));
        $order_info['tid'] = $tid;
        $result = $this->_syncBackToTaobao($order_info);
        if ($result === true) {
            $this->show_message('sync_succeed', 'back_taobao_order', 'index.php?app=taobao_order&vendor=0');
        } else {
            $this->show_warning($result);
        }
    }

    function _syncBackToTaobao($order_info) {
        if (empty($order_info)) {
            return Lang::get('no_such_order');
        }
        $out_sid = $order_info['invoice_no'];
        $logisticscompany_mod =& m('logisticscompany');
        $company_code = $logisticscompany_mod->get_company_code($order_info['dl_id']);
        if ($company_code) {
            $app_key = '21336502';
            $secret_key = '608b67adaba42db656de726b7cf2549f';
            $shipping = json_decode(file_get_contents('http://yjsc.51zwd.com/taobao-upload-multi-store/index.php?g=Taobao&m=Api&a=sendTaobaoLogisticsOnline&tid='.$order_info['tid'].'&out_sid='.$out_sid.'&company_code='.$company_code.'&app_key='.$app_key.'&secret_key='.$secret_key.'&session_key='.$_SESSION['taobao_access_token']));
            if (isset($shipping->is_success) && $shipping->is_success === 'true') {
                $ordervendor_mod =& m('ordervendor');
                $ordervendor_mod->edit("ecm_order_id={$ecm_order_id}", array(
                    'status' => VENDOR_ORDER_SYNCED));
                return true;
            } else {
                $msg = $shipping->sub_code.' '.$shipping->sub_msg;
                if ($shipping->code == '26') {
                    $msg = '请先退出登录，然后重新使用淘宝登录再尝试一下';
                } else if ($shipping->code == '15') {
                    if (strpos($shipping->sub_code, 'AT0011') !== false) {
                        $msg = '物流订单状态不为新建状态,无需发货处理';
                    } else if (strpos($shipping->sub_code, 'B79') !== false) {
                        $msg = '该物流公司揽收或派送范围不支持,可能是您的默认发货、退货地址不对，请前往淘宝(卖家地址库)设置正确的发货地址、退货地址';
                    } else if (strpos($shipping->sub_code, 'B101') !== false) {
                        $msg = '淘宝地址库信息不存在,可能是您的默认发货、退货地址不对，请前往淘宝(卖家地址库)设置正确的发货地址、退货地址';
                    }
                }
                return '发生错误: '.$msg;
            }
        } else {
            return '快递不存在';
        }
    }

    function searchGoods() {
        $keywords = explode(' ', $_GET['keywords']);
        $goods_mod =& m('goods');
        $goods = $goods_mod->get_Mem_list(array(
            'order' => 'views desc',
            'fields' => 'g.goods_id,',
            'limit' => 25,
            'conditions_tt' => $keywords), null, false, true, $total_found);
        $goodsspec_mod =& m('goodsspec');
        $result = array();
        foreach ($goods as $key => $good) {
            if ($this->_filter_goods($_GET['bh_id'], array($good))) {
                $goodsspec = $goodsspec_mod->get_spec_list($good['goods_id']);
                $result = array_merge($result, $goodsspec);
            }
        }

        echo ecm_json_encode($result);
    }

    function _get_orders() {
        $vendor = $_GET['vendor'];
        $con = array(
            array(
                'field' => 'status'),
            array(
                'field' => 'buyer_name',
                'equal' => 'LIKE'),
            array(
                'field' => 'order_sn'));
        $ext = $this->_get_query_conditions($con);
        $page = $this->_get_page();
        $orderVendorMod = &m('ordervendor');
        $orders = $orderVendorMod->findAll(array(
            'conditions' => "vendor=".$vendor." and seller_id=".$this->visitor->get('user_id').$ext,
            'count' => true,
            'limit' => $page['limit'],
            'order' => 'add_time DESC',
            'include' => array(
                'has_goodsvendor',
            )
        ));
        $page['item_count'] = $orderVendorMod->getCount();
        $this->_format_page($page);
        $this->assign('orders', $orders);
        $this->assign('page_info', $page);
        return $orders;
    }

    function view() {
        $orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $orderVendorMod = &m('ordervendor');
        $orderInfo = $orderVendorMod->findAll(array(
            'conditions' => "order_id={$orderId}",
            'include' => array(
                'has_goodsvendor',
            )
        ));
        $this->assign('goods_list', array_values($orderInfo[$orderId]['goods_vendor']));
        $this->assign('order', $orderInfo[$orderId]);
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                         LANG::get('order_manage'), 'index.php?app=taobao_order',
                         LANG::get('order_list'));
        $this->_curitem('order_manage_taobao');
        $this->_curmenu('all_orders');
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('order_manage'));
        $this->display('taobao_order.view.html');
    }

    function quick_daifa() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if (!$order_id) {
            $this->show_warning('no_such_order');
            return;
        }
        if (!IS_POST) {
            $goodsvendor_mod = &m('goodsvendor');
            $goods = $goodsvendor_mod->find(array(
                'conditions' => "goods_vendor.order_id={$order_id}"));
            $matched_goods = array();
            $matched_goods_num = 0;
            $total_price = 0.0;
            $goods_mod =& m('goods');
            $goodsspec_mod = &m('goodsspec');
            foreach ($goods as $good) {
                // 根据$good['outer_iid']查找ecm_goods_attr表, 找到相应的goods_id, 再吧goods_id放入cart
                $outer_iid = $good['outer_iid'];
                $common_outer_iid = $this->_make_common_outer_iid($outer_iid);
                if ($common_outer_iid) {
                    $good_info = $goods_mod->get_Mem_list(array(
                        'order' => 'views desc',
                        'fields' => 'g.goods_id,',
                        'limit' => 1,
                        'conditions_tt' => explode(' ', $common_outer_iid)), null, false, true, $total_found);
                }
                //* to see if good_info is correct, and add default spec to cart
                if ($good_info) {
                    $keys = array_keys($good_info);
                    $goods_id = $good_info[$keys[0]]['goods_id'];
                    $goodsspec = $goodsspec_mod->find(array(
                        'conditions' => "goods_id={$goods_id} and spec_1='{$good['spec_value_1']}' and spec_2='{$good['spec_value_2']}'",
                        'index_key' => ''));
                    if (count($goodsspec) > 0) {
                        $spec_id = $goodsspec[0]['spec_id'];
                        $good_info[$keys[0]]['spec_id'] = $spec_id;
                        $good_info[$keys[0]]['spec_value_1'] = $goodsspec[0]['spec_1'];
                        $good_info[$keys[0]]['spec_value_2'] = $goodsspec[0]['spec_2'];
                        $good_info[$keys[0]]['specification'] = $goodsspec[0]['spec_1'].' '.$goodsspec[0]['spec_2'];
                        $good_info[$keys[0]]['outer_iid'] = $good_info[$keys[0]]['attr_value'];
                        $good_info[$keys[0]]['price'] = $goodsspec[0]['price'];
                        $matched_goods[] = array_merge($good_info[$keys[0]], array('num' => $good['num']));
                        $matched_goods_num += intval($good['num']);
                        $total_price += $good['num'] * $good_info[$keys[0]]['price'];
                    }
                }
            }

            $order = $this->_get_vendor_order($order_id);

            $behalf_mod =& m('behalf');
            $behalfs = $behalf_mod->get_behalfs_deliverys();

            header('Content-Type:text/html;charset='.CHARSET);
            $this->assign('behalfs', $behalfs);
            $this->assign('behalfsJSON', json_encode($behalfs));
            $default_behalf_id = 0;
            $default_delivery_id = 0;
            foreach ($behalfs as $bh_id => $behalf) {
                $this->assign('deliverys', $behalfs[$bh_id]['deliveries']);
                $default_behalf_id = $bh_id;
                foreach ($behalfs[$bh_id]['deliveries'] as $dl_id => $dl) {
                    $default_delivery_id = $behalfs[$bh_id]['deliveries'][$dl_id]['dl_id'];
                    break;
                }
                break;
            }
            $this->assign('goods', $goods);
            $this->assign('matched_goods', $this->_filter_goods($default_behalf_id, $matched_goods));
            $this->assign('total_price', $total_price);
            $this->assign('ship_fee', $this->_get_ship_fee($default_behalf_id, $default_delivery_id, $matched_goods_num));
            $this->assign('order_id', $order_id);
            $this->assign('taobao_post_fee', $order['post_fee']);
            $this->assign('taobao_total_fee', $order['total_fee']);
            $this->display('taobao_order.quick_daifa.html');
        } else {
            $matched_goods = $this->_get_matched_goods($_POST);
            if (count($matched_goods) > 0) {
                $this->_quick_order($matched_goods, $order_id);
            } else {
                $this->show_warning('没有宝贝');
            }
        }
    }

    function _quick_order($goods, $vendor_order_id) {
        $merge_goodsinfo = $this->_get_goods_info($goods);
        if ($merge_goodsinfo === false) {
            $this->show_warning('goods_empty');
            return;
        }
        // TODO: 检查库存
        $order = $this->_get_vendor_order($vendor_order_id);
        $region_id = $this->_get_region_id($order['receiver_city'], $order['receiver_district'], $order['receiver_town']);
        $address_options = $this->_add_receiver_address($order, $region_id);
        $goods_info = array(
            'items'     =>  array(),    //商品列表
            'quantity'  =>  0,          //商品总量
            'amount'    =>  0,          //商品总价
            'store_id'  =>  0,          //所属店铺
            'store_name'=>  '',         //店铺名称
            'type'      =>  null,       //商品类型
            'otype'     =>  'behalf',   //订单类型
            'allow_coupon'  => false,   //是否允许使用优惠券
            'rec_ids' => array(),
            'behalf_fee' => 0);
        $store_ids = array();
        foreach ($merge_goodsinfo as $key=>$value) {
            if(!empty($value['items'])) {
                foreach ($value['items'] as $goods_id=>$goods_value) {
                    $goods_info['items'][$goods_value['spec_id']] = $goods_value;
                }
            }
            $goods_info['quantity'] = intval($goods_info['quantity']) + $value['quantity'];
            $goods_info['amount'] = floatval($goods_info['amount']) + $value['amount'];//2015-06-05 by tanaiquan,intval($goods_info['amount'])变为floatval($goods_info['amount'])
            $goods_info['store_name'] = $goods_info['store_name']." ".$value['store_name'];
            $goods_info['type'] = $value['type'];
            $goods_info['behalf_fee'] = floatval($goods_info['behalf_fee']) + floatval($value['behalf_fee']);
            $store_ids[] = $key;
        }

        $goods_type =& gt($goods_info['type']);
        $order_type =& ot($goods_info['otype']);

        $check_result = $order_type->_check_behalf_blacklist($_POST['behalf'], $store_ids);
        if ($check_result !== true) {
            $this->show_warning(sprintf(Lang::get('store_exist_blacklist'), $check_result['store_name'], $check_result['bh_name']));
            return;
        }

        $post = array(
            'address_options' => $address_options,
            'consignee' => $order['receiver_name'],
            'address' => $order['receiver_address'],
            'phone_tel' => $order['receiver_phone'],
            'phone_mob' => $order['receiver_mobile'],
            'region_name' => $this->_make_region_name($order),
            'zipcode' => $order['receiver_zip'],
            'region_id' => $region_id,
            'behalf' => $_POST['behalf'],
            'shipping_choice' => '2',
            'delivery' => $_POST['delivery'],
            'postscript' => $_POST['postscript']);

        $order_id = $order_type->submit_merge_order(array(
            'goods_info'    =>  $goods_info, //商品信息（包括列表，总价，总量，所属店铺，类型）,可靠的!
            'post'          =>  $post));    //用户填写的订单信息
        if (!$order_id) {
            $this->show_warning($order_type->get_error());
            return;
        }
        /* 发送邮件 */
        $model_order =& m('order');
        /* 减去商品库存 */
        $model_order->change_stock('-', $order_id);
        /* 获取订单信息 */
        $order_info = $model_order->get($order_id);

        $buyer_address = $this->visitor->get('email');
        $model_behalf = &m('behalf');

        /*发送给代发下单通知*/
        $model_member =& m('member');

        $behalf_info = $model_member->get($_POST['behalf']); // FIXME: hard code 51代发
        $behalf_address = $behalf_info['email'];
        $order_info['bh_name'] = $behalf_info['bh_name'];
//        print_r($order_info);
//    dualven 20150803
//        $behalf_mail = get_mail('tobehalf_new_order_notify', array('order' => $order_info));
//        $this->_mailto($behalf_address, addslashes($behalf_mail['subject']), addslashes($behalf_mail['message']));

        /* 发送给买家下单通知 */
//        $buyer_mail = get_mail('tobuyer_new_order_notify', array('order' => $order_info));
//        $this->_mailto($buyer_address, addslashes($buyer_mail['subject']), addslashes($buyer_mail['message']));

        /* 更新下单次数 */
        $model_goodsstatistics =& m('goodsstatistics');
        $goods_ids = array();
        foreach ($goods_info['items'] as $goods)
        {
            $goods_ids[] = $goods['goods_id'];
            //更新销售量
            $model_goodsstatistics->edit($goods['goods_id'], "sales=sales+{$goods['quantity']}");
        }
        $model_goodsstatistics->edit($goods_ids, 'orders=orders+1');

        /* 更新vendor订单状态 */
        $ordervendor_mod =& m('ordervendor');
        $ordervendor_mod->edit($vendor_order_id, array(
            'status' => VENDOR_ORDER_PENDING,
            'ecm_order_id' => $order_id));

        /* 删除前面新增的address options */
        $model_address =& m('address');
        $model_address->drop('addr_id='.$address_options);

        /* 到收银台付款 */
        header('Location:index.php?app=cashier&order_id=' . $order_id);
    }

    function _get_region_id($city, $district, $town) {
        $region_mod =& m('region');
        $likeCity = $this->_cut_region_name($city);
        $region = $region_mod->get(array(
            'conditions' => "region_name like '{$likeCity}%'"));
        if (!$region) {
            return '2';
        }
        $likeDistrict = $this->_cut_region_name($district);
        $descendantRegion = $region_mod->get(array(
            'conditions' => "parent_id = {$region['region_id']} and region_name like '{$likeDistrict}%'"));
        if (!$descendantRegion) {
            return $region['region_id'];
        }
        if ($town) {
            $likeTown = $this->_cut_region_name($town);
            $townRegion = $region_mod->get(array(
                'conditions' => "parent_id = {$descendantRegion['region_id']} and region_name like '{$likeTown}%'"));
            if ($townRegion) {
                return $townRegion['region_id'];
            }
        }
        return $descendantRegion['region_id'];
    }

    function _cut_region_name($region) {
        $newRegion = str_replace('市', '', $region);
        $newRegion = str_replace('区', '', $newRegion);
        $newRegion = str_replace('镇', '', $newRegion);
        return $newRegion;
    }

    function _add_receiver_address($order, $region_id) {
        $region_name = $this->_make_region_name($order);
        $data = array(
            'user_id'       => $this->visitor->get('user_id'),
            'consignee'     => $order['receiver_name'],
            'region_id'     => $region_id,    // FIXME: hard code
            'region_name'   => $region_name,
            'address'       => $order['receiver_address'],
            'zipcode'       => $order['receiver_zip'],
            'phone_tel'     => $order['receiver_phone'],
            'phone_mob'     => $order['receiver_mobile']);
        $model_address =& m('address');
        // $address = $model_address->get(array(
        //     'conditions' => "user_id=".$this->visitor->get('user_id')." and consignee='".$order['receiver_name']."' and region_id=".$region_id." and region_name='".$region_name."' and address='".$order['receiver_address']."' and zipcode='".$order['receiver_zip']."' and phone_tel='".$order['receiver_phone']."' and phone_mob='".$order['receiver_mobile']."'"
        // ));
        // if ($address) {
        //     return $address['addr_id'];
        // } else {
        $address_id = $model_address->add($data);
        return $address_id;
        // }
    }

    function _get_vendor_order($order_id) {
        $ordervendor_mod =& m('ordervendor');
        $order = $ordervendor_mod->find(array(
            'conditions' => "order_vendor.order_id={$order_id}"));
        return $order[$order_id];
    }

    function _get_goods_info($goods) {
        $return = array();
        $store_ids = $this->_get_store_ids($goods);
        foreach ($store_ids as $store_id) {
            $levy_behalf_fee = belong_behalfarea($store_id);
            $data = array(
                'items' => array(),
                'quantity'  =>  0,          //商品总量
                'amount'    =>  0,          //商品总价
                'store_id'  =>  0,          //所属店铺
                'store_name'=>  '',         //店铺名称
                'type'      =>  null,       //商品类型
                'otype'     =>  'normal',   //订单类型
                'allow_coupon'  => true,    //是否允许使用优惠券
                'rec_ids' => array(),
                'behalf_fee' => 0);
            $store_model =& m('store');
            $store_info = $store_model->get($store_id);
            $items = $this->_get_items($goods, $store_id);
            $data['items'] = $items;
            $data['quantity'] += $this->_get_quantity($items);
            $data['amount'] += $this->_get_amount($items);
            $data['store_id'] = $store_id;
            $data['store_name'] = $store_info['store_name'];
            $data['store_im_qq'] = $store_info['im_qq'];
            $data['type'] = 'material';
            $data['otype'] = 'behalf';
            $data['behalf_fee'] = $levy_behalf_fee === false ? $data['quantity'] * floatval(BEHALF_GOODS_SERVICE_FEE) : 0;
            $return[$store_id] = $data;
        }
        return $return;
    }

    function _get_amount($items) {
        $amount = 0;
        foreach ($items as $item) {
            $amount += $item['quantity'] * $item['price'];
        }
        return $amount;
    }

    function _get_quantity($items) {
        $quantity = 0;
        foreach ($items as $item) {
            $quantity += $item['quantity'];
        }
        return $quantity;
    }

    function _get_items($goods, $store_id) {
        $levy_behalf_fee = belong_behalfarea($store_id);
        $items = array();
        foreach ($goods as $good) {
            if ($good['store_id'] == $store_id) {
                $items[] = array(
                    'user_id' => $this->visitor->get('user_id'),
                    'session_id' => SESS_ID,
                    'store_id' => $store_id,
                    'goods_id' => $good['goods_id'],
                    'goods_name' => $good['goods_name'],
                    'spec_id' => $good['spec_id'],
                    'specification' => $good['specification'],
                    'price' => $good['price'],
                    'quantity' => $good['num'],
                    'goods_image' => $good['default_image'],
                    'subtotal' => $good['num'] * $good['price'],
                    'behalf_fee' => $levy_behalf_fee === false ? $goods['num'] * floatval(BEHALF_GOODS_SERVICE_FEE) : 0);
            }
        }
        return $items;
    }

    function _get_store_ids($goods) {
        $return = array();
        foreach ($goods as $good){
            if (array_search($good['store_id'], $return) === false) {
                $return[] = $good['store_id'];
            }
        }
        return $return;
    }

    function _get_matched_goods($post) {
        $result = array();
        $goodsspec_mod = &m('goodsspec');
        foreach ($post as $key => $val) {
            if (strpos($key, 'spec_id') === 0) {
                $specId = $val;
                $num = $post['num_'.$specId];
                $goods = $goodsspec_mod->find(array(
                    'conditions' => "spec_id={$specId}",
                    'fields' => 'gs.spec_id,gs.goods_id,gs.spec_1,gs.spec_2,gs.color_rgb,gs.price,gs.stock,gs.sku,gs.spec_vid_1,gs.spec_vid_2,g.store_id,g.type,g.goods_name,g.description,g.cate_id,g.cate_name,g.brand,g.spec_qty,g.spec_name_1,g.spec_name_2,g.if_show,g.closed,g.close_reason,g.add_time,g.last_update,g.default_spec,g.default_image,g.searchcode,g.recommended,g.cate_id_1,g.cate_id_2,g.cate_id_3,g.cate_id_4,g.service_shipa,g.tags,g.sort_order,g.good_http,g.moods,g.cids,g.realpic,g.spec_pid_1,g.spec_pid_2,g.delivery_template_id,g.delivery_weight',
                    'join' => 'belongs_to_goods',
                    'index_key' => ''));
                if ($goods) {
                    $goods[0]['num'] = $num;
                    $goods[0]['specification'] = $goods[0]['spec_1'].' '.$goods[0]['spec_2'];
                    $result[] = $goods[0];
                }
            }
        }
        return $result;
    }

    function get_ship_fee() {
        echo ecm_json_encode($this->_get_ship_fee($_GET['bh_id'], $_GET['dl_id'], $_GET['goods_quantity']));
    }

    function _get_ship_fee($bh_id, $dl_id, $goods_quantity) {
        $behalf_mod =& m('behalf');
        return $behalf_mod->calculate_behalf_delivery_fee($bh_id, $dl_id, $goods_quantity);
    }

    function _make_common_outer_iid($outer_iid) {
        if (!$outer_iid) {
            return false;
        }
        $parts = explode('_', $outer_iid);
        if (mb_strpos($outer_iid, '#', 0, 'utf-8') == mb_strlen($outer_iid, 'utf-8') - 1 || count($parts) === 3) {
            return $this->_make_outer_iid_from_51($outer_iid);
        }
        if (count($parts) >= 5) {
            return $this->_make_outer_iid_from_17($outer_iid);
        }
        if (mb_strpos($outer_iid, '/', 0, 'utf-8') !== false) {
            return $this->_make_outer_iid_from_ppkoo($outer_iid);
        }
        if (mb_strpos($outer_iid, ' ', 0, 'utf-8') !== false) {
            return $this->_make_outer_iid_from_vvic($outer_iid);
        }
        return false;                   // shouldn't be here
    }

    function _make_outer_iid_from_51($outer_iid) {
        if (!$outer_iid) {
            return '';
        }
        $parts = explode('_', $outer_iid);
        if (count($parts) !== 3) {
            return $outer_iid;
        }
        return $parts[0].' '.$parts[2];
    }

    function _make_outer_iid_from_vvic($vvic_outer_iid) {
        $huoHaoRegex = '/#(.+)#/';
        preg_match($huoHaoRegex, $vvic_outer_iid, $matches);
        $huoHao = $matches[1];
        $parts = explode(' ', $vvic_outer_iid);
        $mall = mb_substr($parts[0], 0, 2, 'utf-8');
        $addressParts = explode('-', $parts[1]);
        $address = $addressParts[0];
        return $mall.' '.$address.' '.$huoHao.'#';
    }

    function _make_outer_iid_from_17($outer_iid_17) {
        $huoHaoRegex = '/#(.+)/';
        preg_match($huoHaoRegex, $outer_iid_17, $matches);
        $huoHao = $matches[1];
        $parts = explode('_', $outer_iid_17);
        $shop = $parts[1];
        $addressRegex = '/(\d+)F(\w+)/';
        preg_match($addressRegex, $shop, $matches);
        $address = $matches[2];
        $mall = mb_substr($shop, 0, 2, 'utf-8');
        return $mall.' '.$address.' '.$huoHao.'#';
    }

    function _make_outer_iid_from_ppkoo($outer_iid_ppkoo) {
        $huoHaoRegex = '/#(.+)/';
        preg_match($huoHaoRegex, $outer_iid_ppkoo, $matches);
        $huoHao = $matches[1];
        $parts = explode('_', $outer_iid_ppkoo);
        $shop = $parts[0];
        $shopParts = explode('/', $shop);
        $address = $shopParts[1];
        $mall = mb_substr($shopParts[0], 0, 2, 'utf-8');
        return $mall.' '.$address.' '.$huoHao.'#';
    }

    function _make_region_name($order) {
        return trim($order['receiver_state'].' '.$order['receiver_city'].' '.$order['receiver_district']);
    }

    function _check_member_fill_contact()
    {
        $ms =& ms();    //连接用户系统
        $mprofile = $ms->user->_local_get(array(
                'conditions'=>'user_id='.$this->visitor->get('user_id'),
                'fields'=>'im_qq,im_aliww,phone_mob',
        ));
        if(!$mprofile['im_qq'] || !$mprofile['im_aliww'] || !$mprofile['phone_mob'])
        {
            $this->show_warning('请完善个人资料，方便我们为您发货，谢谢', '填写基本信息', 'index.php?app=member&act=profile');
            return false;
        }
    }

    function _filter_goods($bh_id, $goods) {
        $result = array();
        $behalf_mod =& m('behalf');
        foreach ($goods as $good) {
            if ($behalf_mod->is_behalf_goods($bh_id, array($good['goods_id'])) && floatval($good['price']) > 5) {
                $result[] = $good;
            }
        }
        return $result;
    }

    function is_behalf_goods() {
        $goods_ids = explode(',', $_GET['goods_ids']);
        $bh_id = $_GET['bh_id'];
        $behalf_mod =& m('behalf');
        if ($behalf_mod->is_behalf_goods($bh_id, $goods_ids)) {
            echo ecm_json_encode(true);
        } else {
            echo ecm_json_encode(false);
        }
    }

    function addTrade($trade) {
        $orderVendorMod = &m('ordervendor');
        $existOrders = $orderVendorMod->find('order_sn='.$trade->tid.' and seller_id='.$this->visitor->get('user_id'));

        // fix bug: $trade->receiver_phone is {}
        if (is_object($trade->receiver_phone)) {
            $trade->receiver_phone = null;
        }

        if (!is_array($existOrders) || count($existOrders) == 0) {
            $orderId = $orderVendorMod->add(array(
                'order_sn' => $trade->tid,
                'seller_id' => $this->visitor->get('user_id'),
                'seller_name' => $this->visitor->get('user_name'),
                'buyer_name' => $trade->buyer_nick,
                'receiver_name' => $trade->receiver_name,
                'receiver_mobile' => $trade->receiver_mobile,
                'receiver_address' => str_replace("'", '', $trade->receiver_address),
                'status' => '50',
                'vendor' => '0',
                'add_time' => gmtime(),
                'pay_time' => gmstr2time($trade->pay_time),
                'price' => $trade->total_fee,
                'post_fee' => $trade->post_fee,
                'receiver_phone' => $trade->receiver_phone,
                'receiver_state' => $trade->receiver_state,
                'receiver_city' => $trade->receiver_city,
                'receiver_district' => $trade->receiver_district,
                'buyer_email' => $trade->buyer_email,
                'receiver_zip' => $trade->receiver_zip,
                'shipping_type' => $trade->shipping_type,
                'total_fee' => $trade->total_fee,
                'discount_fee' => $trade->discount_fee,
                'payment' => $trade->payment,
                'last_update' => gmtime()));
            if (is_array($trade->orders->order)) {
                foreach ($trade->orders->order as $order) {
                    $this->addGoodsVendor($orderId, $order);
                }
            } else {
                $this->addGoodsVendor($orderId, $trade->orders->order);
            }
        } else {
            foreach ($existOrders as $id => $existOrder) {
                $orderVendorMod->edit($id, array(
                    'receiver_name' => $trade->receiver_name,
                    'receiver_mobile' => $trade->receiver_mobile,
                    'receiver_address' => str_replace("'", '', $trade->receiver_address),
                    'pay_time' => gmstr2time($trade->pay_time),
                    'price' => $trade->total_fee,
                    'post_fee' => $trade->post_fee,
                    'receiver_phone' => $trade->receiver_phone,
                    'receiver_state' => $trade->receiver_state,
                    'receiver_city' => $trade->receiver_city,
                    'receiver_district' => $trade->receiver_district,
                    'buyer_email' => $trade->buyer_email,
                    'receiver_zip' => $trade->receiver_zip,
                    'shipping_type' => $trade->shipping_type,
                    'total_fee' => $trade->total_fee,
                    'discount_fee' => $trade->discount_fee,
                    'payment' => $trade->payment,
                    'last_update' => gmtime()));
                break;
            }
        }
    }

    function _syncWithTaobao($pageNo = 1) {
        $resp = $this->getTradesSold($pageNo);
        $this->handleTaobaoTrades($resp->trades->trade);
        if (intval($resp->total_results) > $pageNo * 100) {
            $this->_syncWithTaobao($pageNo + 1);
        }
    }

    function handleTaobaoTrades($trades) {
        if (is_array($trades)) {
            foreach ($trades as $trade) {
                $this->addTrade($trade);
            }
        } else if (isset($trades->buyer_nick)) {
            $this->addTrade($trades);
        }
    }

    function syncWithTaobao() {
        $this->_syncWithTaobao();
        $this->deleteInvalidTrades();
        $this->show_message('同步成功');
    }

    function deleteInvalidTrades() {
        $conditions = 'seller_id='.$this->visitor->get('user_id').' and vendor = 0 and status < 51 and last_update < '.strval(gmtime());
        $ordervendor_mod = &m('ordervendor');
        $ordervendor_mod->drop($conditions);
    }

    function getTradesSold($pageNo) {
        $json = file_get_contents('http://yjsc.51zwd.com/taobao-upload-multi-store/index.php?g=Taobao&m=Api&a=getTradesSold&db='.OEM.'&user_id='.$this->visitor->get('user_id').'&page_no='.strval($pageNo));
        $resp = json_decode($json);
        if (isset($resp->trades->trade)) {
            return $resp;
        } else if (isset($resp->code)) {
            Log::write('[sync failed] username:'.$this->visitor->get('user_name').' uid:'.$this->visitor->get('user_id').' resp:'.$json);
            $this->show_warning('没有找到符合要求的订单:'.strval($resp->code).' '.$resp->msg.' '.$resp->sub_msg);
            exit;
        } else if (isset($resp->total_results) && $resp->total_results === '0') {
            $this->show_message('亲，您没有待发货的淘宝订单');
            exit;
        } else {
            $this->show_warning('没有找到符合要求的订单:'.$json);
            exit;
        }
    }

    function addGoodsVendor($orderId, $order) {
        $goodsVendorMod = &m('goodsvendor');
        $propertiesNameParts = explode(';', $order->sku_properties_name);
        $parts1 = explode(':', $propertiesNameParts[0]);
        $specName1 = $parts1[0];
        $specValue1 = $parts1[1];
        $specName2 = '';
        $specValue2 = '';
        if (count($propertiesNameParts) == 2) {
            $parts2 = explode(':', $propertiesNameParts[1]);
            $specName2 = $parts2[0];
            $specValue2 = $parts2[1];
        }
        $goodsVendorMod->add(array(
            'order_id' => $orderId,
            'goods_name' => $order->title,
            'outer_iid' => $order->outer_iid,
            'spec_name_1' => $specName1,
            'spec_value_1' => $specValue1,
            'spec_name_2' => $specName2,
            'spec_value_2' => $specValue2,
            'default_image' => $order->pic_path,
            'price' => $order->price,
            'num' => $order->num,
            'good_http' => 'http://item.taobao.com/item.htm?id='.$order->num_iid,
        ));
    }

    public static function downloadImage($picUrl, $compress = true) {
        $tmpFile = APP_PATH.'Upload/'.uniqid().'.jpg';
        $content = file_get_contents($picUrl);
        file_put_contents($tmpFile, $content);
        return $tmpFile;
    }

    public static function searchViaPicture() {
        $picUrl = $_GET['picUrl'];
        $result = file_get_contents('http://121.40.85.153:8080/searchPic/SearchPicServ?type=search&path='.$picUrl);
        var_dump($result);
    }
}

?>