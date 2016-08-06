<?php

/**
 * 金牌商家
 *
 * @return  array
 */
class Zwd_goldshopWidget extends BaseWidget
{
    var $_name = 'zwd_goldshop';
    var $_ttl  = 86400;

    function _get_data()
    {
        $cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {  
            $data = array(
            		'cate_name'  => $this->options['cate_name'],
            		'goods_list'   => $this->options['goods_list'],            		
            );
           
            $cache_server->set($key, $data, $this->_ttl);
        }
        return $data;
    }
    
    function parse_config($input)
    {
    	$goods_list = array();
    	$return_params = array();
    	$recom_mod =& m('recommend');
    	$num    = isset($input['cate_name']) ? count($input['cate_name']) : 0;
    	if($num > 0)
    	{
    		for($i=0;$i<$num;$i++)
    		{
    			if(!empty($input['img_recom_id'][$i]))
    			{
    				$goods_list[$i] = $recom_mod->get_recommended_goods($input['img_recom_id'][$i],5, true, $input['img_cate_id'][$i]);
    				$return_params[$i]['cate_name'] = $input['cate_name'][$i];   
    				$return_params[$i]['img_recom_id'] = $input['img_recom_id'][$i];
    				$return_params[$i]['img_cate_id'] = $input['img_cate_id'][$i];
    			}
    		}
    	}    	
    	$input['goods_list'] = $goods_list;
    	$input['return_params'] = $return_params;
    	return $input;
    }
    
	 function get_config_datasrc()
    {
        // 取得推荐类型
        $this->assign('recommends', $this->_get_recommends());

        // 取得一级商品分类
        $this->assign('gcategories', $this->_get_gcategory_options(1));
    }
}
?>