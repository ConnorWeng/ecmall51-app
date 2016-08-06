<?php
/*
   File name: 中通面单
        转自13710329104：【中通快递】ID为1000139296的接口密码2R2KQN2KZS，【请勿向任何人提供您收到的短信效验码】
       数据接口统一访问测试地址：http://testpartner.zto.cn/client/interface.php
       数据接口统一访问正式地址：http://partner.zto.cn/client/interface.php
      测试阶段partner:test,pass:ZTO123开发时应将pass定义为可设置。
   Author: tanaiquan 2015/09/10
*/
//include '../byte.php';
class ZtoModeB{
    //订单号 -
    var $partner = "test";//"test"1000139296;1000196434
    var $pass = "ZTO123";//"ZTO123"2R2KQN2KZS;DHLMRYB6ZT
    var $style="json";
    var $func = 'order.submit';
    //var $timestamp = date("Y-m-d\TH:i:s",time()); 
    var $req_url = 'http://partner.zto.cn/client/interface.php';
    //数据
    var $order;//json
    var $strPOST;
    var $curl;
    var $data=array();


    function __ZtoModeB($partner,$pass)
    {
        //$this->CreateOrderModeB();
        $this->ZtoModeB($partner,$pass);
    }

    function ZtoModeB($partner,$pass)
    {
        $this->partner = $partner;
        $this->pass = $pass;
    }

    /**
     *   中通
     *   order['id'] = $order_sn
     *   order['sender']['name']
     *   order['sender']['mobile']  or order['sender']['phone']
     *   order['sender']['city']
     *   order['sender']['address']
     *   
     *   order['receiver']  同  sender
     *   
     */
    private function init_zto()
    {
        $this->data['style'] = $this->style;
        $this->data['func'] = $this->func;
        $this->data['partner'] = $this->partner;
        $this->data['datetime'] = date('Y-m-d H:i:s',time());
        //$this->data['datetime'] = local_date('Y-m-d H:i:s',time());
        $this->data['content'] = base64_encode(Bytes::toStr(Bytes::getBytes($this->order)));
        $this->data['verify'] = md5($this->partner.$this->data['datetime'].$this->data['content'].$this->pass);
    }

    public function setOrder($order)
    {
        
        $this->order = ecm_json_encode($order);        
       /*  $this->order = ecm_iconv('utf-8', 'utf-8',$this->order);
        $this->order = str_replace(array("\r\n", "\r", "\n"), "", $this->order);
        $this->order = trim($this->order); */
        //dump($this->order);
        //print_r($order);
        $this->init_zto();
    }

    public function setUpdateInfo($info,$flag)
    {
       
    }

    function getOrderModeB()
    {
        $this->curl = curl_init();
        if(stripos($this->req_url, "https://") !== false)
        {
            curl_setopt($this->curl,CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $this->strPOST = http_build_query($this->data);
        curl_setopt($this->curl, CURLOPT_URL, $this->req_url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_POST, 1 );
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->strPOST);

        //dump($this->strPOST);
        //var_dump($curl);

        $tmpInfo = curl_exec ($this->curl);
        curl_close ($this->curl);

       // $xml = simplexml_load_string($tmpInfo);
        //get_object_vars($xml)
        //$success = strval($xml->success);
        // print_r($this->data);
       // echo $tmpInfo;
       // echo "<pre>";print_r("********************************************************************<br>");
       // print_r($xml);print_r("<br>********************************************************************");
       // echo "</pre>";
       // dump($tmpInfo);
        return $tmpInfo;
    }
    
    /*
     * 获取可用单号数量
     */
    public function getMailCounter()
    {
        $this->curl = curl_init();
        if(stripos($this->req_url, "https://") !== false)
        {
            curl_setopt($this->curl,CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        } 
       
        $data=array(
            'style'=>$this->style,
            'func'=>'mail.counter',
            'partner'=>$this->partner,
            'datetime' =>date('Y-m-d H:i:s',time()),
            'content'=>'',
        );
        $data['verify'] = md5($data['partner'].$data['datetime'].$data['content'].$this->pass);
       
        $this->strPOST = http_build_query($data);
        curl_setopt($this->curl, CURLOPT_URL, $this->req_url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_POST, 1 );
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->strPOST);
        
        
        
        $result = curl_exec ($this->curl);
        curl_close ($this->curl);
        
       
        return $result;
    }


}

?>