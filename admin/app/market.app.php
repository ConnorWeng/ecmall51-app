<?php

define('MAX_LAYER', 3);

/* 市场分类控制器 */
class MarketApp extends BackendApp
{
    var $_market_mod;

    function __construct()
    {
        $this->MarketApp();
    }

    function MarketApp()
    {
        parent::__construct();
        $this->_market_mod =& m('market');
    }

    /* 管理 */
    function index()
    {
        /* 取得市场分类 */
        $markets = $this->_market_mod->get_list();
        $tree =& $this->_tree($markets);

        /* 先根排序 */
        $sorted_markets = array();
        $market_ids = $tree->getChilds();
        foreach ($market_ids as $id)
        {
            $sorted_markets[] = array_merge($markets[$id], array('layer' => $tree->getLayer($id)));
        }
        $this->assign('markets', $sorted_markets);

        /* 构造映射表（每个结点的父结点对应的行，从1开始） */
        $row = array(0 => 0); // cate_id对应的row
        $map = array(); // parent_id对应的row
        foreach ($sorted_markets as $key => $m)
        {
            $row[$m['mk_id']] = $key + 1;
            $map[] = $row[$m['parent_id']];
        }
        $this->assign('map', ecm_json_encode($map));
        //引入jquery表单插件
        $this->import_resource(array(
                                    'script' => 'jqtreetable.js,inline_edit.js',
                                    'style'  => 'res:style/jqtreetable.css'));
        //$this->headtag('<link href="{res file=style/jqtreetable.css}" rel="stylesheet" type="text/css" /><script type="text/javascript" src="{lib file=jqtreetable.js}"></script>');
        $this->display('market.index.html');
    }

    /* 新增 */
    function add()
    {
        if (!IS_POST)
        {
            /* 参数 */
            $pid = empty($_GET['pid']) ? 0 : intval($_GET['pid']);
            $market = array('parent_id' => $pid, 'sort_order' => 255);
            $this->assign('market', $market);
            $this->import_resource(array(
                                        'script' => 'jquery.plugins/jquery.validate.js'));
            $this->assign('parents', $this->_get_options());
            $this->display('market.form.html');
        }
        else
        {
            $data = array(
                'mk_name' => $_POST['mk_name'],
                'parent_id' => $_POST['parent_id'],
                'sort_order' => $_POST['sort_order'],
            );

            /* 检查名称是否已存在 */
            if (!$this->_market_mod->unique(trim($data['mk_name']), $data['parent_id']))
            {
                $this->show_warning('name_exist');
                return;
            }

            /* 保存 */
            $mk_id = $this->_market_mod->add($data);
            if (!$mk_id)
            {
                $this->show_warning($this->_market_mod->get_error());
                return;
            }

            $this->show_message('add_ok',
                'back_list',    'index.php?app=market',
                'continue_add', 'index.php?app=market&amp;act=add&amp;pid=' . $data['parent_id']
                );
        }
    }

