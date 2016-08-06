<?php

/* 代发控制器 */
class BehalfApp extends BackendApp
{
    var $_behalf_mod;
    var $_store_mod;

    function __construct()
    {
        $this->BehalfApp();
    }

    function BehalfApp()
    {
        parent::__construct();
        $this->_behalf_mod =& m('behalf');
        $this->_store_mod =& m('store');
    }

    function index()
    {
        $conditions = empty($_GET['wait_verify']) ? 'bh_allowed >= 0 ' : "bh_allowed = '" . STORE_APPLYING . "'";
        $filter = $this->_get_query_conditions(array(            
                'field' => 'bh_name',
                'equal' => 'like',
        ));
        $owner_name = trim($_GET['owner_name']);
        if ($owner_name)
        {
            $filter .= " AND (user_name LIKE '%{$owner_name}%') ";
        }
        //更新排序
        if (isset($_GET['sort']) && isset($_GET['order']))
        {
            $sort  = strtolower(trim($_GET['sort']));
            $order = strtolower(trim($_GET['order']));
            if (!in_array($order,array('asc','desc')))
            {
                $sort  = 'sort_order';
                $order = '';
            }
        }
        else
        {
            $sort  = 'bh_id';
            $order = 'desc';
        }

        $this->assign('filter', $filter);
        $conditions .= $filter;
        $page = $this->_get_page();
        $behalfs = $this->_behalf_mod->find(array(
            'conditions' => $conditions,
            'join'  => 'belongs_to_user',
            'fields'=> 'this.*,member.user_name',
            'limit' => $page['limit'],
            'count' => true,
            'order' => "$sort $order"
        ));
        /*查找快递*/
        foreach ($behalfs as $key=>$behalf)
        {
        	$deliveries = $this->_behalf_mod->getRelatedData('has_delivery',$behalf['bh_id']);
        	$behalfs[$key]['deliveries'] = $deliveries;
        }
        
        $states = array(
            STORE_APPLYING  => LANG::get('wait_verify'),
            STORE_OPEN      => Lang::get('open'),
        );
        foreach ($behalfs as $key => $behalf)
        {
            $behalfs[$key]['bh_allowed'] = $states[$behalf['bh_allowed']];            
        }
        $this->assign('behalfs', $behalfs);

        $page['item_count'] = $this->_behalf_mod->getCount();
        $this->import_resource(array('script' => 'inline_edit.js'));
        $this->_format_page($page);
        $this->assign('filtered', $filter? 1 : 0); //是否有查询条件
        $this->assign('page_info', $page);

        $this->display('behalf.index.html');
    }
    
    
   

  

