<?php

/**
 *    快递管理控制器
 *
 *    @author    tiq
 *    @usage    none
 */
class DeliveryApp extends BackendApp
{
    var $_delivery_mod;

    function __construct()
    {
        $this->DeliveryApp();
    }

    function DeliveryApp()
    {
        parent::BackendApp();

        $this->_delivery_mod =& m('delivery');
    }

    /**
     *    快递索引
     *
     *    @author    tiq
     *    @return    void
     */
    function index()
    {
        $conditions = $this->_get_query_conditions(array(array(
                'field' => 'dl_name',
                'equal' => 'LIKE',
                'assoc' => 'AND',
                'name'  => 'dl_name',
                'type'  => 'string',
            ),
           /*  array(
                'field' => 'tag',
                'equal' => 'LIKE',
                'assoc' => 'AND',
                'name' => 'tag',
                'type' => 'string',
            ), */
        ));
        $page   =   $this->_get_page(10);   //获取分页信息
        //更新排序
        if (isset($_GET['sort']) && isset($_GET['order']))
        {
            $sort  = strtolower(trim($_GET['sort']));
            $order = strtolower(trim($_GET['order']));
            if (!in_array($order,array('asc','desc')))
            {
             $sort  = 'dl_id';
             $order = 'desc';
            }
        }
        else
        {
            $sort  = 'dl_id';
            $order = 'desc';
        }
        $verify =  empty($_GET['wait_verify']) ? ' AND if_show = 1' : ' AND if_show = 0';
        $deliveries=$this->_delivery_mod->find(array(
        'conditions'    => '1=1' . $conditions. $verify,
        'limit'         => $page['limit'],
        'order'         => "$sort $order",
        'count'         => true
        ));
       /*  foreach ($brands as $key => $brand)
        {
            $brand['brand_logo']&&$brands[$key]['brand_logo'] = dirname(site_url()) . '/' . $brand['brand_logo'];
        } */
        $page['item_count']=$this->_delivery_mod->getCount();   //获取统计数据
        /* 导入jQuery的表单验证插件 */
        $this->import_resource(array(
            'script' => 'jqtreetable.js,inline_edit.js',
            'style'  => 'res:style/jqtreetable.css'
        ));
        $this->_format_page($page);
        $this->assign('filtered', $conditions? 1 : 0); //是否有查询条件
        //$this->assign('wait_verify', $_GET['wait_verify']);
        $this->assign('page_info', $page);   //将分页信息传递给视图，用于形成分页条
        $this->assign('deliveries', $deliveries);
        $this->display('delivery.index.html');
    }
     /**
     *    新增快递
     *
     *    @author    tiq
     *    @return    void
     */
    function add()
    {
        if (!IS_POST)
        {
            /* 显示新增表单 */
            $delivery = array(
                'sort_order' => 255,
                'recommended' => 0,
            );
            $yes_or_no = array(
                1 => Lang::get('yes'),
                0 => Lang::get('no'),
            );
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js,mlselection.js'
            ));
            $region_mod =& m('region');
            $this->assign('regions', $region_mod->get_options(0));
            $this->assign('yes_or_no', $yes_or_no);
            $this->assign('delivery', $delivery);
            $this->display('delivery.form.html');
        }
        else
        {
            $data = array();
            $data['dl_name']     = $_POST['dl_name'];
            $data['sort_order']     = $_POST['sort_order'];
            $data['recommended']    = $_POST['recommended'];
            $data['region_id']    = $_POST['region_id'];
            $data['region_name']    = $_POST['region_name'];
            $data['address']    = $_POST['address'];
            $data['dl_desc']    = $_POST['dl_desc'];
            //$data['tag'] = $_POST['tag'];
            $data['if_show'] = 1;

            /* 检查名称是否已存在 */
            if (!$this->_delivery_mod->unique(trim($data['dl_name'])))
            {
                $this->show_warning('name_exist');
                return;
            }
            if (!$dl_id = $this->_delivery_mod->add($data))  //获取brand_id
            {
                $this->show_warning($this->_delivery_mod->get_error());

                return;
            }

            /* 处理上传的图片 */
           /*  $logo       =   $this->_upload_logo($brand_id);
            if ($logo === false)
            {
                return;
            }
            $logo && $this->_brand_mod->edit($brand_id, array('brand_logo' => $logo));  *///将logo地址记下

            $this->show_message('add_delivery_successed',
                'back_list',    'index.php?app=delivery',
                'continue_add', 'index.php?app=delivery&amp;act=add'
            );
        }
    }

    /* 检查快递名称唯一 */
    function check_delivery ()
    {
        $dl_name = empty($_GET['dl_name']) ? '' : trim($_GET['dl_name']);
        $dl_id   = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$dl_name) {
            echo ecm_json_encode(false);
        }
        if ($this->_delivery_mod->unique($dl_name, $dl_id)) {
            echo ecm_json_encode(true);
        }
        else
        {
            echo ecm_json_encode(false);
        }
        return ;
    }

     /**
     *    编辑商品品牌
     *
     *    @author    Hyber
     *    @return    void
     */
    function edit()
    {
        $dl_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$dl_id)
        {
            $this->show_warning('no_such_delivery');
            return;
        }
         if (!IS_POST)
        {
            $find_data     = $this->_delivery_mod->find($dl_id);
            if (empty($find_data))
            {
                $this->show_warning('no_such_delivery');

                return;
            }
            $delivery    =   current($find_data);
           /*  if ($brand['brand_logo'])
            {
                $brand['brand_logo']  =   dirname(site_url()) . "/" . $brand['brand_logo'];
            } */
            /* 显示新增表单 */
            $yes_or_no = array(
                1 => Lang::get('yes'),
                0 => Lang::get('no'),
            );
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js'
            ));
            $this->assign('yes_or_no', $yes_or_no);
            $this->assign('delivery', $delivery);
            $this->display('delivery.form.html');
        }
        else
        {
            $data = array();
            $data['dl_name']     =   $_POST['dl_name'];
            $data['sort_order']     =   $_POST['sort_order'];
            $data['recommended']    =   $_POST['recommended'];
            $data['dl_desc'] = $_POST['dl_desc'];
           
             /* 检查名称是否已存在 */            
            $rows=$this->_delivery_mod->edit($dl_id, $data);
            if ($this->_delivery_mod->has_error())
            {
                $this->show_warning($this->_delivery_mod->get_error());

                return;
            }

            $this->show_message('edit_delivery_successed',
                'back_list',        'index.php?app=delivery',
                'edit_again',    'index.php?app=delivery&amp;act=edit&amp;id=' . $dl_id);
        }
    }

         //异步修改数据
   function ajax_col()
   {
       $id     = empty($_GET['id']) ? 0 : intval($_GET['id']);
       $column = empty($_GET['column']) ? '' : trim($_GET['column']);
       $value  = isset($_GET['value']) ? trim($_GET['value']) : '';
       $data   = array();

       if (in_array($column ,array('dl_name', 'recommended', 'sort_order')))
       {
           $data[$column] = $value;
           if($column == 'dl_name')
           {
               $delivery = $this->_delivery_mod->get_info($id);

               if(!$this->_delivery_mod->unique($value, $id))
               {
                   echo ecm_json_encode(false);
                   return ;
               }
           }
           $this->_delivery_mod->edit($id, $data);
           if(!$this->_delivery_mod->has_error())
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
        $brand_ids = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (!$brand_ids)
        {
            $this->show_warning('no_such_brand');

            return;
        }
        $brand_ids=explode(',',$brand_ids);
        $this->_brand_mod->drop($brand_ids);
        if ($this->_brand_mod->has_error())    //删除
        {
            $this->show_warning($this->_brand_mod->get_error());

            return;
        }

        $this->show_message('drop_brand_successed');
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
            $this->_delivery_mod->edit($id, array('sort_order' => $sort_orders[$key]));
        }

        $this->show_message('update_order_ok');
    }

        /**
     *    处理上传标志
     *
     *    @author    Hyber
     *    @param     int $brand_id
     *    @return    string
     */
    function _upload_logo($brand_id)
    {
        $file = $_FILES['logo'];
        if ($file['error'] == UPLOAD_ERR_NO_FILE) // 没有文件被上传
        {
            return '';
        }
        import('uploader.lib');             //导入上传类
        $uploader = new Uploader();
        $uploader->allowed_type(IMAGE_FILE_TYPE); //限制文件类型
        $uploader->addFile($_FILES['logo']);//上传logo
        if (!$uploader->file_info())
        {
            $this->show_warning($uploader->get_error() , 'go_back', 'index.php?app=brand&amp;act=edit&amp;id=' . $brand_id);
            return false;
        }
        /* 指定保存位置的根目录 */
        $uploader->root_dir(ROOT_PATH);

        /* 上传 */
        if ($file_path = $uploader->save('data/files/mall/brand', $brand_id))   //保存到指定目录，并以指定文件名$brand_id存储
        {
            return $file_path;
        }
        else
        {
            return false;
        }
    }

    /**
     * 更新字段
     *
     */
