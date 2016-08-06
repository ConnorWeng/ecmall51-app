<?php

require_once("includes/sdkalipay/AlipaySystemOauthTokenRequest.php");
require_once("includes/sdkalipay/AlipayUserTradeSearchRequest.php");
require_once("includes/sdkalipay/AlipayUserAccountGetRequest.php");
require_once("includes/sdkalipay/AlipayUserAccountFreezeGetRequest.php");
require_once("includes/sdkalipay/AlipayUserAccountSearchRequest.php");
require_once("includes/sdkalipay/AopClient.php");
require_once("includes/sdkalipay/SignData.php");
define('THUMB_WIDTH', 300);
define('THUMB_HEIGHT', 300);
define('THUMB_QUALITY', 85);
define('ALI_PAGE_SIZE', 500);

class DefaultApp extends MallbaseApp {

    function index() {
        $this->assign('index', 1); // 标识当前页面是首页，用于设置导航状态
        $this->assign('icp_number', Conf::get('icp_number'));

        //判断有无已静态化的半截页面
        $template_name = $this->_get_template_name();
        $filename = ROOT_PATH . "/themes/mall/{$template_name}" . "/index_makehtml_homepage.html";
        if (file_exists($filename)) {
            $this->assign("file_exists", 1);
        }
        $this->_config_seo(array(
            'title' => Conf::get('site_title'),
        ));
        $this->assign('page_description', Conf::get('site_description'));
        $this->assign('page_keywords', Conf::get('site_keywords'));
        $this->display('index.html');
    }

    /* 首页静态化，生成index.html */

    function makehtml_homepage() {
        $cache_server = & cache_server();
        $cache_server->clear();

        $this->assign('index', 1); // 标识当前页面是首页，用于设置导航状态
        $this->assign('icp_number', Conf::get('icp_number'));

        /* 热门搜素 */
        $this->assign('hot_keywords', $this->_get_hot_keywords());
        $this->assign('page_title', Conf::get('site_title'));
        $this->assign('page_description', Conf::get('site_description'));
        $this->assign('page_keywords', Conf::get('site_keywords'));
        $data = $this->outhtml('makehtml_index.html');
        $template_name = $this->_get_template_name();
        $file = "themes/mall/{$template_name}" . '/index_makehtml_homepage.html';
        @unlink($file);
        $filesize = file_put_contents($file, $data);
        @chmod($file, 0777);
        $mtime = filemtime($file);
        echo '<div style="line-height:30px;">' . Lang::get('generate_index_success') . '<br />' .
        Lang::get('updatetime') . ':' . date("Y-m-d H:i:s", $mtime) . '<br />'
        . Lang::get('filesize') . ':' . filesize_caculate($filesize) . '<br/>' .
        Lang::get('homebrowse') . '  <a href="' . SITE_URL . '/index.php" target="_blank">' . Lang::get('homepage') . '</a>' .
        '' . '</div>';
    }

    /* 删除index.html */

    function delhtml_homepage() {
        $template_name = $this->_get_template_name();
        $filename = ROOT_PATH . "/themes/mall/{$template_name}" . "/index_makehtml_homepage.html";
        @unlink($filename);
        echo "del success!";
    }

    private function getMyDB($oem, $model) {
        if (!empty($oem) && $oem == 'nc') {
            $paylogmodel = &sm($model);
        } else if (!empty($oem) && $oem == 'changshu') {
            $paylogmodel = &changm($model);
        } else if (!empty($oem) && $oem == 'nt') {
            $paylogmodel = &ntm($model);
        } else if (!empty($oem) && $oem == 'mall') {
            $paylogmodel = &mallm($model);
        } else if (!empty($oem) && $oem == 'dg') {
            $paylogmodel = &dgm($model);
        } else {
            $paylogmodel = &m($model);
        }
        return $paylogmodel;
    }

    private function getAliAccount($oem) {
        if (!empty($oem) && $oem == 'nc') {
            return NC_ALIACCOUNT;
        } else if (!empty($oem) && $oem == 'changshu') {
            return CHANGSHU_ALIACCOUNT;
        } else if (!empty($oem) && $oem == 'nt') {
            return NT_ALIACCOUNT;
        } else if (!empty($oem) && $oem == 'mall') {
            return MALL_ALIACCOUNT;
        } else if (!empty($oem) && $oem == 'dg') {
            return DG_ALIACCOUNT;
        } else {
            return ALIACCOUNT;
        }
    }

