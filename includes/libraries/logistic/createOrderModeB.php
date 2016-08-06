<?php
/*
   File name: CreateOrderModeB yto
   Author: tanaiquan 2015/08/13
*/
include 'byte.php';
class CreateOrderModeB{
    //订单号 -
    var $clientId = "K24000154";//"K24000154";K200225829
    var $customerId = "K24000154";
    var $parternId = "weH71Rbq";//'weH71Rbq';
    var $req_url = 'http://service.yto56.net.cn/CommonOrderModeBServlet.action';
    //数据
    var $order = array();
    var $string;
    var $data_digest;
    var $curl;
    var $data=array();


    function __CreateOrderModeB($clientId,$parternId)
    {
        $this->CreateOrderModeB($clientId,$parternId);
    }

    function CreateOrderModeB($clientId,$parternId)
    {
        $this->clientId = $clientId;
        $this->customerId = $this->clientId;
        $this->parternId = $parternId;
        //$this->req_url = $req_url;
    }

    /**
     *   圆通
     */
    private function init_yto()
    {
        $this->string  = "<RequestOrder>";
        $this->string  .= "<clientID>".$this->clientId."</clientID>";//商家代码
        $this->string  .= "<logisticProviderID>YTO</logisticProviderID>";//物流公司ID
        $this->string  .= "<customerId>".$this->customerId."</customerId>"; //其值等于clientId
        $this->string  .= "<txLogisticID>".$this->clientId.$this->order['order_sn']."</txLogisticID>";//物流订单号 :clientID+数字，必须唯一，最后一位必须是数字
        //$this->string  .= "<tradeNo>2007082300225709</tradeNo>";//业务交易号，可选
        $this->string  .= "<totalServiceFee>0.0</totalServiceFee>";//总服务费，可选
        $this->string  .= "<codSplitFee>0.0</codSplitFee>";//物流公司分润，可选
        $this->string  .= "<type>0</type>";//向下兼容
        $this->string  .= "<orderType>1</orderType>";//订单类型：0-COD,1-普通订单，2-便携式订单，3-退货单
        $this->string  .= "<serviceType>0</serviceType>";//服务类型：1-上门揽收，2-次日达，4-次晨达，8-当日达，0-自己联系。默认0
        $this->string  .= "<flag>1</flag>";//可选，订单标识，便于以后分拣和标识默认0
        $this->string  .= "<sender>";
        $this->string  .= "<name>".$this->order['sender_name']."</name>";//用户姓名
        $this->string  .= "<postCode>".$this->order['sender_code']."</postCode>";//用户邮编，可选
        $this->string  .= "<phone>".$this->order['sender_phone']."</phone>";//用户电话，可选
        $this->string  .= "<mobile>".$this->order['sender_mob']."</mobile>";//用户手机，两个至少填一项
        $this->string  .= "<prov>".$this->order['sender_prov']."</prov>";//用户所在省
        $this->string  .= "<city>".$this->order['sender_city']."</city>";//用户所在市县区，市区之间用英文","分隔
        $this->string  .= "<address>".$this->order['sender_address']."</address>";//用户详细地址
        $this->string  .= "</sender>";
        $this->string  .= "<receiver>";
        $this->string  .= "<name>".$this->order['receiver_name']."</name>";
        $this->string  .= "<postCode>".$this->order['receiver_code']."</postCode>";
        $this->string  .= "<phone>".$this->order['receiver_phone']."</phone>";
        $this->string  .= "<mobile>".$this->order['receiver_mob']."</mobile>";
        $this->string  .= "<prov>".$this->order['receiver_prov']."</prov>";
        $this->string  .= "<city>".$this->order['receiver_city']."</city>";
        $this->string  .= "<address>".$this->order['receiver_address']."</address>";
        $this->string  .= "</receiver>";
        $this->string  .= "<sendStartTime>".local_date('Y-m-d H:i:s',gmtime())."</sendStartTime>";
        $this->string  .= "<sendEndTime>".local_date('Y-m-d H:i:s',(gmtime()+60*60*2))."</sendEndTime>";
        $this->string  .= "<goodsValue>0</goodsValue>";
        //$this->string  .= "<itemsValue>0</itemsValue>";
        $this->string  .= "<items>";
        foreach ($this->order['order_goods'] as $goods)
        {
            $this->string  .= "<item>";
            $this->string  .= "<itemName>".$goods['goods_name']."</itemName>";//商品名称
            $this->string  .= "<number>".$goods['quantity']."</number>";//数量
            $this->string  .= "<itemValue>".(intval($goods['quantity'])*floatval($goods['price']))."</itemValue>";//价值，可选
            $this->string  .= "</item>";
        }

       /*  $this->string  .= "<item>";
        $this->string  .= "<itemName>Nokia N72</itemName>";
        $this->string  .= "<number>1</number>";
        $this->string  .= "<itemValue>2</itemValue>";
        $this->string  .= "</item>"; */
        $this->string  .= "</items>";
        $this->string  .= "<insuranceValue>0.0</insuranceValue>";//保值金额，可选
        $this->string  .= "<special>0</special>";//商品类型，可选
        $this->string  .= "<remark>goods</remark>";//备注，可选
        $this->string  .= "</RequestOrder>";

        $this->data_digest= base64_encode(Bytes::toStr(Bytes::getBytes(md5($this->string.$this->parternId,true))));


        $this->data['logistics_interface']=$this->string;//urlencode();xml内容，要发送的内容，如拉取面单，上传订单。
        $this->data['data_digest']=$this->data_digest;
        //数字签名签名的原理是对要发送的xml报文字符串加上商家密钥，先进行MD5加密(16位)，然后转换成base64字符串，作为数字签名的数据。
       // 即：Base64 [ MD5 [ logistics_interface +partnerId ] ]最后对所有参数的数据内容进行url编码。
        $this->data['clientId']=$this->clientId;//客户端编码
        //$this->data['type']=online;//offline,可不填，根据clientId自行判断。
        //dump($this->data);
    }

