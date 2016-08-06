<?php

/**
 * 商品分类挂件
 *
 * @return  array   $category_list
 */
class Zwd_gcategory_listWidget extends BaseWidget
{
    var $_name = 'zwd_gcategory_list';
    var $_ttl  = 86400;


    function _get_data()
    {
        $cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
			$amount = array(
			 "f_amount"	=>	(empty($this->options['f_amount']) || intval($this->options['f_amount'])<=0) ? 0 : intval($this->options['f_amount']),
			 "amount"	=>	(empty($this->options['amount']) || intval($this->options['amount'])<=0) ? 0 : intval($this->options['amount']),
			 "t_amount"	=>	(empty($this->options['t_amount']) || intval($this->options['t_amount'])<=0) ? 0 : intval($this->options['t_amount']),
					
			);
        	//$amount = (empty($this->options['amount']) || intval($this->options['amount'])<=0) ? 0 : intval($this->options['amount']);
			
			import('init.lib');
			$init = new Init_FrontendApp();
	
			/* position: 给弹出层设置高度，使得页面效果美观 */
			$position = array('0px','-39px','-50px','-80px','-100px','-170px','-200px','-100px','-100px','-100px','-100px','-100px','-100px','-100px');
			$data = $init->_get_header_gcategories($amount,$position,1);// 参数说明（二级分类显示数量,弹出层位置,品牌是否为推荐）

			$cache_server->set($key, $data, $this->_ttl);
        }
        return $data;
    }
	function parse_config($input)
    {
        return $input;
    }
    
    

}

?>