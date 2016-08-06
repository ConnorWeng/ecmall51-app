<?php

class My_checkModule extends AdminbaseModule {

    function __construct() {
        $this->My_checkModule();
    }

    function My_checkModule() {
        parent::__construct();

        $this->my_money_mod = & m('my_money');
        $this->paylog_mod = & m('paylog');
        $this->my_moneylog_mod = & m('my_moneylog');
        $this->my_mibao_mod = & m('my_mibao');
        $this->my_card_mod = & m('my_card');
        $this->my_jifen_mod = & m('my_jifen');
        $this->my_paysetup_mod = & m('my_paysetup');
        $this->remtrade_mod = & m('alitrade');
        $this->behalf_mod = & m('behalf'); //tanaiquan 2015-11-10
        $this->batchtrans_mod = & m('batchtrans');
    }

    function index() {
        $this->display('index_index.html');
        return;
    }

    function check_sphinx() {
        $cache_server = & cache_server();
        $current = $cache_server->get('currentSphinx');
        $assistant = $cache_server->get('assistantSphinx');
        $current_value = $cache_server->get('value_' . $current);
        $second_value = $cache_server->get('value_' . $assistant);
        $index[0]['server'] = $current;
        $index[0]['ismain'] = 'yes';
        $index[0]['updatetime'] = $current_value['last_update_time'];
        $index[0]['updateresult'] = $current_value['last_update_result']['result'];
        $index[0]['updateinfo'] = $current_value['last_update_result']['info'];
        $index[1]['server'] = $assistant;
        $index[1]['ismain'] = 'no';
        $index[1]['updatetime'] = $second_value['last_update_time'];
        $index[1]['updateresult'] = $second_value['last_update_result']['result'];
        $index[1]['updateinfo'] = $second_value['last_update_result']['info'];
        $this->assign('index', $index); //传递到风格里
        $this->display('check_sphinx.html');
    }

//用户资金列表 含搜索
    function check_resolve_list() {
        $so_user_name = $_GET["soname"];
        $somoney = $_GET["somoney"];
        $endmoney = $_GET["endmoney"];
        $order = $_GET['order'];
        $type = $_GET['type'];
        $alipay_no = $_GET['alipay_no'];
        if (!empty($alipay_no)) {
            $sql = 'select *  from ecm_paylog where trade_status =1 and out_trade_no ="' . $alipay_no . '";';
            $stat = $this->paylog_mod->getRow($sql);
            if ($stat) {
                $this->remtrade_mod->edit('ali_trade_no="' . $alipay_no . '"', array('trade_status' => 1, 'endtime' => date("Y-m-d H:i:s", time())));
            }
        }
        $page = $this->_get_page();
        //搜索用户为空就搜索全部	
        if (empty($so_user_name)) {

            $so_user_name = '';
        }
        if (empty($somoney)) {
            $somoney = 0;
        }
        if (empty($endmoney)) {
            $endmoney = 999999999;
        }
        if ($type != '3' && ($type === '0' || $type === '1')) {
            $typec = ' and trade_status=' . $type;
        } else {
            $typec = '';
        }
        $index = $this->remtrade_mod->find(array(
            'conditions' => "ali_trade_no LIKE '%$so_user_name%' and total_fee>='$somoney' and total_fee<='$endmoney'" . $typec, //条件
            'limit' => $page['limit'],
            'order' => $order ? $order : "createtime desc,endtime desc",
            'count' => true));
        $page['item_count'] = $this->remtrade_mod->getCount();
        $sql = 'select sum(total_fee) from ecm_alitrade where trade_status =0 ';
        $stat = $this->remtrade_mod->db->getOne($sql);
        $this->assign('notdo', $stat);
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('index', $index); //传递到风格里
        $this->display('check_resolve_list.html');
        return;
    }

