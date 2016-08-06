<?php

/* 商品规格 goodsspec */
class GoodsspecModel extends BaseModel
{
    var $table  = 'goods_spec';
    var $prikey = 'spec_id';
    var $alias  = 'gs';
    var $_name  = 'goodsspec';

    var $_relation  = array(
        // 一个商品规格只能属于一个商品
        'belongs_to_goods' => array(
            'model'         => 'goods',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'goods_id',
            'reverse'       => 'has_goodsspec',
        ),
        'has_cart_items' => array(
            'model'         => 'cart',
            'type'          => HAS_MANY,
            'foreign_key'   => 'spec_id',
        ),
    );

    function get_spec_list($goodsId) {
        $sql = "select g.goods_id,g.goods_name,g.default_image,gs.spec_id,gs.price,gs.spec_1,gs.spec_2,ga.attr_value from ecm_goods_spec gs left join ecm_goods g ON gs.goods_id = g.goods_id left join ecm_goods_attr ga on ga.goods_id = g.goods_id and ga.attr_id = 1 where gs.goods_id={$goodsId}";
        return $this->db->getAll($sql);
    }
}

?>