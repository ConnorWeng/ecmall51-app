<?php

/* 代发申请 */
class BhapplyApp extends MallbaseApp
{

    function index()
    {
    	$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
    	
    	/* 判断是否开启了代发申请 */
    	if (!Conf::get('behalf_allow'))
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

        /* 已申请过或已有代发不能再申请 */
        $behalf_mod =& m('behalf');
        $behalf = $behalf_mod->get($this->visitor->get('user_id'));
        if ($behalf)
        {
            if ($behalf['bh_allowed'])
            {
                $this->show_warning('user_has_behalf');
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
        
              
        switch ($step)
        {
            case 1:
                $this->_config_seo('title', Lang::get('title_step1') . ' - ' . Conf::get('site_title'));
                $this->display('bhapply.step1.html');
                break;
            case 2:                
                if (!IS_POST)
                {
                    $region_mod =& m('region');
                    $shipping_mod = & m('delivery');
                    $market_mod = & m('market');
                    $shippings = $shipping_mod->getAll('select dl_id,dl_name from '.DB_PREFIX.'delivery'.' where if_show > 0');
                    $my_markets = $market_mod->get_list(1);
                    $this->assign('markets',$my_markets);
                    $this->assign('site_url', site_url());
                    $this->assign('regions', $region_mod->get_options(0));
                    $this->assign('shippings',$shippings);
                    
                    /* 导入jQuery的表单验证插件 */
                    $this->import_resource(array('script' => 'mlselection.js,jquery.plugins/jquery.validate.js'));

                    $this->_config_seo('title', Lang::get('title_step2') . ' - ' . Conf::get('site_title'));
                    $this->assign('behalf', $behalf);
                    $this->display('bhapply.step2.html');
                }
                else
                {
                    $behalf_mod  =& m('behalf');

                    $bh_id = $this->visitor->get('user_id');
                    $data = array(
                        'bh_id'     => $bh_id,
                        'bh_name'   => $_POST['bh_name'],
                        'bh_logo'   => $_POST['bh_logo'],
                        'bh_qq'   => $_POST['bh_qq'],
                    	'bh_ww'   => $_POST['bh_ww'],
                    	'bh_wx'   => $_POST['bh_wx'],
                        'region_id'    => $_POST['region_id'],
                        'region_name'  => $_POST['region_name'],
                        'bh_address'      => $_POST['bh_address'],
                        'zipcode'      => $_POST['zipcode'],
                        'bh_tel'          => $_POST['bh_tel'],
                    	//'bh_shipping' =>implode(',',$_POST['bh_shipping']),
                        'create_time'     => gmtime(),
                    );
                    /* 检查名称是否已存在 */
                    if (!$behalf_mod->unique(trim($_POST['bh_name']),$bh_id))
                    {
                    	$this->show_warning('name_exist');
                    	return;
                    }
                    
                    $image = $this->_upload_image($bh_id);
                    if ($this->has_error())
                    {
                        $this->show_warning($this->get_error());

                        return;
                    }
                    /* 判断是否已经申请过 */
                    $state = $behalf['bh_allowed'];
                    if ($state != '' && $state == STORE_APPLYING)
                    {
                        $behalf_mod->edit($bh_id, array_merge($data, $image));
                    }
                    else
                    {                    	
                        $behalf_mod->add(array_merge($data, $image));
                    }
                    
                    if ($behalf_mod->has_error())
                    {
                        $this->show_warning($behalf_mod->get_error());
                        return;
                    }
                    else
                    {
                    	//删除已有关联
                    	$behalf_mod->unlinkRelation('has_market',$bh_id);
                    	if(!empty($_POST['bh_markets']))
                    	{
                    		$behalf_mod->createRelation('has_market',$bh_id,$_POST['bh_markets']);
                    	}                    	
                    	$behalf_mod->unlinkRelation('has_delivery',$bh_id);
                    	if(!empty($_POST['bh_shipping']))
                    	{
                    		$behalf_mod->createRelation('has_delivery',$bh_id,$_POST['bh_shipping']);
                    	}
                    }
                    
                    /*上面又执行了所以重新判断*/
                    if ($behalf_mod->has_error())
                    {
                    	$this->show_warning($behalf_mod->get_error());
                    	return;
                    }


                    if ($behalf['bh_allowed'])
                    {
                        $this->show_message('apply_ok',
                            'index', 'index.php');
                    }
                    else
                    {
                        $this->send_feed('behalf_created', array(
                            'user_id'   => $this->visitor->get('user_id'),
                            'user_name'   => $this->visitor->get('user_name'),
                            'behalf_url'   => SITE_URL . '/' . url('app=behalf&id=' . $bh_id),
                            'behalf_name'   => $data['bh_name'],
                        ));
                        $this->_hook('after_opening', array('user_id' => $bh_id));
                        $this->show_message('behalf_opening',
                            'index', 'index.php');
                    }
                }
                break;
            default:
                header("Location:index.php?app=bhapply&step=1");
                break;
        }
    }

    function check_name()
    {
        $bh_name = empty($_GET['bh_name']) ? '' : trim($_GET['bh_name']);
        $bh_id = empty($_GET['bh_id']) ? 0 : intval($_GET['bh_id']);
        
        if($bh_id == 0){
        	echo ecm_json_encode(true);
        	return;
        }

        $behalf_mod =& m('behalf');
        if (!$behalf_mod->unique($bh_name, $bh_id))
        {
            echo ecm_json_encode(false);
            return;
        }
        echo ecm_json_encode(true);
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

}

?>