    function check_batch_list() {
        $so_user_name = $_GET["soname"];
        $somoney = $_GET["somoney"];
        $endmoney = $_GET["endmoney"];
        $order = $_GET['order'];
        $type = $_GET['type'];
        $alipay_no = $_GET['alipay_no'];
        if (!empty($alipay_no)) {
            $sql = 'select *  from ecm_paylog where trade_status =1 and out_trade_no ="' . $alipay_no . '";';
            $stat = $this->paylog_mod->getRow($sql);
            if ($stat) {
                $this->batchtrans_mod->edit('ali_trade_no="' . $alipay_no . '"', array('trade_status' => 1, 'endtime' => date("Y-m-d H:i:s", time())));
            }
        }
        $page = $this->_get_page();
        //搜索用户为空就搜索全部	
        if (empty($so_user_name)) {

            $so_user_name = '';
        }
        if (empty($somoney)) {
            $somoney = 0;
        }
        if (empty($endmoney)) {
            $endmoney = 999999999;
        }
        if ($type != '3' && ($type === '0' || $type === '1')) {
            $typec = ' and flag=' . $type;
        } else {
            $typec = '';
        }
        $index = $this->batchtrans_mod->find(array(
            'conditions' => "flownumber LIKE '%$so_user_name%' and ori_money>='$somoney' and ori_money<='$endmoney'" . $typec, //条件
            'limit' => $page['limit'],
            'order' => $order ? $order : "createtime desc,finishtime desc",
            'count' => true));
        $page['item_count'] = $this->batchtrans_mod->getCount();
        $sql = 'select sum(total_fee) from ecm_alitrade where trade_status =0 ';
        $stat = $this->batchtrans_mod->db->getOne($sql);
        $this->assign('notdo', $stat);
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('index', $index); //传递到风格里
        $this->display('check_batch_list.html');
        return;
    }

//增加用户资金   
    function check_behalf_list() {
        if (isset($_GET['id']) && $_GET['id']) {
//            $ids2 = trim($_GET['id']);
            $ids = explode(',', trim($_GET['id'])); //数组，请使用    //dump($ids);
            var_dump($ids);
        }
        /*         * **********多代发支持   tanaiquan******************** */
        if (empty($_GET['behalf']) && empty($_POST['behalf'])) {
            $bh_id = 10919;
        } else {
            $bh_id = empty($_POST['behalf']) ? intval($_GET['behalf']) : intval($_POST['behalf']);
        }
//        $bh_id = empty($_POST['behalf'])?'10919':intval($_POST['behalf']);//默认代发为bh_id=10919
        //给页面代发列表
        $behalfs = $this->behalf_mod->find();
        $this->assign('behalfs', $behalfs);
        //查询代发给页面显示
        $this->assign('bh_id', $bh_id);
        /*         * **********end  tanaiquan******************** */

        $log = $_GET["type"];
        if ($log == 'log') {
            $sqla = ' select order_id ,count(order_id) as cc from ecm_my_moneylog where  leixing in (10) and caozuo in (10,20)  and user_id=' . $bh_id . ' group by order_id having cc > 1;';
            $index = $this->my_moneylog_mod->getAll($sqla);
        } else if ($log == 'lostorder') {
            $sqla = ' select order_id from ecm_my_moneylog where  leixing in (10) and caozuo in (10,20)  and user_id=' . $bh_id . ' and order_id not in  (select order_id from ecm_order where status in (20,30) and bh_id=' . $bh_id . ' )';
            $index = $this->my_moneylog_mod->getAll($sqla);
        }
        $page = $this->_get_page();
        $adjust = $_GET["adjust"];
        $del = $_GET["del"];

        $change = false;
        if ($adjust == 'adjust') {
            $change = true;
        }
        if ($del == 'yes') {
            require_once ROOT_PATH . '/admin/app/fakemoney.app.php';
            $b = new FakeMoneyApp();
            @$b->behalforderextralog($bh_id);
        }
        if ($log == 'delpaylog') {
            require_once ROOT_PATH . '/admin/app/fakemoney.app.php';
            $b = new FakeMoneyApp();
            @$b->delpaylog();
        }
        if ($log == 'behalfeight') {
            require_once ROOT_PATH . '/admin/app/fakemoney.app.php';
            $b = new FakeMoneyApp();
            @$b->behalfEight($bh_id);
        }
        if ($log == 'addLostLog') {
            $this->addLostLog();
        }
        $this->userspec($bh_id, false, $change);
        $page['item_count'] = count($index);
        if ($log == 'log' && count($index) > 0) {
            $sqla = ' select * from ecm_my_moneylog where leixing in (10) and caozuo in (10,20) and  order_id in (select order_id  from ecm_my_moneylog where  leixing in (10) and caozuo in (10,20)  and user_id=' . $bh_id . ' group by order_id having count(order_id) > 1);';
            $index = $this->my_moneylog_mod->getAll($sqla);
        }
        if ($log == 'lostorder' && count($index) > 0) {
            $sqla = 'select * from ecm_my_moneylog where  user_id=' . $bh_id . ' and order_id in (select order_id from ecm_my_moneylog where  leixing in (10) and caozuo in (10,20)  and user_id=' . $bh_id . ' and order_id not in  (select order_id from ecm_order where status in (20,30) and bh_id=' . $bh_id . ' ));';
            $index = $this->my_moneylog_mod->getAll($sqla);
        }
        /*         * *
         * start of dj and tx
         */
        $my_money = $this->my_money_mod->getAll("select * from " . DB_PREFIX . "my_money where user_id=$bh_id");
        $djed_sql = 'select sum(order_amount) from ecm_order where status in (20,30) and  bh_id=' . $bh_id;
        $shouddj = $this->my_moneylog_mod->getOne($djed_sql);
        $shoud['shouddj'] = $shouddj;
        $shoud['sjdj'] = $my_money[0]['money_dj'];
        $shoud['exdj'] = $shouddj - $my_money[0]['money_dj'];
        $shoud['twdj'] = $shouddj * 0.2;
        $this->assign('shoud', $shoud);

        $tx_sql = 'select sum(money_zs)  from ecm_my_moneylog where leixing in(40 ) and caozuo in (60)  and user_log_del = 0 and user_id=' . $bh_id;
        ;
        $money_tx = $this->my_moneylog_mod->getOne($tx_sql);
        $basedj = $shouddj * 0.2 + $money_tx;
//            echo $basedj . '-' . $my_money[0]['money_dj'];
        if (bccomp($basedj, $my_money[0]['money_dj']) == 1) {
            $duojd_money = $basedj - $my_money[0]['money_dj'];
            $cantx = $my_money[0]['money'] - $duojd_money;
        } else {
            $cantx = $my_money[0]['money'];
        }
        $this->assign('cantx', $cantx);
        /**
         * end of tx and jd 
         */
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('index', $index); //传递到风格里
        $this->display('check_behalf_list.html');
        return;
    }

