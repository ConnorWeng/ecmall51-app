<?php

/* 店铺 store */
class StorediscountModel extends BaseModel
{
    var $table  = 'store_discount';
    var $prikey = 'id';
    var $alias  = 'sdiscount';
    var $_name  = 'storediscount';

    var $_relation = array(
        // 一个优惠只能属于一个店铺
        'belongs_to_store' => array(
            'model'         => 'store',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'store_id',
            'reverse'       => 'has_storediscount',
        ),
    );

    var $_autov = array(
        /* 'owner_name' => array(
            'required'  => true,
            'filter'    => 'trim',
        ), 
         'store_name' => array(
            'required'  => true,
            'filter'    => 'trim',
        ), */
    );
    
    /**
     * 生成代发拿货之后的 分润
     * @param 店铺 $store_id
     * @param 商品体格 $price
     */
    function get_goods_discount($store_id,$price)
    {
        $discount  = 0;//分润
        if($store_id <= 0 || $price <= 0)
        {
            return 0;
        }
        $stores = $this->_get_stores();
        if(empty($stores) || !in_array($store_id, $stores))
        {
            return 0;
        }
        $discount_stores = $this->_get_store($store_id);
        foreach ($discount_stores as $value)
        {
            //判断价格位于哪个区间
            if($price >= $value['first_price'] && $price < $value['end_price'])
            {
                $discount = $value['discount'];
            }
        }
        return $discount/2;
    }
    
    /**
     * 可分润的所有店铺
     */
    private function _get_stores()
    {
        $res = array();
        $sql = "SELECT DISTINCT store_id from ".$this->table;
        $res = $this->getCol($sql);
        return $res;
    }
    
    /**
     * 店铺分润
     * @param 店铺 $store_id
     */
    private function _get_store($store_id)
    {
        $res = array();
        $res = $this->find(array(
            'conditions'=>'store_id='.$store_id
        ));
        return $res;
    }

    
}

?>
