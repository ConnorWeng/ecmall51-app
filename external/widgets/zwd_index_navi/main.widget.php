<?php

/**
 * 首页分类导航挂件
 *
 * @return  array   $category_list
 */
class Zwd_index_naviWidget extends BaseWidget
{
    var $_name = 'zwd_index_navi';
    var $_ttl  = 86400;


    function _get_data()
    {
        $cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
        	$gcate_num = empty($this->options['gcate_num'])?5:intval($this->options['gcate_num']);
        	$mcate_num = empty($this->options['mcate_num'])?15:intval($this->options['mcate_num']);
			$market_mod = & m('market');
			$gcates_mod = & m('gcategory');
			
			$markets = $market_mod->get_list(1);				
				
			
			$gcategories = $gcates_mod->get_list(0,true);
			foreach ($gcategories as $key=>$value)
			{
				if($value['store_id'] > 0)
				{
					unset($gcategories[$key]);
				}
			}
			$gcategories = array_slice($gcategories,0,$gcate_num);
			foreach ($gcategories as $key=>$value)
			{
				$gcates_children = $gcates_mod->get_list($value['cate_id']);
				$gcates_children = array_slice($gcates_children,0,200);
				$gcategories[$key]['children'] = $gcates_children;
			}
			
			$data['all_markets'] = $markets;
			$data['markets'] = array_slice($markets,0,$mcate_num);
			$data['gcategory'] = $gcategories; 

			$cache_server->set($key, $data, $this->_ttl);
        }
        return $data;
    }
	function parse_config($input)
    {
        return $input;
    }
    
    function _get_market(){
    	$market_mod =& m('market');
    	$markets = $market_mod->get_list(-1,true);
    	
    	import('tree.lib');
    	$tree = new Tree();
    	$tree->setTree($markets, 'mk_id', 'parent_id', 'mk_name');
    	return $tree->getOptions(3);
    }
    

}

?>