    private function userspec($id, $fro = false, $adjust = false) {
//        $id = 10919;
        $my_money_mod = & m('my_money');
        $my_moneylog_mod = & m('my_moneylog');
        $my_order = &m('order');
        $statis2 = $my_money_mod->getRow('select * from ecm_my_money where user_id=' . $id);
//        echo 'Welcome to User Spec! ';
        $user_id = $statis2['user_id'];
        $user_name = $statis2['user_name'];
        $money = $statis2['money'];
        $money_dj = $statis2['money_dj'];
        $total = $money + $money_dj;
        $this->assign('user_id', $user_id);
        $this->assign('user_name', $user_name);
        $this->assign('money', $money);
        $this->assign('money_dj', $money_dj);
        $this->assign('total', $total);

        $djed_sql = 'select sum(order_amount) from ecm_order where status in (20,30) and bh_id=' . $id;
        $money_odj = $my_moneylog_mod->getOne($djed_sql);
        $this->assign('money_odj', $money_odj);
        //日志检验上面的
        $djed2_sql = 'select sum(money_zs)  from ecm_my_moneylog where  leixing in (10) and caozuo in (10,20)  and user_id=' . $id;
        $log_money_dj = $my_moneylog_mod->getOne($djed2_sql);
        $this->assign('log_money_dj', $log_money_dj);

        $finished_sql = 'select sum(order_amount) from ecm_order where  status = 40 and bh_id=' . $user_id;
        $money_finished = $my_moneylog_mod->getOne($finished_sql);
        $this->assign('money_finished', $money_finished);
        //日志检验
        $finished2_sql = 'select sum(money_zs)  from ecm_my_moneylog where  leixing in (10) and caozuo in (40)  and user_id=' . $user_id;
        $log_money_finished = $my_moneylog_mod->getOne($finished2_sql);
        $this->assign('log_money_finished', $log_money_finished);
//1,2,3 完成的金额
        $finished_sql_123 = 'select sum(l.money_zs)  from ecm_my_moneylog l , ecm_order o  where  l.order_id=o.order_id and o.status=0 and l.caozuo=80 and user_id=' . $user_id;
        $money_finished_123 = $my_moneylog_mod->getOne($finished_sql_123);
        $this->assign('money_finished_123', $money_finished_123);


        $inout_sql = 'select sum(money_zs)  from ecm_my_moneylog where leixing in(30 ) and caozuo in (50,4)  and user_log_del = 0 and user_id=' . $user_id;
        $money_ct = $my_moneylog_mod->getOne($inout_sql);
        $this->assign('money_ct', $money_ct);
        $tx_sql = 'select sum(money)  from ecm_my_moneylog where leixing in(40 ) and caozuo in (61)  and user_log_del = 0 and user_id=' . $user_id;
        $money_tx = $my_moneylog_mod->getOne($tx_sql);
        $this->assign('money_tx', $money_tx);

        $io_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (20,30,40,50) and leixing in(21,11) and  user_id=' . $user_id;
        $money_io = $my_moneylog_mod->getOne($io_sql);
        $this->assign('money_io', $money_io);


        $common_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (20,40,50,4,10,80) and  user_id=' . $user_id;
        $money_common = $my_moneylog_mod->getOne($common_sql);
        $this->assign('money_common', $money_common);
        $c_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (30) and leixing in(21,11) and  user_id=' . $user_id;
        $money_io_only30 = $my_moneylog_mod->getOne($c_sql);
        $this->assign('money_io_only30', $money_io_only30);

        $money_common = $money_common + $money_io_only30 + $money_tx ;//21,11不可能取消；取消的都是误操作
        $this->assign('money_common', $money_common);
        $money_all_add = ($money_odj + $money_finished + $money_finished_123 + $money_ct + $money_io + $money_tx);
        $this->assign('money_all_add', $money_all_add);

        if (bccomp($money_odj, $log_money_dj) == 0 && bccomp($money_finished, $log_money_finished) == 0
                && bccomp($money_common, $money_all_add) == 0 && $adjust) {
            $this->assign('system', 'you see , log system say it is right ! ');
            if (bccomp($total, $money_common) == 0) {
                $this->assign('realMoney', 'you see , realMoney is also right as log system! ');
            } else if ($total > $money_common) {

                $statis2 = $my_money_mod->getRow('select * from ecm_my_money where user_id=' . $id);
                $money = $statis2['money'];
                $money_dj = $statis2['money_dj'];

                $sub = $total - $money_common;
                $money_new = $money - $sub;
                $money_array = array(
                    'money' => $money_new,
                );
                $this->my_money_mod->edit('user_id=' . $id, $money_array);
                $this->assign('realMoney', 'you see , realMoney > log system! sub' . $sub . ' to be right ');
            } else if ($total < $money_common) {

                $statis2 = $my_money_mod->getRow('select * from ecm_my_money where user_id=' . $id);
                $money = $statis2['money'];
                $money_dj = $statis2['money_dj'];

                $add = $money_common - $total;
                $money_new = $money + $add;
                $money_array = array(
                    'money' => $money_new,
                );
                $this->my_money_mod->edit('user_id=' . $id, $money_array);
                $this->assign('realMoney', 'you see , realMoney < log system! add ' . $add . ' to be right ');
            }
            //冻结资金的调整
//            if ($fro) {
//                if ($this->floatgtr($log_money_dj, $money_dj)) {
//                    $adustd = $log_money_dj - $money_dj;
//                    $issuc = $this->manuFro($id, $adustd);
//                    if ($issuc) {
//                        echo '<br>manu fro success!: ' . $adustd;
//                    } else {
//                        echo '<br>manu fro failed!: ' . $adustd;
//                    }
//                    $this->userspec();
//                } else if ($this->floatles($log_money_dj, $money_dj)) {
//                    $adustd = $money_dj - $log_money_dj;
//                    $issuc = $this->manuReFro($id, $adustd);
//                    if ($issuc) {
//                        echo '<br>manuReFro success!: ' . $adustd;
//                    } else {
//                        echo '<br>manuReFro failed!: ' . $adustd;
//                    }
//                    $this->userspec();
//                } else {
//                    echo '<br>fro money is right as log';
//                    exit(' <br>it is over !');
//                }
//            } else {
////                echo '<br>we do not check fro money ';
//            }
        } else {
            $this->assign('system', 'you see , log system is not right or we have not adjust ! ');
        }
    }