    /**
     * 暂时因为权限问题不能使用 
     */
    function getaliMoney() {
        $c = new AopClient ();
        $c->appId = APP_ID;
        $c->rsaPrivateKeyFilePath = ROOT_PATH . "/rsa_private_key.pem";
        $c->format = "json";
        // 获取授权令牌
        $authtokenresp = &m('my_money_life');
        $sql = 'SELECT * from  ' . $authtokenresp->table . ' where alipay_user_id = 1';
        $results = $authtokenresp->getAll($sql);
        $authToken = $results [0];
        $nowtime = time();
        header("Content-type: text/html; charset= utf-8");
        // 授权令牌是否过期
        if ($nowtime > $authToken ['updatetime'] + $authToken ['expires_in']) {
            if ($nowtime > $authToken ['updatetime'] + $authToken ['re_expires_in']) {
                // 刷新令牌过期需要重新授权
//                header("location: https://openauth.alipay.com/oauth2/authorize.htm?client_id=" . APP_ID);
                $finalresult['result'] = 'auth';
                $finalresult['info'] = "https://openauth.alipay.com/oauth2/authorize.htm?client_id=" . APP_ID;
                exit(json_encode($finalresult));
            } else {
                // 刷新令牌未过期使用刷新令牌重新获得令牌
                $req = new AlipaySystemOauthTokenRequest ();
                $req->setGrantType("refresh_token");
                $req->setRefreshToken($authToken ['refresh_token']);
                $resp = $c->execute($req);
                $authdatas = (array) $resp;
                foreach ($authdatas as $key => $val) {
                    if (is_object($val)) {
                        $authdatas [$key] = (array) $val;
                    }
                }
                // 刷新令牌接口结果
                if ($authdatas ['error_response']) {
                    $finalresult['result'] = 'error';
                    $finalresult['info'] = "刷新令牌失败!";
                    exit(json_encode($finalresult));
                } else if ($authdatas ['alipay_system_oauth_token_response']) {
                    $authtokenresp = &m('my_money_life');
                    $authtokenresp->drop(array('alipay_user_id' => 1));
                    $authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] = 1;
                    $authdatas ['alipay_system_oauth_token_response'] ['updatetime'] = time();
                    $result = $authtokenresp->add($authdatas ['alipay_system_oauth_token_response'], true);
                    $accesstoken = $authdatas ['alipay_system_oauth_token_response'] ['access_token'];
//                    echo '系统数据刷新令牌成功！';
                } else {
                    $finalresult['result'] = 'refresh';
                    $finalresult['info'] = "支付宝数据异常!";
                    exit(json_encode($finalresult));
                }
            }
        } else {
            $accesstoken = $authToken ['access_token'];
        }
        // 交易记录查询接口
        $req1 = new AlipayUserAccountGetRequest ();
        $resp1 = $c->execute($req1, $accesstoken);
        print_r($resp1);
        $result = var_export($resp1, true);
        Log::write($result);
        exit(json_encode($resp1));
    }

    /**
     * 暂时因为权限问题不能使用 
     */
    function getaliFrozenMoney() {
        $c = new AopClient ();
        $c->appId = APP_ID;
        $c->rsaPrivateKeyFilePath = ROOT_PATH . "/rsa_private_key.pem";
        $c->format = "json";
        // 获取授权令牌
        $authtokenresp = &m('my_money_life');
        $sql = 'SELECT * from  ' . $authtokenresp->table . ' where alipay_user_id = 1';
        $results = $authtokenresp->getAll($sql);
        $authToken = $results [0];
        $nowtime = time();
        header("Content-type: text/html; charset= utf-8");
        // 授权令牌是否过期
        if ($nowtime > $authToken ['updatetime'] + $authToken ['expires_in']) {
            if ($nowtime > $authToken ['updatetime'] + $authToken ['re_expires_in']) {
                // 刷新令牌过期需要重新授权
//                header("location: https://openauth.alipay.com/oauth2/authorize.htm?client_id=" . APP_ID);
                $finalresult['result'] = 'auth';
                $finalresult['info'] = "https://openauth.alipay.com/oauth2/authorize.htm?client_id=" . APP_ID;
                exit(json_encode($finalresult));
            } else {
                // 刷新令牌未过期使用刷新令牌重新获得令牌
                $req = new AlipaySystemOauthTokenRequest ();
                $req->setGrantType("refresh_token");
                $req->setRefreshToken($authToken ['refresh_token']);
                $resp = $c->execute($req);
                $authdatas = (array) $resp;
                foreach ($authdatas as $key => $val) {
                    if (is_object($val)) {
                        $authdatas [$key] = (array) $val;
                    }
                }
                // 刷新令牌接口结果
                if ($authdatas ['error_response']) {
                    $finalresult['result'] = 'error';
                    $finalresult['info'] = "刷新令牌失败!";
                    exit(json_encode($finalresult));
                } else if ($authdatas ['alipay_system_oauth_token_response']) {
                    $authtokenresp = &m('my_money_life');
                    $authtokenresp->drop(array('alipay_user_id' => 1));
                    $authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] = 1;
                    $authdatas ['alipay_system_oauth_token_response'] ['updatetime'] = time();
                    $result = $authtokenresp->add($authdatas ['alipay_system_oauth_token_response'], true);
                    $accesstoken = $authdatas ['alipay_system_oauth_token_response'] ['access_token'];
//                    echo '系统数据刷新令牌成功！';
                } else {
                    $finalresult['result'] = 'refresh';
                    $finalresult['info'] = "支付宝数据异常!";
                    exit(json_encode($finalresult));
                }
            }
        } else {
            $accesstoken = $authToken ['access_token'];
        }
        // 交易记录查询接口
        $req1 = new AlipayUserAccountFreezeGetRequest ();
        $resp1 = $c->execute($req1, $accesstoken);
        print_r($resp1);
        exit(json_encode($resp1));
    }

    function searchtrade() {
        $tradeno = empty($_POST['tradeno']) ? $_GET['tradeno'] : $_POST['tradeno'];
        $totalfee = empty($_POST['totalfee']) ? $_GET['totalfee'] : $_POST['totalfee'];
        $oem = empty($_POST['oem']) ? $_GET['oem'] : $_POST['oem'];
        $title = empty($_POST['title']) ? $_GET['title'] : $_POST['title'];
        $time = empty($_POST['time']) ? $_GET['time'] : $_POST['time'];
        $len = strlen($tradeno);
        if ($len != 28) {
            
        }
        $finalresult = array();
        $paylogmodel = $this->getMyDB($oem, 'paylog');
        //var_dump($paylogmodel);
        if (empty($title)) {
            $sql = 'SELECT out_trade_no from  ' . $paylogmodel->table . ' where out_trade_no="' . $tradeno . '"';
            $results = $paylogmodel->db->getOne($sql);
            if ($results && !empty($results)) {
                $finalresult['result'] = 'error';
                $finalresult['info'] = '该交易号已经在站内被充值过！';
                exit(json_encode($finalresult));
            }
        } else {
            
        }
        // 获取授权令牌
        $aliaccount = $this->getAliAccount($oem);
        $tokens = $this->getToken(APP_ID, $aliaccount, '', '');
        if ($tokens && $tokens['result'] == 'success') {
            $accesstoken = $tokens['info'];
        } else {
            $finalresult['result'] = 'error';
            $finalresult['info'] = "get token failed!";
            exit(json_encode($finalresult));
        }
        $user_id = $_GET['user_id'];
        $user_name = $_GET['user_name'];
        // 交易记录查询接口
        $req1 = new AlipayUserTradeSearchRequest ();
        // 每页获取条数
        $req1->setPageSize(ALI_PAGE_SIZE);
        // 结束时间。与开始时间间隔在七天之内
        $end_time = date('Y-m-d H:i:s', time());
        $req1->setEndTime($end_time);
        // 支付宝订单号，为空查询所有记录
        $start_time = date('Y-m-d H:i:s', time() - 1 * 24 * 60 * 60 + 1);
        if (empty($title) && !empty($tradeno)) {
            $req1->setAlipayOrderNo($tradeno);
            $start_time = date('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60 + 1); //这个不耗性能
        }
        // 开始时间，时间必须是今天范围之内

        $req1->setStartTime($start_time);

        $resp1 = $this->getResp($req1, "1", $accesstoken);
        $datas = $this->getData($resp1);
        if ($datas ['response_error']) {
            $finalresult['result'] = 'error';
            $finalresult['info'] = "交易记录查询失败!";
            exit(json_encode($finalresult));
        } else if ($datas ['alipay_user_trade_search_response']) {
            $count = $datas ['alipay_user_trade_search_response'] ['total_results'];
//                echo 'thers are : ' . $count . '<br>';
            if ($count > 0) {
                $done = false;
                for ($j = 0; $j < intval($count / ALI_PAGE_SIZE) + 1; $j++) {//页码
                    if ($j > 0) {
                        $resp1 = $this->getResp($req1, $j + 1, $accesstoken);
                        $datas = $this->getData($resp1);
                    }
                    for ($i = 0; $i < (ALI_PAGE_SIZE < ($count - $j * ALI_PAGE_SIZE) ? ALI_PAGE_SIZE : ($count - $j * ALI_PAGE_SIZE)); $i++) {
                        $tradeinfo = $datas ['alipay_user_trade_search_response'] ['trade_records'] ['trade_record'] [$i];
//                        echo $tradeinfo ['order_title'] . "-" . $tradeinfo ['total_amount'] . "-" . $tradeinfo ['in_out_type'] . '<br>';
                        if (((!empty($title) && strpos($tradeinfo ['order_title'], $title) > -1) || (!empty($tradeno) && $tradeinfo ['alipay_order_no'] == $tradeno)) && $tradeinfo ['total_amount'] == $totalfee && $tradeinfo ['in_out_type'] == 'in'
                                && ($tradeinfo ['order_status'] == 'TRADE_FINISHED' || $tradeinfo ['order_status'] == 'TRADE_SUCCESS' || $tradeinfo ['order_status'] == 'TRANSFER_WITHDRAW_SUCCESS')) {

                            $sql = 'SELECT out_trade_no from  ' . $paylogmodel->table . ' where out_trade_no="' . $tradeinfo ['alipay_order_no'] . '"';
                            $results = $paylogmodel->db->getOne($sql);
                            if ($results && !empty($results)) {
                                $finalresult['result'] = 'error';
                                $finalresult['info'] = "alipay_order_no-used-b Ex!";
                                exit(json_encode($finalresult));
                            }
                            $user_id = $_GET['user_id'];
                            $user_name = $_GET['user_name'];
                            $sql = 'SELECT trade_no from  ' . $paylogmodel->table . ' where trade_status=0 and trade_no="' . $tradeinfo ['order_title'] . '"';
                            $results = $paylogmodel->db->getOne($sql);
                            if ($results) {
                                $updateOuttrade = array(
                                    'out_trade_no' => $tradeinfo ['alipay_order_no'],
                                    'trade_status' => 1,
                                );
                                $paylogmodel->edit('trade_no="' . $tradeinfo ['order_title'] . '"', $updateOuttrade);
                            } else {
                                $condition ['out_trade_no'] = $tradeinfo ['alipay_order_no'];
                                $condition ['total_fee'] = $tradeinfo ['total_amount'];
                                $condition ['createtime'] = date("Y-m-d H:i:s", time());
                                $condition ['endtime'] = date("Y-m-d H:i:s", time());
                                $condition ['trade_status'] = 1;
                                $condition ['customer_id'] = $user_id;
                                $condition ['type'] = 0;
                                $condition ['trade_no'] = $tradeinfo ['order_title'];
                                $paylogmodel->add($condition);
                            }
                            $my_money_mod = $this->getMyDB($oem, 'my_money');
                            $my_moneylog_mod = $this->getMyDB($oem, 'my_moneylog');


                            $user_row = $my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
                            $user_money = $user_row['money'];
                            $user_jifen = $user_row['jifen'];
                            $my_money_dj = $user_row['money_dj'];
                            $user_name = $user_row['user_name']; //当稽核时,却只有user_id 20150916

                            $new_money = $user_money + $totalfee;
                            $new_jifen = $user_jifen + $totalfee;
                            $edit_mymoney = array(
                                'money' => $new_money,
                            );
                            $edit_myjifen = array(
                                'jifen' => $new_jifen,
                            );
                            $my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
                            $my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);
                            //添加日志
                            $log_text = '无手续费充值tradeEx'; //$this->visitor->get('user_name') . Lang::get('tongguoalipaychongzhi') . $total_fee . Lang::get('yuan');

                            $add_mymoneylog = array(
                                'user_id' => $user_id,
                                'user_name' => $user_name,
                                'buyer_name' => Lang::get('alipay'),
                                'seller_id' => $user_id,
                                'seller_name' => $user_name,
                                'order_sn ' => $tradeinfo ['alipay_order_no'],
                                'add_time' => gmtime(),
                                'admin_time' => gmtime(),
                                'leixing' => 30,
                                'money_zs' => $totalfee,
                                'money' => $totalfee,
                                'log_text' => $log_text,
                                'caozuo' => 4,
                                's_and_z' => 1,
                                'moneyleft' => $new_money + $my_money_dj,
                            );
                            $my_moneylog_mod->add($add_mymoneylog);
                            $done = true;
//                            echo 'year!.<br>';
                        }
                    }
                }
                if (!$done) {
                    $finalresult['result'] = 'error';
                    $finalresult['info'] = "没有找到对应的记录，充值失败!";
                    exit(json_encode($finalresult));
                }
            } else {
                $finalresult['result'] = 'error';
                $finalresult['info'] = "没有记录，充值失败!";
                exit(json_encode($finalresult));
            }
        } else {
            $finalresult['result'] = 'error';
            $finalresult['info'] = "数据异常，查询失败！";
            exit(json_encode($finalresult));
        }
        $finalresult['result'] = 'success';
        $finalresult['info'] = "充值成功!";
        exit(json_encode($finalresult));
    }

    /**
     * 财务明细的查询 
     */
    function searchfinance() {
//        echo 'i am searchtrade';
        $tradeno = empty($_POST['tradeno']) ? $_GET['tradeno'] : $_POST['tradeno'];
        $totalfee = empty($_POST['totalfee']) ? $_GET['totalfee'] : $_POST['totalfee'];
        $oem = empty($_POST['oem']) ? $_GET['oem'] : $_POST['oem'];
        $title = empty($_POST['title']) ? $_GET['title'] : $_POST['title'];
        $len = strlen($tradeno);
        if ($len != 28) {
//            echo 'tradno is error';
        }
//        echo 'title is : '.$title.'<br>';

        $c = new AopClient ();
        $c->appId = APP_ID;
        $c->rsaPrivateKeyFilePath = ROOT_PATH . "/rsa_private_key.pem";
        $c->format = "json";
        // 获取授权令牌
        $authtokenresp = &m('my_money_life');
        $sql = 'SELECT * from  ' . $authtokenresp->table . ' where alipay_user_id = 1';
        $results = $authtokenresp->getAll($sql);
        $authToken = $results [0];
        $nowtime = time();
        header("Content-type: text/html; charset= utf-8");
        // 授权令牌是否过期
        if ($nowtime > $authToken ['updatetime'] + $authToken ['expires_in']) {
            if ($nowtime > $authToken ['updatetime'] + $authToken ['re_expires_in']) {
                // 刷新令牌过期需要重新授权
//                header("location: https://openauth.alipay.com/oauth2/authorize.htm?client_id=" . APP_ID);
                $finalresult['result'] = 'auth';
                $finalresult['info'] = "https://openauth.alipay.com/oauth2/authorize.htm?client_id=" . APP_ID;
                exit(json_encode($finalresult));
            } else {
                // 刷新令牌未过期使用刷新令牌重新获得令牌
                $req = new AlipaySystemOauthTokenRequest ();
                $req->setGrantType("refresh_token");
                $req->setRefreshToken($authToken ['refresh_token']);
                $resp = $c->execute($req);
                $authdatas = (array) $resp;
                foreach ($authdatas as $key => $val) {
                    if (is_object($val)) {
                        $authdatas [$key] = (array) $val;
                    }
                }
                // 刷新令牌接口结果
                if ($authdatas ['error_response']) {
                    $finalresult['result'] = 'error';
                    $finalresult['info'] = "刷新令牌失败!";
                    exit(json_encode($finalresult));
                } else if ($authdatas ['alipay_system_oauth_token_response']) {
                    $authtokenresp = &m('my_money_life');
                    $authtokenresp->drop(array('alipay_user_id' => 1));
                    $authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] = 1;
                    $authdatas ['alipay_system_oauth_token_response'] ['updatetime'] = time();
                    $result = $authtokenresp->add($authdatas ['alipay_system_oauth_token_response'], true);
                    $accesstoken = $authdatas ['alipay_system_oauth_token_response'] ['access_token'];
//                    echo '系统数据刷新令牌成功！';
                } else {
                    $finalresult['result'] = 'refresh';
                    $finalresult['info'] = "支付宝数据异常!";
                    exit(json_encode($finalresult));
                }
            }
        } else {
            $accesstoken = $authToken ['access_token'];
        }
        // 交易记录查询接口
        $req1 = new AlipayUserAccountSearchRequest ();
        // 每页获取条数
        $req1->setPageSize("100");
        // 结束时间。与开始时间间隔在七天之内
        $end_time = date('Y-m-d H:i:s', time());
        $req1->setEndTime($end_time);
        // 支付宝订单号，为空查询所有记录
