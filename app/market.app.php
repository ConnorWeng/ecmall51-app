<?php

/**
 *    Desc
 *
 *    @author    tiq
 *    @usage    none
 */
class MarketApp extends MallbaseApp
{
	/**
	 * 首页市场导航展示店铺
	 */
    function store()
    {
    	$mkid = isset($_GET['mkid'])?intval($_GET['mkid']):0;
    	if(empty($mkid))
    	{
    		echo ecm_json_encode("非法操作！");
    		return ;
    	}
    	
    	$cache_server = & cache_server();
    	$cache_key = 'market_navigation_by_mkid_'.$mkid;
    	$data = $cache_server->get($cache_key);
    	
    	if($data === false)
    	{
    	    
    	    $market_mod =& m('market');
    	
	        $floors = $market_mod->get_list($mkid);
	    	if(empty($floors)) 
	    	{   //市场下面没有楼层
	    		$no_floors_stores = $market_mod->getRelatedData('has_store',$mkid,array('conditions'=>'s.state='.STORE_OPEN));
	    		if(empty($no_floors_stores))
	    		{
	    			//市场下也没店铺
	    			echo ecm_json_encode(false);
	    		}
	    		else 
	    		{
	    			//按档口地址排序
	    			$dangkou_address_order = array();
	    			if($floors[$key]['stores'])
	    			{
	    				foreach ($no_floors_stores as $value)
	    				{
	    					$dangkou_address_order[] = $value['dangkou_address'];
	    				}
	    				array_multisort($dangkou_address_order,SORT_ASC,$no_floors_stores);
	    			}
	    			//市场下面有店铺，没楼层
	    			$result = "<div id='market_floor_tab'><div class='bd'>"."<div><ul class='clearfix'>";
	    			foreach ($no_floors_stores as $k=>$v)
	    			{
	    				$store_name = $v['store_name'];
	    				$store_recommeded = '';
	    				if($v['serv_addred'])
	    				{
	    					$store_name = "<font color='red'>".$v['store_name']."</font>";
	    				}
	    				if($v['recommended'])
	    				{
	    					$store_recommeded = "<em style='background:#f44;color:white;border-radius:3px;padding:0px 3px;margin-left:5px;'>优</em>";
	    				}
	    				$result .= "<li class='yahei'><span style='color:#2079b3;padding-right:2px;'>["
	    						.$v['dangkou_address']."]</span><a href='shop/"
	    								.$v['store_id']."'>".$store_name."</a>".$store_recommeded."</li>";
	    			}
	    			$result .= "</ul></div>"."</div></div>";
	    			//echo ecm_json_encode($result);
	    		}
	    		
	    	}
	    	else
	    	{
	    		//拼凑显示html
	    		$result = "<div id='market_floor_tab'><ul class='hd clearfix'>";
	    		$floors = array_values($floors);
	    		foreach ($floors as $key=>$floor)
	    		{
	    			if($key == 0)
	    			{
	    				$result .= "<li class='on'><a href='javascript:;'>".$floor['mk_name']."</a></li>";
	    			}
	    			else
	    			{
	    				$result .= "<li><a href='javascript:;'>".$floor['mk_name']."</a></li>";
	    			}    			
	    			$floors[$key]['stores'] = $market_mod->getRelatedData('has_store',$floor['mk_id'],array('conditions'=>'s.state='.STORE_OPEN));
	    			//按档口地址排序
	    			$dangkou_address_order = array();
	    			if($floors[$key]['stores'])
	    			{
	    				foreach ($floors[$key]['stores'] as $value)
	    				{
	    					$dangkou_address_order[] = $value['dangkou_address'];
	    				}
	    				array_multisort($dangkou_address_order,SORT_ASC,$floors[$key]['stores']);
	    			}
	    			
	    		}
	    		$result .= "</ul><div class='bd'>";
	    		foreach ($floors as $key=>$floor)
	    		{
	    			if(empty($floor['stores']))
	    			{
	    				if($key == 0)
	    				{
	    					$result .= "<ul>暂无店铺数据</ul>";
	    				}
	    				else
	    				{
	    					$result .= "<ul class='hidden'>暂无店铺数据</ul>";
	    				}
	    				
	    			}
	    			else    				
	    			{
	    				if($key == 0)
	    				{
	    					$result .= "<ul class='clearfix'>";
	    				}
	    				else
	    				{
	    					$result .= "<ul class='hidden clearfix'>";
	    				}    				
	    				foreach ($floor['stores'] as $k=>$v)
	    				{
	    					$store_name = $v['store_name'];
	    					$store_recommeded = '';
	    					if($v['serv_addred'])
	    					{
	    						$store_name = "<font color='red'>".$v['store_name']."</font>";
	    					}
	    					if($v['recommended'])
	    					{
	    						$store_recommeded = "<em style='background:#f44;color:white;border-radius:3px;padding:0px 3px;margin-left:5px;'>优</em>";
	    					}
	    					$result .= "<li class='yahei'><span style='color:#2079b3;padding-right:2px;'>["
	    							.$v['dangkou_address']."]</span><a href='shop/"
	    							.$v['store_id']."' target='_blank'>".$store_name."</a>".$store_recommeded."</li>";
	    				}
	    				$result .= "</ul>";
	    			}
	    		}    		
	    		
	    		$result .= "</div></div>";
	    		$result .= "<script>$('#market_floor_tab').Tabs();</script>";
	    		
	    		//echo ecm_json_encode($result);
	    	}
	    	
	    	$cache_server->set($cache_key, $result, 3600);
    	}
    	echo ecm_json_encode($data);
    	
        //}
    	/*  $this->assign('id',$floors);
    	$this->display('market.store.html');  */
    }
}

?>