    /**
     * 对于今天在各种里面都加,却没有在log里加的
     */
    function addLostLog() {
        $sql = 'select out_trade_no, total_fee,customer_id ,  (unix_timestamp(endtime)-8*3600) as tt  from ecm_paylog where trade_status=1 and createtime > curdate() and out_trade_no not in (select order_sn from ecm_my_moneylog where leixing in(30, 40 ) and caozuo in (50,4)  and user_log_del = 0 and from_unixtime(admin_time) > date_sub(curdate(), interval 8 hour))';
        $index = $this->my_moneylog_mod->getAll($sql);
        if ($index) {
            foreach ($index as $in) {
                //添加日志
                $log_text = '无手续费充值tradeEx'; //$this->visitor->get('user_name') . Lang::get('tongguoalipaychongzhi') . $total_fee . Lang::get('yuan');
                $user_name = $this->my_money_mod->getOne('select user_name from ecm_my_money where user_id=' . $in['customer_id']);
                $add_mymoneylog = array(
                    'user_id' => $in['customer_id'],
                    'user_name' => $user_name,
                    'buyer_name' => Lang::get('alipay'),
                    'seller_id' => $in['customer_id'],
                    'seller_name' => $user_name,
                    'order_sn ' => $in['out_trade_no'],
                    'add_time' => $in['tt'],
                    'admin_time' => $in['tt'],
                    'leixing' => 30,
                    'money_zs' => $in['total_fee'],
                    'money' => $in['total_fee'],
                    'log_text' => $log_text,
                    'caozuo' => 4,
                    's_and_z' => 1,
                    'moneyleft' => 0,
                );
                $this->my_moneylog_mod->add($add_mymoneylog);
            }
        }
    }