/*     function update()
    {
        $allow_cols=array(
        'recommended',  //允许更新的字段
        );
        $col    =   trim($_GET['col']);
        $value  =   trim($_GET['value']);
        if (!in_array($col, $allow_cols))
        {
            $this->show_warning('Hacking attempt');
            return;
        }
        $brand_ids = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (!$brand_ids)
        {
            $this->show_warning('no_such_brand');

            return;
        }
        $brand_ids=explode(',',$brand_ids);
        $data = array();
        $data[$col] = $value;

        $rows=$this->_brand_mod->edit($brand_ids, $data);
        if ($this->_brand_mod->has_error())
        {
            $this->show_warning($this->_brand_mod->get_error());

            return;
        }
        $this->show_message('update_' . $col . '_successed');
    } */

    function pass()
    {
        $id = $_GET['id'];
        if (empty($id))
        {
            $this->show_warning('request_error');
            exit;
        }
        $ids = explode(',', $id);
        $deliveries = $this->_delivery_mod->find(db_create_in($ids, 'dl_id') . " AND if_show = 0");
        $this->_delivery_mod->edit(db_create_in(array_keys($deliveries), 'dl_id'), array('if_show' => 1));
        if ($this->_delivery_mod->has_error())
        {
            $this->show_warning($this->_delivery_mod->get_error());
            exit;
        }
        /* $ms =& ms();
        $content = '';
        foreach ($brands as $brand)
        {
            $content = get_msg('toseller_delivery_passed_notify', array('dl_name' => $brand['dl_name']));
            $ms->pm->send(MSG_SYSTEM, $brand['store_id'], '', $content);
        } */
        $this->show_message('delivery_passed',
            'back_list', 'index.php?app=delivery&wait_verify=1');
    }

    function refuse()
    {
        $id = $_GET['id'];
        if (empty($id))
        {
            $this->show_warning('request_error');
            exit;
        }
        if (!IS_POST)
        {
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js'
            ));
            $this->display('delivery_refuse.html');
        }
        else
        {
            if (empty($_POST['content']))
            {
                $this->show_warning('content_required');
                exit;
            }
            $ids = explode(',', trim($_GET['id']));
            $deliveries = $this->_delivery_mod->find(db_create_in($ids, 'dl_id') . ' AND if_show = 0');
            /* $ms =& ms();
            $content = '';
            foreach ($brands as $brand)
            {
                $content = get_msg('toseller_brand_refused_notify', array('brand_name' => $brand['brand_name'], 'reason' => trim($_POST['content'])));
                $ms->pm->send(MSG_SYSTEM, $brand['store_id'], '', $content);
                if (is_file(ROOT_PATH . '/' . $brand['brand_logo']) && file_exists(ROOT_PATH . '/' . $brand['brand_logo']))
                {
                    unlink(ROOT_PATH . '/' . $brand['brand_logo']);
                }
                $this->_brand_mod->drop($brand['brand_id']);
            } */
            $this->show_message('delivery_refused',
                    'back_list', 'index.php?app=delivery&wait_verify=1');
        }
    }


}

?>