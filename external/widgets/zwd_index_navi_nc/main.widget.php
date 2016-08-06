<?php

/**
 * 首页分类导航挂件
 *
 * @return  array   $category_list
 */
class Zwd_index_navi_ncWidget extends BaseWidget
{
    var $_name = 'zwd_index_navi_nc';
    var $_ttl  = 86400;


    function _get_data()
    {
        $cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
        	$gcate_num = empty($this->options['gcate_num'])?5:intval($this->options['gcate_num']);
        	$city = empty($this->options['market_name'])?'南城':trim($this->options['market_name']);
			$market_mod = & m('market');
			$gcates_mod = & m('gcategory');
			if(defined('OEM'))
			{				
				$markets = $market_mod->find("market.mk_name like '%".$city."%'");
				if(count($markets) == 1)
				{
					$markets = array_values($markets);
					$markets = $market_mod->get_list($markets[0]['mk_id']);
					if(!empty($markets))
					{
						foreach ($markets as $key=>$value)
						{ 
							$stores =  $market_mod->getRelatedData('has_store',$value['mk_id']);
							$stores = array_slice($stores,0,100);
							$markets[$key]['stores'] = $stores;
						}
					}
				}
				
			  
			}
			
			$gcategories = $gcates_mod->get_list(0);
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
			
			$data['markets'] = $markets;
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