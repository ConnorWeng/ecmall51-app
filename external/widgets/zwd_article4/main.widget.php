<?php

/**
 * 新版文章
 *
 * @return  array
 */
class Zwd_article4Widget extends BaseWidget
{
    var $_name = 'zwd_article4';
    var $_ttl  = 86400;

    function _get_data()
    {
        $cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
           
			$amount = (!empty($this->options['amount']) && intval($this->options['amount']) >0) ? intval($this->options['amount']) : 3;
			
            $acategory_mod =& m('acategory');
            $article_mod =& m('article');
		

				
				import('init.lib');
				$init = new Init_Ymall_articleWidget();
				$init->options['cate_id'] = $this->options['cate_id1'];
				$conditions1 = $init->_get_data();
				
				$articles1 = $article_mod->find(array(
				   'conditions'=>'code = "" AND if_show=1 AND store_id=0 ' . $conditions1,
				   'fields'=>'article_id, title',
				   'limit'=> $amount,
				   'order'=>'sort_order ASC, article_id DESC'
				));

				$init->options['cate_id'] = $this->options['cate_id2'];
				$conditions2 = $init->_get_data();
				
				$articles2 = $article_mod->find(array(
				   'conditions'=>'code = "" AND if_show=1 AND store_id=0 ' . $conditions2,
				   'fields'=>'article_id, title',
				   'limit'=> $amount,
				   'order'=>'sort_order ASC, article_id DESC'
				));

				$init->options['cate_id'] = $this->options['cate_id3'];
				$conditions3 = $init->_get_data();
				
				$articles3 = $article_mod->find(array(
				   'conditions'=>'code = "" AND if_show=1 AND store_id=0 ' . $conditions3,
				   'fields'=>'article_id, title',
				   'limit'=> $amount,
				   'order'=>'sort_order ASC, article_id DESC'
				));

				$init->options['cate_id'] = $this->options['cate_id4'];
				$conditions4 = $init->_get_data();
				
				$articles4 = $article_mod->find(array(
				   'conditions'=>'code = "" AND if_show=1 AND store_id=0 ' . $conditions4,
				   'fields'=>'article_id, title',
				   'limit'=> $amount,
				   'order'=>'sort_order ASC, article_id DESC'
				));




			$data = array(
			   'cate_name_1'  => $this->options['cate_name_1'],
			   'cate_name_2'  => $this->options['cate_name_2'],
			   'cate_name_3'  => $this->options['cate_name_3'],
			   'cate_name_4'  => $this->options['cate_name_4'],
			   'articles1'   => $articles1,
				'articles2'   => $articles2,
				'articles3'   => $articles3,
				'articles4'   => $articles4,
			);
            $cache_server->set($key, $data, $this->_ttl);
        }

        return $data;
    }
	 function get_config_datasrc()
    {
       // 取得多级文章分类
       $this->assign('acategories', $this->_get_acategory_options(2));
    }

	function _get_acategory_options($layer = 0)
	{
		$acategory_mod =& m('acategory');
        $acategories = $acategory_mod->get_list();
		foreach($acategories as $key=>$val)
		{
			if($val['code'] == ACC_SYSTEM){
				unset($acategories[$key]);
			}
		}

        import('tree.lib');
        $tree = new Tree();
        $tree->setTree($acategories, 'cate_id', 'parent_id', 'cate_name');

        return $tree->getOptions($layer);
	}
}
?>