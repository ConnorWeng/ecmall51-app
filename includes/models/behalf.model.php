<?php

/* 代发 behalf */
class BehalfModel extends BaseModel
{
    var $table  = 'behalf';
    var $prikey = 'bh_id';
    var $_name  = 'behalf';

    var $_relation = array(
            // 一个代发属于一个会员
            'belongs_to_user' => array(
                    'model'         => 'member',
                    'type'          => BELONGS_TO,
                    'foreign_key'   => 'bh_id',
                    'reverse'       => 'has_behalf',
            ),
            // 代发和市场是多对多的关系
            'has_market' => array(
                    'model'         => 'market',
                    'type'          => HAS_AND_BELONGS_TO_MANY,
                    'middle_table'  => 'market_behalf',
                    'foreign_key'   => 'bh_id',
                    'reverse'       => 'belongs_to_behalf',
            ),
            // 代发和快递是多对多的关系
            'has_delivery' => array(
                    'model'         => 'delivery',
                    'type'          => HAS_AND_BELONGS_TO_MANY,
                    'middle_table'  => 'behalf_delivery',
                    'foreign_key'   => 'bh_id',
                    'reverse'       => 'belongs_to_behalf',
            ),
            // 代发和会员是多对多的关系（会员收藏代发）
            'be_collect' => array(
                    'model'         => 'member',
                    'type'          => HAS_AND_BELONGS_TO_MANY,
                    'middle_table'  => 'collect',
                    'foreign_key'   => 'item_id',
                    'ext_limit'     => array('type' => 'behalf'),
                    'reverse'       => 'collect_behalf',
            ),
            // 代发和会员是多对多的关系（会员签约代发）
            'be_signed' => array(
                    'model'         => 'member',
                    'type'          => HAS_AND_BELONGS_TO_MANY,
                    'middle_table'  => 'collect',
                    'foreign_key'   => 'item_id',
                    'ext_limit'     => array('type' => 'sbehalf'),
                    'reverse'       => 'collect_sbehalf',
            ),
            //一个代发有多个拿货员
            'has_membertaker'=>array(
                    'model'         => 'member',
                    'type'          => HAS_MANY,
                    'foreign_key'   => 'behalf_goods_taker',
                    'dependent' => true
            ),
            //一个代发有多个拿货市场
            'has_markettakers'=>array(
                    'model'         => 'markettaker',
                    'type'          => HAS_MANY,
                    'foreign_key'   => 'bh_id',
                    'dependent' => true
            ),
            //一个代发有多个拿货单
            'has_goodstakerinventory'=>array(
                    'model'         => 'goodstakerinventory',
                    'type'          => HAS_MANY,
                    'foreign_key'   => 'bh_id',
                    'dependent' => true
            ),
            // 代发黑名单和店铺是多对多的关系
            'has_blacklist_stores' => array(
                'model'         => 'store',
                'type'          => HAS_AND_BELONGS_TO_MANY,
                'middle_table'  => 'behalf_store_blacklist',
                'foreign_key'   => 'bh_id',
                'reverse'       => 'belongs_to_behalf_blacklist',
            ),
            // 代发和分类是多对多的关系
            /* 'belongs_to_gcategory' => array(
                    'model'         => 'gcategory',
                    'type'          => HAS_AND_BELONGS_TO_MANY,
                    'middle_table'  => 'category_behalf',
                    'foreign_key'   => 'bh_id',
                    'reverse'       => 'has_behalf',
            ), */

    );

    /*
     * 判断名称是否唯一
    */
    function unique($bh_name, $bh_id = 0)
    {
        $conditions = "bh_name = '" . $bh_name . "'";
        $bh_id && $conditions .= " AND bh_id <> '" . $bh_id . "'";
        return count($this->find(array('conditions' => $conditions))) == 0;
    }

    /**
     * 代发待发货订单数是否小于设置值
     * @param 代发 $bh_id
     */
    function usable_behalf_by_max_orders($bh_id)
    {
        if(!$bh_id) return false;
        $behalf = $this->get($bh_id);
        if(!$behalf) return false;
        if($behalf['max_orders'] == 0) return true;
        $model_order =& m('order');
        $count = $model_order->getOne("SELECT count(*) from ".$model_order->table." WHERE bh_id=".$bh_id." AND status=".ORDER_ACCEPTED);
        if($count >= $behalf['max_orders']) return false;
        return true;

    }

    /**
     *
     * @param 代发id  $bh_id
     * @param 快递id  $dl_id
     * @param 订单商品总数  $goods_quantity
     * @return 返回  订单 中  某个快递费
     */
    function calculate_behalf_delivery_fee($bh_id,$dl_id,$goods_quantity)
    {
        $shipping_fee = 0;
        //$behalf_mod =& m('behalf');
        if(!$bh_id || !$dl_id ||!$goods_quantity)
        {
            return 0;
        }
        $deliveries = $this->getRelatedData('has_delivery',$bh_id,array(
                'order'=>'sort_order'
        ));
        foreach($deliveries as $delivery)
        {

            if(intval($delivery['dl_id']) == intval($dl_id))
            {
                if($goods_quantity <= $delivery['first_amount'])
                {
                    $shipping_fee = $delivery['first_price'];
                }
                else
                {
                    if($delivery['step_amount'] > 0)
                    {
                        $shipping_fee =$delivery['first_price'] + (ceil(($goods_quantity - $delivery['first_amount'])/$delivery['step_amount']))*$delivery['step_price'];
                    }
                    else
                    {
                        $shipping_fee = $delivery['first_price'];
                    }
                }
            }
        }
        return $shipping_fee;
    }