    function batch_transfer() {
        require_once("../data/config.alipay.php");
        if (isset($_GET['ids']) && $_GET['ids']) {
            $this->handle_ids(trim($_GET['ids']));
        }
        $this->assign('WIDemail', $batch_ali['account']);
        $this->assign('WIDaccount_name', $batch_ali['name']);
        $this->assign('WIDpay_date', date("Ymd", time()));
        $this->display('batch_transfer.html');
        return;
    }

    /**
     * 前提:私有调用　，必然非空
     * 1通过ids 将选择好的id 进行数据的组织，进行assign
     * 2 将id对应的相关记录进行入库; 如果存在，且原来是空或者失败，则先删除再插入；如果成功过，当然不能处理了，记错误日志．
     * @param type $ids 
     */
    private function handle_ids($ids) {
        $ids = explode(',', $ids); //
        $details = '';
        $sumc = 0;
        $sum = 0;
        $batchnum = date("YmdHis", time());
        foreach ($ids as $id) {
            $sql = ' select a.id as flownumber , a.money_zs as ori_money,b.bank_sn as account  ,b.bank_username as name, b.bank_name as bankname,b.bank_add as bankadd   from ecm_my_moneylog a , ecm_my_money  b where a.caozuo=60 and a.id = ' . $id . ' and a.user_id =b.user_id';
            $row = $this->my_moneylog_mod->db->getRow($sql);
          
            if (!$row) {
                continue;
            }
            if (strpos($row['name'], '银行') > 0) {
                continue;
            }
            if (!empty($row['bankname']) && !empty($row['bankadd'])) {
                continue;
            }
//              var_dump($row);
            $now_money = $row['ori_money'] * 0.988;
            $sitesave_money = $row['ori_money'] * 0.012;
            if (bccomp($row['ori_money'] * 0.012, 2) < 0) {
                $now_money = $row['ori_money'] - 2;
                $sitesave_money = 2;
            }
            $now_money = round($now_money, 2);
            $sitesave_money = round($sitesave_money, 2);
            if (!empty($details)) {
                $details.='|';
            }
            //(1) 组建detail
            $details.=$row['flownumber'] . '^' . $row['account'] . '^' . $row['name'] . '^' . $now_money . '^51提现';
            $sumc++;
            $sum += $now_money;
            $aliget_money = $now_money * 0.005;
            if ($aliget_money < 1) {
                $aliget_money = 1;
            } else if ($aliget_money > 25) {
                $aliget_money = 25;
            }
            //(2) 写入数据库
            $logs_data = $this->batchtrans_mod->find('flownumber=' . $id . ' and flag="S"');
            if ($logs_data) {
                Log::write("transfer is already exists:" . $id);
            } else {
                $this->batchtrans_mod->drop('flownumber=' . $id); // flag为空或者是Ｆ
            }
            $row['now_money'] = $now_money;
            $row['aliget_money'] = $aliget_money;
            $row['sitesave_money'] = $sitesave_money;
            $row['createtime'] = date("Y-m-d H:i:s", time());
            $row['batchnum'] = $batchnum;
            unset($row['bankname']);
            unset($row['bankadd']);
            $row['name'] = mysql_escape_string($row['name']);
            $this->batchtrans_mod->add($row);
        }
        //(3) 
        $this->assign('WIDbatch_fee', $sum);
        $this->assign('WIDbatch_num', $sumc);
        $this->assign('WIDbatch_no', $batchnum);
        $this->assign('WIDdetail_data', $details);
    }

}

?>