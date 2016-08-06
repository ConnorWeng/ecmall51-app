<?php

/* 申请开店 */
class ApplyApp extends MallbaseApp
{

    function index()
    {
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
        /* 判断是否开启了店铺申请 */
        if (!Conf::get('store_allow'))
        {
            $this->show_warning('apply_disabled');
            return;
        }

        /* 只有登录的用户才可申请 */
        if (!$this->visitor->has_login)
        {
            $this->login();
            return;
        }

        /* 已申请过或已有店铺不能再申请 */
        $store_mod =& m('store');
        $store = $store_mod->get($this->visitor->get('user_id'));
        if ($store)
        {
            if ($store['state'])
            {
                $this->show_warning('user_has_store');
                return;
            }
            else
            {
                if ($step != 2)
                {
                    $this->show_warning('user_has_application');
                    return;
                }                
            }
        }
        $sgrade_mod =& m('sgrade');
        
        switch ($step)
        {
            case 1:
                $sgrades = $sgrade_mod->find(array(
                    'order' => 'sort_order',
                ));
                foreach ($sgrades as $key => $sgrade)
                {
                    if (!$sgrade['goods_limit'])
                    {
                        $sgrades[$key]['goods_limit'] = LANG::get('no_limit');
                    }
                    if (!$sgrade['space_limit'])
                    {
                        $sgrades[$key]['space_limit'] = LANG::get('no_limit');
                    }
                    $arr = explode(',', $sgrade['functions']);
                    $subdomain = array();
                    foreach ( $arr as $val)
                    {
                        if (!empty($val))
                        {
                            $subdomain[$val] = 1;
                        }
                    }
                    $sgrades[$key]['functions'] = $subdomain;
                    unset($arr);
                    unset($subdomain);
                }
                $this->assign('domain', ENABLED_SUBDOMAIN);
                $this->assign('sgrades', $sgrades);

                $this->_config_seo('title', Lang::get('title_step1') . ' - ' . Conf::get('site_title'));
                $this->display('apply.step1.html');
                break;
            case 2:
                $sgrade_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                $sgrade = $sgrade_mod->get($sgrade_id);
                if (empty($sgrade))
                {
                    $this->show_message('request_error',
                        'back_step1', 'index.php?app=apply');
                         exit;
                }

                $market_mod =& m('market');
                
                if (!IS_POST)
                {
                    $region_mod =& m('region');                   
                    $this->assign("markets",$market_mod->get_options(1));
                    $this->assign('site_url', site_url());
                    $this->assign("nc",OEM);
                    $this->assign('regions', $region_mod->get_options(0));
                    $this->assign('scategories', $this->_get_scategory_options());
                    $this->assign('goods_discounts',Lang::get('goods_discounts'));

                    /* 导入jQuery的表单验证插件 */
                    $this->import_resource(array('script' => 'mlselection.js,jquery.plugins/jquery.validate.js'));

                    $this->_config_seo('title', Lang::get('title_step2') . ' - ' . Conf::get('site_title'));
                    $this->assign('store', $store);
                    $scategory = $store_mod->getRelatedData('has_scategory', $this->visitor->get('user_id'));
                    if ($scategory)
                    {
                        $scategory = current($scategory);
                    }
                    $this->assign('scategory', $scategory);
                    $this->display('apply.step2.html');
                }
                else
                {
                	//for connor.W
                	$shop_mall_floor = explode('-', trim($_POST['mk_name']));
                	
                    $store_mod  =& m('store');

                    $store_id = $this->visitor->get('user_id');
                    $data = array(
                        'store_id'     => $store_id,
                        'store_name'   => $_POST['store_name'],
                        //'owner_name'   => $_POST['owner_name'],
                        //'owner_card'   => $_POST['owner_card'],
                        'region_id'    => $_POST['region_id'],
                        'region_name'  => $_POST['region_name'],
                        'address'      => $_POST['dangkou_address'],
                    	'dangkou_address' => $_POST['dangkou_address'],
                    	'mk_id' => $_POST['mk_id'], 
                    	'mk_name' => $_POST['mk_name'],
                    	//给connor.W 备用
                    	'shop_mall'=> $shop_mall_floor[0],
                    	'floor' => $shop_mall_floor[1],
                    	//'mk_name' => $this->_get_full_market_name(intval(trim($_POST['mk_id']))),
                    	'im_qq' => $_POST['im_qq'], 
                        //'zipcode'      => $_POST['zipcode'],
                        'shop_http' => $_POST['shop_http'],
                    	'see_price' => $_POST['see_price'],
                    	'serv_refund' => $_POST['serv_refund']?1:0,
                    	'serv_exchgoods' => $_POST['serv_exchgoods']?1:0,
                    	'serv_sendgoods' => $_POST['serv_sendgoods']?1:0,
                    	'im_ww' => $_POST['im_ww'],
                    	'im_wx' => $_POST['im_wx'],
                        'tel'          => $_POST['tel'],
                        'sgrade'       => $sgrade['grade_id'],
                       //'apply_remark' => $_POST['apply_remark'],
                        'state'        => $sgrade['need_confirm'] ? 0 : 1,
                        'add_time'     => gmtime(),
                    );
                    $mk_layer = $market_mod->get_layer($data['mk_id']);
                    if($mk_layer != 3)
                    {
                        $this->show_warning('market_floor_bottom');
                        return;
                    }                   
                    //$image = $this->_upload_image($store_id);
                   /*  if ($this->has_error())
                    {
                        $this->show_warning($this->get_error());
                        return;
                    } */
                    //print_r($data);
                    
                    if(empty($data['shop_http']) || empty($data['im_ww']) )
                    {
                        $this->show_warning('ww_checked');
                        return ;
                    }
                    if($store_mod->get("shop_http like '%".$data['shop_http']."%'") || $store_mod->get("im_ww like '%".$data['im_ww']."%'"))
                    {
                        $this->show_warning('shop_http_checked');
                        return ;
                    }
                                     
                    /* 判断是否已经申请过 */
                    $state = $this->visitor->get('state');
                    if ($state != '' && $state == STORE_APPLYING)
                    {
                        //$store_mod->edit($store_id, array_merge($data, $image));
                    	$affect_row = $store_mod->edit($store_id, $data);
                    }
                    else
                    {
                        $affect_row = $store_mod->add($data);
                    }
                    
                    if ($store_mod->has_error())
                    {
                        $this->show_warning($store_mod->get_error());
                        return;
                    }
                    

                    $cate_id = intval($_POST['cate_id']);
                    $store_mod->unlinkRelation('has_scategory', $store_id);
                    if ($cate_id > 0)
                    {                        
                        $store_mod->createRelation('has_scategory', $store_id, $cate_id);
                    }

                    if ($sgrade['need_confirm'])
                    {
                        $this->show_message('apply_ok',
                            'index', 'index.php');
                    }
                    else
                    {
                        $this->send_feed('store_created', array(
                            'user_id'   => $this->visitor->get('user_id'),
                            'user_name'   => $this->visitor->get('user_name'),
                            'store_url'   => SITE_URL . '/' . url('app=store&id=' . $store_id),
                            'seller_name'   => $data['store_name'],
                        ));
                        $this->_hook('after_opening', array('user_id' => $store_id));
                        $this->show_message('store_opened',
                            'index', 'index.php');
                    }
                }
                break;
            default:
                header("Location:index.php?app=apply&step=1");
                break;
        }
    }

