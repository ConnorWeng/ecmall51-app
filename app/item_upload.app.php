<?php

class Item_uploadApp extends MallbaseApp {
    public function index() {
        $goodsId = $_GET['goods_id'];
        $goodsModel = &m('goods');
        $goods = $goodsModel->get_info($goodsId);
        // if ($goods['good_http'] != null) {
        //     $taobaoItemId = $this->fetchNumIidFromUrl($goods['good_http']);
        // } else {
        $taobaoItemId = '';
        // }
        $dest = $_GET['dest'];
        header("Location: http://yjsc.51zwd.com/taobao-upload-multi-store/index.php?g={$dest}&m=Index&a=Auth&taobaoItemId={$taobaoItemId}&goodsId={$goodsId}&db=".OEM);
    }

    private function fetchNumIidFromUrl($url) {
        $pos = strpos($url, 'id=');
        return substr($url, $pos + 3);
    }
}