//        if (empty($title)) {
//            $req1->setAlipayOrderNo($_POST ['tradeno']);
//        }
        // 开始时间，时间必须是今天范围之内
        $start_time = date('Y-m-d H:i:s', time() - 1 * 24 * 60 * 60 + 1);
        $req1->setStartTime($start_time);
        // 页码
        $req1->setPageNo("1");
        // $req1->setOrderStatus("zhuangtai");
        // $req1->setOrderType("dingdanleixing");
        $resp1 = $c->execute($req1, $accesstoken);
        $datas = (array) $resp1;
        print_r($datas);
        foreach ($datas as $key => $val) {
            if (is_object($val)) {
                $datas [$key] = (array) $val;

                foreach ($datas [$key] as $key1 => $val1) {
                    if (is_object($val1)) {
                        $datas [$key] [$key1] = (array) $val1;

                        foreach ($datas [$key] [$key1] as $key2 => $val2) {
                            if (is_object($val2)) {
                                $datas [$key] [$key1] [$key2] = (array) $val2;
                            }
                            foreach ($datas [$key] [$key1] [$key2] as $key3 => $val3) {
                                if (is_object($val3)) {
                                    $datas [$key] [$key1] [$key2] [$key3] = (array) $val3;
                                }
                            }
                        }
                    }
                }
            }
        }
        // $datas = json_decode ( $resp1, true );
        if (!empty($title)) {
            if ($datas ['response_error']) {
                $finalresult['result'] = 'error';
                $finalresult['info'] = "交易记录查询失败!";
                exit(json_encode($finalresult));
            } else if ($datas ['alipay_user_account_search_response']) {
                $count = $datas ['alipay_user_account_search_response'] ['total_results'];
//                echo 'thers are : ' . $count . '<br>';
                if ($count > 0) {
                    $done = false;
                    for ($i = 0; $i < $count; $i++) {
                        $tradeinfo = $datas ['alipay_user_account_search_response'] ['account_records'] ['account_record'] [$i];
//                        echo $tradeinfo ['order_title'] . "-" . $tradeinfo ['total_amount'] . "-" . $tradeinfo ['in_out_type'] . '<br>';
                        if ($tradeinfo ['order_title'] == $title && $tradeinfo ['total_amount'] == $totalfee && $tradeinfo ['in_out_type'] == 'in'
                                && ($tradeinfo ['order_status'] == 'TRADE_FINISHED' || $tradeinfo ['order_status'] == 'TRADE_SUCCESS')) {
//                            echo 'I am in !';

                            $sql = 'SELECT out_trade_no from  ' . $paylogmodel->table . ' where out_trade_no="' . $tradeinfo ['alipay_order_no'] . '"';
                            $results = $paylogmodel->db->getOne($sql);
                            if ($results && !empty($results)) {
                                $finalresult['result'] = 'error';
                                $finalresult['info'] = '该交易号已经在站内被充值过！';
                                exit(json_encode($finalresult));
                            }
                            $user_id = $_GET['user_id'];
                            $user_name = $_GET['user_name'];
                            $sql = 'SELECT trade_no from  ' . $paylogmodel->table . ' where trade_no="' . $tradeinfo ['order_title'] . '"';
                            $results = $paylogmodel->db->getOne($sql);
                            if ($results) {
                                $updateOuttrade = array(
                                    'out_trade_no' => $tradeinfo ['alipay_order_no'],
                                    'trade_status' => 1,
                                );
                                $paylogmodel->edit('trade_no="' . $tradeinfo ['order_title'] . '"', $updateOuttrade);
                            } else {
                                $condition ['out_trade_no'] = $tradeinfo ['alipay_order_no'];
                                $condition ['total_fee'] = $tradeinfo ['total_amount'];
                                $condition ['createtime'] = date("Y-m-d H:i:s", time());
                                $condition ['endtime'] = date("Y-m-d H:i:s", time());
                                $condition ['trade_status'] = 1;
                                $condition ['customer_id'] = $user_id;
                                $condition ['type'] = 0;
                                $condition ['trade_no'] = $tradeinfo ['order_title'];
                                $paylogmodel->add($condition);
                            }
//                            echo $paylogmodel->get_error();
//                            echo '站内充值成功';
                            $my_money_mod = $this->getMyDB($oem, 'my_money');
                            $my_moneylog_mod = $this->getMyDB($oem, 'my_moneylog');


                            $user_row = $my_money_mod->getrow("select money from " . DB_PREFIX . "my_money where user_id='$user_id'");
                            $user_money = $user_row['money'];
                            $user_jifen = $user_row['jifen'];

                            $new_money = $user_money + $totalfee;
                            $new_jifen = $user_jifen + $totalfee;
                            $edit_mymoney = array(
                                'money' => $new_money,
                            );
                            $edit_myjifen = array(
                                'jifen' => $new_jifen,
                            );
                            $my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
                            $my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);
                            //添加日志
                            $log_text = '无手续费充值'; //$this->visitor->get('user_name') . Lang::get('tongguoalipaychongzhi') . $total_fee . Lang::get('yuan');

                            $add_mymoneylog = array(
                                'user_id' => $user_id,
                                'user_name' => $user_name,
                                'buyer_name' => Lang::get('alipay'),
                                'seller_id' => $user_id,
                                'seller_name' => $user_name,
                                'order_sn ' => $tradeinfo ['alipay_order_no'],
                                'add_time' => gmtime(),
                                'leixing' => 30,
                                'money_zs' => $totalfee,
                                'money' => $totalfee,
                                'log_text' => $log_text,
                                'caozuo' => 4,
                                's_and_z' => 1,
                            );
                            $my_moneylog_mod->add($add_mymoneylog);
                            $done = true;
//                            echo 'year!.<br>';
                        }
                    }
                    if (!$done) {
                        $finalresult['result'] = 'error';
                        $finalresult['info'] = "没有找到对应的记录，充值失败!";
                        exit(json_encode($finalresult));
                    }
                } else {
                    $finalresult['result'] = 'error';
                    $finalresult['info'] = "没有记录，充值失败!";
                    exit(json_encode($finalresult));
                }
            } else {
                $finalresult['result'] = 'error';
                $finalresult['info'] = "数据异常，查询失败！";
                exit(json_encode($finalresult));
            }
            $finalresult['result'] = 'success';
            $finalresult['info'] = "充值成功!";
            exit(json_encode($finalresult));
        }
        if ($datas ['response_error']) {
            echo ( '交易记录查询失败！' );
        } else if ($datas ['alipay_user_trade_search_response']) {
            if ($datas ['alipay_user_trade_search_response'] ['trade_records'] ['trade_record'] [0]) {
                $tradeinfo = $datas ['alipay_user_trade_search_response'] ['trade_records'] ['trade_record'] [0];
                if ($tradeinfo ['alipay_order_no'] == $tradeno && $tradeinfo ['total_amount'] == $totalfee && $tradeinfo ['in_out_type'] == 'in') {
                    // 站内充值，更新账户余额
                    $condition ['out_trade_no'] = $tradeinfo ['alipay_order_no'];
                    $condition ['total_fee'] = $tradeinfo ['total_amount'];
                    $condition ['createtime'] = date("Y-m-d H:i:s", time());
                    $condition ['endtime'] = date("Y-m-d H:i:s", time());
                    $condition ['trade_status'] = 1;
                    $condition ['customer_id'] = $_POST ['customer_id'];
                    $condition ['type'] = 0;
                    if (!empty($oem) && $oem == 'nc') {
                        $paylogmodel = &sm('paylog');
                    } else {
                        $paylogmodel = &m('paylog');
                    }
                    $paylogmodel->add($condition);
                    echo '站内充值成功';
                    if (!empty($oem) && $oem == 'nc') {
                        $my_money_mod = & sm('my_money');
                        $my_moneylog_mod = & sm('my_moneylog');
                    } else {
                        $my_money_mod = & m('my_money');
                        $my_moneylog_mod = & m('my_moneylog');
                    }
                    $user_id = $_GET['user_id'];
                    $user_name = $_GET['user_name'];
                    $user_row = $my_money_mod->getrow("select money from " . DB_PREFIX . "my_money where user_id='$user_id'");
                    $user_money = $user_row['money'];
                    $user_jifen = $user_row['jifen'];

                    $new_money = $user_money + $totalfee;
                    $new_jifen = $user_jifen + $totalfee;
                    $edit_mymoney = array(
                        'money' => $new_money,
                    );
                    $edit_myjifen = array(
                        'jifen' => $new_jifen,
                    );
                    $my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
                    $my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);
                    //添加日志
                    $log_text = '无手续费充值'; //$this->visitor->get('user_name') . Lang::get('tongguoalipaychongzhi') . $total_fee . Lang::get('yuan');

                    $add_mymoneylog = array(
                        'user_id' => $user_id,
                        'user_name' => $user_name,
                        'buyer_name' => Lang::get('alipay'),
                        'seller_id' => $user_id,
                        'seller_name' => $user_name,
                        'order_sn ' => $tradeno,
                        'add_time' => gmtime(),
                        'leixing' => 30,
                        'money_zs' => $totalfee,
                        'money' => $totalfee,
                        'log_text' => $log_text,
                        'caozuo' => 4,
                        's_and_z' => 1,
                    );
                    $my_moneylog_mod->add($add_mymoneylog);
                    //          $this->show_message('chongzhi_chenggong_jineyiruzhang', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                    //           );
//					
                } else {
                    echo '充值失败，请联系管理员！';
                }
            } else {
                echo '交易号错误，充值失败！';
            }
        } else {
            echo '数据异常，查询失败！';
        }
    }

    function getTokentest() {
        $t = $_GET['t'];
        $t2 = $_GET['t2'];
        var_dump($this->getToken(APP_ID, 'dualven@163.com', $t, $t2));
    }

    function getToken($appid, $aliaccount, $t, $t2) {
        $c = new AopClient ();
        $c->appId = $appid;
        $c->rsaPrivateKeyFilePath = ROOT_PATH . "/rsa_private_key.pem";
        $c->format = "json";
        // 获取授权令牌
        $authtokenresp = &m('my_money_life');
        $sql = 'SELECT * from  ' . $authtokenresp->table . ' where appid ="' . $appid . '" and account="' . $aliaccount . '"';
//        echo $sql;
        $results = $authtokenresp->getAll($sql);
        if (!$results)
            return null;
        $authToken = $results [0];
        $nowtime = time();
        header("Content-type: text/html; charset= utf-8");
        // 授权令牌是否过期
        if ($nowtime > $authToken ['updatetime'] + $authToken ['expires_in'] || $t == '1') {
            if ($nowtime > $authToken ['updatetime'] + $authToken ['re_expires_in'] || $t2 == '1') {
                // 刷新令牌过期需要重新授权
//                header("location: https://openauth.alipay.com/oauth2/authorize.htm?client_id=" . APP_ID);
                $finalresult['result'] = 'auth';
                $finalresult['info'] = "https://openauth.alipay.com/oauth2/authorize.htm?client_id=" . $appid;
                return $finalresult;
            } else {
                // 刷新令牌未过期使用刷新令牌重新获得令牌
                $req = new AlipaySystemOauthTokenRequest ();
                $req->setGrantType("refresh_token");
                $req->setRefreshToken($authToken ['refresh_token']);
                $resp = $c->execute($req);
                $authdatas = (array) $resp;
//                print_r($authdatas);
                foreach ($authdatas as $key => $val) {
                    if (is_object($val)) {
                        $authdatas [$key] = (array) $val;
                    }
                }
                // 刷新令牌接口结果
                if ($authdatas ['error_response']) {
                    $finalresult['result'] = 'error';
                    $finalresult['info'] = "刷新令牌失败!";
                    return $finalresult;
                } else if ($authdatas ['alipay_system_oauth_token_response']) {
                    $authtokenresp = &m('my_money_life');
                    $authdatas ['alipay_system_oauth_token_response'] ['updatetime'] = time();
                    $authdatas ['alipay_system_oauth_token_response'] ['appid'] = $appid;
                    if ($authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] == 'rm-WA9PtAvq9izpb8RVJpvvLhDXsZt2KIWFyjTYu9OXXdNId5sa0eliZSlKtsXW-01') {
                        $authdatas ['alipay_system_oauth_token_response'] ['account'] = 'dualven@163.com';
                    } else if ($authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] == 'hQ8OQn962ewZMF5NjRNq9fvLhDXsZt2KIWFyjTYu9OXXdNId5sa0eliZSlKtsXW-01') {
                        $authdatas ['alipay_system_oauth_token_response'] ['account'] = '13524098465';
                    } else if ($authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] == '9Q6DetuE3AROFpBqSqICHPvLhDXsZt2KIWFyjTYu9OXXdNId5sa0eliZSlKtsXW-01') {
                        $authdatas ['alipay_system_oauth_token_response'] ['account'] = 'enterprise51zwd_dg@163.com';
                    } else if ($authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] == 'T4hrz7mtD8Gm50w5NWivW-vLhDXsZt2KIWFyjTYu9OXXdNId5sa0eliZSlKtsXW-01') {
                        $authdatas ['alipay_system_oauth_token_response'] ['account'] = 'enterprise51zwd@163.com';
                    }
                    array_splice($authdatas ['alipay_system_oauth_token_response'], 5, 1);
                    $authtokenresp->edit('alipay_user_id="' . $authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] . '"', $authdatas ['alipay_system_oauth_token_response']);
                    $accesstoken = $authdatas ['alipay_system_oauth_token_response'] ['access_token'];
                    $finalresult['result'] = 'success';
                    $finalresult['info'] = $accesstoken;