    /* 检查店铺分类名称的唯一性 */
    function check_market()
    {
        $mk_name = empty($_GET['mk_name']) ? '' : trim($_GET['mk_name']);
        $parent_id = empty($_GET['parent_id']) ? 0  : intval($_GET['parent_id']);
        $mk_id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$mk_name)
        {
            echo ecm_json_encode(true);
            return ;
        }
        if ($this->_market_mod->unique($mk_name, $parent_id, $mk_id))
        {
            echo ecm_json_encode(true);
        }
        else
        {
            echo ecm_json_encode(false);
        }
        return ;
    }

    /* 编辑 */
    function edit()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!IS_POST)
        {
            /* 是否存在 */
            $market = $this->_market_mod->get_info($id);
            if (!$market)
            {
                $this->show_warning('market_empty');
                return;
            }
            $this->assign('market', $market);
            $this->import_resource(array(
                                        'script' => 'jquery.plugins/jquery.validate.js'));
            $this->assign('parents', $this->_get_options($id));
            $this->display('market.form.html');
        }
        else
        {
            $data = array(
                'mk_name' => $_POST['mk_name'],
                'parent_id' => $_POST['parent_id'],
                'sort_order' => $_POST['sort_order'],
            );

            /* 检查名称是否已存在 */
            if (!$this->_market_mod->unique(trim($data['mk_name']), $data['parent_id'], $id))
            {
                $this->show_warning('name_exist');
                return;
            }

            /* 保存 */
            $rows = $this->_market_mod->edit($id, $data);
            if ($this->_market_mod->has_error())
            {
                $this->show_warning($this->_market_mod->get_error());
                return;
            }

            $this->show_message('edit_ok',
                'back_list',    'index.php?app=market',
                'edit_again',   'index.php?app=market&amp;act=edit&amp;id=' . $id
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

       if (in_array($column ,array('mk_name', 'sort_order')))
       {
           $data[$column] = $value;
           if($column == 'mk_name')
           {
               $market = $this->_market_mod->get_info($id);

               if(!$this->_market_mod->unique($value, $market['parent_id'], $id))
               {
                   echo ecm_json_encode(false);
                   return ;
               }
           }
           $this->_market_mod->edit($id, $data);
           if(!$this->_market_mod->has_error())
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

    /* 删除 */
    function drop()
    {
    	// add by tanaiquan 2015-07-12
    	$this->show_warning('ban_to_delete');
    	return ;
    	//
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (!$id)
        {
            $this->show_warning('no_market_to_drop');
            return;
        }

        $ids = explode(',', $id);
        if (!$this->_market_mod->drop($ids))
        {
            $this->show_warning($this->_market_mod->get_error());
            return;
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
            $this->_market_mod->edit($id, array('sort_order' => $sort_orders[$key]));
        }

        $this->show_message('update_order_ok');
    }

/* 导出数据 */
    function export()
    {
        // 目标编码
        $to_charset = (CHARSET == 'utf-8') ? substr(LANG, 0, 2) == 'sc' ? 'gbk' : 'big5' : '';

        if (!IS_POST)
        {
            if (CHARSET == 'utf-8')
            {
                $this->assign('note_for_export', sprintf(LANG::get('note_for_export'), $to_charset));
                $this->display('common.export.html');

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

        $markets = $this->_market_mod->get_list();
        $tree =& $this->_tree($markets);
        $csv_data = $tree->getCSVData(0, 'sort_order');
        $this->export_to_csv($csv_data, 'market', $to_charset);
    }

    /* 导入数据 */
    function import()
    {
        if (!IS_POST)
        {
            $this->assign('note_for_import', sprintf(LANG::get('note_for_import'), CHARSET));
            $this->display('common.import.html');
        }
        else
        {
            $file = $_FILES['csv'];
            if ($file['error'] != UPLOAD_ERR_OK)
            {
                $this->show_warning('select_file');
                return;
            }
            if ($file['name'] == basename($file['name'],'.csv'))
            {
                $this->show_warning('not_csv_file');
                return;
            }

            $data = $this->import_from_csv($file['tmp_name'], false, $_POST['charset'], CHARSET);
            $parents = array(0 => 0); // 存放layer的parent的数组
            $fileds = array_intersect($data[0],array('mk_name', 'sort_order')); //第一行含有的字段
            $start_col = intval(array_search('mk_name', $fileds)); //主数据区开始列号
            foreach ($data as $row)
            {
                $layer = -1;
                if(array_intersect($row,array('mk_name', 'sort_order')))
                {
                    continue;
                }
                $sort_order_col = array_search('sort_order', $fileds); //从表头得到sort_order的列号
                $sort_order = is_numeric($sort_order_col) && isset($row[$sort_order_col]) ? $row[$sort_order_col] : 255;
                for ($i = $start_col; $i < count($row); $i++)
                {
                    if (trim($row[$i]))
                    {
                        $layer = $i - $start_col;
                        $cate_name  = trim($row[$i]);
                        break;
                    }
                }

                // 没数据或超出级数
                if ($layer < 0 || $layer >= MAX_LAYER)
                {
                    continue;
                }

                // 只处理有上级的
                if (isset($parents[$layer]))
                {
                    $market = $this->_market_mod->get("mk_name = '$cate_name' AND parent_id = '$parents[$layer]'");
                    if (!$market)
                    {
                        // 不存在
                        $id = $this->_market_mod->add(array(
                            'mk_name'     => $cate_name,
                            'parent_id'     => $parents[$layer],
                            'sort_order'    => $sort_order,
                        ));
                        $parents[$layer + 1] = $id;
                    }
                    else
                    {
                        // 已存在
                        $parents[$layer + 1] = $market['mk_id'];
                    }
                }
            }

            $this->show_message('import_ok',
                'back_list', 'index.php?app=market');
        }
    }

    /* 构造并返回树 */
    function &_tree($markets)
    {
        import('tree.lib');
        $tree = new Tree();
        $tree->setTree($markets, 'mk_id', 'parent_id', 'mk_name');
        return $tree;
    }

    /* 取得可以作为上级的店铺分类数据 */
    function _get_options($except = NULL)
    {
        $markets = $this->_market_mod->get_list();
        $tree =& $this->_tree($markets);
        return $tree->getOptions(MAX_LAYER - 1, 0, $except);
    }
}

?>