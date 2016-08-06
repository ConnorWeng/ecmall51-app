<?php

/* 后台资金处理　
 * 2015-11-17加批量资金转移 */
require_once("../includes/Alipay/corefunction.php");
require_once("../includes/Alipay/md5function.php");
require_once("../includes/Alipay/notify.php");
require_once("../includes/Alipay/submit.php");

class FakeMoneyApp extends FakeBackendApp {

    function notifyurl_51_batch() {
        require_once("../data/config.alipay.php");
        // 计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        if ($verify_result) {

            //批量付款数据中转账成功的详细信息
            $success_details = $_POST['success_details'];

            //批量付款数据中转账失败的详细信息
            $fail_details = $_POST['fail_details'];
            Log::write('in notify_batch: success_details:');
            Log::write($success_details);
            Log::write('in notify_batch: fail_details:');
            Log::write($fail_details);

            //to do 性能改造处
            $this->handle_detail($success_details);
            $this->handle_detail($fail_details);
            echo "success"; // 请不要修改或删除
        } else {
            // 验证失败
            Log::write('fail');
            echo "fail";
        }
    }

    /**
     * 关键方法测试 
     */
    function test_handle_detail() {
        $detail = $_GET['detail'];
        $this->handle_detail($detail);
    }

    /**
     * 20151119日使用 
     * 之后禁用
     */
    private function onlytoday() {
        $sql = 'select user_id, money_zs from ecm_my_moneylog where id in (select flownumber from ecm_batchtrans where flag ="S")';
        $records = $this->_moneylog_mod->getAll($sql);
//        var_dump($records);
        foreach ($records as $r) {
//             $this->my_money_mod->edit('user_id=' . $r['user_id'], 'money_dj = money_dj -'.$r['money_zs']);
            $row = $this->my_money_mod->getRow('select money,money_dj from ecm_my_money where user_id=' . $r['user_id']);
            echo 'user_id:' . $r['user_id'] . '--usermoney:' . $row['money'] . '--dj:' . $row['money_dj'] . '----statis:' . $this->getuserMoney($r['user_id']);
            if ($row['money_dj'] < 0) {
                $this->my_money_mod->edit('user_id=' . $r['user_id'], 'money_dj =0');
            }
        }
    }

    function onlytoday2() {
        $sql = 'select user_name,user_id,money ,money_dj from ecm_my_money where user_id in (select user_id from ecm_my_moneylog where id in (select flownumber from ecm_batchtrans where flag ="F"));';
        $records = $this->_moneylog_mod->getAll($sql);
        var_dump($records);
        foreach ($records as $r) {
            $dj = $this->_moneylog_mod->getOne('select sum(money_zs) from ecm_my_moneylog where user_id=' . $r['user_id'] . ' and caozuo=60;');
            if (!$dj) {
                $dj = 0;
            }

            $ddj = $dj - $r['money_dj'];
            $ymoney = $r['money'] - $ddj;
            $new_money_array = array(
                'money' => $ymoney,
                'money_dj' => $dj,
            );
            var_dump($new_money_array);
            $this->my_money_mod->edit('user_id=' . $r['user_id'], $new_money_array);
            echo "<br>";
        }
    }

    private function getuserMoney($user_id) {
        $tx_sql = 'select sum(money)  from ecm_my_moneylog where leixing in(40 ) and caozuo in (61)  and user_log_del = 0 and user_id=' . $user_id;
        $money_tx = $this->_moneylog_mod->getOne($tx_sql);
        $common_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (20,40,50,4,10) and  user_id=' . $user_id;
        $money_common = $this->_moneylog_mod->getOne($common_sql);
        $c_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (30) and leixing in(21,11) and  user_id=' . $user_id;
        $money_io_only30 = $this->_moneylog_mod->getOne($c_sql);

        $finished_sql_123 = 'select sum(l.money_zs)  from ecm_my_moneylog l , ecm_order o  where  l.order_id=o.order_id and o.status=0 and l.caozuo=80 and user_id=' . $user_id;
        $money_finished_123 = $this->_moneylog_mod->getOne($finished_sql_123);

        $money_common = $money_common + $money_io_only30 + $money_tx + $money_finished_123;
        $statis = $money_common;
        return $statis;
    }

    public function isRight($user_id) {
        $sta = $this->getuserMoney($user_id);
        $row = $this->my_money_mod->getRow('select money,money_dj,bank_username from ecm_my_money where user_id=' . $user_id);
        $isright = bccomp($row['money'] + $row['money_dj'], $sta);
        if ($isright == 0) {
            if (strlen($row['bank_username']) < 5) {
                return 'name_short!';
            }
            $str = $row['bank_username'];
            if (!eregi("[^\x80-\xff]", "$str")) {
//                echo "all";
            } else {
                return 'not_chinese!';
            }
            return 'yes';
        } else {
            return 'no！！！';
        }
    }

    private function getuserMoneyEx($user_id, $exCon) {
//        $tb001 = $this->getMillisecond();
        $prefix = 'select * from ecm_my_moneylog where  user_id=' . $user_id;
        $prefix.=$exCon;
        $test = $this->_moneylog_mod->getRow($prefix);
//        echo $prefix . '<br>';
//        $tb002 = $this->getMillisecond();
//        echo 'getuserMoneyEx cost:' . ($tb002 - $tb001) . '<br>';
        if (!$test) {
//            echo 'return 0 <br>';
            return 0;
        }
        $tx_sql = 'select sum(money)  from ecm_my_moneylog where leixing in(40 ) and caozuo in (61)  and user_log_del = 0 and user_id=' . $user_id;
        $tx_sql.=$exCon;
//        echo $tx_sql . '<br>';
        $money_tx = $this->_moneylog_mod->getOne($tx_sql);
        $common_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (20,40,50,4,10) and  user_id=' . $user_id;
        $common_sql.=$exCon;
//        echo $common_sql . '<br>';
        $money_common = $this->_moneylog_mod->getOne($common_sql);
        $c_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (30) and leixing in(21,11) and  user_id=' . $user_id;
        $c_sql.=$exCon;
//        echo $c_sql . '<br>';
        $money_io_only30 = $this->_moneylog_mod->getOne($c_sql);

        $finished_sql_123 = 'select sum(l.money_zs)  from ecm_my_moneylog l , ecm_order o  where  l.order_id=o.order_id and o.status=0 and l.caozuo=80 and user_id=' . $user_id;
        $finished_sql_123.=$exCon;
//        echo $finished_sql_123 . '<br>';
        $money_finished_123 = $this->_moneylog_mod->getOne($finished_sql_123);

        $money_common = $money_common + $money_io_only30 + $money_tx + $money_finished_123;
//        echo $money_common . '<br>';
        $statis = $money_common;
        return $statis;
    }

    function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    /**
     * 测试isRightEx  
     */
    public function testRight() {
        $user_id = $_GET['id'];
//        $tb001 = $this->getMillisecond();
        $this->isRightEx($user_id);
//        $tb002 = $this->getMillisecond();
//        echo 'cost is :' . ($tb002 - $tb001);
    }

    public function isRightEx($user_id) {
        $row2 = $this->statistics_mod->getRow('select * from ecm_statistics where user_id=' . $user_id);
        $exCon = '';
        if ($row2) {
            $admin_time = $row2['admin_time'];
            $exCon .= ' and admin_time >' . $admin_time;
        }
        $sta = $this->getuserMoneyEx($user_id, $exCon);
        if (!empty($exCon)) {
            $sta = $sta + $row2['statis'];
        }
//        $tb001 = $this->getMillisecond();
        $row = $this->my_money_mod->getRow('select id,user_id,user_name, money,money_dj from ecm_my_money where user_id=' . $user_id);
        $isright = bccomp($row['money'] + $row['money_dj'], $sta);
//        echo 'bccomp:'.($row['money'] + $row['money_dj']).'--'.$sta.'<br>';
//        $tb003 = $this->getMillisecond();
        if ($isright == 0) {
            $row3 = $this->_moneylog_mod->getRow('select id,admin_time,moneyleft from ecm_my_moneylog where caozuo in (10,20,30,40,50,61,4, 80) and user_id=' . $user_id . ' order by admin_time desc limit 1');
            if (!empty($exCon)) {
                $data = array(
                    'money' => $row['money'],
                    'money_dj' => $row['money_dj'],
                    'statis' => $row['money'] + $row['money_dj'],
                    'admin_time' => $row3['admin_time'],
                    'log_id' => $row3['id'],
                );
                $this->statistics_mod->edit('user_id=' . $user_id, $data);
//                $tb004 = $this->getMillisecond();
            } else {
                $data = array(
                    'user_id' => $row['user_id'],
                    'user_name' => $row['user_name'],
                    'money' => $row['money'],
                    'money_dj' => $row['money_dj'],
                    'statis' => $row['money'] + $row['money_dj'],
                    'admin_time' => $row3['admin_time'],
                    'log_id' => $row3['id'],
                );
                $this->statistics_mod->add($data);
//                $tb005 = $this->getMillisecond();
            }
//            $tb002 = $this->getMillisecond();
//            echo 'add record2 cost:' . ($tb002 - $tb001) . '<br>';
//            echo 'add record3 cost:' . ($tb003 - $tb001) . '<br>';
//            echo 'add record4 cost:' . ($tb004 - $tb001) . '<br>';
//            echo 'add record5 cost:' . ($tb005 - $tb001) . '<br>';
            return 'yes';
        } else {
//            echo 'no';
//            echo $user_id.'bccomp:'.($row['money'] + $row['money_dj']).'--'.$sta.'<br>';
            return 'no';
        }
    }

    /**
     * 001^dualven@163.com^段雄文^1.00^S^^20151117531313648^20151117172143|002^zling_hust@hotmail.com^周玲^1.00^S^^20151117531313649^20151117172143|^M
     * 1 解析，
     * ２入库
     * ３进行自动的审核通过/或者不通过
     * @param type $detail 
     *  这个地方,调用的地方可以写成 method_register　在内存执行，看其性能了．
     */
    private function handle_detail($detail) {

        $records = explode('|', $detail);
        var_dump($records);
        foreach ($records as $record) {
            unset($db);
            if (empty($record))
                continue;

            $row = explode('^', $record);
            $db['flownumber'] = $row[0];
            $db['now_money'] = $row[3];
            $db['flag'] = $row[4];
            $db['reason'] = $row[5];
            $db['tradeno'] = $row[6];
            $db['finishtime'] = $row[7];
            $flag = $row[4];
            $flownumber = $row[0];
            $now_money = $row[3];
            //(1) 存库存
//            var_dump('flownumber="' . $flownumber . '" and format(now_money,2)= format(' .$now_money.',2)');
            $result = $this->batchtrans_mod->getRow('select * from ecm_batchtrans where flownumber="' . $flownumber . '" and flag is null');
            if (!$result) {
                continue;
            }
            $this->batchtrans_mod->edit('flownumber="' . $flownumber . '" and format(now_money,2)= format(' . $now_money . ',2)', $db);
            $user_id = $this->my_money_mod->getOne('select user_id from ecm_my_moneylog where id=' . $db['flownumber']);
            $jd_money = $this->my_money_mod->getOne('select money_zs from ecm_my_moneylog where id=' . $db['flownumber']);
//            var_dump($db);
//            var_dump($user_id);
//            var_dump($jd_money);
            //(２）
            if ($flag == 'S') {
                $this->shenhe($db['flownumber'], $user_id, $db['tradeno'], $jd_money);
            } else if ($flag == 'F') {
                $this->shenheBack($db['flownumber'], $user_id, $db['reason'], $jd_money);
            }
        }
    }

    /**
     * 审核失败
     * @param type $log_id
     * @param type $user_id
     * @param type $log
     * @param type $jd_money 
     */
    function shenheBack($log_id, $user_id, $log, $jd_money) {
        $reason = '支付宝账号或者名称错误！'; //取代$log

        $check = $this->_moneylog_mod->getRow('select * from ecm_my_moneylog where id=' . $log_id . ' and caozuo=60');
        if (!$check)
            return;

        $behalf_money_row = $this->my_money_mod->getRow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
        $sell_money = $behalf_money_row['money']; //卖家的资金
        $sell_money_dj = $behalf_money_row['money_dj']; //卖家的冻结资金
        $new_money = $sell_money + $jd_money;
        $new_money_dj = $sell_money_dj - $jd_money;
        Log::write('$sell_money,$sell_money_dj,$jd_money,$new_money and $$new_money_dj is :' . $sell_money . '--' . $sell_money_dj . '--***' . $jd_money . '***--' . $new_money . '--' . $new_money_dj);
//        if ($new_money_dj < 0) {
//            echo ecm_json_encode(false);
//            return false;
//        }
        //更新数据
        $new_money_array = array(
            'money' => $new_money,
            'money_dj' => $new_money_dj,
        );
        $this->my_money_mod->edit('user_id=' . $user_id, $new_money_array);
        $edit_moneylog = array(
            'log_text' => $reason,
            'admin_time' => gmtime(),
            'caozuo' => 62,
        );
        $this->_moneylog_mod->edit('id=' . $log_id, $edit_moneylog);
    }

    /**
     * $log_id  日志id 
     * @param type $user_id
     * @param type $order_sn  －－－trade_no
     */
    private function shenhe($log_id, $user_id, $order_sn, $money_djs) {
        var_dump($log_id . $user_id . $order_sn);

        $check = $this->_moneylog_mod->getRow('select * from ecm_my_moneylog where id=' . $log_id . ' and caozuo=60');
        if (!$check)
            return;

        $admin_time = gmtime();
        $money_row = $this->my_money_mod->getrow("select money,money_dj,jifen from " . DB_PREFIX . "my_money where user_id='$user_id'");
        $row_money_dj = $money_row['money_dj'];
        $row_jifen = $money_row['jifen'];
        $row_money = $money_row['money'];
//                if ($row_money_dj < $money_djs) {
//                    $this->show_warning('feifacanshu');
//                    return;
//                }

        $new_money_dj = $row_money_dj - $money_djs;
        $new_money = array(
            'money_dj' => $new_money_dj,
        );
        $edit_myjifen = array(
            'jifen' => $row_jifen - $money_djs,
        );
        $this->my_money_mod->edit('user_id=' . $user_id, $new_money); //读取所有数据库
        $this->my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);
        $edit_moneylog = array(
            'order_sn' => $order_sn,
            'admin_time' => $admin_time,
            'caozuo' => 61,
            'moneyleft' => $row_money + $new_money_dj,
        );
        $this->_moneylog_mod->edit('id=' . $log_id, $edit_moneylog);
    }

    /**
     * 文档里说此接口已经废弃 
     */
    function returnurl_51_batch() {
        require_once("../data/config.alipay.php");
        // 计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();

        if ($verify_result) {
            echo 'it is right!';
            Log::write('in return_batch: success_details:');
            $success_details = $_POST['success_details'];
            Log::write('in return_batch: failde_detail :');
            //批量付款数据中转账失败的详细信息
            $fail_details = $_POST['fail_details'];
            Log::write($success_details);
            Log::write($fail_details);
        } else {
            echo 'it is wrong!';
            Log::write('wrong');
        }
    }

    function doalipayBank_51_batch() {
        require_once("../data/config.alipay.php");
        //服务器异步通知页面路径
        $notify_url = $batch_ali['notify_url'];
        //需http://格式的完整路径，不允许加?id=123这类自定义参数
        //付款账号
        $email = $_POST['WIDemail'];
        //必填
        //付款账户名
        $account_name = $_POST['WIDaccount_name'];
        //必填，个人支付宝账号是真实姓名公司支付宝账号是公司名称
        //付款当天日期
        $pay_date = $_POST['WIDpay_date'];
        //必填，格式：年[4位]月[2位]日[2位]，如：20100801
        //批次号
        $batch_no = $_POST['WIDbatch_no'];
        //必填，格式：当天日期[8位]+序列号[3至16位]，如：201008010000001
        //付款总金额
        $batch_fee = $_POST['WIDbatch_fee'];
        //必填，即参数detail_data的值中所有金额的总和
        //付款笔数
        $batch_num = $_POST['WIDbatch_num'];
        //必填，即参数detail_data的值中，“|”字符出现的数量加1，最大支持1000笔（即“|”字符出现的数量999个）
        //付款详细数据
        $detail_data = $_POST['WIDdetail_data'];
        //必填，格式：流水号1^收款方帐号1^真实姓名^付款金额1^备注说明1|流水号2^收款方帐号2^真实姓名^付款金额2^备注说明2....


        /*         * ********************************************************* */

//构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "batch_trans_notify",
            "partner" => trim($alipay_config['partner']),
            "notify_url" => $notify_url,
            "email" => $email,
            "account_name" => $account_name,
            "pay_date" => $pay_date,
            "batch_no" => $batch_no,
            "batch_fee" => $batch_fee,
            "batch_num" => $batch_num,
            "detail_data" => $detail_data,
            "_input_charset" => trim(strtolower($alipay_config['input_charset']))
        );

