<?php
/*
 *绫诲悕锛敛lipay_notify
 *锷熻始锛氢粯娆捐绷绋嬩腑链嶅垓鍣ㄩ€氧煡绫?
 *璇︾粏锛氲椤甸溃鏄€氧煡杩斿洖镙稿绩澶勭悊鏂渊欢锛屼笉闇€瑕佷慨鏀?
 *锏堟湰锛?.0
 *淇敼镞ユ湡锛?010-06-29
 '璇存梅锛?
 '浠ヤ笅浠ｇ爜鍙槸涓轰简鏂逛究鍟嗘埛娴奶瘯钥屾彁渚涚殑镙蜂褓浠ｇ爜锛屽晢鎴峰彨浠ユ抵鎹嚜宸辩绣绔櫕殑闇€瑕觥纴镌夌收鎶€链枃妗ｇ紪鍐?骞堕潪涓€瀹氲浣跨敤璇ヤ唬镰并€?
 '璇ヤ唬镰佷米渚涘涔豺拰镰旗┒鏀粯瀹涩帴鍙ｄ娇鐢纴鍙槸锁愪緵涓€涓弬钥寇€?
*/

////////////////////娉ㄦ剩/////////////////////////
//璋冭瘯阃氧煡杩斿洖镞讹纴鍙煡鐪嬫守鏀瑰启log镞ュ织镄勫启鍏XT阅出殑锁版嵁锛屾潵妫€镆ラ€氧煡杩斿洖鏄惁姝ｅ父
/////////////////////////////////////////////////

require_once("alipay_function.php");

class alipay_notify {
    var $gateway;           //缃贼叧鍦板潃
    var $security_code;  	//瀹夊夬镙￠獙镰?
    var $partner;           //钖堜綔浼郁即ID
    var $sign_type;         //锷豺瘑鏂瑰纺 绯荤稗榛椫
    var $mysign;            //锷豺瘑缁撴灉锛堡钖岖粨鏋滐级
    var $_input_charset;    //瀛楃缂栫爜镙煎纺
    var $transport;         //璁块梾妯〃纺

    /**鏋勯€豺嚱锁?
	*浠庨历缃枃浠朵腑鍒濆鍖枻彉阅?
	*$partner 钖堜綔韬唤钥匢D
	*$security_code 瀹夊夬镙￠獙镰?
	*$sign_type 锷豺瘑绫诲瀷
	*$_input_charset 瀛楃缂栫爜镙煎纺
	*$transport 璁块梾妯〃纺
     */
    function alipay_notify($partner,$security_code,$sign_type,$_input_charset = "GBK",$transport= "https") {

        $this->transport = $transport;
        if($this->transport == "https") {
            $this->gateway = "https://www.alipay.com/cooperate/gateway.do?";
        }else {
            $this->gateway = "http://notify.alipay.com/trade/notify_query.do?";
        }
        $this->partner          = $partner;
        $this->security_code    = $security_code;
        $this->mysign           = "";
        $this->sign_type	    = $sign_type;
        $this->_input_charset   = $_input_charset;
    }

    /********************************************************************************/

    /**瀵筐otify_url镄剧璇?
	*杩斿洖镄勯獙璇佺粨鏋滐细true/false
     */
    function notify_verify() {
        //銮峰彇杩灭▼链嶅垓鍣ˋTN缁撴灉锛岄獙璇佹槸钖︽槸鏀粯瀹涩湇锷〃櫒鍙戛潵镄剧姹?
        if($this->transport == "https") {
            $veryfy_url = $this->gateway. "service=notify_verify" ."&partner=" .$this->partner. "&notify_id=".$_POST["notify_id"];
        } else {
            $veryfy_url = $this->gateway. "partner=".$this->partner."&notify_id=".$_POST["notify_id"];
        }
        $veryfy_result = $this->get_verify($veryfy_url);

        //鐢熸垚绛惧悕缁撴灉
		if(empty($_POST)) {							//鍒ゆ柇POST鏉ョ殑锁扮粍鏄惁涓虹┖
			return false;
		}
		else {		
			$post          = para_filter($_POST);	    //瀵规塈链埘OST杩斿洖镄勫弬锁板幓绌?
			$sort_post     = arg_sort($post);	    //瀵规塈链埘OST鍙嶉锲炴潵镄勬暟鎹帓搴?
			$this->mysign  = build_mysign($sort_post,$this->security_code,$this->sign_type);   //鐢熸垚绛惧悕缁撴灉
	
			//鍐欐棩蹇匾褰?
			log_result("veryfy_result=".$veryfy_result."\n notify_url_log:sign=".$_POST["sign"]."&mysign=".$this->mysign.",".create_linkstring($sort_post));
	
			//鍒ゆ柇veryfy_result鏄惁涓篓ure锛出敓鎴愮殑绛惧悕缁撴灉mysign涓庤幏寰楃殑绛惧悕缁撴灉sign鏄惁涓€镊?
			//$veryfy_result镄勭粨鏋滀笉鏄痶rue锛屼笌链嶅垓鍣ㄨ缃梾棰朴€佸悎浣滆韩浠借€匢D銆乶otify_id涓€鍒嗛挓澶辨晥链夊叧
			//mysign涓岩ign涓岖瓑锛屼笌瀹夊夬镙￠獙镰并€佽姹傛椂镄勫弬锁版牸寮忥纸濡傦细宁﹁嚜瀹氢箟鍙傛暟绛多级銆佺紪镰佹牸寮忔湁鍏?
			if (preg_match("/true$/i",$veryfy_result) && $this->mysign == $_POST["sign"]) {
				return true;
			} else {
				return false;
			}
		}
    }