    function edit()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!IS_POST)
        {
            /* 是否存在 */
            $behalf = $this->_behalf_mod->get_info($id);
            if (!$behalf)
            {
                $this->show_warning('behalf_empty');
                return;
            }
            
            $this->assign('behalf', $behalf);

            $this->assign('states', array(
                STORE_OPEN   => Lang::get('open'),
                STORE_CLOSED => Lang::get('close'),
            ));

            $this->assign('recommended_options', array(
                '1' => Lang::get('yes'),
                '0' => Lang::get('no'),
            ));

            $region_mod =& m('region');
            $this->assign('regions', $region_mod->get_options(0));          

            /* 导入jQuery的表单验证插件 */
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js,mlselection.js'
            ));
            $this->assign('enabled_subdomain', ENABLED_SUBDOMAIN);
            $this->display('behalf.form.html');
        }
        else
        {
            /* 检查名称是否已存在 */
            if (!$this->_behalf_mod->unique(trim($_POST['bh_name']), $id))
            {
                $this->show_warning('name_exist');
                return;
            }
            $behalf_info = $this->_behalf_mod->get_info($id);           

            $data = array(
                'bh_name'   => $_POST['bh_name'],
                'region_id'    => $_POST['region_id'],
                'region_name'  => $_POST['region_name'],
                'bh_address'      => $_POST['bh_address'],
                'zipcode'      => $_POST['zipcode'],
                'tel'          => $_POST['tel'],
                'bh_allowed'   => $_POST['bh_allowed'],
                'sort_order'   => $_POST['sort_order'],
                'recommended'  => $_POST['recommended'],
            );
           

            $old_info = $this->_behalf_mod->get_info($id); // 修改前的代发信息
            $this->_behalf_mod->edit($id, $data);           

            /* 如果修改了状态，通知店主 */
            if ($old_info['bh_allowed'] != $data['bh_allowed'])
            {
                $ms =& ms();
                if ($data['bh_allowed'] == STORE_CLOSED)
                {
                    // 关闭
                   // $subject = Lang::get('close_store_notice');
                    //$content = sprintf(Lang::get(), $data['close_reason']);
                    //$content = get_msg('toseller_store_closed_notify',array('reason' => $data['close_reason']));
                }
                else
                {
                    // 开启
                    $subject = Lang::get('open_behalf_notice');
                    $content = Lang::get('toseller_behalf_opened_notify');
                }
                $ms->pm->send(MSG_SYSTEM, $old_info['bh_id'], '', $content);
                $this->_mailto($old_info['email'], $subject, $content);
            }

            $ret_page = isset($_GET['ret_page']) ? intval($_GET['ret_page']) : 1;
            $this->show_message('edit_ok',
                'back_list',    'index.php?app=behalf&page=' . $ret_page,
                'edit_again',   'index.php?app=behalf&amp;act=edit&amp;id=' . $id
            );
        }
    }

    //异步修改数据
   function ajax_col()
   {
       $id     = empty($_GET['id']) ? 0 : intval($_GET['id']);
       $column = empty($_GET['column']) ? '' : trim($_GET['column']);
       $value  = isset($_GET['value']) ? trim($_GET['value']) : '';
       $data   = array();
       if (in_array($column ,array('recommended','sort_order')))
       {
           $data[$column] = $value;
           $this->_behalf_mod->edit($id, $data);
           if(!$this->_behalf_mod->has_error())
           {
               echo ecm_json_encode(true);
           }
       }
       else
       {
           return ;
       }
       return ;
   }

    function drop()
    {
    	// add by tanaiquan 2015-07-12
    	$this->show_warning('ban_to_delete');
    	return ;
    	//
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (!$id)
        {
            $this->show_warning('no_store_to_drop');
            return;
        }

        $ids = explode(',', $id);
        foreach ($ids as $id)
        {
            $this->_drop_behalf_image($id); // 注意这里要先删除图片，再删除代发，因为删除图片时要查代发信息
        }
        if (!$this->_behalf_mod->drop($ids))
        {
            $this->show_warning($this->_behalf_mod->get_error());
            return;
        }

        /* 通知店主 */
        $user_mod =& m('member');
        $users = $user_mod->find(array(
            'conditions' => "user_id" . db_create_in($ids),
            'fields'     => 'user_id, user_name, email',
        ));
        foreach ($users as $user)
        {
            $ms =& ms();
            $subject = Lang::get('drop_store_notice');
            $content = get_msg('toseller_behalf_droped_notify');
            $ms->pm->send(MSG_SYSTEM, $user['user_id'], $subject, $content);
            $this->_mailto($user['email'], $subject, $content);
        }

        $this->show_message('drop_ok');
    }

    /* 更新排序 */
    function update_order()
    {
        if (empty($_GET['id']))
        {
            $this->show_warning('Hacking Attempt');
            return;
        }

        $ids = explode(',', $_GET['id']);
        $sort_orders = explode(',', $_GET['sort_order']);
        foreach ($ids as $key => $id)
        {
            $this->_behalf_mod->edit($id, array('sort_order' => $sort_orders[$key]));
        }

        $this->show_message('update_order_ok');
    }

    /* 查看并处理代发申请 */
    function view()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!IS_POST)
        {
            /* 是否存在 */
            $behalf = $this->_behalf_mod->get_info($id);
            if (!$behalf)
            {
                $this->show_warning('Hacking Attempt');
                return;
            }
            
            $this->show_message('behalf_building',
            		'behalf_manage', 'index.php?app=behalf');
            
           // $this->assign('behalf', $behalf);
           

           // $this->display('behalf.view.html');
        }
       
    }

    function batch_edit()
    {
        if (!IS_POST)
        {            
            $this->display('behalf.batch.html');
        }
        else
        {
            $id = isset($_POST['id']) ? trim($_POST['id']) : '';
            if (!$id)
            {
                $this->show_warning('Hacking Attempt');
                return;
            }

            $ids = explode(',', $id);
            $data = array();
            if($_POST['bh_allowed'] >= 0)
            {
            	$data['bh_allowed'] = $_POST['bh_allowed'];
            }
            
            if (empty($data))
            {
                $this->show_warning('no_change_set');
                return;
            }

            $this->_behalf_mod->edit($ids, $data);
            $ret_page = isset($_GET['ret_page']) ? intval($_GET['ret_page']) : 1;
            
            //发送开通或关闭的消息和邮件
            if($data['bh_allowed'] > 0)
            {
            	$subject = Lang::get('open_behalf_notice');
            	$content = Lang::get('toseller_behalf_opened_notify');
            }
            else
            {            	
            	$subject = Lang::get('close_behalf_notice');
            	$content = Lang::get('toseller_behalf_closed_notify');
            }
            $user_mod =& m('member');
            $users = $user_mod->find(array(
            		'conditions' => "user_id" . db_create_in($ids),
            		'fields'     => 'user_id, user_name, email',
            ));
            foreach ($users as $user)
            {
            	$ms =& ms();
            	$ms->pm->send(MSG_SYSTEM, $user['user_id'], $subject, $content);
                $this->_mailto($user['email'], $subject, $content);
            	
            }
            
            $this->show_message('edit_ok',
            		'back_list', 'index.php?app=behalf&page=' . $ret_page);
           
        }
    }

    function check_name()
    {
        $id         = empty($_GET['id']) ? 0 : intval($_GET['id']);
        $bh_name = empty($_GET['bh_name']) ? '' : trim($_GET['bh_name']);

        if (!$this->_behalf_mod->unique($bh_name, $id))
        {
            echo ecm_json_encode(false);
            return;
        }
        echo ecm_json_encode(true);
    }

    /* 删除代发相关图片 */
    function _drop_behalf_image($bh_id)
    {
        $files = array();

        /* 申请代发时上传的图片 */
        $behalf = $this->_behalf_mod->get_info($bh_id);

        /* 代发设置中的图片 */       
        if ($behalf['bh_logo'])
        {
            $files[] = $behalf['bh_logo'];
        }

        /* 删除 */
        foreach ($files as $file)
        {
            $filename = ROOT_PATH . '/' . $file;
            if (file_exists($filename))
            {
                @unlink($filename);
            }
        }
    }
    
    function behalf_discount()
    {
        $conditions = empty($_GET['wait_verify']) ? "state <> '" . STORE_APPLYING . "'" : "state = '" . STORE_APPLYING . "'";
        $filter = $this->_get_query_conditions(array(
            array(
                'field' => 'store_name',
                'equal' => 'like',
            ),
            array(
                'field' => 'sgrade',
            ),
        ));
        $owner_name = trim($_GET['owner_name']);
        if ($owner_name)
        {
        
            $filter .= " AND (user_name LIKE '%{$owner_name}%' OR owner_name LIKE '%{$owner_name}%') ";
        }
        //更新排序
        if (isset($_GET['sort']) && isset($_GET['order']))
        {
            $sort  = strtolower(trim($_GET['sort']));
            $order = strtolower(trim($_GET['order']));
            if (!in_array($order,array('asc','desc')))
            {
                $sort  = 'sort_order';
                $order = '';
            }
        }
        else
        {
            $sort  = 'store_id';
            $order = 'desc';
        }
        
        $this->assign('filter', $filter);
        $conditions .= $filter;
        $page = $this->_get_page();
        $stores = $this->_store_mod->find(array(
            'conditions' => $conditions.' AND s.state ='.STORE_OPEN,
            'join'  => 'belongs_to_user',
            'fields'=> 'this.*,member.user_name',
            'limit' => $page['limit'],
            'count' => true,
            'order' => "$sort $order"
        ));
        $sgrade_mod =& m('sgrade');
        $grades = $sgrade_mod->get_options();
        $this->assign('sgrades', $grades);
        
        $states = array(
            STORE_APPLYING  => LANG::get('wait_verify'),
            STORE_OPEN      => Lang::get('open'),
            STORE_CLOSED    => Lang::get('close'),
        );
        foreach ($stores as $key => $store)
        {
            $stores[$key]['sgrade'] = $grades[$store['sgrade']];
            $stores[$key]['state'] = $states[$store['state']];
            $certs = empty($store['certification']) ? array() : explode(',', $store['certification']);
            for ($i = 0; $i < count($certs); $i++)
            {
            $certs[$i] = Lang::get($certs[$i]);
            }
            $stores[$key]['certification'] = join('<br />', $certs);
        }
                $this->assign('stores', $stores);
        
            $page['item_count'] = $this->_store_mod->getCount();
            $this->import_resource(array('script' => 'inline_edit.js'));
            $this->_format_page($page);
        $this->assign('filtered', $filter? 1 : 0); //是否有查询条件
            $this->assign('page_info', $page);
        
            $this->display('behalf.store_discount.index.html');
    }
    
    /**
     * 已设置优惠的档口
     */
    function store_discount_view()
    {
        $stores = array();
        $model_store_discount =& m('storediscount');
        $page = $this->_get_page();
        $sql ="select distinct store_id from ".$model_store_discount->table . " limit ".$page['limit'];
        $sql_count = "select count(distinct store_id) as c from ".$model_store_discount->table;
        $store_ids = $model_store_discount->getCol($sql);
        if($store_ids)
        {
            foreach ($store_ids as $id)
            {
                $store = $this->_store_mod->get($id);
                $stores[] = $store;
            }
        }
        $store_counts = $model_store_discount->getRow($sql_count);
        $page['item_count'] = $store_counts['c'];
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('stores',$stores);
        $this->display('behalf.store_discount.setup.html');
    }

}

?>
