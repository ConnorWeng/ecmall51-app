<?php

/**
 * 销售排行榜
 *
 * @return  array
 */
class Zwd_kefuWidget extends BaseWidget
{
    var $_name = 'zwd_kefu';
    var $_ttl  = 86400;

    function _get_data()
    {
        $cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
            
			$amount = (!empty($this->options['amount']) && intval($this->options['amount']) >0) ? intval($this->options['amount']) : 3;
			

			$data = array(
			   'telphone'  => $this->options['telphone'],
			   'qq'  => $this->options['qq'],
			);
            $cache_server->set($key, $data, $this->_ttl);
        }

        return $data;
    }
	 function get_config_datasrc()
    {
       // 取得多级文章分类
       //$this->assign('acategories', $this->_get_acategory_options(2));
    }

	
}
?>