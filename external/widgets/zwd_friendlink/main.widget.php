<?php

/**
 * 友情链接
 *
 * @return  array
 */
class Zwd_friendlinkWidget extends BaseWidget
{
    var $_name = 'zwd_friendlink';
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
			  
			);
            $cache_server->set($key, $data, $this->_ttl);
        }

        return $data;
    }
	 

	
}
?>