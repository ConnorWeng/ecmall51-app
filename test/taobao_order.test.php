<?php
class TaobaoOrderTest extends PHPUnit_Framework_TestCase {
    var $taobaoOrderApp = null;

    function __construct() {
        $this->taobaoOrderApp = new Taobao_orderApp();
    }

    public function testMakeOuterIidFrom51() {
        $res = $this->taobaoOrderApp->_make_outer_iid_from_51('富丽B2078-A_P30_001#');
        $this->assertEquals('富丽B2078-A 001#', $res);
    }

    public function testMakeOuterIidFromVvic() {
        $result = $this->taobaoOrderApp->_make_outer_iid_from_vvic('国投6F 607-608-15.0#6601#');
        $this->assertEquals('国投 607 6601#', $result);
    }

    public function testMakeOuterIidFrom17() {
        $result = $this->taobaoOrderApp->_make_outer_iid_from_17('广州_国大7F709_爆款衣族_P16_#国大709档-88');
        $this->assertEquals('国大 709 国大709档-88#', $result);
    }

    public function testMakeOuterIidFromPpkoo() {
        $result = $this->taobaoOrderApp->_make_outer_iid_from_ppkoo('国投F2/250-A_15#5009');
        $this->assertEquals('国投 250-A 5009#', $result);
    }

    public function testCutRegionWords() {
        $regions = array(
            '上海市' => '上海',
            '阳泉市' => '阳泉',
            '上海' => '上海',
            '六盘水市' => '六盘水',
            '迪庆藏族自治州' => '迪庆藏族自治州');
        foreach ($regions as $given => $expected) {
            $region = $this->taobaoOrderApp->_cut_region_name($given);
            $this->assertEquals($expected, $region);
        }
    }

    public function testMakeRegionName() {
        $order = array(
            'receiver_state' => '浙江省',
            'receiver_city' => '杭州市',
            'receiver_district' => '其他区');
        $region_name = $this->taobaoOrderApp->_make_region_name($order);
        $this->assertEquals('中国 浙江省 杭州市 其他区', $region_name);
    }

    public function testMakeRegionNameWithEmpty() {
        $order = array(
            'receiver_state' => '浙江省',
            'receiver_city' => '杭州市',
            'receiver_district' => null);
        $region_name = $this->taobaoOrderApp->_make_region_name($order);
        $this->assertEquals('中国 浙江省 杭州市', $region_name);
    }
}
?>