    /********************************************************************************/

    /**瀵箁eturn_url镄剧璇?
	*return 楠岃愈缁撴灉锛歵rue/false
     */
    function return_verify() {
        //銮峰彇杩灭▼链嶅垓鍣ˋTN缁撴灉锛岄獙璇佹槸钖︽槸鏀粯瀹涩湇锷〃櫒鍙戛潵镄剧姹?
        if($this->transport == "https") {
            $veryfy_url = $this->gateway. "service=notify_verify" ."&partner=" .$this->partner. "&notify_id=".$_GET["notify_id"];
        } else {
            $veryfy_url = $this->gateway. "partner=".$this->partner."&notify_id=".$_GET["notify_id"];
        }
        $veryfy_result = $this->get_verify($veryfy_url);

        //鐢熸垚绛惧悕缁撴灉
		if(empty($_GET)) {							//鍒ゆ柇GET鏉ョ殑锁扮粍鏄惁涓虹┖
			return false;
		}
		else {
			$get          = para_filter($_GET);	    //瀵规塈链塆ET鍙嶉锲炴潵镄勬暟鎹幓绌?
			$sort_get     = arg_sort($get);		    //瀵规塈链塆ET鍙嶉锲炴潵镄勬暟鎹帓搴?
			$this->mysign  = build_mysign($sort_get,$this->security_code,$this->sign_type);    //鐢熸垚绛惧悕缁撴灉
	
			//鍐欐棩蹇匾褰?
			//log_result("veryfy_result=".$veryfy_result."\n return_url_log:sign=".$_GET["sign"]."&mysign=".$this->mysign."&".create_linkstring($sort_get));
	
			//鍒ゆ柇veryfy_result鏄惁涓篓ure锛出敓鎴愮殑绛惧悕缁撴灉mysign涓庤幏寰楃殑绛惧悕缁撴灉sign鏄惁涓€镊?
			//$veryfy_result镄勭粨鏋滀笉鏄痶rue锛屼笌链嶅垓鍣ㄨ缃梾棰朴€佸悎浣滆韩浠借€匢D銆乶otify_id涓€鍒嗛挓澶辨晥链夊叧
			//mysign涓岩ign涓岖瓑锛屼笌瀹夊夬镙￠獙镰并€佽姹傛椂镄勫弬锁版牸寮忥纸濡傦细宁﹁嚜瀹氢箟鍙傛暟绛多级銆佺紪镰佹牸寮忔湁鍏?
			if (preg_match("/true$/i",$veryfy_result) && $this->mysign == $_GET["sign"]) {            
				return true;
			}else {
				return false;
			}
		}
    }

    /********************************************************************************/

    /**銮峰彇杩灭▼链嶅垓鍣ˋTN缁撴灉
	*$url 镌囧畾URL璺缎鍦板潃
	*return 链嶅垓鍣ˋTN缁撴灉板?
     */
    function get_verify($url,$time_out = "60") {
        $urlarr     = parse_url($url);
        $errno      = "";
        $errstr     = "";
        $transports = "";
        if($urlarr["scheme"] == "https") {
            $transports = "ssl://";
            $urlarr["port"] = "443";
        } else {
            $transports = "tcp://";
            $urlarr["port"] = "80";
        }
        $fp=@fsockopen($transports . $urlarr['host'],$urlarr['port'],$errno,$errstr,$time_out);
        if(!$fp) {
            die("ERROR: $errno - $errstr<br />\n");
        } else {
            fputs($fp, "POST ".$urlarr["path"]." HTTP/1.1\r\n");
            fputs($fp, "Host: ".$urlarr["host"]."\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: ".strlen($urlarr["query"])."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $urlarr["query"] . "\r\n\r\n");
            while(!feof($fp)) {
                $info[]=@fgets($fp, 1024);
            }
            fclose($fp);
            $info = implode(",",$info);
            return $info;
        }
    }

    /********************************************************************************/

}
?>