//建立请求
        header("Content-type: text/html; charset= utf-8");
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
        echo $html_text;
    }

    function indexgoods() {
        $goodsids = $_GET['goodsids'];
        if (!$goodsids) {
            echo 'not goodsids exists;';
            return;
        }
        echo 'do it !' . $goodsids;
        $scope = explode('-', $goodsids);
        if ($scope[1] - $scope[0] > 1000) {
            $l = ($scope[1] - $scope[0]) / 1000;
            echo '$l is ' . $l;
            for ($i = 0; bccomp($i, $l + 1) == -1; $i++) {
                $goodsid = (1000 * $i + $scope[0]) . '-' . (1000 * $i + $scope[0] + 1000);
                if (bccomp($i, $l) == 0) {
                    $goodsid = (1000 * $i + $scope[0]) . '-' . ($scope[1]);
                }
                echo $goodsid . '<br>';
                $issuc = $this->indexit($goodsid);
                if ($issuc) {
                    echo $goodsid . 'is ok !';
                }
            }
        }
    }

    function indexit($goodsids) {
        $url = 'http://121.40.85.153:8080/searchPic/SearchPicServ?type=indexgoods&goodsids=' . $goodsids;
//        $yes =  Get($url);
//        echo $yes;
        $aliM = @json_decode(Get($url), true);
//        echo 'here';
//        var_dump($aliM);
        if ($aliM) {
            var_dump($aliM);
            if (strpos($aliM['result'], 'success')) {
                return true;
            } else {
//                echo ' wrong ' . $goodsids;
                return $this->indexgoods($goodsids);
            }
        } else {
//            echo ' wrong ' . $goodsids;
            return $this->indexgoods($goodsids);
        }
    }

    /**
     * 手动的,没用了
     * @return type 
     */
    function index1000s() {
        $count = $_GET['count'];
        if (!$count) {
            echo 'not count exists;';
            return;
        }
        for ($i = 0; $i < $count; $i++) {
            $issuc = $this->index1000();
            if ($issuc) {
                echo $i . 'is ok !';
            }
        }
    }

    /**
     * 被调度任务使用!
     * @return boolean 
     */
    function index1000() {
        $url = 'http://121.40.85.153:8080/searchPic/SearchPicServ?type=indexgoods1000';
        $aliM = @json_decode(Get($url), true);
        if ($aliM) {
            var_dump($aliM);
            if (strpos($aliM['result'], 'success')) {
                return true;
            } else {
                return $this->index1000();
            }
        } else {
            return $this->index1000();
        }
    }

    /**
     * 今天多余的充值 
     */
    function todayExtra() {
        $del = false;
        if ($_GET['del'] && $_GET['del'] == 'yes') {
            $del = true;
        }
        $day = 8;
        if ($_GET['day']) {
            $day = $_GET['day'] * 24 + 8;
        }
        $sql = ' Select b.id,b.money,b.order_sn, b.user_name, from_unixtime(b.add_time) ,b.moneyleft from ecm_my_moneylog b , (SELECT order_sn,id , count(order_sn) as cc  FROM `ecm_my_moneylog` where leixing=30 and caozuo=4 and user_log_del = 0 and  FROM_UNIXTIME(add_time) >date_sub(curdate(), interval ' . $day . ' hour) GROUP BY order_sn having cc >1) a  where b.order_sn=a.order_sn;';
        $result = $this->_moneylog_mod->getAll($sql);

        echo var_export($result, trure);
        Log::write(var_export($result, trure));
        if ($result && $del && count($result) == 2) {
            if ($result[0]['moneyleft'] == $result[1]['moneyleft'] && $result[0]['moneyleft'] > 0) {
                $sql = 'delete from ecm_my_moneylog where id=' . $result[1]['id'];
                Log::write(' In todayExtra: ' . $sql);
                $result = $this->_moneylog_mod->db->query($sql);
                Log::write(' In todayExtra: ' . $result);
            }
        } if ($result && $del && count($result) > 2 && count($result) % 2 == 0) {
            for ($i = 0; $i < count($result) / 2; $i++) {
                if ($result[2 * $i]['order_sn'] == $result[2 * $i + 1]['order_sn'] && $result[2 * $i]['moneyleft'] == $result[2 * $i + 1]['moneyleft'] && $result[2 * $i]['moneyleft'] > 0) {
                    $sql = 'delete from ecm_my_moneylog where id=' . $result[2 * $i + 1]['id'];
                    Log::write(' In todayExtra: ' . $sql);
                    $result = $this->_moneylog_mod->db->query($sql);
                    Log::write(' In todayExtra: ' . $result);
                }
            }
        } else {
            Log::write(' In todayExtra: it is no need to del!');
        }
    }

    function todayForYes() {
        $day = 8;
        $dd = 1;
        if ($_GET['day']) {
            $day = $_GET['day'] * 24 + 8;
            $dd = $_GET['day'] + 1;
        }
        $ymoney = 0;
        $sql = 'SELECT id,money,order_sn, user_name, from_unixtime(add_time) ,moneyleft FROM `ecm_my_moneylog` 
            where user_log_del=0 and leixing=30 and caozuo in(4,50) and FROM_UNIXTIME(admin_time) >date_sub(curdate(), interval ' . $day . ' hour)
                and FROM_UNIXTIME(admin_time) <date_sub(curdate(), interval ' . ($day - 24) . ' hour)
                and locate(  date_sub(curdate(), interval ' . ($dd - 1) . ' day) +0 ,order_sn) = 0;';
        $result = $this->_moneylog_mod->getAll($sql);
        foreach ($result as $r) {
            $ymoney += $r['money'];
        }
        $result['ymoney'] = $ymoney;
//        var_dump($result);
//        $result = var_export($result, true);
        Log::write($result);
        exit(json_encode($result));
//        Log::write(var_export($result, trure));
    }

    function __construct() {
        $this->FakeMoneyApp();
    }

    function FakeMoneyApp() {
        parent::__construct();
        $this->_moneylog_mod = & m('my_moneylog');
        $this->my_money_mod = & m('my_money');
        $this->batchtrans_mod = & m('batchtrans');
        $this->statistics_mod = & m('statistics');
    }

    function behalfEight() {
        $behalf_money_row = $this->my_money_mod->getRow("select * from " . DB_PREFIX . "my_money where user_id='10919'");
        $sell_money = $behalf_money_row['money']; //卖家的资金
        $sell_money_dj = $behalf_money_row['money_dj']; //卖家的冻结资金

        $djed_sql = 'select sum(order_amount) from ecm_order where status in (20,30) and bh_id=10919';
        $money_odj = $this->_moneylog_mod->getOne($djed_sql);
        $tx_sql = 'select sum(money_zs)  from ecm_my_moneylog where leixing in(40 ) and caozuo in (60)  and user_log_del = 0 and user_id=10919';
        $money_tx = $this->_moneylog_mod->getOne($tx_sql);

        $jd_money = $sell_money_dj - $money_odj * 0.2 - $money_tx;
        if ($jd_money < 0) {
            echo ' you dont have enought dj money to be jiedong!';
            Log::write(date("Y-m-d H:i:s", time()) . ' you dont have enought dj money to be jiedong!');
            return;
        }
        Log::write(date("Y-m-d H:i:s", time()) . ' In behalfEight before:money,moneydj,orderdj,willjd is ' . $sell_money . '-' . $sell_money_dj . '--' . $money_odj . '--' . $jd_money);
        echo (date("Y-m-d H:i:s", time()) . ' In behalfEight before:money,moneydj,orderdj,willjd is ' . $sell_money . '-' . $sell_money_dj . '--' . $money_odj . '--' . $jd_money);
        $this->manuRefro(10919, $jd_money);
        echo '<br>';
        $behalf_money_row = $this->my_money_mod->getRow("select * from " . DB_PREFIX . "my_money where user_id='10919'");
        $sell_money = $behalf_money_row['money']; //卖家的资金
        $sell_money_dj = $behalf_money_row['money_dj']; //卖家的冻结资金
        Log::write(date("Y-m-d H:i:s", time()) . ' In behalfEight after:money,moneydj,willjd is ' . $sell_money . '-' . $sell_money_dj);
        echo (date("Y-m-d H:i:s", time()) . ' In behalfEight after:money,moneydj is ' . $sell_money . '-' . $sell_money_dj);
    }

    function testmanu() {
        $what = $_GET['manu'];
        $money = $_GET['money'];
        $user_id = $_GET['user_id'];
        if ($what == 'fro') {
            $this->manuFro($user_id, $money);
        } else if ($what == 'refro') {
            $this->manuRefro($user_id, $money);
        }
    }

    function manuRefro($user_id, $jd_money) {

        $behalf_money_row = $this->my_money_mod->getRow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
        $sell_money = $behalf_money_row['money']; //卖家的资金
        $sell_money_dj = $behalf_money_row['money_dj']; //卖家的冻结资金
        $new_money = $sell_money + $jd_money;
        $new_money_dj = $sell_money_dj - $jd_money;
        if ($new_money_dj < 0 && $_GET['force'] != 'yes') {
            return false;
        }
        //更新数据
        $new_money_array = array(
            'money' => $new_money,
            'money_dj' => $new_money_dj,
        );
        $this->my_money_mod->edit('user_id=' . $user_id, $new_money_array);
        $jd_behalf_moneylog = array(
            'user_id' => $user_id,
            'user_name' => $this->visitor->get('user_name'),
            'order_sn' => 'manuRefro', //基于订单，便于对账
            'add_time' => gmtime(),
            'leixing' => 90, //特殊标记，代发全额退款解冻资金
            'money_zs' => $jd_money,
            'money' => $jd_money,
            'log_text' => '管理员手动解冻金额', //代发全额退款之后，解冻资金
            'caozuo' => 90, //特殊标记，代发全额退款解冻资金
            's_and_z' => 3, //特殊标记，代发全额退款解冻资金
        );
        $this->_moneylog_mod->add($jd_behalf_moneylog);

        return true;
    }

    function delpaylog() {
        $paylog = & m('paylog');
        $result = $paylog->db->query('delete from ecm_paylog where createtime <  curdate() and trade_status=0;');
        echo $result;
    }

    /**
     * 这个用户的详情统计
     * @param type $id 用户id
     */
    function userspec() {
        $fro = false;
        if ($_GET['fro'] && $_GET['fro'] == 'yes') {
            $fro = true;
        }
        echo '<br>Welcome to User Spec! ';
        $id = 10919;
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

        $money_all_add = ($money_odj + $money_finished + $money_finished_123 + $money_ct + $money_io + $money_tx);
        if (bccomp($money_odj, $log_money_dj) == 0 && bccomp($money_finished, $log_money_finished) == 0
                && bccomp($money_common, $money_all_add) == 0) {
            echo '<br>you see , log system say it is right ! ';
            if (bccomp($total, $money_common) == 0) {
                echo '<br>you see , realMoney is also right as log system! ';
            } else if ($total > $money_common) {
                echo '<br>you see , realMoney > log system! sub some to be right ';
                $statis2 = $my_money_mod->getRow('select * from ecm_my_money where user_id=' . $id);
                $money = $statis2['money'];
                $money_dj = $statis2['money_dj'];

                $sub = $total - $money_common;
                $money_new = $money - $sub;
                $money_array = array(
                    'money' => $money_new,
                );
                $this->my_money_mod->edit('user_id=' . $id, $money_array);
            } else if ($total < $money_common) {
                echo '<br>you see , realMoney < log system! add some to be right ';
                $statis2 = $my_money_mod->getRow('select * from ecm_my_money where user_id=' . $id);
                $money = $statis2['money'];
                $money_dj = $statis2['money_dj'];

                $add = $money_common - $total;
                $money_new = $money + $add;
                $money_array = array(
                    'money' => $money_new,
                );
                $this->my_money_mod->edit('user_id=' . $id, $money_array);
            }
            //冻结资金的调整
            if ($fro) {
                if ($this->floatgtr($log_money_dj, $money_dj)) {
                    $adustd = $log_money_dj - $money_dj;
                    $issuc = $this->manuFro($id, $adustd);
                    if ($issuc) {
                        echo '<br>manu fro success!: ' . $adustd;
                    } else {
                        echo '<br>manu fro failed!: ' . $adustd;
                    }
                    $this->userspec();
                } else if ($this->floatles($log_money_dj, $money_dj)) {
                    $adustd = $money_dj - $log_money_dj;
                    $issuc = $this->manuReFro($id, $adustd);
                    if ($issuc) {
                        echo '<br>manuReFro success!: ' . $adustd;
                    } else {
                        echo '<br>manuReFro failed!: ' . $adustd;
                    }
                    $this->userspec();
                } else {
                    echo '<br>fro money is right as log';
                    exit(' <br>it is over !');
                }
            } else {
                echo '<br>we do not check fro money ';
            }
        } else {
            echo '<br>if the log system is not right , we do nothing! ';
        }
    }

    function manuFro($user_id, $jd_money) {

        $behalf_money_row = $this->my_money_mod->getRow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
        $sell_money = $behalf_money_row['money']; //卖家的资金
        $sell_money_dj = $behalf_money_row['money_dj']; //卖家的冻结资金
        $new_money = $sell_money - $jd_money;
        $new_money_dj = $sell_money_dj + $jd_money;
        if ($new_money < 0 && $_GET['force'] != 'yes') {
            return false;
        }
        //更新数据
        $new_money_array = array(
            'money' => $new_money,
            'money_dj' => $new_money_dj,
        );
        $this->my_money_mod->edit('user_id=' . $user_id, $new_money_array);
        $jd_behalf_moneylog = array(
            'user_id' => $user_id,
            'user_name' => $this->visitor->get('user_name'),
            'order_sn' => 'manuFro', //基于订单，便于对账
            'add_time' => gmtime(),
            'leixing' => 91, //特殊标记，代发全额退款解冻资金
            'money_zs' => $jd_money,
            'money' => $jd_money,
            'log_text' => '管理员手动冻结金额', //代发全额退款之后，解冻资金
            'caozuo' => 91, //特殊标记，代发全额退款解冻资金
            's_and_z' => 3, //特殊标记，代发全额退款解冻资金
        );
        $this->_moneylog_mod->add($jd_behalf_moneylog);

        return true;
    }

    function floatcmp($f1, $f2, $precision = 3) {// are 2 floats equal
        $e = pow(10, $precision);
        $i1 = intval($f1 * $e);
        $i2 = intval($f2 * $e);
        return ($i1 == $i2);
    }

    function test() {// are 2 floats equal
        $f1 = 1.12;
        $f2 = 1.13;

        $e = pow(10, 3);
        echo $e;
        $i1 = intval($f1 * $e);
        $i2 = intval($f2 * $e);
        echo '<br> ' . $i1 . '--' . $i2 . '<br>';
        if ($i1 > $i2) {
            echo 'big';
        } else if ($i1 < $i2) {
            echo 'less';
        } else if ($i1 == $i2) {
            echo 'equal';
        }
    }

    function floatgtr($big, $small, $precision = 3) {// is one float bigger than another
        $e = pow(10, $precision);
        $ibig = intval($big * $e);
        $ismall = intval($small * $e);
        return ($ibig > $ismall);
    }

    function floatles($big, $small, $precision = 3) {// is one float bigger than another
        $e = pow(10, $precision);
        $ibig = intval($big * $e);
        $ismall = intval($small * $e);
        return ($ibig < $ismall);
    }

    function floatgtre($big, $small, $precision = 3) {// is on float bigger or equal to another
        $e = pow(10, $precision);
        $ibig = intval($big * $e);
        $ismall = intval($small * $e);
        return ($ibig >= $ismall);
    }

    function behalforderextralog($bh_id) {
        $del = false;
        if ($_GET['del'] && $_GET['del'] == 'yes') {
            $del = true;
        }
        $sqla = ' select order_id ,count(order_id) as cc from ecm_my_moneylog where  leixing in (10) and caozuo in (10,20)  and user_id=' . $bh_id . ' group by order_id having cc > 1;';
        $resulta = $this->_moneylog_mod->getAll($sqla);

        echo var_export($resulta, true);
        Log::write(var_export($resulta, true));
        if ($resulta) {
            foreach ($resulta as $r) {
                $oid = $r['order_id'];
                $sql = 'select * from ecm_my_moneylog where  leixing in (10) and caozuo in (10,20) and user_id=' . $bh_id . ' and order_id=' . $oid;
                $result = $this->_moneylog_mod->getAll($sql);
                echo var_export($result, trure);
                Log::write(var_export($result, trure));
                if ($result && $del && count($result) > 1 && count($result) % 2 == 0) {
                    for ($i = 0; $i < count($result) / 2; $i++) {
                        if ($result[2 * $i]['order_sn'] == $result[2 * $i + 1]['order_sn'] && bccomp($result[2 * $i]['moneyleft'], $result[2 * $i + 1]['moneyleft']) == 0) {
                            $sql = 'delete from ecm_my_moneylog where id=' . $result[2 * $i + 1]['id'];
                            echo 'del id:' . $result[2 * $i + 1]['id'];
                            Log::write(' In behalforderextralog: ' . $sql);
                            $result = $this->_moneylog_mod->db->query($sql);
                            Log::write(' In behalforderextralog: ' . $result);
                        }
                    }
                } else {
                    Log::write(' In behalforderextralog: it is no need to del!' . $oid);
                }
                $sql = 'select * from ecm_my_moneylog where  leixing in (10) and caozuo in (10,20) and user_id!=' . $bh_id . ' and order_id=' . $oid;
                $result = $this->_moneylog_mod->getAll($sql);
                echo var_export($result, trure);
                Log::write(var_export($result, trure));
                if ($result && $del && count($result) > 1 && count($result) % 2 == 0) {
                    for ($i = 0; $i < count($result) / 2; $i++) {
                        if ($result[2 * $i]['order_sn'] == $result[2 * $i + 1]['order_sn'] && bccomp($result[2 * $i]['moneyleft'], $result[2 * $i + 1]['moneyleft']) == 0) {
                            $sql = 'delete from ecm_my_moneylog where id=' . $result[2 * $i + 1]['id'];
                            echo 'not behalf --del id:' . $result[2 * $i + 1]['id'];
                            Log::write(' In behalforderextralog: ' . $sql);
                            $result = $this->_moneylog_mod->db->query($sql);
                            Log::write(' In behalforderextralog: ' . $result);
                        }
                    }
                } else {
                    Log::write(' In behalforderextralog: it is no need to del!' . $oid);
                }
            }
        }
    }

    /**
     * @to_user目标账户user_id
     * @to_money 目标金额
     * 必须是有一个是10919
     * @return type 
     */
    function to_user_withdraw($user, $to_user, $to_money, $reason, $order_id, $order_sn) {
        $user_id = $user;
        if (preg_match("/[^0.-9]/", $to_money)) {
            return 'cuowu_nishurudebushishuzilei';
        }
        if ($user != 10919 && $to_user != 10919) {
            return ' the change should between one and behalf!';
        }
        $to_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$to_user'");
        $to_user_id = $to_row['user_id'];
        $to_user_name = $to_row['user_name'];
        $to_user_money = $to_row['money'];
        $to_user_money_dj = $to_row['money_dj'];
        if ($to_user_id == $user_id) {
            return 'cuowu_bunenggeizijizhuanzhang';
        }

        if (empty($to_user_id)) {
            return 'cuowu_mubiaoyonghubucunzai';
        }
        $user_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
        $user_name = $user_row['user_name'];
        $user_money = $user_row['money'];
        $user_money_dj = $user_row['money_dj'];

        if ($user_money < $to_money) {
            return 'cuowu_zhanghuyuebuzu';
        } else {
            //添加日志
            $log_text = $user_name . Lang::get('gei') . $to_user . Lang::get('zhuanchujine') . $to_money . Lang::get('yuan') . $reason;

            $add_mymoneylog = array(
                'user_id' => $user_id,
                'user_name' => $user_name,
                'buyer_name' => $user_name,
                'seller_name' => $to_user_name,
                'order_id' => $order_id,
                'order_sn' => $order_sn,
                'add_time' => gmtime(),
                'admin_time' => gmtime(),
                'leixing' => 21,
                'money_zs' => '-' . $to_money,
                'money' => $to_money,
                'log_text' => $log_text,
                'caozuo' => 50,
                's_and_z' => 2,
                'moneyleft' => $user_money + $user_money_dj - $to_money,
            );
            $this->_moneylog_mod->add($add_mymoneylog);


            $log_text_to = $user_name . Lang::get('gei') . $to_user_name . Lang::get('zhuanrujine') . $to_money . Lang::get('yuan') . $reason;
            $add_mymoneylog_to = array(
                'user_id' => $to_user_id,
                'user_name' => $to_user_name,
                'order_id' => $order_id,
                'order_sn' => $order_sn,
                'buyer_name' => $user_name,
                'seller_name' => $to_user_name,
                'add_time' => gmtime(),
                'admin_time' => gmtime(),
                'leixing' => 11,
                'money_zs' => $to_money,
                'money' => $to_money,
                'log_text' => $log_text_to,
                'caozuo' => 50,
                's_and_z' => 1,
                'moneyleft' => $to_user_money + $to_user_money_dj + $to_money,
            );
            $this->_moneylog_mod->add($add_mymoneylog_to);

            $new_user_money = $user_money - $to_money;
            $new_to_user_money = $to_user_money + $to_money;

            $add_jia = array(
                'money' => $new_to_user_money,
            );
            $this->my_money_mod->edit('user_id=' . $to_user_id, $add_jia);
            $add_jian = array(
                'money' => $new_user_money,
            );
            $this->my_money_mod->edit('user_id=' . $user_id, $add_jian);

            return true;
        }
    }

    function statistics() {
        $df = $_GET['name'];
        $sql = "select order_sn,order_id, money,log_text, count(order_id) as cc ,from_unixtime(admin_time+8) as time from ecm_my_moneylog where user_name='" . $df . "' and money>10.00 and (leixing=21 or leixing=20) and (caozuo=40 or caozuo=50 or caozuo=80) group by order_id having cc > 1;";
        $result = $this->_moneylog_mod->getAll($sql);
        foreach ($result as $r) {
            if ($r['cc'] > 1) {
                echo $r['order_sn'] . '--' . $r['order_id'] . '---' . $r['money'] . '---' . $r['time'] . '---' . $r['cc'] . '---' . iconv("utf-8", "gb2312", $r['log_text']) . '<br>';
            }
        }
    }

}

?>