    public function setOrder($order,$flag)
    {
        $this->order = $order;
        if(empty($flag))
        {
            return;
            //$this->init_yto();
        }
        elseif($flag == 'yto')
        {
            $this->init_yto();
        }

    }

    public function setUpdateInfo($info,$flag)
    {
        $this->string = "<UpdateInfo>";
        $this->string .= "<logisticProviderID>".$info['logisticProviderID']."</logisticProviderID>";
        $this->string .= "<clientID>".$info['clientID']."</clientID>";
        $this->string .= "<mailNo>".$info['mailNo']."</mailNo>";
        $this->string .= "<txLogisticID>".$info['txLogisticID']."</txLogisticID>";
        $this->string .= "<infoType>".$info['infoType']."</infoType>";
        $this->string .= "<infoContent>".$info['infoContent']."</infoContent>";
        $this->string .= "<remark>".$info['remark']."</remark>";
        $this->string .= "</UpdateInfo>";
        
        /* $this->string = "<UpdateInfo>";
        $this->string .= "<logisticProviderID>".trim($info['logisticProviderID'])."</logisticProviderID>";
        $this->string .= "<clientID>".trim($info['clientID'])."</clientID>";
        $this->string .= "<mailNo>".trim($info['mailNo'])."</mailNo>";
        $this->string .= "<txLogisticID>".trim($info['txLogisticID'])."</txLogisticID>";
        $this->string .= "<infoType>".trim($info['infoType'])."</infoType>";
        $this->string .= "<infoContent>".trim($info['infoContent'])."</infoContent>";
        $this->string .= "<remark>".trim($info['remark'])."</remark>";
        $this->string .= "</UpdateInfo>"; */
        $this->data_digest= base64_encode(Bytes::toStr(Bytes::getBytes(md5($this->string.$this->parternId,true))));

        $this->data['logistics_interface']=$this->string;//urlencode();xml内容，要发送的内容，如拉取面单，上传订单。
        $this->data['data_digest']=$this->data_digest;
        $this->data['clientId']=$this->clientId;//客户端编码
    }

    function getOrderModeB()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_URL, $this->req_url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_POST, 1 );
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->data);


        //var_dump($curl);

        $tmpInfo = curl_exec ($this->curl);
        curl_close ($this->curl);

       // $xml = simplexml_load_string($tmpInfo);
        //get_object_vars($xml)
        //$success = strval($xml->success);
        //print_r($this->data);
        //echo $tmpInfo;
       // echo "<pre>";print_r("********************************************************************<br>");
       // print_r($xml);print_r("<br>********************************************************************");
       // echo "</pre>";
       // dump($tmpInfo);
        return $tmpInfo;
    }


}

?>