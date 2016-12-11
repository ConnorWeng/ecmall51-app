<?php

require_once(ROOT_PATH.'/test/fake/frontend.base.php');
require_once(ROOT_PATH.'/test/fake/mobile_frontend.app.php');
require_once(ROOT_PATH.'/test/fake/goods.model.php');

require_once(ROOT_PATH.'/app/mobile_goods.app.php');

class Mobile_goodsTest extends TestCase {

    private $mobile_goods;

    function __construct() {
        $goods_stub = $this->stub('GoodsModel', 'get', array(
            'description' => '这是宝贝描述，里面嵌了一张图片：<p><img style="max-width: 750.0px;" src="https://img.alicdn.com/imgextra/i3/752015177/TB2RTBFXZIa61Bjy0FbXXbWXpXa_!!752015177.png" align="absmiddle"></p>，能识别吗？',
        ));
        $this->mobile_goods = new Mobile_goodsApp($goods_stub);
    }

    function test_describe() {
        $json = json_decode(ajax_method_return_json($this->mobile_goods, '_describe', '123456'));
        $this->assertEquals($json->imgs_in_desc, array('https://img.alicdn.com/imgextra/i3/752015177/TB2RTBFXZIa61Bjy0FbXXbWXpXa_!!752015177.png'));
        $this->assertEquals($json->need_move_pics, true);
    }

}

?>