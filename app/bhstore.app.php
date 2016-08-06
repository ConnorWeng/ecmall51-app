<?php
/**
 * 代发商店
 * @author tiq
 *
 */
class BhstoreApp extends MallbaseApp
{
    function index()
    {        
    	    
	    	$_GET['act'] = 'index';
	    	$id = empty($_GET['id']) ? 0 : intval($_GET['id']);
	    	if (!$id)
	    	{
	    		$this->show_warning('Hacking Attempt');
	    		return;
	    	}
	    	
	    	$mod_behalf = & m('behalf');
	    	$behalf = $mod_behalf->get($id);
	    	$markets = $mod_behalf->getRelatedData('has_market',$id);
	    	$deliveries = $mod_behalf->getRelatedData('has_delivery',$id);
	    	
	    	/*获取代发与它签约的用户有哪些*/
	    	
	    		$userArray = array();
	    		$result = $mod_behalf->getRelatedData('be_signed',$behalf['bh_id']);
	    		
	    		if(!empty($result))
	    		{
	    			foreach ($result as $value)
	    			{
	    				$userArray[] = $value["user_id"];
	    			}
	    		}
	    		
	    		$behalf['signed_users'] = $userArray;
	    	
	    		
	    	/*获取代发与它收藏的用户有哪些*/    
	    	
	    		$userArray = array();
	    		$result = $mod_behalf->getRelatedData('be_collect',$behalf['bh_id']);
	    		if(!empty($result))
	    		{
	    			foreach ($result as $value)
	    			{
	    				$userArray[] = $value["user_id"];
	    			}
	    		}
	    		
	    		$behalf['collect_users'] = $userArray;
	    	
	    	
	    	$this->assign('markets',$markets);
	    	$this->assign('deliveries',$deliveries);
	    	$this->assign('behalf',$behalf);
	    	$this->assign("site_url",SITE_URL);
	    	
	    	$this->_config_seo(array(
	    			'title' => Lang::get('bhstore_index') . ' - ' . Conf::get('site_title'),
	    	));
	    	$this->assign('icp_number', Conf::get('icp_number'));
	    	$this->assign('page_description', Conf::get('site_description'));
	    	$this->assign('page_keywords', Conf::get('site_keywords'));
	    	$this->import_resource(array(
	    			'style' => 'res:css/behalf.css',
	    	));
	    	$this->_config_seo(array(
	    			'title' => Lang::get('bhstore_index') . ' - ' . Conf::get('site_title'),
	    	));
	        $this->display('bhstore.index.html');
    	
    }

    function _get_hot_keywords()
    {
        $keywords = explode(',', conf::get('hot_search'));
        return $keywords;
    }
    
    /**
     * 去除2维数组重复的值
     * @param unknown $array
     * @return multitype:unknown
     */
    function get_array_unique($array,$stkeep=false,$ndformat=true)
    {
    	//判断是否保留一级数组键（一级数组键可以为非数字）
    	if($stkeep) $stArr = array_keys($array);
    	//判断是否保留二级数组键(所有二级数组键必须相同)
    	if($ndformat) $ndArr = array_keys(end($array));
    	//降维，也可以用implode，将一维数组转换为用逗号连接的字符串
    	foreach($array as $v)
    	{
    		$v = join(',', $v);
    		$temp[] = $v;
    	}
    	//去掉重复的字符串，也就是重复的一维数组
    	$temp = array_unique($temp);
    	//再将拆开的数组重新组装
    	foreach ($temp as $k=>$v)
    	{
    		if($stkeep) $k = $stArr[$k];
    		if($ndformat)
    		{
    			$tempArr = explode(",", $v);
    			foreach ($tempArr as $ndkey=>$ndval)
    				$output[$k][$ndArr[$ndkey]] = $ndval;
    		}
    		else
    		{
    			$output[$k] = explode(',', $v);
    		}
    	}
    	return $output;
    }
    
    
}

?>