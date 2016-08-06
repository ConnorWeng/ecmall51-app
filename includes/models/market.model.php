<?php

/* 市场类 market */
class MarketModel extends BaseModel
{
    var $table  = 'market';
    var $prikey = 'mk_id';
    var $_name  = 'market';
    var $_relation  =   array(
        // 一个分类有多个子分类
        'has_market' => array(
            'model'         => 'market',
            'type'          => HAS_MANY,
            'foreign_key'   => 'parent_id',
            'dependent'     => true
        ),
    	//一个市场有多个店铺
    	'has_store' => array(
    			'model'             => 'store',
    			'type'              => HAS_MANY,
    			'foreign_key'       => 'mk_id',
    			'dependent'         => true
    	),
    	// 市场和代发是多对多的关系
    	'belongs_to_behalf' => array(
    			'model'         => 'behalf',
    			'type'          => HAS_AND_BELONGS_TO_MANY,
    			'middle_table'  => 'market_behalf',
    			'foreign_key'   => 'mk_id',
    			'reverse'       => 'has_market',
    	),
    );

    var $_autov = array(
        'mk_name' => array(
            'required'  => true,
            'filter'    => 'trim',
        ),
        'parent_id'  => array(
        ),
        'sort_order'    => array(
            'filter'    => 'intval',
        ),
    );

    /**
     * 取得分类列表
     *
     * @param int $parent_id 大于等于0表示取某个分类的下级店铺分类，小于0表示取所有分类
     * @return array
     */
    function get_list($parent_id = -1)
    {       
        $conditions = "1 = 1";
        $parent_id >= 0 && $conditions .= " AND parent_id = '$parent_id'";
        
        return $this->find(array(
        		'conditions' => $conditions,
        		'order' => 'sort_order, mk_id',
        ));
    }
    
    function get_sm_list($parent_id = -1)
    {
    	$conditions = "1 = 1";
    	$parent_id >= 0 && $conditions .= " AND parent_id = '$parent_id'";
    	$conditions .= " AND sort_order > 0";
    
    	return $this->find(array(
    			'conditions' => $conditions,
    			'order' => 'sort_order, mk_id',
    	));
    }
    
    function get_options($parent_id = -1, $shown = false)
    {
    	$options = array();
    	$rows = $this->get_list($parent_id, $shown);
    	foreach ($rows as $row)
    	{
    		$options[$row['mk_id']] = $row['mk_name'];
    	}
    
    	return $options;
    }

    /*
     * 判断名称是否唯一
     */
    function unique($mk_name, $parent_id, $mk_id = 0)
    {
        $conditions = "parent_id = '" . $parent_id . "' AND mk_name = '" . $mk_name . "'";
        $mk_id && $conditions .= " AND mk_id <> '" . $mk_id . "'";
        return count($this->find(array('conditions' => $conditions))) == 0;
    }

    /**
     * 把某分类及其上级分类加到数组前
     */
    function get_parents(&$parents, $id)
    {
        $data = $this->get(intval($id));
        array_unshift($parents, array('mk_id' => $data['mk_id'], 'mk_name' => $data['mk_name']));
        if ($data['parent_id'] > 0)
        {
            $this->get_parents($parents, $data['parent_id']);
        }
    }

     /**
     * 取得某分类的所有子孙分类id
     */
    function get_descendant($id)
    {
        $ids = array($id);
        $this->_get_descendant($ids, $id);
        return $ids;
    }
    function _get_descendant(&$ids, $id)
    {
        $childs = $this->find("parent_id = '$id'");
        foreach ($childs as $child)
        {
            $ids[] = $child['mk_id'];
            $this->_get_descendant($ids, $child['mk_id']);
        }
    }
    
    /**
     * 取得某分类的祖先分类（包括自身，按层级排序）
     *
     * @param   int     $id     分类id
     * @param   bool    $cached 是否缓存（缓存数据不包括不显示的分类，一般用于前台；非缓存数据包括不显示的分类，一般用于后台）
     * @return  array(
     *              array('cate_id' => 1, 'cate_name' => '数码产品'),
     *              array('cate_id' => 2, 'cate_name' => '手机'),
     *              ...
     *          )
     */
    function get_ancestor($id, $cached = false)
    {
    	$res = array();
    
    	if ($cached)
    	{
    		$data = $this->_get_structured_data();
    		if ($id > 0 && isset($data[$id]))
    		{
    			while ($id > 0)
    			{
    				$res[] = array('mk_id' => $id, 'mk_name' => $data[$id]['name']);
    				$id    = $data[$id]['pid'];
    			}
    		}
    	}
    	else
    	{
    		while ($id > 0)
    		{
    			$sql = "SELECT mk_id, mk_name, parent_id FROM {$this->table} WHERE mk_id = '$id'";
    			$row = $this->getRow($sql);
    			if ($row)
    			{
    				$res[] = array('mk_id' => $row['mk_id'], 'mk_name' => $row['mk_name']);
    				$id    = $row['parent_id'];
    			}
    		}
    	}
    
    	return array_reverse($res);
    }
    
    /**
     * 取得某分类的层级（从1开始算起）
     *
     * @param   int     $id     分类id
     * @param   bool    $cached 是否缓存（缓存数据不包括不显示的分类，一般用于前台；非缓存数据包括不显示的分类，一般用于后台）
     * @return  int     层级     当分类不存在或不显示时返回false
     */
    function get_layer($id, $cached = false)
    {
    	$ancestor = $this->get_ancestor($id, $cached);
    	if (empty($ancestor))
    	{
    		return false; //分类不存在或不显示
    	}
    	else
    	{
    		return count($ancestor);
    	}
    }
    
    /**
     * 取得结构化的分类数据（不包括不显示的分类，数据会缓存）
     *
     * @return array(
     *      0 => array(                             'cids' => array(1, 2, 3))
     *      1 => array('name' => 'abc', 'pid' => 0, 'cids' => array(2, 3, 4)),
     *      2 => array('name) => 'xyz', 'pid' => 1, 'cids' => array()
     * )
     *    分类id        分类名称             父分类id     子分类ids
     */
    function _get_structured_data()
    {
    	  $data = array(0 => array('mids' => array()));
    
    		$marktet_list = $this->get_list(-1);
    		foreach ($marktet_list as $id => $cate)
    		{
    			$data[$id] = array(
    					'name' => $cate['mk_name'],
    					'pid'  => $cate['parent_id'],
    					'mids' => array()
    			);
    		}
    
    		foreach ($marktet_list as $id => $cate)
    		{
    			$pid = $cate['parent_id'];
    			isset($data[$pid]) && $data[$pid]['mids'][] = $id;
    		}
    
    
    	  return $data;
    }
    
   
}

?>