    function check_name()
    {
        $store_name = empty($_GET['store_name']) ? '' : trim($_GET['store_name']);
        $store_id = empty($_GET['store_id']) ? 0 : intval($_GET['store_id']);

        $store_mod =& m('store');
        if (!$store_mod->unique($store_name, $store_id))
        {
            echo ecm_json_encode(false);
            return;
        }
        echo ecm_json_encode(true);
    }
    /**
     * 入驻时，不能有重复的旺旺号，否则需要申诉
     * 如果存在，return false
     */
    function check_ww()
    {
        $im_ww = $_POST['ww'] ? trim($_POST['ww']) : '';
        
        if(empty($im_ww))
        {
            echo ecm_json_encode(false);
            return;
        }
        $store_mod =& m('store');
        $result = $store_mod->get("im_ww like '%".$im_ww."%'");
        if(empty($result))
        {
            echo ecm_json_encode(true);
            return ;
        }
        echo ecm_json_encode(false);
    }
    
    /**
     * 入驻时，检查淘宝网址是否重复
     */
    function check_shop_address()
    {
        $shop_http = $_POST['taobao'] ? trim($_POST['taobao']) : '';
                
        if(empty($shop_http))
        {
            echo ecm_json_encode(false);
            return;
        }
        $store_mod =& m('store');
        $result = $store_mod->get("shop_http like '%".$shop_http."%'");
        if(empty($result))
        {
            echo ecm_json_encode(true);
            return ;
        }
        echo ecm_json_encode(false);
    }

    /* 上传图片 */
    function _upload_image($store_id)
    {
        import('uploader.lib');
        $uploader = new Uploader();
        $uploader->allowed_type(IMAGE_FILE_TYPE);
        $uploader->allowed_size(SIZE_STORE_CERT); // 400KB

        $data = array();
        for ($i = 1; $i <= 3; $i++)
        {
            $file = $_FILES['image_' . $i];
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
                $dirname   = 'data/files/mall/application';
                $filename  = 'store_' . $store_id . '_' . $i;
                $data['image_' . $i] = $uploader->save($dirname, $filename);
            }
        }
        return $data;
    }

    /* 取得店铺分类 */
    function _get_scategory_options()
    {
        $mod =& m('scategory');
        $scategories = $mod->get_list();
        import('tree.lib');
        $tree = new Tree();
        $tree->setTree($scategories, 'cate_id', 'parent_id', 'cate_name');
        return $tree->getOptions();
    }
    
    function _get_full_market_name($mk_id)
    {
    	$mod_market =& m('market');
    	$mk_name = '';
    	$temp_array = array();
    	if(!empty($mk_id))
    	{
    		$temp_array = $mod_market->get_ancestor($mk_id);
    		if(!empty($temp_array))
    		{
    			$temp_array = array_values($temp_array);
    			unset($temp_array["0"]);
    		}
    		$temp_array = array_reverse($temp_array);
    	}
    	foreach ($temp_array as $key => $value)
    	{
    		$mk_name = $value['mk_name'] ."-".$mk_name;
    	}
    	if(strlen($mk_name))
    	{
    		$mk_name = substr($mk_name, 0,-1);
    	}
    	return $mk_name;
    }
}

?>
