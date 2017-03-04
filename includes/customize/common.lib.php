<?php
/**
 *
 * 通用的自定义函数库，今后不要写在global.lib.php ,这样便于升级维护
 * 作用同global.lib.php
 * 在global.lib.php  include_once();
 *
 * author:tanaiquan
 * date:2015-11-23
 */


//PHP stdClass Object转array
function object_array($array) {
    if(is_object($array)) {
        $array = (array)$array;
    } if(is_array($array)) {
        foreach($array as $key=>$value) {
            $array[$key] = object_array($value);
        }
    }
    return $array;
}


/**
 * 快递单号 是否存在
 * @param unknown $invoice_no
 */
function exist_invoiceno($invoice_no)
{
    //利用php文件锁解决并发问题
    $lock_file = ROOT_PATH."/data/exist_invoiceno.lock";
    if(!file_exists($lock_file))
    {
        file_put_contents($lock_file, 1);
    }

    //对文件加锁
    $fp = fopen($lock_file, 'a+');
    if(!$fp)
    {
        echo 'fail to open lock file!';
        return;
    }
    flock($fp, LOCK_EX);
    /* 主体程序 */
    $is_exist = false;
    $model_order = & m('order');
    $model_orderrefund = & m('orderrefund');

    $model_order->getOne("SELECT count(order_id) as c FROM ".$model_order->table." WHERE invoice_no='".trim($invoice_no)."'") > 0 && $is_exist = true;
    $model_orderrefund->getOne("SELECT count(order_id) as c FROM ".$model_orderrefund->table." WHERE invoice_no='".trim($invoice_no)."' AND status <> 2 ") > 0 && $is_exist = true;

    /*文件解锁*/
    flock($fp, LOCK_UN);
    fclose($fp);

    return $is_exist;
}
/**
 * 店铺是否属于代发区、实拍区和精品区
 * return false,order.app.php and taobao_order.app.php 会收取代发费
 * @param 店铺 $store_id
 */
function belong_behalfarea($store_id)
{
    $cache_server = & cache_server();
    $indexkey = 'store_belong_behalfarea_realityzone_brandarea_';
    $data = $cache_server->get($indexkey);
    if (!$data || empty($data)) {
        $mod_behalfarea = & m('storebehalfarea');
        $b_stores = $mod_behalfarea->getCol("SELECT store_id FROM ".$mod_behalfarea->table." WHERE state='1'");

        $mod_realityzone = & m('storerealityzone');
        $r_stores = $mod_realityzone->getCol("SELECT store_id FROM ".$mod_realityzone->table." WHERE state='1'");

        $mod_brandarea = & m('storebrandarea');
        $d_stores = $mod_brandarea->getCol("SELECT store_id FROM ".$mod_brandarea->table." WHERE state='1'");

        $stores = array_merge($b_stores,$r_stores,$d_stores);
        $stores = array_unique($stores);

        $cache_server->set($indexkey, $stores, 7200);
    }
    else
    {
        $stores = $data;
    }


    if(empty($stores))
    {
        return false;
    }

    return in_array($store_id, $stores);
}

/**每年开年都会面临档口调整，代发无法拿货的问题。
 * 故每年年初都会先整理部分档口，先将代发区档口全删除，
 * 整理一些后加入代发区，让客户只代发这个区的商品。
 * 等档口地址整理好后，可开放所有档口。
 * 允许 代发区 ，精品区，DH精选区，实拍区，全部，在后台配置
 */