    /**
     *
     * @param 代发 $bh_id
     * @param 订单商品数量 $goods_quantity
     * @return 返回  订单中代发  所有快递费
     */
    function calculate_delivery_fee_bybehalf($bh_id,$goods_quantity)
    {
        //$behalf_mod =& m('behalf');
        if(!$bh_id || !$goods_quantity)
        {
            return 0;
        }
        $deliveries = $this->getRelatedData('has_delivery',$bh_id,array(
                'order'=>'sort_order'
        ));
        if(empty($deliveries))
        {
            return 0;
        }
        foreach($deliveries as $key=>$delivery)
        {


                if($goods_quantity <= $delivery['first_amount'])
                {
                    $deliveries[$key]['dl_fee'] = $delivery['first_price'];
                }
                else
                {
                    if($delivery['step_amount'] > 0)
                    {
                        $deliveries[$key]['dl_fee'] =$delivery['first_price'] + (ceil(($goods_quantity - $delivery['first_amount'])/$delivery['step_amount']))*$delivery['step_price'];
                    }
                    else
                    {
                        $deliveries[$key]['dl_fee'] = $delivery['first_price'];
                    }
                }

        }
        return $deliveries;
    }

    /**
     * 获取代发和代发可用的快递，(不用来计算快递费用)
     * $store_id = 0,获取所有市场的代发和本代发可发的快递
     * $store_id > 0,获取店铺所在市场 的代发和本代发可发的快递
     * @param number $store_id
     */
    function get_behalfs_deliverys($store_id=0)
    {
        $mk_id = 0;//市场id
        if($store_id > 0)
        {
            $mod_store = & m('store');
            $store = $mod_store->get($store_id);
            $store && $mk_id = $store['mk_id'];
        }

        $mod_market = & m('market');
        if($mk_id > 0)
        {
            //店铺所在市场的代发 及 相应可发的快递
            $market = $mod_market->get($mk_id);
            $market_layer = $mod_market->get_layer($mk_id);
            if($market_layer == 3)
            {
                $mk_id = $market['parent_id'];
            }
            if($market_layer == 1)
            {
                $temp_array = array();
                $mk_id = $mod_market->get_list($mk_id);
                foreach($my_mk_id as $value)
                {
                    $temp_array[] = $value['mk_id'];
                }
                $mk_id = $temp_array;
            }
            $behalfs = $mod_market->getRelatedData('belongs_to_behalf',$mk_id);
            if(!empty($behalfs))
            {
                foreach ($behalfs as $key=>$behalf)
                {
                    $behalfs[$key]['deliveries']=$this->getRelatedData('has_delivery',$behalf['bh_id'],array(
                            'order'=>'sort_order'
                    ));
                }
            }
            //dump($behalfs);
        }
        else
        {
            //所有代发 及 相应可发的快递,加入  判别 代发是否 有拿货范围
            $behalfs = $this->findAll(array(
                    'include'=>array(
                        'has_market'
                     )
            ));

            if(!empty($behalfs))
            {
                foreach ($behalfs as $key=>$behalf)
                {
                    $behalfs[$key]['deliveries']=$this->getRelatedData('has_delivery',$behalf['bh_id'],array(
                            'order'=>'sort_order'
                    ));
                    if(empty($behalf['market']))
                    {
                        unset($behalfs[$key]);
                    }
                }
            }
            //dump($behalfs);
        }
        //
        if(!empty($behalfs))
        {
            foreach ($behalfs as $key=>$behalf)
            {
                if(!$this->usable_behalf_by_max_orders($behalf['bh_id']))
                {
                    unset($behalfs[$key]);
                }

                // FIXME: 临时过滤，只留下“官方代发”，后续应该想办法和PC端做成通用的逻辑
                if ($behalf['bh_id'] != '165663') {
                    unset($behalfs[$key]);
                }
            }
            //随机排列代发
            shuffle($behalfs);
        }
        return $behalfs;
    }

    /**
     * 判断商品是否在代发的拿货范围
     * @param 代发id    $bh_id
     * @param 商品id数组    $goods_ids
     */
    function is_behalf_goods($bh_id=0,$goods_ids=array())
    {
        if(empty($bh_id))
        {
            return false;
        }
        if(empty($goods_ids))
        {
            return false;
        }
        //获取代发的拿货范围
        $markets = $this->getRelatedData('has_market', $bh_id);
        if(empty($markets))
        {
            return false;
        }
        $model_market=& m('market');

        $mk_ids = array();
        $mk_floor_ids=array();
        foreach ($markets as $market)
        {
            $mk_ids[] = $market['mk_id'];
        }
        foreach ($mk_ids as $mk_id)
        {
            $floors = $model_market->get_list($mk_id);
            if(!empty($floors))
            {
                foreach ($floors as $floor)
                {
                    $mk_floor_ids[] = $floor['mk_id'];
                }
            }
        }
        $mk_ids = array_merge($mk_ids,$mk_floor_ids);

        $goods_mk_ids = array();
        $model_goods=& m('goods');
        foreach ($goods_ids as $goods_id)
        {
            $store = $model_goods->get(array(
                'conditions'=>'goods_id='.$goods_id,
                'fields'=>'s.*',
                'join'=>'belongs_to_store',
            ));
            if(!empty($store) && !in_array($store['mk_id'],$goods_mk_ids))
            {
                $goods_mk_ids[] = $store['mk_id'];
            }
        }

        foreach ($goods_mk_ids as $gmi)
        {
            if(!in_array($gmi, $mk_ids))
            {
                return false;
            }
        }
        return true;
    }


}

?>