//                    echo '系统数据刷新令牌成功！';
                    return $finalresult;
                } else {
                    $finalresult['result'] = 'refresh';
                    $finalresult['info'] = "支付宝数据异常!";
                    return $finalresult;
                }
            }
        } else {
            $finalresult['result'] = 'success';
            $finalresult['info'] = $authToken['access_token'];
            return $finalresult;
        }
    }

    private function getData($resp1) {
        $datas = (array) $resp1;
        foreach ($datas as $key => $val) {
            if (is_object($val)) {
                $datas [$key] = (array) $val;

                foreach ($datas [$key] as $key1 => $val1) {
                    if (is_object($val1)) {
                        $datas [$key] [$key1] = (array) $val1;

                        foreach ($datas [$key] [$key1] as $key2 => $val2) {
                            if (is_object($val2)) {
                                $datas [$key] [$key1] [$key2] = (array) $val2;
                            }
                            foreach ($datas [$key] [$key1] [$key2] as $key3 => $val3) {
                                if (is_object($val3)) {
                                    $datas [$key] [$key1] [$key2] [$key3] = (array) $val3;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $datas;
    }

    private function getResp($req1, $pageno, $accesstoken) {
        $c = new AopClient ();
        $c->appId = APP_ID;
        $c->rsaPrivateKeyFilePath = ROOT_PATH . "/rsa_private_key.pem";
        $c->format = "json";
        $req1->setPageNo($pageno);
        $resp1 = $c->execute($req1, $accesstoken);
        return $resp1;
    }

    /**
     * 查询当天的 
     */
    function tradeSpec() {
        // 获取授权令牌
        $oem = empty($_POST['oem']) ? $_GET['oem'] : $_POST['oem'];
        $aliaccount = $this->getAliAccount($oem);
        $tokens = $this->getToken(APP_ID, $aliaccount, '', '');
        if ($tokens && $tokens['result'] == 'success') {
            $accesstoken = $tokens['info'];
        } else {
            $finalresult['result'] = 'error';
            $finalresult['info'] = "get token failed!";
            exit(json_encode($finalresult));
        }
        $req1 = new AlipayUserTradeSearchRequest ();
        $req1->setPageSize(ALI_PAGE_SIZE);
        $end_time = date('Y-m-d H:i:s', strtotime('+1 day'));
        $req1->setEndTime($end_time);
        $start_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d')));
        //$start_time = date('Y-m-d H:i:s', time() - 2 * 24 * 60 * 60 + 1);
        $req1->setStartTime($start_time);
        $resp1 = $this->getResp($req1, "1", $accesstoken);
        $datas = $this->getData($resp1);
//        print_r($datas);
        if ($datas ['response_error']) {
            $finalresult['result'] = 'error';
            $finalresult['info'] = "交易记录查询失败!";
            exit(json_encode($finalresult));
        } else if ($datas ['alipay_user_trade_search_response']) {
            $count = $datas ['alipay_user_trade_search_response'] ['total_results'];
            if ($count > 0) {
                for ($j = 0; $j < intval($count / ALI_PAGE_SIZE) + 1; $j++) {//页码
                    if ($j > 0) {
                        $resp1 = $this->getResp($req1, $j + 1, $accesstoken);
                        $datas = $this->getData($resp1);
                    }
                    for ($i = 0; $i < (ALI_PAGE_SIZE < ($count - $j * ALI_PAGE_SIZE) ? ALI_PAGE_SIZE : ($count - $j * ALI_PAGE_SIZE)); $i++) {
                        $tradeinfo = $datas ['alipay_user_trade_search_response'] ['trade_records'] ['trade_record'] [$i];
                        if ($tradeinfo ['in_out_type'] == 'in'
                                && ($tradeinfo ['order_status'] == 'TRADE_FINISHED' || $tradeinfo ['order_status'] == 'TRADE_SUCCESS' || $tradeinfo ['order_status'] == 'TRANSFER_WITHDRAW_SUCCESS')) {
                            $title = $tradeinfo ['order_title'];
                            $tradeno = $tradeinfo ['alipay_order_no'];
                            $money = $tradeinfo ['total_amount'];
                            $totalfee += $tradeinfo ['total_amount'];
                            $spec[$title] = array('money' => $money, 'title' => $title, 'tradeno' => $tradeno);
                        }
                    }
                }
            }
            $finalresult['result'] = 'success';
            $finalresult['total'] = $totalfee;
            $finalresult['spec'] = $spec;
            exit(json_encode($finalresult));
        }
    }

    /**
     * 查询某一条的数据
     */
    function tradeSpec2() {
        $tradeno = empty($_POST['tradeno']) ? $_GET['tradeno'] : $_POST['tradeno'];
        $oem = empty($_POST['oem']) ? $_GET['oem'] : $_POST['oem'];
        if (empty($tradeno)) {
            $finalresult['result'] = 'error';
            $finalresult['info'] = "交易记录查询失败!";
            exit(json_encode($finalresult));
        }
        // 获取授权令牌
        $aliaccount = $this->getAliAccount($oem);
        $tokens = $this->getToken(APP_ID, $aliaccount, '', '');
        if ($tokens && $tokens['result'] == 'success') {
            $accesstoken = $tokens['info'];
        } else {
            $finalresult['result'] = 'error';
            $finalresult['info'] = "get token failed!";
            exit(json_encode($finalresult));
        }
        // 交易记录查询接口
        $req1 = new AlipayUserTradeSearchRequest ();
        // 每页获取条数
        $nowtime = time();
        $req1->setPageSize(ALI_PAGE_SIZE);
        $end_time = date('Y-m-d H:i:s', $nowtime);
        $start_time = date('Y-m-d H:i:s', $nowtime - 1 * 24 * 60 * 60 + 1);
        $req1->setEndTime($end_time);
        if (!empty($tradeno)) {
            $req1->setAlipayOrderNo($tradeno);
        }
        $req1->setStartTime($start_time);
        $resp1 = $this->getResp($req1, "1", $accesstoken);
        $datas = $this->getData($resp1);
//        print_r($datas);
        if ($datas ['response_error']) {
            $finalresult['result'] = 'error';
            $finalresult['info'] = "交易记录查询失败!";
            exit(json_encode($finalresult));
        } else if ($datas ['alipay_user_trade_search_response']) {
            $count = $datas ['alipay_user_trade_search_response'] ['total_results'];
            if ($count > 0) {//其实只有一个
                for ($i = 0; $i < $count; $i++) {
                    $tradeinfo = $datas ['alipay_user_trade_search_response'] ['trade_records'] ['trade_record'] [$i];
                    if ($tradeinfo ['in_out_type'] == 'in'
                            && ($tradeinfo ['order_status'] == 'TRADE_FINISHED' || $tradeinfo ['order_status'] == 'TRADE_SUCCESS' || $tradeinfo ['order_status'] == 'TRANSFER_WITHDRAW_SUCCESS')
                            && $tradeinfo ['alipay_order_no'] == $tradeno) {
                        $title = $tradeinfo ['order_title'];
                        $money = $tradeinfo ['total_amount'];
                        $finalresult['title'] = $title;
                        $finalresult['totalfee'] = $money;
                        $finalresult['create_time'] = $tradeinfo ['create_time'];
                    }
                }
            }
            $finalresult['result'] = 'success';
            exit(json_encode($finalresult));
        }
    }

    /**
     * 基于trade的旧版本
     * @return type 
     */
    public function checkMoney() {
        //(1)得到两边今日的金额
        //支付宝的
        $paylog_mod = & m('paylog');
        $my_moneylog_mod = & m('my_moneylog');
        $url = 'http://' . MONEYSITE . '/index.php?app=default&act=tradeSpec&oem=' . OEM;
        $aliM = @json_decode($this->Get($url), true);
        if ($aliM) {
            if ($aliM['result'] == 'success') {
                $alimoney = $aliM['total'];
            } else {
                return;
            }
        } else {
            return;
        }
        $force = false;
        if ($_GET['force'] && $_GET['force'] == 'yes') {
            $force = true;
        }
        $index = $my_moneylog_mod->find(array(
            'conditions' => 'leixing in(30, 40 ) and caozuo in (50,4)  and user_log_del = 0 and admin_time> ' . gmstr2time(date('Y-m-d')),
            'order' => "id desc",
            'count' => true));
        $page['item_count'] = $my_moneylog_mod->getCount();
        foreach ($index as $var) {
            $total += $var['money'];
        }
        $checkresult['ali'] = $alimoney;
        $checkresult['site'] = $total;
        $checkresult['check'] = 'no';
        $checkresult['num'] = 0;
        $checkresult['suc'] = 0;
        $checkresult['failed'] = 0;
        $oem = OEM;
        if ($total < $alimoney || $force) {
            $index = $paylog_mod->find(array(
                'conditions' => 'createtime > CURDATE()-1 and trade_status =1 ',
                'limit' => $page['limit'],
                'order' => "createtime desc",
                'count' => true));
            $page['item_count'] = $paylog_mod->getCount();
            foreach ($index as $var) {
                $sitekey[] = $var['out_trade_no'];
            }
            foreach ($aliM['spec'] as $stat => $v) {
                $t = in_array($v['tradeno'], $sitekey, true);
                if (!$t) {
                    $checkresult['check'] = 'yes';
                    $money = $v['money'];
                    $memo = $v['title'];
                    $key = $v['tradeno']; //　ali_pay_order

                    unset($spec);
                    $spec[$key] = $key;
                    $spec['money'] = $money;
                    $spec['title'] = $memo; {
                        $r = $this->parseMemo($memo, $titleG, $user_idG);
                        if (!$r || !$user_idG || !$titleG) {
                            $spec['result'] = 'failed';
                            $checkresult['failed']++;
                            $checkresult[$key] = $spec;
                            $checkresult['num']++;
                            continue;
                        }
                        $user_id = $user_idG;
                        $user_name = '';
                        $total_fee = $money;
                        $title = $titleG;
                        //直接走有tradeno的接口
//                        $url = "http://" . MONEYSITE . "/index.php?app=default&act=searchtrade&title=" . $title . "&totalfee=" . $total_fee . "&user_id=" . $user_id . "&user_name=" . $user_name . "&oem=" . $oem;
                        //不要title ----------------------但是有trade_no;允许传title进去吧
                        $url = "http://" . MONEYSITE . "/index.php?app=default&act=searchtrade"
                                . "&totalfee=" . $total_fee . "&user_id=" .
                                $user_id . "&user_name=" . $user_name .
                                "&oem=" . $oem . "&tradeno=" . $key;
                        $res = @json_decode($this->Get($url), true);
                        if ($res['result'] == 'success') {
                            $updateOuttrade = array(
                                'status' => 1, //成功
                            );
                            $paylog_mod->edit('trade_no="' . $title . '"', $updateOuttrade);
                            $spec['result'] = 'success';
                            $checkresult['suc']++;
                        } else if ($res['result'] == 'error') {
                            $updateOuttrade = array(
                                'status' => 2, //失败
                                'out_trade_no' => mt_rand(10000, 99999) . $res['info'],
                            );
                            if (strpos($res['info'], 'used-b') > 0 || strpos($res['info'], '被充值过') > 0) {
                                //do nothing  alipay_order_no-used-b Ex
                            } else {
                                $paylog_mod->edit('trade_no="' . $title . '"', $updateOuttrade);
                            }
                            $spec['result'] = 'failed';
                            $checkresult['failed']++;
                        }
                    }
                    $checkresult[$key] = $spec;
                    $checkresult['num']++;
                }
            }

//            print_r($checkresult);
        } else if ($total > $alimoney) {
//            echo ' site m > ali m!!';
        } else {// ==
//            echo 'equal ';
        }
        $smail = get_mail('sms_money_notify', array('money' => $checkresult));
        $this->sendSaleSms('15900402562', addslashes($smail['message']));

        $result = var_export($checkresult, true);
        Log::write($result);
        exit(json_encode($checkresult));
        //(2) 如果不一致，则对可能的进行处理
        //处理完了，对有稽核的进行通知
    }

    /**
     * 基于account的新版本
     * @return type 
     */
    public function checkMoney2() {
        //(1)得到两边今日的金额
        //支付宝的
        if (MONEYSITE == 'yjsk.51zwd.com') {
            return $this->checkMoney();
        }
        $paylog_mod = & m('paylog');
        $my_moneylog_mod = & m('my_moneylog');
        $url = 'http://' . MONEYSITE . '/index.php?app=default&act=financeSpec';
        $aliM = @json_decode($this->Get($url), true);
        if ($aliM) {
            if ($aliM['result'] == 'success') {
                $alimoney = $aliM['total'];
            } else {
                return;
            }
        } else {
            return;
        }
        $force = false;
        if ($_GET['force'] && $_GET['force'] == 'yes') {
            $force = true;
        }
        $index = $my_moneylog_mod->find(array(
            'conditions' => 'leixing in(30, 40 ) and caozuo in (50,4)  and user_log_del = 0 and admin_time> ' . gmstr2time(date('Y-m-d')),
            'order' => "id desc",
            'count' => true));
        $page['item_count'] = $my_moneylog_mod->getCount();
        foreach ($index as $var) {
            $total += $var['money'];
        }
        $checkresult['ali'] = $alimoney;
        $checkresult['site'] = $total;
        $checkresult['check'] = 'no';
        $checkresult['num'] = 0;
        $checkresult['suc'] = 0;
        $checkresult['failed'] = 0;
        $oem = OEM;
        if ($total < $alimoney || $force) {
            $index = $paylog_mod->find(array(
                'conditions' => 'createtime > CURDATE()-1 and trade_status =1 ',
                'limit' => $page['limit'],
                'order' => "createtime desc",
                'count' => true));
            $page['item_count'] = $paylog_mod->getCount();
            foreach ($index as $var) {
                $sitekey[] = $var['out_trade_no'];
            }
            foreach ($aliM['spec'] as $stat => $v) {
                $t = in_array($stat, $sitekey, true);
                if (!$t) {
                    $checkresult['check'] = 'yes';
                    $time = $v['create_time'];
                    $money = $v['money'];
                    if (!$money) {
                        continue;
                    }
                    $memo = $v['memo'];
                    $key = $stat; //　ali_pay_order

                    unset($spec);
                    $spec[$key] = $key;
                    $spec['money'] = $money;
                    $spec['time'] = $time;

                    $urlts = 'http://' . MONEYSITE . '/index.php?app=default&act=tradeSpec2&tradeno=' . $stat;
                    $ts = @json_decode($this->Get($urlts), true);
                    Log::write('in ts : ' . $ts['result']);
                    if ($ts['result'] == 'success' && !empty($ts['title'])) {
                        //如果可以在trade里找到相应的记录，就去找title 
                        $title = $ts['title'];
                        $total_fee = $ts['totalfee'];
                        $time = $ts['create_time'];
                        $issuc = $this->parseMemo($title, $newtitle, $user_id);
                        if (!$issuc) {
                            $spec['result'] = 'failed';
                            $checkresult['failed']++;
                            $checkresult[$key] = $spec;
                            $checkresult['num']++;
                            continue;
                        }
                        $sqlts = 'select * from ecm_paylog where trade_no="' . $newtitle . '" and trade_status=0'; //neccessary
                        Log::write('in ts $sqlts : ' . $sqlts);
                        $resultts = $paylog_mod->db->getRow($sqlts);
                        if ($resultts) {
                            $user_id = $resultts['customer_id'];
                            $user_name = $resultts['customer_name'];
                            //不要title ----------------------但是有trade_no;允许传title进去吧
                            $url = "http://" . MONEYSITE . "/index.php?app=default&act=searchtrade"
                                    . "&title=" . $newtitle . "&totalfee=" . $total_fee . "&user_id=" .
                                    $user_id . "&user_name=" . $user_name .
                                    "&oem=" . $oem . "&tradeno=" . $stat . "&time=" . $time;
                            Log::write('in ts $url last : ' . $url);
                            $res = @json_decode($this->Get($url), true);
                            if ($res['result'] == 'success') {
                                $updateOuttrade = array(
//                                        'out_trade_no' => $stat,
                                    'status' => 1, //成功
                                );
                                $paylog_mod->edit('trade_no="' . $title . '"', $updateOuttrade);
                                $spec['result'] = 'success';
                                $checkresult['suc']++;
                            } else if ($res['result'] == 'error') {
                                $updateOuttrade = array(
                                    'status' => 2, //失败
                                    'out_trade_no' => mt_rand(10000, 99999) . $res['info'],
                                );
                                //2015p-07-29因为并发的问题,有时候会对已经成功的进行了重写,所以要判断回来的值
                                if (strpos($res['info'], 'used-b') > 0) {
                                    //do nothing  alipay_order_no-used-b Ex
                                } else {
                                    $paylog_mod->edit('trade_no="' . $title . '"', $updateOuttrade);
                                }

                                $spec['result'] = 'failed';
                                $checkresult['failed']++;
                            }
                        } else {
                            $spec['result'] = 'failed';
                            $checkresult['failed']++;
                        }
                    } else {
                        $r = $this->parseMemo($memo, $titleG, $user_idG);
                        if (!$r || !$user_idG || !$titleG) {
                            $spec['result'] = 'failed';
                            $checkresult['failed']++;
                            $checkresult[$key] = $spec;
                            $checkresult['num']++;
                            continue;
                        }
//                        $sqla = 'select * from (select   time_to_sec(timediff("' . $time . '",l.createtime)) as offset,l.total_fee ,l.trade_no, l.out_trade_no,l.createtime,l.customer_id,l.customer_name  from ecm_paylog l where l.total_fee=' . $money . ' and l.trade_status =0)a where a.offset > 0 and a.offset < 240';
//                        $resulta = $paylog_mod->db->getRow($sqla);
//                        if (!$resulta || empty($resulta)) {
//                            $spec['result'] = 'failed';
//                            $checkresult['failed']++;
//                            $checkresult[$key] = $spec;
//                            $checkresult['num']++;
//                            continue;
//                        }
//                        $user_id = $resulta['customer_id'];
//                        $user_name = $resulta['customer_name'];
//                        $total_fee = $resulta['total_fee'];
//                        $title = $resulta['trade_no'];
                        $user_id = $user_idG;
                        $user_name = '';
                        $total_fee = $money;
                        $title = $titleG;
                        //直接走有tradeno的接口
//                        $url = "http://" . MONEYSITE . "/index.php?app=default&act=searchtrade&title=" . $title . "&totalfee=" . $total_fee . "&user_id=" . $user_id . "&user_name=" . $user_name . "&oem=" . $oem;
                        //不要title ----------------------但是有trade_no;允许传title进去吧
                        $url = "http://" . MONEYSITE . "/index.php?app=default&act=searchtrade"
                                . "&title=" . $title . "&totalfee=" . $total_fee . "&user_id=" .
                                $user_id . "&user_name=" . $user_name .
                                "&oem=" . $oem . "&tradeno=" . $stat;
                        $url.= "&time=" . strtotime($time);
//                        $url = str_replace(' ', '%', $url); //时间带空格了
                        $res = @json_decode($this->Get($url), true);
                        if ($res['result'] == 'success') {
                            $updateOuttrade = array(
                                'status' => 1, //成功
                            );
                            $paylog_mod->edit('trade_no="' . $title . '"', $updateOuttrade);
                            $spec['result'] = 'success';
                            $checkresult['suc']++;
                        } else if ($res['result'] == 'error') {
                            $updateOuttrade = array(
                                'status' => 2, //失败
                                'out_trade_no' => mt_rand(10000, 99999) . $res['info'],
                            );
                            if (strpos($res['info'], 'used-b') > 0) {
                                //do nothing  alipay_order_no-used-b Ex
                            } else {
                                $paylog_mod->edit('trade_no="' . $title . '"', $updateOuttrade);
                            }
                            $spec['result'] = 'failed';
                            $checkresult['failed']++;
                        }
                    }
                    $checkresult[$key] = $spec;
                    $checkresult['num']++;
                }
            }

//            print_r($checkresult);
        } else if ($total > $alimoney) {
//            echo ' site m > ali m!!';
        } else {// ==
//            echo 'equal ';
        }
        $smail = get_mail('sms_money_notify', array('money' => $checkresult));
        $this->sendSaleSms('15900402562', addslashes($smail['message']));

        $result = var_export($checkresult, true);
        Log::write($result);
        exit(json_encode($checkresult));
        //(2) 如果不一致，则对可能的进行处理
        //处理完了，对有稽核的进行通知
    }

    public function alipayback() {

        $c = new AopClient ();
        $c->appId = APP_ID;
        $c->rsaPrivateKeyFilePath = ROOT_PATH . "/rsa_private_key.pem";
        $c->format = "json";
        // 根据code获取授权令牌接口
        $req = new AlipaySystemOauthTokenRequest ();
        $req->setGrantType("authorization_code");
        $req->setCode($_GET ['code']);
        echo 'the code is ' . $_GET ['code'];
        $resp = $c->execute($req);
        $authdatas = (array) $resp;
        foreach ($authdatas as $key => $val) {
            if (is_object($val)) {
                $authdatas [$key] = (array) $val;
            }
        }
        // $authdatas = json_decode ( $resp, true );
        header("Content-type: text/html; charset= utf-8");
        if ($authdatas ['error_response']) {
            echo "授权失败！";
            print_r($authdatas);
        } else if ($authdatas ['alipay_system_oauth_token_response']) {
            echo ' now we are in $authdatas  ';
            print_r($authdatas);
            $authtokenresp = &m('my_money_life'); //M ( 'authtokenresp' );
            // 删除原来授权令牌，用于更换支付宝账户
            $authtokenresp->drop(array('alipay_user_id' => $authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id']));
//            $authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] = 1;
            $authdatas ['alipay_system_oauth_token_response'] ['updatetime'] = time();
            $authdatas ['alipay_system_oauth_token_response'] ['appid'] = APP_ID;
            if ($authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] == 'rm-WA9PtAvq9izpb8RVJpvvLhDXsZt2KIWFyjTYu9OXXdNId5sa0eliZSlKtsXW-01') {
                $authdatas ['alipay_system_oauth_token_response'] ['account'] = 'dualven@163.com';
            } else if ($authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] == 'hQ8OQn962ewZMF5NjRNq9fvLhDXsZt2KIWFyjTYu9OXXdNId5sa0eliZSlKtsXW-01') {
                $authdatas ['alipay_system_oauth_token_response'] ['account'] = '13524098465';
            } else if ($authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] == '9Q6DetuE3AROFpBqSqICHPvLhDXsZt2KIWFyjTYu9OXXdNId5sa0eliZSlKtsXW-01') {
                $authdatas ['alipay_system_oauth_token_response'] ['account'] = 'enterprise51zwd_dg@163.com';
            } else if ($authdatas ['alipay_system_oauth_token_response'] ['alipay_user_id'] == 'T4hrz7mtD8Gm50w5NWivW-vLhDXsZt2KIWFyjTYu9OXXdNId5sa0eliZSlKtsXW-01') {
                $authdatas ['alipay_system_oauth_token_response'] ['account'] = 'enterprise51zwd@163.com';
            }
            $result = $authtokenresp->add($authdatas ['alipay_system_oauth_token_response'], true);
            echo '授权成功1';
//			if ($result) {
//				echo "授权令牌更新成功！";
//			} else {
//				echo "1系统数据异常！". $result;
//			}
        } else {
            echo "支付宝授权数据异常！";
            print_r($resp);
        }
    }

    public function remoteimage() {
        import('image.func');
        import('uploader.lib');
        $uploader = new Uploader();
        $uploader->allowed_type(IMAGE_FILE_TYPE);
        $uploader->allowed_size(SIZE_GOODS_IMAGE); // 2M
        $upload_mod = & m('uploadedfile');
        $uploader->root_dir(ROOT_PATH);
        $dirname = '';
        $remote_url = $_GET['remote_url'];
        echo $remote_url;
        $id = $_GET['id'];
        echo $id;
        $goodid = $_GET['goodid'];
        $_goods_mod = & m('goods');
        $goods_info = $_goods_mod->get_basic_info($goodid);
        $id = $goods_info['store_id'];
        $remote_url = $goods_info['default_image'];

        echo 'the store id is :' . $id . '<br>';
        echo 'the url :' . $remote_url . '<br>';
        if (!empty($remote_url)) {
            if (preg_match("/^(http:\/\/){1,1}.+(gif|png|jpeg|jpg){1,1}$/i", $remote_url)) {

                $img_url = _at('file_get_contents', $remote_url);
                $dirname = '';
                if (true) {
                    $dirname = 'data/files/store_' . $id . '/goods_' . (time() % 200);
                }
                $filename = $uploader->random_filename();
                $new_url = $dirname . '/' . $filename . '.' . substr($remote_url, strrpos($remote_url, '.') + 1);
                ecm_mkdir(ROOT_PATH . '/' . $dirname);
                $fp = _at('fopen', ROOT_PATH . '/' . $new_url, "w");
                _at('fwrite', $fp, $img_url);
                _at('fclose', $fp);
                if (!file_exists(ROOT_PATH . '/' . $new_url)) {
                    $res = Lang::get("system_error");
                    echo "<script type='text/javascript'>alert('{$res}');</script>";
                    return false;
                }
                /* 处理文件入库 */
                $data = array(
                    'store_id' => $id,
                    'file_type' => $this->_return_mimetype(ROOT_PATH . '/' . $new_url),
                    'file_size' => filesize(ROOT_PATH . '/' . $new_url),
                    'file_name' => substr($remote_url, strrpos($remote_url, '/') + 1),
                    'file_path' => $new_url,
                    'belong' => 2,
                    'item_id' => $goodid,
                    'add_time' => gmtime(),
                );
                $file_id = $upload_mod->add($data);
                if (!$file_id) {
                    $this->_error($upload_mod->get_error());
                    echo 'it is wrong';
                    return false;
                }

                if (true) { // 如果是上传商品相册图片
                    /* 生成缩略图 */
                    $thumbnail = dirname($new_url) . '/small_' . basename($new_url);
                    make_thumb(ROOT_PATH . '/' . $new_url, ROOT_PATH . '/' . $thumbnail, THUMB_WIDTH, THUMB_HEIGHT, THUMB_QUALITY);
                    echo 'after thumb';
                    /* 更新商品相册 */
                    $mod_goods_image = &m('goodsimage');
                    $goods_image = array(
                        'goods_id' => $goodid,
                        'image_url' => $new_url,
                        'thumbnail' => $thumbnail,
                        'sort_order' => 255,
                        'file_id' => $file_id,
                    );
                    if (!$mod_goods_image->add($goods_image)) {
                        $this->_error($this->mod_goods_imaged->get_error());
                        return false;
                    }
                    $data['thumbnail'] = $thumbnail;
                }

                echo "<script type='text/javascript'>alert('{$res}');</script>";
                $goods_info['default_image'] = $new_url;
//             $goods_info = $this->array_remove_key($goods_info,'state');
//               unset($goods_info['state']);
//               unset($goods_info['views']);
//               unset($goods_info['collects']);
//                 unset($goods_info['carts']);
//                unset($goods_info['orders']);
                echo $goods_info['default_image'] . $goodid;
                if (!$_goods_mod->edit($goodid, $goods_info)) {
                    $this->_error($this->_goods_mod->get_error());
                    return false;
                }
                return true;
            } else {
                $res = Lang::get('url_invalid');
                echo "<script type='text/javascript'>alert('{$res}');</script>";
                return false;
            }
        } else {
            $res = Lang::get('remote_empty');
            echo "<script type='text/javascript'>alert('{$res}');</script>";
            return false;
        }
    }

    function array_remove_key($array, $keys) {

        if (!is_array($array) || !is_array($keys)) {

            return false;
        }
        foreach ($array as $t) {

            foreach ($keys as $k) {

                unset($t[$k]);
            }

            $doc[] = $t;
        }

        return $doc;
    }

    function _return_mimetype($filename) {
        preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);
        switch (strtolower($fileSuffix[1])) {
            case "js" :
                return "application/x-javascript";

            case "json" :
                return "application/json";

            case "jpg" :
            case "jpeg" :
            case "jpe" :
                return "image/jpeg";

            case "png" :
            case "gif" :
            case "bmp" :
            case "tiff" :
                return "image/" . strtolower($fileSuffix[1]);

            case "css" :
                return "text/css";

            case "xml" :
                return "application/xml";

            case "doc" :
            case "docx" :
                return "application/msword";

            case "xls" :
            case "xlt" :
            case "xlm" :
            case "xld" :
            case "xla" :
            case "xlc" :
            case "xlw" :
            case "xll" :
                return "application/vnd.ms-excel";

            case "ppt" :
            case "pps" :
                return "application/vnd.ms-powerpoint";

            case "rtf" :
                return "application/rtf";

            case "pdf" :
                return "application/pdf";

            case "html" :
            case "htm" :
            case "php" :
                return "text/html";

            case "txt" :
                return "text/plain";

            case "mpeg" :
            case "mpg" :
            case "mpe" :
                return "video/mpeg";

            case "mp3" :
                return "audio/mpeg3";

            case "wav" :
                return "audio/wav";

            case "aiff" :
            case "aif" :
                return "audio/aiff";

            case "avi" :
                return "video/msvideo";

            case "wmv" :
                return "video/x-ms-wmv";

            case "mov" :
                return "video/quicktime";

            case "rar" :
                return "application/x-rar-compressed";

            case "zip" :
                return "application/zip";

            case "tar" :
                return "application/x-tar";

            case "swf" :
                return "application/x-shockwave-flash";

            default :
                if (function_exists("mime_content_type")) {
                    $fileSuffix = mime_content_type($filename);
                }
                return "unknown/" . trim($fileSuffix[0], ".");
        }
    }

    function notifyurl_51() {
        require_once("data/config.alipay.php");
        // 计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();

        if ($verify_result) {
            echo 'it is right!';
//            $out_trade_no = $_GET ['out_trade_no'];
            $trade_no = $_GET ['out_trade_no'];
            $total_fee = $_GET ['total_fee'];

            if ($_GET ['trade_status'] == 'TRADE_FINISHED' || $_GET ['trade_status'] == 'TRADE_SUCCESS') {
                if ($_GET ['trade_status'] == 'TRADE_SUCCESS') {
                    $trade_status = 1;
                } else if ($_GET ['trade_status'] == 'TRADE_FINISHED') {
                    $trade_status = 2;
                }
                $this->mymoney(null, null, $trade_no, $total_fee);
            }
        } else {
            echo 'it is wrong!';
        }
    }

    function returnurl_51() {
        require_once("data/config.alipay.php");
        // 计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();

        if ($verify_result) {
            echo 'it is right!';
//            $out_trade_no = $_GET ['out_trade_no'];
            $trade_no = $_GET ['out_trade_no'];
            $total_fee = $_GET ['total_fee'];

            if ($_GET ['trade_status'] == 'TRADE_FINISHED' || $_GET ['trade_status'] == 'TRADE_SUCCESS') {
                if ($_GET ['trade_status'] == 'TRADE_SUCCESS') {
                    $trade_status = 1;
                } else if ($_GET ['trade_status'] == 'TRADE_FINISHED') {
                    $trade_status = 2;
                }
                $user_id = $this->visitor->get('user_id');
                $user_name = $this->visitor->get('user_name');
//                header("location: http://yjsk.51zwd.com/index.php?app=default&act=mymoney&tradeno=" . $trade_no . "&totalfee=" . $total_fee . "&user_id=" . $user_id . "&user_name=" . $user_name);
                $this->mymoney($user_id, $user_name, $trade_no, $total_fee);
            }
        } else {
            echo 'it is wrong!';
        }
    }

    function mymoney($user_id, $user_name, $tradeno, $total_fee, $user_log_del = 0) {
        $my_money_mod = & m('my_money');
        $my_moneylog_mod = & m('my_moneylog');
        if ($user_log_del == 0) {
            $res = $my_moneylog_mod->edit('order_sn="' . $tradeno . '" and money=' . $total_fee, array('user_log_del' => 0));
            if (!$res) {
                $this->show_message('failed for check in!');
                return;
            }
            if (empty($user_id)) {
                $user_id = $my_moneylog_mod->get(array('order_sn' => $tradeno));
                $user_id = $user_id['user_id'];
            }
            $user_row = $my_money_mod->getrow("select money from ecm_my_money where user_id=" . $user_id);

            $user_money = $user_row['money'];
            $user_jifen = $user_row['jifen'];
            $new_money = $user_money + $total_fee;
            $new_jifen = $user_jifen + $total_fee;
            $edit_mymoney = array(
                'money' => $new_money,
            );
            $edit_myjifen = array(
                'jifen' => $new_jifen,
            );
            $my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
            $my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);
        }
    }

    /**
     *  一键提交新款
     */
    function submit_newgoods() {
        if (!IS_POST) {
            $this->display("my_goods.submit_newgoods.html");
        } else {
            
        }
    }

    /**
     * 用于界面ajax登录
     */
    function loginWithAjax() {
        if (!IS_POST) {
            if (Conf::get('captcha_status.login')) {
                $this->assign('captcha', 1);
            }
            $this->display('login.ajax.html');
        } else {
            if (Conf::get('captcha_status.login') && base64_decode($_SESSION['captcha']) != strtolower($_POST['captcha'])) {
                $this->show_warning('captcha_failed');

                return;
            }

            $user_name = trim($_POST['user_name']);
            $password = $_POST['password'];

            $ms = & ms();
            $user_id = $ms->user->auth($user_name, $password);
            if (!$user_id) {
                /* 未通过验证，提示错误信息 */
                //$this->show_warning($ms->user->get_error());
                echo "<script type='text/javascript'>window.parent.jbox_close('" . Lang::get('login_failed') . "');</script>";
                //$this->json_result(0,Lang::get('login_failed'));
                return;
            } else {
                /* 通过验证，执行登陆操作 */
                $this->_do_login($user_id);

                /* 同步登陆外部系统 */
                $synlogin = $ms->user->synlogin($user_id);
            }
            //$this->json_result(1,Lang::get('login_successed'));
            echo "<script type='text/javascript'>window.parent.jbox_close('" . Lang::get('login_successed') . "');</script>";
            //$this->show_message(Lang::get('login_successed') . $synlogin);
        }
    }

    /**
     * spec : refresh sphinx! refresh mem! change current sphinx!
     * @author : duanxiongwen
     * @date: 2015-06-01 
     * first_sphinx ,second_sphinx:确定的是相对系统而言，谁主谁备，这个是确定的
     * current,assistant,对某一时刻而言，这个是时刻变化着的
     */
    function refreshSphinx() {
        //没定义主备，就直接用本地的！
        if (!defined('FIRST_SPHINX') || !defined('SECOND_SPHINX')) {
            return;
        }
        $cache_server = & cache_server();
        $clearmem = empty($_POST['clearmem']) ? $_GET['clearmem'] : $_POST['clearmem'];
        if ($clearmem && $clearmem == 'clear') {
            $cache_server->set('currentSphinx', 0);
            $cache_server->set('assistantSphinx', 0);
        }

        $current = $cache_server->get('currentSphinx');
        if (!$current) {
            $current = FIRST_SPHINX;
            $cache_server->set('currentSphinx', '127.0.0.1', 0); //先用本地的，等更新好了再用主备
        }
        $assistant = $cache_server->get('assistantSphinx');
        if (!$assistant) {
            $assistant = SECOND_SPHINX;
            $cache_server->set('assistantSphinx', SECOND_SPHINX, 0); //备用的可以先设置好
        }
        //默认启动时，或者memcache初始化时，用的是127.0.0.1的，所以要先进行备用的更新
        $_GET['serverAddress'] = $assistant;
        $res = $this->refreshLocalSphinx();
        $res = @json_decode($res, true);
//        echo 'then i print ';
//        print_r($res);
        //更新memcache
        $assiConfN['last_update_time'] = date('Y-m-d H:i:s', time());
        $assiConfN['last_update_result'] = $res;
        $cache_server->set('value_' . $assistant, $assiConfN, 0);
//        echo '<br> set assistant vlue';
        //切换主备
        if ($res['result'] == 'success') {
            $cache_server->set('currentSphinx', $assistant, 0);
            $cache_server->set('assistantSphinx', $current, 0);

            $refresh['server'] = $assistant;
            $refresh['result'] = 'success';
            $refresh['time'] = $assiConfN['last_update_time'];
            $result = var_export($refresh, true);
            Log::write('in default: ' . $result);
            exit(json_encode($refresh));
        } else {
            $refresh['server'] = $assistant;
            $refresh['result'] = 'failed';
            $refresh['time'] = $assiConfN['last_update_time'];
            $result = var_export($refresh, true);
            Log::write('in default : ' . $result);
            exit(json_encode($refresh));
        }
    }

    function Get($sms) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $sms);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
        $output = curl_exec($curl);
        return $output;
    }

    /**
     * (1) 可供本地直接调用
     * (2) 可供远程调用
     */
    function refreshLocalSphinx() {
        $serverAddress = empty($_POST['serverAddress']) ? $_GET['serverAddress'] : $_POST['serverAddress'];
        if (!$serverAddress) {
            $serverAddress = '127.0.0.1';
        }
//        echo '<br> the trans is : '. $serverAddress;
        //因为php调用本地命令的不便捷性，我们直接使用已经有的java
        $url = $serverAddress . ':8080/Smsweb/yjscServlet?WebType=localcmd&cmd=/home/dualven/sphinxSh/refresh.sh';
//        echo $url;
        $res = $this->Get($url);
//        var_dump($res);
        $finalresult['result'] = 'failed';
        $finalresult['info'] = $res;
        if (strpos($res, 'MySQL server has gone away') > 0 || strpos($res, 'ERROR:') > 0) {
            $finalresult['result'] = 'failed';
        } else if (strpos($res, 'succesfully sent SIGHUP to searchd') > 0) {
            $finalresult['result'] = 'success';
        }
        return (json_encode($finalresult));
    }

    /**
     * 自动校验所有的自动收货的单子
     * 适用于第一次所有的单子都没有解冻的情况 
     */
    function resolvedM() {
        $order = &m('order');
        $sql = 'select o.status,o.order_id,o.buyer_id,order_amount, o.evaluation_status ,l.*  from ecm_order o ,ecm_order_log l where  l.order_id = o.order_id  and  o.bh_id=10919 and o.status =40 and l.operator=0 and l.remark=""';
        $results = $order->db->getAll($sql);
        foreach ($results as $vals) {
            echo 'order id ' . $vals['order_id'] . '-' . $vals['buyer_id'] . '-' . $vals['order_amount'] . '<br>';
            //  $this->refrozen($vals['order_id'], $vals['buyer_id'], $vals['order_amount']);
        }
    }

    /**
     * 根据算法，对所有已经完成却没有斛冻的予以校正！这个应该比较实用。 
     */
    function autoRefro() {
        $order = &m('order');
        $sql = 'select o.order_id,o.buyer_id, o.order_amount, o.status ,l.caozuo ,l.leixing ,l.s_and_z from ecm_order o, ecm_my_moneylog l where l.caozuo in(10,20) and l.s_and_z =2 and o.order_id=l.order_id and o.status=40';
        $results = $order->db->getAll($sql);
        foreach ($results as $vals) {
            echo 'order id ' . $vals['order_id'] . '-' . $vals['buyer_id'] . '-' . $vals['order_amount'] . '<br>';
            Log::write('in default autoRefro ---order id ' . $vals['order_id'] . '-' . $vals['buyer_id'] . '-' . $vals['order_amount']);
            $this->refrozen($vals['order_id'], $vals['buyer_id'], $vals['order_amount']);
        }
    }

    function refro() {
        $order_id = $_GET['order_id'];
        $buy_user_id = $_GET['buy_user_id'];
        $order_amount = $_GET['order_amount'];
        $this->refrozen($order_id, $buy_user_id, $order_amount);
    }

    function refrozen($order_id, $buy_user_id, $order_amount) {
        /* 商付通v2.2.1  更新商付通定单状态 确认收货 开始 */
        $my_money_mod = & m('my_money');
        $my_moneylog_mod = & m('my_moneylog');
        $my_moneylog_row = $my_moneylog_mod->getrow("select * from " . DB_PREFIX . "my_moneylog where order_id='$order_id' and s_and_z=2 and caozuo in (10,20)");
        //$money=$my_moneylog_row['money'];//定单价格
        $money = $order_amount;
        $sell_user_id = $my_moneylog_row['seller_id']; //卖家ID
        if ($my_moneylog_row['order_id'] == $order_id) {
//            $buy_user_id = $this->visitor->get('user_id');
            $sell_money_row = $my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$sell_user_id'");
            $buy_money_row = $my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$buy_user_id'");
            $buy_money = $buy_money_row['money'];  //买家资金
            $sell_money = $sell_money_row['money']; //卖家的资金
            $sell_money_dj = $sell_money_row['money_dj']; //卖家的冻结资金
            $new_money = $sell_money + $money;
            $new_money_dj = $sell_money_dj - $money;
            $new_buy_money = $buy_money;
            //更新数据
            $new_money_array = array(
                'money' => $new_money,
                'money_dj' => $new_money_dj,
            );
            $new_buy_money_array = array(
                'money' => $new_buy_money,
            );
            $my_money_mod->edit('user_id=' . $sell_user_id, $new_money_array);
//            $my_money_mod->edit('user_id=' . $buy_user_id, $new_buy_money_array);
            //更新商付通log为 定单已完成
            $my_moneylog_mod->edit('order_id=' . $order_id, array('caozuo' => 40));
        }
    }

    function autoCancle() {
        $order = &m('order');
        $sql = 'select l.order_id ,l.caozuo ,l.leixing ,l.buyer_id, o.status from ecm_my_moneylog l , ecm_order o  where  l.order_id=o.order_id and o.status=0 and l.caozuo!=30 and l.s_and_z=2 ';
        $results = $order->db->getAll($sql);
        foreach ($results as $vals) {
            echo 'order id ' . $vals['order_id'] . '-' . $vals['buyer_id'] . '-' . $vals['order_amount'] . '<br>';
//              $this->cancleOrder($vals['order_id'], $vals['buyer_id'], $vals['order_amount']);
        }
    }

    /**
     * 对1,2,3,种情形的进行状态处理 
     */
    function autoCancle123() {
        $order = &m('order');
        $my_moneylog_mod = & m('my_moneylog');
        $sql = 'select l.order_id ,l.caozuo ,l.leixing ,l.buyer_id, o.status from ecm_my_moneylog l , ecm_order o  where  l.order_id=o.order_id and o.status=0 and l.caozuo!=30 and l.caozuo!=80 and l.s_and_z=2 ';
        $results = $order->db->getAll($sql);
        foreach ($results as $vals) {
            echo 'order id ' . $vals['order_id'] . '-' . $vals['buyer_id'] . '-' . $vals['order_amount'] . '<br>';
            $my_moneylog_mod->edit('order_id=' . $vals['order_id'], array('caozuo' => 80));
//              $this->cancleOrder($vals['order_id'], $vals['buyer_id'], $vals['order_amount']);
        }
    }

    function cancleO() {
        $order_id = $_GET['order_id'];
        $buy_user_id = $_GET['buy_user_id'];
        $order_amount = $_GET['order_amount'];
        $this->cancleOrder($order_id, $buy_user_id, $order_amount);
    }

    /**
     * 这个用户的详情统计
     * @param type $id 用户id
     */
    function userspec() {
        echo 'Welcome to User Spec! ';
        $id = $_GET['id'];
        $my_money_mod = & m('my_money');
        $my_moneylog_mod = & m('my_moneylog');
        $my_order = &m('order');
        echo 'Welcome to User Spec! ';
        $statis2 = $my_money_mod->getRow('select * from ecm_my_money where user_id=' . $id);
        echo 'Welcome to User Spec! ';
        $user_id = $statis2['user_id'];
        $user_name = $statis2['user_name'];
        $money = $statis2['money'];
        $money_dj = $statis2['money_dj'];
        $total = $money + $money_dj;

        $djed_sql = 'select sum(order_amount) from ecm_order where status in (20,30) and bh_id=' . $id;
//        echo '<br> '.$djed_sql;
        $money_odj = $my_moneylog_mod->getOne($djed_sql);
        //日志检验上面的
        $djed2_sql = 'select sum(money_zs)  from ecm_my_moneylog where  leixing in (10) and caozuo in (10,20)  and user_id=' . $id;
        $log_money_dj = $my_moneylog_mod->getOne($djed2_sql);

        $finished_sql = 'select sum(order_amount) from ecm_order where  status = 40 and bh_id=' . $user_id;
        $money_finished = $my_moneylog_mod->getOne($finished_sql);
        //日志检验
        $finished2_sql = 'select sum(money_zs)  from ecm_my_moneylog where  leixing in (10) and caozuo in (40)  and user_id=' . $user_id;
        $log_money_finished = $my_moneylog_mod->getOne($finished2_sql);
//1,2,3 完成的金额
        $finished_sql_123 = 'select sum(l.money_zs)  from ecm_my_moneylog l , ecm_order o  where  l.order_id=o.order_id and o.status=0 and l.caozuo=80 and user_id=' . $user_id;
        $money_finished_123 = $my_moneylog_mod->getOne($finished_sql_123);


        $inout_sql = 'select sum(money_zs)  from ecm_my_moneylog where leixing in(30 ) and caozuo in (50,4)  and user_log_del = 0 and user_id=' . $user_id;
        $money_ct = $my_moneylog_mod->getOne($inout_sql);
        $tx_sql = 'select sum(money)  from ecm_my_moneylog where leixing in(40 ) and caozuo in (61)  and user_log_del = 0 and user_id=' . $user_id;
        $money_tx = $my_moneylog_mod->getOne($tx_sql);

        $io_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (20,30,40,50) and leixing in(21,11) and  user_id=' . $user_id;
        $money_io = $my_moneylog_mod->getOne($io_sql);

//        $cancle_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (30) and leixing in(21,11) and  user_id='.$user_id;
//        $money_cancle = $my_moneylog_mod->getOne($cancle_sql);

        $common_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (20,40,50,4,10,80) and  user_id=' . $user_id;
        $money_common = $my_moneylog_mod->getOne($common_sql);
        $c_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (30) and leixing in(21,11) and  user_id=' . $user_id;
        $money_io_only30 = $my_moneylog_mod->getOne($c_sql);

        $money_common = $money_common + $money_io_only30 + $money_tx;

        echo '<br> User ' . $user_id . '--' . $user_name . ' active M is  ' . $money . ', dj M is ' . $money_dj . ', total is ' . $total;
        echo '<br> the Money as a common  :' . $money_common;
        echo '<br> We show the spec if you are behalf :';


        echo '<br> the Money in order djed is :' . $money_odj;
        echo '<br> the Money in log djed is :' . $log_money_dj;
        echo '<br> the Money in order finished is : ' . $money_finished;
        echo '<br> the Money in log finished is : ' . $log_money_finished;
        echo '<br> the Money in log finished_123 is : ' . $money_finished_123;
        echo '<br> the Money the user in-out is :' . $money_ct;
        echo '<br> the Money the user tx is :' . $money_tx;
        echo '<br> the Money the user tran-in-out  is :' . $money_io;
        echo '<br> so , the Money total should be :';
        echo '<br> jied + finished + finished123 + in-out  + tran-in-out + money_tx  ';
        echo '<br> ' . $money_odj . '+' . $money_finished . '+' . $money_finished_123 . '+' . $money_ct . '+' . $money_io . '+' . $money_tx . '= ' . ($money_odj + $money_finished + $money_finished_123 + $money_ct + $money_io + $money_tx);
    }

    function cancleOrder($id, $buy_user_id, $order_amount) {
        /* 商付通v2.2.1  更新商付通定单状态 开始 */
        $my_money_mod = & m('my_money');
        $my_moneylog_mod = & m('my_moneylog');
        $my_moneylog_row = $my_moneylog_mod->getrow("select * from " . DB_PREFIX . "my_moneylog where order_id='$id' and (caozuo='10' or caozuo='20') and s_and_z=1");
        $money = $my_moneylog_row['money']; //定单价格
        $buy_user_id = $my_moneylog_row['buyer_id']; //买家ID
        $sell_user_id = $my_moneylog_row['seller_id']; //卖家ID
        if ($my_moneylog_row['order_id'] == $id) {
            $buy_money_row = $my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$buy_user_id'");
            $buy_money = $buy_money_row['money']; //买家的钱

            $sell_money_row = $my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$sell_user_id'");
            $sell_money = $sell_money_row['money_dj']; //卖家的冻结资金

            $new_buy_money = $buy_money + $money;
            $new_sell_money = $sell_money - $money;
            //更新数据
            $my_money_mod->edit('user_id=' . $buy_user_id, array('money' => $new_buy_money));
            $my_money_mod->edit('user_id=' . $sell_user_id, array('money_dj' => $new_sell_money));
            //更新商付通log为 定单已取消
            $my_moneylog_mod->edit('order_id=' . $id, array('caozuo' => 30));
        }
    }

//   function test(){
//      $value= array('1'=>'a','2'=> 'b');
//      Log::write('dxw2 log test : $message');
////        var_dump($_SERVER);
//      exit(json_encode($value));
//   }
    function basicecho() {
        $finalresult = array();
        $finalresult['result'] = 'success';
        $finalresult['info'] = $res;
        exit(json_encode($finalresult));
    }

    function endsWith($str, $sub) {
        return ( substr($str, strlen($str) - strlen($sub)) == $sub );
    }

    function checkBasic() {
//       $url = "http://www.51zwd.com/index.php?app=default&act=basicecho";
//       $result = @json_decode(Get($url),true);
        $url = "http://www.51zwd.com";
        $result = Get($url);
        if ($result && $this->endsWith($result, '</html>')) {
            //do nothing
            $result = 'common test !! ok!';
            Log::write($result);
            exit($result);
        } else {
            $url = '112.124.54.224:8080/Smsweb/yjscServlet?WebType=localcmd&cmd=/home/dualven/51zwdsh/del.sh';
            $result = @json_decode(Get($url), true);
        }
        $result = var_export($result, true);
        Log::write($result);
    }

    /**
     *
     * @param type $meto
     * @param type $title
     * @param type $user_id
     * @return boolean 
     */
    function parseMemo($meto, &$title, &$user_id) {
        $pos = strpos($meto, 'ZSerial:');
        if ($pos !== false) {
            $title = substr($meto, $pos);
            $pos = $pos + 8;
            $pos2 = strpos($meto, '_');
            $user_id = substr($meto, $pos, $pos2 - $pos);
            return true;
        } else {
            return false;
        }
    }

}

?>
