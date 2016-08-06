<?php

/**
 * 新版搜款页推荐宝贝挂件
 *
 * @return  array  
 */
class Zwd_search_recommendWidget extends BaseWidget
{
    var $_name = 'zwd_search_recommend';
	var $_ttl  = 1;
	
    function _get_data()
    {
		$cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
			$recom_mod =& m('recommend');
			$goods_list= $recom_mod->get_recommended_goods($this->options['img_recom_id'], 
					isset($this->options['goods_total'])?intval($this->options['goods_total']):30, true, $this->options['img_cate_id']);
			/*当用户登录时，如果商品为已收藏，则标记为已收藏*/
			if($_SESSION['user_info']['user_id'])
			{
				$goods_list = $this->_add_collect_flag_goods_list($goods_list);
			}
			$data = array(
				'model_id'			=> mt_rand(),
				'model_name'	 	=> $this->options['model_name'],
				'goods_list'	 	=> $goods_list,	
				'sticky' => $this->options['sticky'],
			);
        	$cache_server->set($key, $data,$this->_ttl);
        }
        return $data;
    }

    function parse_config($input)
    {
        if ($input['img_recom_id'] >= 0)
        {
            $input['img_cate_id'] = 0;
        }
		if(empty($input['goods_total']))
		{
			$input['goods_total'] = 30;
		}
		
        return $input;
    }
	
	
	function get_config_datasrc()
    {
         // 取得推荐类型
        $this->assign('recommends', $this->_get_recommends());

        // 取得一级商品分类
        $this->assign('gcategories', $this->_get_gcategory_options(2));
		
    }
    
    /**
     *  当用户登录了，标记其已收藏的商品
     * @param unknown $goods_list
     */
    function _add_collect_flag_goods_list($goods_list)
    {
    	$mod_member =& m('member');
    	$collect_goods = $mod_member->getRelatedData('collect_goods',$_SESSION['user_info']['user_id']);
    	$collect_goods_ids = array();
    	foreach ($collect_goods as $goods)
    	{
    		$collect_goods_ids[] = $goods['goods_id'];
    	}
    	foreach ($goods_list as $goods_id=>$goods)
    	{
    		if(in_array($goods['goods_id'],$collect_goods_ids))
    		{
    			//加入collect_goods标记
    			$goods_list[$goods_id]['collect_goods'] = 1;
    		}
    	}
    	return $goods_list;
    }
}

?>