function behalf_open_stores() {
    $behalf_open = Conf::get('behalf_open');

    $cache_server = & cache_server();
    $indexkey = 'store_belong_open_';
    $data = $cache_server->get($indexkey);
    if (!$data || empty($data)) {
        $b_stores = $c_stores =$r_stores = $d_stores = array();

        if(in_array('bba', $behalf_open)){
            $mod_behalfarea = & m('storebehalfarea');
            $b_stores = $mod_behalfarea->getCol("SELECT store_id FROM ".$mod_behalfarea->table." WHERE state='1'");
        }
        if(in_array('brz', $behalf_open)){
            $mod_realityzone = & m('storerealityzone');
            $r_stores = $mod_realityzone->getCol("SELECT store_id FROM ".$mod_realityzone->table." WHERE state='1'");
        }
        if(in_array('bbr', $behalf_open)){
            $mod_brandarea = & m('storebrandarea');
            $d_stores = $mod_brandarea->getCol("SELECT store_id FROM ".$mod_brandarea->table." WHERE state='1'");
        }
        if(in_array('bbc', $behalf_open)){
             $mod_behalfchoice = & m('storebehalfchoice');
             $c_stores = $mod_behalfchoice->getCol("SELECT store_id FROM ".$mod_behalfchoice->table." WHERE state='1'");
        }
        $stores = array_merge($b_stores,$r_stores,$d_stores,$c_stores);
        $stores = array_unique($stores);


        $cache_server->set($indexkey, $stores, 7200);
    }
    else
    {
        $stores = $data;
    }

    return $stores;
}

function filter_behalf_open_stores($stores, $limit = 0) {
    $store_ids = behalf_open_stores();
    $filtered = array();
    $count = 0;
    foreach ($stores as $store) {
        if (in_array($store['store_id'], $store_ids)) {
            array_push($filtered, $store);
            $count += 1;
            if ($limit !== 0 && $count >= $limit) {
                break;
            }
        }
    }
    return $filtered;
}

/**
 * 判断商品是否位于某个时间之后的拿货单中
 * @param $inp_time 10位整数
 */
function after_goods_taker_inventory($inp_time,$goods_warehouse_ids=array())
{
    $model_git = & m('goodstakerinventory');
    $gits = $model_git->find(array('conditions'=>"createtime >= {$inp_time}"));
    if(empty($gits)) return false;
    $query_goods_ids=array();
    foreach ($gits as $g)
    {
        $query_goods_ids = array_merge($query_goods_ids,explode(',', $g['content']));
    }
    if(empty($query_goods_ids)) return false;

    foreach ($goods_warehouse_ids as $gid)
    {
        if(in_array($gid, $query_goods_ids))
            return true;
    }
    return false;
}

/**
 * 获取商品退货率 缺货率
 * @param  $goods_id
 */
function get_goods_rates_in_common($goods_id)
{
    $bm_goods = & bm('goods');
    return $bm_goods->get_goods_rates($goods_id);
}

/**
 * 店铺是否在精品区
 * @param $store_id
 */
function exist_brandarea($store_id)
{
    $mod_sba =& m('storebrandarea');
    if($store_id <= 0 || !is_numeric($store_id)){ return false; }
    $store = $mod_sba->get(array('conditions'=>"store_id = '{$store_id}' AND state = 1"));
    if($store){ return true; }else{ return  false;}

}
/**
 * php文件处理并发
 * @param 文件名 $filename
 */
function zwd51_handle_concurrence_with_file_open($filename)
{
    $lock_file = ROOT_PATH."/data/".$filename.".lock";
    if(!file_exists($lock_file))
    {
        file_put_contents($lock_file, 1);
    }
    //对文件加锁
    $fp = fopen($lock_file, 'a+');

    return $fp;
}

function zwd51_handle_concurrence_with_file_close($fp)
{
    /*文件解锁*/
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * 改变淘宝宝贝图片尺寸
 */
function change_taobao_imgsize($goods_image)
{
    if(preg_match('/\d{3}x\d{3}\.jpg$/', $goods_image))
    {
        return str_replace('180x180.jpg', '240x240.jpg', $goods_image);
    }
    if(!preg_match('/\d{3}x\d{3}\.jpg$/', $goods_image))
    {
        return $goods_image.'_240x240.jpg';
    }

    return $goods_image;
}


?>
