<?php
require_once("includes/Alipay/corefunction.php");
require_once("includes/Alipay/md5function.php");
require_once("includes/Alipay/notify.php");
require_once("includes/Alipay/submit.php");

class My_moneyApp extends MemberbaseApp {

    function My_moneyApp() {
        parent::__construct();
        $this->my_money_mod = & m('my_money');
        $this->my_moneylog_mod = & m('my_moneylog');
        $this->my_mibao_mod = & m('my_mibao');
        $this->order_mod = & m('order');
        $this->my_card_mod = & m('my_card');
        $this->my_jifen_mod = & m('my_jifen');
        $this->my_paysetup_mod = & m('my_paysetup');
    }

    function exits() {
        //执行关闭页面
        echo "<script language='javascript'>window.opener=null;window.close();</script>";
    }

    function index() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('jiaoyichaxun')
        );
        /* 当前用户中心菜单 */
        $this->_curitem('jiaoyichaxun');
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('shangfutong'));
        $my_money = $this->my_money_mod->getAll("select * from " . DB_PREFIX . "my_money where user_id=$user_id");
        $this->assign('my_money', $my_money);
        $this->display('my_money.index.html');
    }

    function loglist() {
        $user_id = $this->visitor->get('user_id');

        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('jiaoyichaxun')
        );
//        $this->keepSerial(null, 3);
        /* 当前用户中心菜单 */
        $this->_curitem('jiaoyichaxun');
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('jiaoyichaxun') . ' - ' . Lang::get('yuezhuanzhang'));
        $my_money = $this->my_money_mod->getAll("select * from " . DB_PREFIX . "my_money where user_id=$user_id");
        $this->assign('my_money', $my_money);
        
          $isbehalfsql = ' select * from ecm_behalf where bh_id='.$user_id;
            $isbehalf = $this->my_moneylog_mod->getRow($isbehalfsql);
          if (!empty($isbehalf)) {
             $this->assign('isbehalf', 1);
//        if ($user_id == 10919) {
            $djed_sql = 'select sum(order_amount) from ecm_order where status in (20,30) and  bh_id='.$user_id;
            $shouddj = $this->my_moneylog_mod->getOne($djed_sql);
            $shoud['shouddj'] = $shouddj;
            $shoud['sjdj'] = $my_money[0]['money_dj'];
            $shoud['exdj'] = $shouddj - $my_money[0]['money_dj'];
            $shoud['twdj'] = $shouddj * 0.2;
            $this->assign('shoud', $shoud);

            $tx_sql = 'select sum(money_zs)  from ecm_my_moneylog where leixing in(40 ) and caozuo in (60)  and user_log_del = 0 and user_id='.$user_id;;
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
        }
//         $page['item_count'] = $this->my_moneylog_mod->getCount();

        $tx_sql = 'select sum(money)  from ecm_my_moneylog where leixing in(40 ) and caozuo in (61)  and user_log_del = 0 and user_id=' . $user_id;
        $money_tx = $this->my_moneylog_mod->getOne($tx_sql);
        $common_sql1 = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (20,40,50,10) and  user_id=' . $user_id;
        $money_common1 = $this->my_moneylog_mod->getOne($common_sql1);
        $common_sql2 = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (4) and  user_id=' . $user_id;
        $money_common2 = $this->my_moneylog_mod->getOne($common_sql2);
        $c_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (30) and leixing in(21,11) and  user_id=' . $user_id;
        $money_io_only30 = $this->my_moneylog_mod->getOne($c_sql);

        $finished_sql_123 = 'select sum(l.money)  from ecm_my_moneylog l , ecm_order o  where  l.order_id=o.order_id and o.status=0 and l.caozuo=80 and user_id=' . $user_id;
        $money_finished_123 = $this->my_moneylog_mod->getOne($finished_sql_123);

        $money_common = $money_common1 + $money_common2 + $money_io_only30 + $money_tx + $money_finished_123;
//         echo $money_common. ' is the total <br>';
//         echo '$money_common1 + $money_common2 + $money_io_only30 + $money_tx + $money_finished_123;'.$money_common1.' +' .
//                 $money_common2 .'+ '.$money_io_only30 .'+'. $money_tx.'+ '. $money_finished_123;
        $this->assign('one', $money_common1 ? $money_common1 : 0);
        $this->assign('two', $money_common2 ? $money_common2 : 0);
        $this->assign('three', $money_io_only30 ? $money_io_only30 : 0);
        $this->assign('four', $money_tx ? $money_tx : 0);
        $this->assign('five', $money_finished_123 ? $money_finished_123 : 0);
        $this->assign('six', $money_common);
        $this->display('my_money.loglist.html');
    }

//买入查询
    function buyer() {
        $user_id = $this->visitor->get('user_id');
        $type = $_GET['select'];
        $so = $_GET['so'];
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('mairuchaxun')
        );
        /* 当前用户中心菜单 */
        $this->_curitem('jiaoyichaxun');
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('jiaoyichaxun') . ' - ' . Lang::get('mairuchaxun'));
        $page = $this->_get_page();
        $mycondition = '';
        if ($so && !empty($so)) {
            if ($type == '1') {
                $mycondition = ' and order_sn="' . $so . '"';
            } else if ($type == '2') {
                $mycondition = ' and money_zs="' . $so . '"';
            } else if ($type == '3') {
                $mycondition = ' and log_text like "%' . $so . '%"';
            }
        }
        $my_money = $this->my_moneylog_mod->find(array(
            'conditions' => "user_id='$user_id' and s_and_z=2 and user_log_del=0 and leixing=20" . $mycondition,
            'limit' => $page['limit'],
            'order' => "id desc",
            'count' => true,
                ));

        $page['item_count'] = $this->my_moneylog_mod->getCount();

        $statistic = array();
        $statistic['daifukuan'] = $this->my_moneylog_mod->getOne('select count(*) from ecm_my_moneylog where ' . "user_id='$user_id' and s_and_z=2 and user_log_del=0 and leixing=20" .
                ' and caozuo = 0');
        $statistic['yizifu'] = $this->my_moneylog_mod->getOne('select count(*) from ecm_my_moneylog where ' . "user_id='$user_id' and s_and_z=2 and user_log_del=0 and leixing=20" .
                ' and caozuo = 10');
        $statistic['yifahuo'] = $this->my_moneylog_mod->getOne('select count(*) from ecm_my_moneylog where ' . "user_id='$user_id' and s_and_z=2 and user_log_del=0 and leixing=20" .
                ' and caozuo = 20');
        $statistic['yiwangcheng'] = $this->my_moneylog_mod->getOne('select count(*) from ecm_my_moneylog where ' . "user_id='$user_id' and s_and_z=2 and user_log_del=0 and leixing=20" .
                ' and caozuo = 40');
        $statistic['yiquxiao'] = $this->my_moneylog_mod->getOne('select count(*) from ecm_my_moneylog where ' . "user_id='$user_id' and s_and_z=2 and user_log_del=0 and leixing=20" .
                ' and caozuo = 30');
        $statistic['quaner'] = $this->my_moneylog_mod->getOne('select count(*) from ecm_my_moneylog where ' . "user_id='$user_id' and s_and_z=2 and user_log_del=0 and leixing=20" .
                ' and caozuo = 80');
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('my_money', $my_money);
        $this->assign('statistic', $statistic);
        $this->assign('one', $statistic['daifukuan']);
        $this->assign('two', $statistic['yizifu']);
        $this->assign('three', $statistic['yifahuo']);
        $this->assign('four', $statistic['yiwangcheng']);
        $this->assign('five', $statistic['yiquxiao']);
        $this->assign('six', $statistic['quaner']);

        $this->display('my_money.buyer.html');
    }

//收入查询
    function seller() {
        $user_id = $this->visitor->get('user_id');
        $type = $_GET['select'];
        $so = $_GET['so'];
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('maichuchaxun')
        );
        /* 当前用户中心菜单 */
        $this->_curitem('jiaoyichaxun');
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('jiaoyichaxun') . ' - ' . Lang::get('maichuchaxun'));
        $page = $this->_get_page();
        $mycondition = '';
        if ($so && !empty($so)) {
            if ($type == '1') {
                $mycondition = ' and order_sn="' . $so . '"';
            } else if ($type == '2') {
                $mycondition = ' and money_zs="' . $so . '"';
            } else if ($type == '3') {
                $mycondition = ' and log_text like "%' . $so . '%"';
            }
        }
        $my_money = $this->my_moneylog_mod->find(array(
            'conditions' => "user_id='$user_id' and s_and_z=1 and user_log_del=0 and leixing=10" . $mycondition,
            'limit' => $page['limit'],
            'order' => "id desc",
            'count' => true,
                ));

        /* 判断代发订单 or 卖家订单  by tiq  start */
        if ($my_money) {
            $my_money_orders_ids = array();
            //get order_id  只用查1次db
            foreach ($my_money as $value) {
                $my_money_orders_ids[] = $value['order_id'];
            }

            $mod_order = & m('order');
            $my_money_orders = $mod_order->find(array(
                'conditions' => db_create_in($my_money_orders_ids, 'order_id'),
                'fields' => 'order_id,bh_id',
                    ));

            foreach ($my_money as $key => $value) {
                foreach ($my_money_orders as $mkey => $mvalue) {
                    if ($mvalue['order_id'] == $value['order_id']) {
                        $my_money[$key]['bh_id'] = $mvalue['bh_id'];
                        break;
                    }
                }
            }
        }
        /* 判断代发订单 or 卖家订单  by tiq  end */

        $page['item_count'] = $this->my_moneylog_mod->getCount();

        $basicCon = 'select count(*) from ecm_my_moneylog where ' . 'user_id=' . $user_id . ' and s_and_z=1 and user_log_del=0 and leixing=10';
        $statistic = array();
        $statistic['daifukuan'] = $this->my_moneylog_mod->getOne($basicCon . ' and caozuo = 0');
        $statistic['yizifu'] = $this->my_moneylog_mod->getOne($basicCon . ' and caozuo = 10');
        $statistic['yifahuo'] = $this->my_moneylog_mod->getOne($basicCon . ' and caozuo = 20');
        $statistic['yiwangcheng'] = $this->my_moneylog_mod->getOne($basicCon . ' and caozuo = 40');
        $statistic['yiquxiao'] = $this->my_moneylog_mod->getOne($basicCon . ' and caozuo = 30');
        $statistic['quaner'] = $this->my_moneylog_mod->getOne($basicCon . ' and caozuo = 80');
        $this->assign('statistic', $statistic);
        $this->assign('one', $statistic['daifukuan']);
        $this->assign('two', $statistic['yizifu']);
        $this->assign('three', $statistic['yifahuo']);
        $this->assign('four', $statistic['yiwangcheng']);
        $this->assign('five', $statistic['yiquxiao']);
        $this->assign('six', $statistic['quaner']);

        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('my_money', $my_money);
        $this->display('my_money.seller.html');
    }

//收入查询
    function flow() {
        $user_id = $this->visitor->get('user_id');
        $type = $_GET['select'];
        $so = $_GET['so'];
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('flow')
        );
        /* 当前用户中心菜单 */
        $this->_curitem('jiaoyichaxun');
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('jiaoyichaxun') . ' - ' . Lang::get('maichuchaxun'));
        $page = $this->_get_page();
        $mycondition = '';
        if ($so && !empty($so)) {
            if ($type == '1') {
                $mycondition = ' and order_sn="' . $so . '"';
            } else if ($type == '2') {
                $mycondition = ' and money_zs="' . $so . '"';
            } else if ($type == '3') {
                $mycondition = ' and log_text like "%' . $so . '%"';
            }
        }
//        $my_money = $this->my_moneylog_mod->find(array(
//            'conditions' => "user_id='$user_id' and s_and_z in(1,2) and user_log_del=0 and leixing in (11,21,10,20,30,40)" . $mycondition,
//            'limit' => $page['limit'],
//            'order' => "admin_time desc, add_time desc",
////                 'order' => "id desc",
//            'count' => true,
//                ));
          $ssql = 'select a.* from 
                 (select   user_id as user_id,  user_name as user_name,    order_id as order_id,    order_sn as order_sn,      add_time as add_time,   admin_name as admin_name,  admin_time as admin_time,  leixing as leixing ,caozuo as caozuo ,s_and_z as s_and_z ,user_log_del as user_log_del,  money_zs as money_zs,  money as money  ,log_text as log_text,  moneyleft as moneyleft from ecm_my_moneylog where user_log_del=0 '.$mycondition.' and user_id="'.$user_id.'")a
                     union
select b.* from (select   user_id as user_id,  user_name as user_name,    order_id as order_id,    order_sn as order_sn,      add_time as add_time,   admin_name as admin_name,  add_time as admin_time,  leixing as leixing ,10 as caozuo ,s_and_z as s_and_z ,user_log_del as user_log_del,  money_zs as money_zs,  money as money  ,log_text as log_text,  0.00 as moneyleft from ecm_my_moneylog where caozuo=30 '.$mycondition.' and user_id="'.$user_id.'" and user_log_del=0)b
  order by admin_time desc limit '. $page['limit'];
           $ssql2 = 'select count(*) from (select a.* from (select user_id as user_id,  user_name as user_name,    order_id as order_id,    order_sn as order_sn,      add_time as add_time,   admin_name as admin_name,  admin_time as admin_time,  leixing as leixing ,caozuo as caozuo ,s_and_z as s_and_z ,user_log_del as user_log_del,  money_zs as money_zs,  money as money  ,log_text as log_text,  moneyleft as moneyleft from ecm_my_moneylog where user_log_del=0 '.$mycondition.' and user_id="'.$user_id.'")a
                     union
select b.* from (select   user_id as user_id,  user_name as user_name,    order_id as order_id,    order_sn as order_sn,      add_time as add_time,   admin_name as admin_name,  add_time as admin_time,  leixing as leixing ,10 as caozuo ,s_and_z as s_and_z ,user_log_del as user_log_del,  money_zs as money_zs,  money as money  ,log_text as log_text,  0.00 as moneyleft from ecm_my_moneylog where caozuo=30 '.$mycondition.' and user_id="'.$user_id.'" and user_log_del=0)b
  )c';
           
          $my_money =   $this->my_moneylog_mod->db->getAll($ssql);
           $page['item_count'] = $this->my_moneylog_mod->getOne($ssql2);
//      echo "user_id='$user_id' and s_and_z in(1,2) and user_log_del=0 and leixing in (11,21,10,20,30,40)".$mycondition;
        /* 判断代发订单 or 卖家订单  by tiq  start */
        if ($my_money) {
            $my_money_orders_ids = array();
            //get order_id  只用查1次db
            foreach ($my_money as $value) {
                $value['order_id'] > 0 && $my_money_orders_ids[] = $value['order_id'];
            }

            $mod_order = & m('order');
            $my_money_orders = $mod_order->find(array(
                'conditions' => db_create_in($my_money_orders_ids, 'order_id'),
                'fields' => 'order_id,bh_id',
                    ));

            foreach ($my_money as $key => $value) {
                foreach ($my_money_orders as $mkey => $mvalue) {
                    if ($mvalue['order_id'] == $value['order_id']) {
                        $my_money[$key]['bh_id'] = $mvalue['bh_id'];
                        break;
                    }
                }
            }
        }
        /* 判断代发订单 or 卖家订单  by tiq  end */

//        $page['item_count'] = $this->my_moneylog_mod->getCount();

        $tx_sql = 'select sum(money)  from ecm_my_moneylog where leixing in(40 ) and caozuo in (61)  and user_log_del = 0 and user_id=' . $user_id;
        $money_tx = $this->my_moneylog_mod->getOne($tx_sql);
        $common_sql1 = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (20,40,50,10) and  user_id=' . $user_id;
        $money_common1 = $this->my_moneylog_mod->getOne($common_sql1);
        $common_sql2 = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (4) and  user_id=' . $user_id;
        $money_common2 = $this->my_moneylog_mod->getOne($common_sql2);
        $c_sql = 'select sum(money_zs) from ecm_my_moneylog where user_log_del=0 and caozuo in (30) and leixing in(21,11) and  user_id=' . $user_id;
        $money_io_only30 = $this->my_moneylog_mod->getOne($c_sql);

        $finished_sql_123 = 'select sum(l.money)  from ecm_my_moneylog l , ecm_order o  where  l.order_id=o.order_id and o.status=0 and l.caozuo=80 and user_id=' . $user_id;
        $money_finished_123 = $this->my_moneylog_mod->getOne($finished_sql_123);

        $money_common = $money_common1 + $money_common2 + $money_io_only30 + $money_tx + $money_finished_123;
//         echo $money_common. ' is the total <br>';
//         echo '$money_common1 + $money_common2 + $money_io_only30 + $money_tx + $money_finished_123;'.$money_common1.' +' .
//                 $money_common2 .'+ '.$money_io_only30 .'+'. $money_tx.'+ '. $money_finished_123;
        $this->assign('one', $money_common1 ? $money_common1 : 0);
        $this->assign('two', $money_common2 ? $money_common2 : 0);
        $this->assign('three', $money_io_only30 ? $money_io_only30 : 0);
        $this->assign('four', $money_tx ? $money_tx : 0);
        $this->assign('five', $money_finished_123 ? $money_finished_123 : 0);
        $this->assign('six', $money_common);

        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('my_money', $my_money);
        $this->display('my_money.flow.html');
    }

//帐户转出
    function outlog() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('zhuanchuchaxun')
        );
        /* 当前用户中心菜单 */
        $this->_curitem('jiaoyichaxun');
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('jiaoyichaxun') . ' - ' . Lang::get('zhuanchuchaxun'));
        $page = $this->_get_page();
        $my_money = $this->my_moneylog_mod->find(array(
            'conditions' => "user_id='$user_id' and s_and_z=2 and user_log_del=0 and leixing=21",
            'limit' => $page['limit'],
            'order' => "id desc",
            'count' => true,
                ));

        $page['item_count'] = $this->my_moneylog_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('my_money', $my_money);
        $this->display('my_money.outlog.html');
    }

//帐户转入
    function intolog() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('zhuanruchaxun')
        );
        /* 当前用户中心菜单 */
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('jiaoyichaxun') . ' - ' . Lang::get('zhuanruchaxun'));
        $this->_curitem('jiaoyichaxun');
        $page = $this->_get_page();

        $my_money = $this->my_moneylog_mod->find(array(
            'conditions' => "user_id='$user_id' and s_and_z=1 and user_log_del=0 and leixing=11",
            'limit' => $page['limit'],
            'order' => "id desc",
            'count' => true,
                ));

        $page['item_count'] = $this->my_moneylog_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('my_money', $my_money);
        $this->display('my_money.intolog.html');
    }

    function keepSerial($title, $type) {
        $cache_server = & cache_server();
        if ($title && $type == 1) {
            $data = $cache_server->get('serialkeyResolved');
            if (is_array($data)) {
                return $data[$title];
            }
        } else if ($title && $type == 0) {
            $data = $cache_server->get('serialkeyResolved');
            if (is_array($data)) {
                //让其自动超时吧
//                unset($data[$title]);
//                $cache_server->set('serialkeyResolved',$data);
            }
            return;
        } else if ($title == null && $type == 3) {
            $data = $cache_server->get('serialkeyResolved');
            if ($data && is_array($data)) {
                foreach ($data as $k => $v) {
                    $user_id = $v['user_id'];
                    $user_name = $v['user_name'];
                    $title = $v['title'];
                    $totalfee = $v['totalfee'];
                    if (defined('OEM')) {
                        $user_name.='&oem=' . OEM;
                    }
                    $user_name.='&title=' . $title;
                    $url = "http://" . MONEYSITE . "/index.php?app=default&act=searchtrade" . "&totalfee=" . $totalfee . "&user_id=" . $user_id . "&user_name=" . $user_name;
                    $res = @json_decode($this->Get($url), true);
//                    print_r($res);
                    $this->keepSerial($title, 0);
                }
            }
        }
    }

    function keepPossible() {
        $cache_server = & cache_server();
        $data = $cache_server->get('serialkeyResolved');
        $userinfo = array();
        !$data && $data = array();
        $userinfo['user_id'] = $this->visitor->get('user_id');
        $userinfo['user_name'] = $this->visitor->get('user_name');
        $userinfo['title'] = $_GET['title'];
        $userinfo['totalfee'] = $_GET['totalfee'];
        $key = $userinfo['title'];
        $data[$key] = $userinfo;
        $cache_server->set('serialkeyResolved', $data, 600);

//        $paylogmodel = &m('paylog');
//        $condition ['out_trade_no'] = $userinfo['title'];
//        $condition ['total_fee'] = $userinfo['totalfee'];
//        $condition ['createtime'] = date("Y-m-d H:i:s", time());
//        $condition ['endtime'] = date("Y-m-d H:i:s", time());
//        $condition ['trade_status'] = 0; //未成交
//        $condition ['customer_id'] = $userinfo['user_id'];
//        $condition ['customer_name'] = $userinfo['user_name'];
//        $condition ['type'] = 0;
//        $condition ['trade_no'] = $userinfo['title'];
//        $paylogmodel->add($condition);
        $this->addPaylog($userinfo['user_name'], $userinfo['user_id'], $userinfo['title'], $userinfo['totalfee']);
        $this->json_result('true');
    }

    /**
     * 对title作唯一的存储，如果有的话，就进行编辑与更新；　如果没有，就加入
     * @param type $user_name
     * @param type $user_id
     * @param type $title
     * @param type $totalfee 
     */
    function addPaylog($user_name, $user_id, $title, $totalfee) {
        if (!$totalfee || empty($totalfee)) {
            $totalfee = 0;
        }
        $paylogmodel = &m('paylog');
        $condition ['out_trade_no'] = $title;
        $condition ['total_fee'] = $totalfee;
        $condition ['createtime'] = date("Y-m-d H:i:s", time());
        $condition ['endtime'] = date("Y-m-d H:i:s", time());
        $condition ['trade_status'] = 0; //未成交
        $condition ['customer_id'] = $user_id;
        $condition ['customer_name'] = $user_name;
        $condition ['type'] = 0;
        $condition ['trade_no'] = $title;

        $sql = 'SELECT trade_no from  ' . $paylogmodel->table . '  where trade_no="' . $title . '"';
        $results = $paylogmodel->db->getOne($sql);
        if ($results && !empty($results)) {
            $paylogmodel->edit('trade_no="' . $title . '"', $condition);
        } else {
            $paylogmodel->add($condition);
        }
    }

//充值查询
    function paylist() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('chongzhichaxun')
        );
        /* 当前用户中心菜单 */
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('chongzhichaxun') . ' - ' . Lang::get('zaixianchongzhi'));
        $this->_curitem('chongzhichaxun');
        $merchantSn = 'ZSerial:' . $user_id . '_' . date('y_m_d_H_i_s', time());
        $this->addPaylog($this->visitor->get('user_name'), $user_id, $merchantSn, 0);
        $my_money = $this->my_money_mod->getAll("select * from " . DB_PREFIX . "my_money where user_id=$user_id");
        $this->import_resource(array(
            'script' => array(
                array(
                    'path' => 'jquery.plugins/jquery.validate.js',
                    'attr' => '',
                )
            ),
        ));
        $this->assign('aliaccount',ALIACCOUNT);
        $this->assign('my_money', $my_money);
        $this->assign('merchantSn', $merchantSn);
        $this->display('my_money.paylist.html');
    }

    function getNewSerial() {
//        $merchantSn = 'ZSerial:' . mt_rand(10000, 99999) . '_' . date('y_m_d_H_i_s', time());
        $merchantSn = 'ZSerial:' . $this->visitor->get('user_id') . '_' . date('y_m_d_H_i_s', time());
        $this->addPaylog($this->visitor->get('user_name'), $this->visitor->get('user_id'), $merchantSn, 0);
        $this->json_result($merchantSn);
    }

//积分兑换
    function jifen() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('jifenduihuan')
        );
        /* 当前用户中心菜单 */
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('shangfutong') . ' - ' . Lang::get('jifenduihuan'));
        $this->_curitem('jifenduihuan');
        $my_money = $this->my_money_mod->getAll("select * from " . DB_PREFIX . "my_money where user_id=$user_id");
        $this->assign('my_money', $my_money);

        $page = $this->_get_page(2);
        $index = $this->my_jifen_mod->find(array(
            'conditions' => 'yes_no=1 and user_id=0', //条件
            'limit' => $page['limit'],
            'order' => 'jifen desc',
            'count' => true));

        $page['item_count'] = $this->my_jifen_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('index', $index);
        $this->display('my_money.jifen.html');
    }

    function jifen_post() {
        $id = $_GET["id"];
        $user_id = $this->visitor->get('user_id');

        if ($_POST) {
            $duihuanshu = trim($_POST['duihuanshu']);
            $my_jifen = $this->my_jifen_mod->getrow("select * from " . DB_PREFIX . "my_jifen where id=$id");
            $shengyushuliang = $my_jifen['shuliang'] - $my_jifen['yiduihuan']; //剩余可兑换数

            if (empty($duihuanshu)) {
                $this->show_warning('shuliangbugou');
                return;
            }
            if (preg_match("/[^0.-9]/", $duihuanshu)) {
                $this->show_warning('cuowu_nishurudebushishuzilei');
                return;
            }
            if ($duihuanshu > $shengyushuliang) {
                $this->show_warning('shuliangbugou');
                return;
            }
            $jifen = $my_jifen['jifen'] * $duihuanshu;
            $money_row = $this->my_money_mod->getrow("select jifen from " . DB_PREFIX . "my_money where user_id='$user_id'");
            if ($jifen > $money_row['jifen']) {
                $this->show_warning('jifenbuzu'); //积分不足
                return;
            }
            //兑换成功，减少该用户的积分
            $xjifen = $money_row['jifen'] - $jifen;
            $user_jifen = array(
                'jifen' => $xjifen,
            );
            $this->my_money_mod->edit('user_id=' . $user_id, $user_jifen);
            //兑换成功，写入一条数据
            $add_array = array(
                'add_time' => time(),
                'jifen' => $jifen,
                'wupin_name' => $my_jifen['wupin_name'],
                'wupin_img' => $my_jifen['wupin_img'],
                'jiazhi' => $my_jifen['jiazhi'],
                'shuliang' => $duihuanshu,
                'user_id' => $this->visitor->get('user_id'),
                'user_name' => $this->visitor->get('user_name'),
                'my_name' => trim($_POST['my_name']),
                'my_add' => trim($_POST['my_add']),
                'my_tel' => trim($_POST['my_tel']),
                'my_mobile' => trim($_POST['my_mobile']),
                'log_text' => $my_jifen['log_text'],
            );
            $this->my_jifen_mod->add($add_array);
            //兑换成功，更新ID对应的数量及已兑换数量
            $edit_array = array(
                'yiduihuan' => $my_jifen['yiduihuan'] + $duihuanshu,
            );
            $this->my_jifen_mod->edit('id=' . $id, $edit_array);
            $this->show_message('duihuanchenggong', 'duihuanchenggong', 'index.php?app=my_money&act=duihuan_jilu'); //兑换成功 index.php?app=my_money&act=duihuan_jilu
            return;
        } else {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('jifenduihuan')
            );
            /* 当前用户中心菜单 */
            $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('shangfutong') . ' - ' . Lang::get('jifenduihuan'));
            $this->_curitem('jifenduihuan');


            $index = $this->my_jifen_mod->find(array(
                'conditions' => "yes_no=1 and id='$id' and user_id=0", //条件
                'limit' => $page['limit'],
                'count' => true));


            $this->assign('index', $index);
            $this->display('my_money.jifen_post.html');
        }
    }

//已兑换记录
    function duihuan_jilu() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('jifenduihuan')
        );
        /* 当前用户中心菜单 */
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('shangfutong') . ' - ' . Lang::get('jifenduihuan'));
        $this->_curitem('jifenduihuan');
        $page = $this->_get_page();

        $index = $this->my_jifen_mod->find(array(
            'conditions' => "yes_no=0 and user_id='$user_id'", //条件
            'limit' => $page['limit'],
            'order' => 'id desc',
            'count' => true,
                ));

        $page['item_count'] = $this->my_jifen_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('index', $index);
        $this->display('my_money.jifen_duihuan_jilu.html');
    }

//充值记录
    function paylog() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('zhuanruchaxun')
        );
       // $this->keepSerial(null, 3);
        /* 当前用户中心菜单 */
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('chongzhichaxun') . ' - ' . Lang::get('chongzhijilu'));
        $this->_curitem('chongzhichaxun');
        $page = $this->_get_page();

        $my_money = $this->my_moneylog_mod->find(array(
            'conditions' => "user_id='$user_id' and s_and_z=1 and user_log_del=0 and leixing=30",
            'limit' => $page['limit'],
            'order' => "id desc",
            'count' => true,
                ));

        $page['item_count'] = $this->my_moneylog_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('my_money', $my_money);
        $this->display('my_money.paylog.html');
    }

//提现查询
    function txlist() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('tixianshenqing')
        );
        /* 当前用户中心菜单 */
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('tixianshenqing'));
        $this->_curitem('tixianshenqing');

        $my_money = $this->my_money_mod->getAll("select * from " . DB_PREFIX . "my_money where user_id=$user_id");
        $this->assign('my_money', $my_money);
        $this->display('my_money.txlist.html');
    }

//提现记录
    function txlog() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('tixianjilu')
        );
        /* 当前用户中心菜单 */
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('tixianshenqing') . ' - ' . Lang::get('tixianjilu'));
        $this->_curitem('tixianshenqing');
        $page = $this->_get_page();

        $my_money = $this->my_moneylog_mod->find(array(
            'conditions' => "user_id='$user_id' and s_and_z=2 and user_log_del=0 and leixing=40",
            'limit' => $page['limit'],
            'count' => true,
                ));

        $page['item_count'] = $this->my_moneylog_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->assign('my_money', $my_money);
        $this->display('my_money.txlog.html');
    }

//用户设置
    function mylist() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('zhanghushezhi')
        );
        /* 当前用户中心菜单 */
        $this->_curitem('zhanghushezhi');
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('zhanghushezhi'));
        //读取帐户金额
        $my_money = $this->my_money_mod->getAll("select * from " . DB_PREFIX . "my_money where user_id=$user_id");
        $this->assign('my_money', $my_money);
        $this->display('my_money.mylist.html'); //对应风格文件
    }

//用户隐藏流水，但不会删除数据
    function user_log_del() {
        $user_id = $this->visitor->get('user_id');
        $id = trim($_GET['id']);
        if (empty($id)) {
            $this->show_warning('feifacanshu');
            return;
        } else {
            $ids = explode(',', $id);
            $user_log_del = array(
                'user_log_del' => 1,
            );
            $this->my_moneylog_mod->edit($ids, $user_log_del);
            $this->show_message('shanchuchenggong');
            return;
        }
    }

    function tx_cancle() {
        $user_id = $this->visitor->get('user_id');
        $id = trim($_GET['id']);
        if (empty($id)) {
            $this->show_warning('feifacanshu');
            return;
        }
        $log = $this->my_moneylog_mod->getRow("select * from " . DB_PREFIX . "my_moneylog where id='$id'");
        $jd_money = $log['money_zs'];


        $behalf_money_row = $this->my_money_mod->getRow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
        $result = var_export($behalf_money_row, true);
        Log::write($result);
        $sell_money = $behalf_money_row['money']; //卖家的资金
        $sell_money_dj = $behalf_money_row['money_dj']; //卖家的冻结资金
        $new_money = $sell_money + $jd_money;
        $new_money_dj = $sell_money_dj - $jd_money;
        if ($new_money_dj < 0) {
            $this->show_warning('余额不足?');
            return false;
        }
        //更新数据
        $new_money_array = array(
            'money' => $new_money,
            'money_dj' => $new_money_dj,
        );
        $result = var_export($new_money_array, true);
        Log::write($result);
        $this->my_money_mod->edit('user_id=' . $user_id, $new_money_array);
        $edit_moneylog = array(
            'log_text' => '用户提现取消!',
            'admin_time' => gmtime(),
            'caozuo' => 63,
            'order_sn' => '0',
        );
        $this->my_moneylog_mod->edit('id=' . $id, $edit_moneylog);
        $this->show_message('取消提现成功!');
        //echo ecm_json_encode(true);
        return true;
    }

//用户显示流水，但不会删除数据，此功能暂时隐藏不使用
    function user_log_huifu() {

        $user_id = $this->visitor->get('user_id');
        $id = trim($_GET['id']);
        if (empty($id)) {
            $this->show_warning('feifacanshu');
            return;
        } else {
            $ids = explode(',', $id);
            $user_log_del = array(
                'user_log_del' => 0,
            );
            $this->my_moneylog_mod->edit($ids, $user_log_del);
            $this->show_message('ok');
            return;
        }
    }

//设置新支付密码
    function newpassword() {
        $user_id = $this->visitor->get('user_id');
        if ($_POST) {//检测是否提交
            $zf_pass = trim($_POST['zf_pass']);
            $zf_pass2 = trim($_POST['zf_pass2']);
            if (empty($zf_pass)) {
                $this->show_warning('cuowu_zhifumimabunengweikong');
                return;
            }
            if ($zf_pass != $zf_pass2) {
                $this->show_warning('cuowu_liangcishurumimabuyizhi');
                return;
            }
//读原始密码
            $money_row = $this->my_money_mod->getrow("select zf_pass from " . DB_PREFIX . "my_money where user_id='$user_id'");
//转换32位 MD5
            $md5zf_pass = md5($zf_pass);

            if (empty($money_row['zf_pass'])) {//检测为空密码才允许新设置
                $newpass_array = array(
                    'zf_pass' => $md5zf_pass,
                );
                $this->my_money_mod->edit('user_id=' . $user_id, $newpass_array);
                $this->show_message('zhifumimaxiugaichenggong', 'zhifumimaxiugaichenggong', 'index.php?app=my_money&act=password');
                return;
            } else {
                $this->show_warning('cuowu_yuanzhifumimayanzhengshibai');
                return;
            }
        } else {
//读原始密码
            $money_row = $this->my_money_mod->getrow("select zf_pass from " . DB_PREFIX . "my_money where user_id='$user_id'");
            if (!empty($money_row['zf_pass'])) {
                header("Location: index.php?app=my_money&act=password");
                return;
            }//检测空密码就跳到新密码设

            $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('zhifumimaxiugai')
            );
            $this->_curitem('zhanghushezhi');
            $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('zhanghushezhi') . ' - ' . Lang::get('zhifumimaxiugai'));
            $this->display('my_money.newpassword.html');
            return;
        }
    }

//修改支付密码
    function password() {
        $user_id = $this->visitor->get('user_id');
        if ($_POST) {//检测是否提交
            $y_pass = trim($_POST['y_pass']);
            $zf_pass = trim($_POST['zf_pass']);
            $zf_pass2 = trim($_POST['zf_pass2']);
            if (empty($zf_pass)) {
                $this->show_warning('cuowu_zhifumimabunengweikong');
                return;
            }
            if ($zf_pass != $zf_pass2) {
                $this->show_warning('cuowu_liangcishurumimabuyizhi');
                return;
            }
//读原始密码
            $money_row = $this->my_money_mod->getrow("select zf_pass from " . DB_PREFIX . "my_money where user_id='$user_id'");
//转换32位 MD5
            $md5y_pass = md5($y_pass);
            $md5zf_pass = md5($zf_pass);

            if ($money_row['zf_pass'] != $md5y_pass) {
                $this->show_warning('cuowu_yuanzhifumimayanzhengshibai');
                return;
            } else {
                $newpass_array = array(
                    'zf_pass' => $md5zf_pass,
                );
                $this->my_money_mod->edit('user_id=' . $user_id, $newpass_array);
                $this->show_message('zhifumimaxiugaichenggong');
                return;
            }
        } else {
//读原始密码
            $money_row = $this->my_money_mod->getrow("select zf_pass from " . DB_PREFIX . "my_money where user_id='$user_id'");
            if (empty($money_row['zf_pass'])) {
                header("Location: index.php?app=my_money&act=newpassword");
                return;
            }//检测空密码就跳到新密码设置
            $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('zhifumimaxiugai')
            );
            $this->_curitem('zhanghushezhi');
            $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('zhanghushezhi') . ' - ' . Lang::get('zhifumimaxiugai'));
            $this->display('my_money.password.html');
            return;
        }
    }

//显示找回支付密码
    function find_password() {
        header("Location: index.php?app=find_password");
        return;
    }

//密保绑定页面
    function mibao() {
        $user_id = $this->visitor->get('user_id');
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member', LANG::get('shangfutong'), 'index.php?app=my_money&act=index', LANG::get('mibaobangding')
        );
        /* 当前用户中心菜单 */
        $this->_curitem('zhanghushezhi');
        $this->assign('page_title', Lang::get('member_center') . ' - ' . Lang::get('zhanghushezhi') . ' - ' . Lang::get('mibaobangding'));
        //读取帐户金额
        $my_money = $this->my_money_mod->getAll("select * from " . DB_PREFIX . "my_money where user_id=$user_id");
        $this->assign('my_money', $my_money);
        $this->display('my_money.mibao.html'); //对应风格文件
    }

//提现申请
    function txsq() {
        if ($_POST) {
            $user_id = $this->visitor->get('user_id');
            $tx_money = trim($_POST['tx_money']);
            $post_zf_pass = trim($_POST['post_zf_pass']);
            $user_zimuz1 = trim($_POST['user_zimuz1']);
            $user_zimuz2 = trim($_POST['user_zimuz2']);
            $user_zimuz3 = trim($_POST['user_zimuz3']);
            $md5zf_pass = md5($post_zf_pass);
            $user_shuzi1 = trim($_POST['user_shuzi1']);
            $user_shuzi2 = trim($_POST['user_shuzi2']);
            $user_shuzi3 = trim($_POST['user_shuzi3']);
            $money_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
//检测用户的银行信息
            if (empty($money_row['bank_sn'])) {
                $this->show_warning('cuowu_nihaimeiyoushezhiyinhangxinxi');
                return;
            }
            if (empty($tx_money)) {
                $this->show_warning('cuowu_tixianjinebunengweikong');
                return;
            }
            if (preg_match("/[^0.-9]/", $tx_money)) {
                $this->show_warning('cuowu_nishurudebushishuzilei');
                return;
            }
            if ($money_row['money'] < $tx_money) {
                $this->show_warning('duibuqi_zhanghuyuebuzu');
                return;
            }
            $isbehalfsql = ' select * from ecm_behalf where bh_id='.$user_id;
            $isbehalf = $this->my_moneylog_mod->getRow($isbehalfsql);
//            if(empty($isbehalf)){
//                echo 'it is not behalf';
//            }
            if (!empty($isbehalf) && $user_id!= 94299) {//51df6 不限制
                $djed_sql = 'select sum(order_amount) from ecm_order where status in (20,30) and bh_id='.$user_id;
                $money_odj = $this->my_moneylog_mod->getOne($djed_sql);
                $tx_sql = 'select sum(money_zs)  from ecm_my_moneylog where leixing in(40 ) and caozuo in (60)  and user_log_del = 0 and user_id='.$user_id;
                $money_tx = $this->my_moneylog_mod->getOne($tx_sql);
                $basedj = $money_odj * 0.2 + $money_tx;
//                echo $basedj . '-' . $my_money[0]['money_dj'];
                if (bccomp($basedj, $money_row['money_dj']) == 1) {
                    $duojd_money = $basedj - $money_row['money_dj'];
                    $cantx = $money_row['money'] - $duojd_money;
                } else {
                    $cantx = $money_row['money'];
                }
                if (bccomp($tx_money,$cantx) == 1) {
                    $this->show_warning('目前最多只能再提现' . $cantx);
                    return;
                }
            }
//检测是密保用户就执行
            if ($money_row['mibao_id'] > 0) {
                if (empty($user_shuzi1) or empty($user_shuzi2) or empty($user_shuzi3)) {
                    $this->show_warning('cuowu_dongtaimimabunengweikong');
                    return;
                }
                $mibao_row = $this->my_mibao_mod->getrow("select * from " . DB_PREFIX . "my_mibao where user_id='$user_id'");
//检测数字错，就提示并停止
                if ($mibao_row[$user_zimuz1] != $user_shuzi1 or $mibao_row[$user_zimuz2] != $user_shuzi2 or $mibao_row[$user_zimuz3] != $user_shuzi3) {
                    echo Lang::get('money_banben');
                    $this->show_warning('cuowu_dongtaimimayanzhengshibai');
                    return;
                }
            } else {
//否则检测 支付密码
                if (empty($post_zf_pass)) {
                    $this->show_warning('cuowu_zhifumimabunengweikong');
                    return;
                }
                if ($money_row['zf_pass'] != $md5zf_pass) {
                    $this->show_warning('cuowu_zhifumimayanzhengshibai');
                    return;
                }
            }
//通过验证 开始操作数据
            $newmoney = $money_row['money'] - $tx_money;
            $newmoney_dj = $money_row['money_dj'] + $tx_money;

            //添加日志
            $log_text = $this->visitor->get('user_name') . Lang::get('tixianshenqingjine') . $tx_money . Lang::get('yuan');
            $add_mymoneylog = array(
                'user_id' => $user_id,
                'user_name' => $this->visitor->get('user_name'),
                'order_id ' => Lang::get('tixian_dengdaiguanliyuangongbu'),
                'add_time' => gmtime(),
                'leixing' => 40,
                's_and_z' => 2,
                'money_zs' => $tx_money,
                'money' => '-' . $tx_money,
                'log_text' => $log_text,
                'caozuo' => 60,
            );
            $this->my_moneylog_mod->add($add_mymoneylog);
            $edit_mymoney = array(
                'money_dj' => $newmoney_dj,
                'money' => $newmoney,
            );
            $this->my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
            $this->show_message('tixian_chenggong');
            return;
        } else {
            $this->show_warning('feifacanshu');
            return;
        }
    }

//银行信息设置
    function bank_set() {
        if ($_POST) {
            //检测两次银行号码
            if (trim($_POST['yes_bank_sn']) != trim($_POST['yes_bank_sn_queren'])) {
                $this->show_warning('liangxitixianzhenghaobuyizhi');
                return;
            }
            $user_id = $this->visitor->get('user_id');
            $bank_edit = trim($_POST['bank_edit']);
            if ($bank_edit == "YES") {
                $zf_pass = trim($_POST['zf_pass']);
                $user_zimuz1 = trim($_POST['user_zimuz1']);
                $user_zimuz2 = trim($_POST['user_zimuz2']);
                $user_zimuz3 = trim($_POST['user_zimuz3']);
                $user_shuzi1 = trim($_POST['user_shuzi1']);
                $user_shuzi2 = trim($_POST['user_shuzi2']);
                $user_shuzi3 = trim($_POST['user_shuzi3']);

//读取密保卡资料
                $money_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
                if ($money_row['mibao_id'] > 0) {
                    $mibao_row = $this->my_mibao_mod->getrow("select * from " . DB_PREFIX . "my_mibao where user_id='$user_id'");
//检测数字错，就提示并停止
                    if ($mibao_row[$user_zimuz1] != $user_shuzi1 or $mibao_row[$user_zimuz2] != $user_shuzi2 or $mibao_row[$user_zimuz2] != $user_shuzi2) {
                        echo Lang::get('money_banben');
                        $this->show_warning('cuowu_dongtaimimayanzhengshibai');
                        return;
                    }
                } else {
//检测密码回答
                    if (empty($zf_pass)) {
                        $this->show_warning('cuowu_zhifumimabunengweikong');
                        return;
                    }
                    $md5zf_pass = md5($zf_pass);
                    if ($money_row['zf_pass'] != $md5zf_pass) {
                        $this->show_warning('cuowu_zhifumimayanzhengshibai');
                        return;
                    }
                }//mibao>0
//验证都通过了开始修改数据
                $bank_array = array(
                    //'bank_name' => trim($_POST['yes_bank_name']),
                    'bank_name' => empty($_POST['zhifubao']) ? trim($_POST['yes_bank_name']) : '', //当是支付宝时，把银行名称置空，便于区分！modify by tiq 2015-04-07
                    'bank_sn' => trim($_POST['yes_bank_sn']),
                    'bank_username' => trim($_POST['yes_bank_username']),
                    'bank_add' => empty($_POST['zhifubao']) ? trim($_POST['yes_bank_add']) : '', //当是支付宝时，把开户行地区置空，便于区分！modify by tiq 2015-04-07
                );
//执行SQL操作
                $this->my_money_mod->edit('user_id=' . $user_id, $bank_array);
                $this->show_message('baocuntixianxinxichenggong');
                return;
            }//YES
        }//post
        else {
            $this->show_warning('feifacanshu');
            return;
        }
    }

//绑定密保卡
    function add_mibao() {
        if ($_POST) {
            $user_id = $this->visitor->get('user_id');
            $zf_pass = trim($_POST['zf_pass']);
            $post_mb_sn = trim($_POST['post_mb_sn']);
            $user_zimuz1 = trim($_POST['user_zimuz1']);
            $user_zimuz2 = trim($_POST['user_zimuz2']);
            $user_zimuz3 = trim($_POST['user_zimuz3']);
            $user_shuzi1 = trim($_POST['user_shuzi1']);
            $user_shuzi2 = trim($_POST['user_shuzi2']);
            $user_shuzi3 = trim($_POST['user_shuzi3']);
            if (empty($zf_pass)) {
                $this->show_warning('cuowu_zhifumimabunengweikong');
                return;
            }
            if (empty($post_mb_sn)) {
                $this->show_warning('mibaosnbunengweikong');
                return;
            }
            $money_row = $this->my_money_mod->getrow("select zf_pass from " . DB_PREFIX . "my_money where user_id='$user_id'");

            if ($money_row['mibao_id'] > 0) {
                $this->show_warning('cuowu_gaiyonghuyijingbangdingmibaole');
                return;
            }
            $md5zf_pass = md5($zf_pass);
            if ($money_row['zf_pass'] != $md5zf_pass) {
                $this->show_warning('cuowu_zhifumimayanzhengshibai');
                return;
            }
            $mibao_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_mibao where mibao_sn='$post_mb_sn'");
            $mibao_id = $mibao_row['id'];
            $mibao_sn = $mibao_row['mibao_sn'];
            $mibao_shuzi1 = $mibao_row[$user_zimuz1];
            $mibao_shuzi2 = $mibao_row[$user_zimuz2];
            $mibao_shuzi3 = $mibao_row[$user_zimuz3];
            if (empty($mibao_id) or empty($mibao_sn)) {
                $this->show_warning('cuowu_mibaokasncuowu');
                return;
            }
            if ($mibao_row['user_id'] > 0) {
                $this->show_warning('cuowu_gaimibaokazhengzaishiyongzhong');
                return;
            }
            if ($user_shuzi1 != $mibao_shuzi1 or $user_shuzi2 != $mibao_shuzi2 or $user_shuzi3 != $mibao_shuzi3) {
                echo Lang::get('money_banben');
                $this->show_warning('cuowu_dongtaimimayanzhengshibai');
                return;
            } else {
                //检测绑定时间
                if (empty($mibao_row['bd_time'])) {
                    $mibao_array = array(
                        'user_id' => $this->visitor->get('user_id'),
                        'user_name' => $this->visitor->get('user_name'),
                        'bd_time' => time(),
                        'dq_time' => time() + 31536000,
                        'ztai' => 1,
                    );
                } else {//绑时间 否则
                    $mibao_array = array(
                        'user_id' => $this->visitor->get('user_id'),
                        'user_name' => $this->visitor->get('user_name'),
                    );
                }

                $money_edit = array(
                    'mibao_id' => $mibao_id,
                    'mibao_sn' => $mibao_sn,
                );

                $this->my_money_mod->edit('user_id=' . $user_id, $money_edit);
                $this->my_mibao_mod->edit('id=' . $mibao_id, $mibao_array);
                $this->show_message('bangding_chenggong');
            }
        } else {
            $this->show_warning('feifacanshu');
            return;
        }
    }

//解除密保卡
    function del_mibao() {
        if ($_POST) {
            $user_id = $this->visitor->get('user_id');
            $post_mb_sn = trim($_POST['post_mb_sn']);
            $user_zimuz1 = trim($_POST['user_zimuz1']);
            $user_zimuz2 = trim($_POST['user_zimuz2']);
            $user_zimuz3 = trim($_POST['user_zimuz3']);
            $user_shuzi1 = trim($_POST['user_shuzi1']);
            $user_shuzi2 = trim($_POST['user_shuzi2']);
            $user_shuzi3 = trim($_POST['user_shuzi3']);
            if (empty($post_mb_sn)) {
                $this->show_warning('mibaosnbunengweikong');
                return;
            }

            $mibao_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_mibao where mibao_sn='$post_mb_sn'");

            $mibao_id = $mibao_row['id'];
            $mibao_sn = $mibao_row['mibao_sn'];

            $mibao_shuzi1 = $mibao_row[$user_zimuz1];
            $mibao_shuzi2 = $mibao_row[$user_zimuz2];
            $mibao_shuzi3 = $mibao_row[$user_zimuz3];
            if (empty($mibao_id) or empty($mibao_sn)) {
                $this->show_warning('cuowu_mibaokasncuowu');
                return;
            }
            if ($user_shuzi1 != $mibao_shuzi1 or $user_shuzi2 != $mibao_shuzi2 or $user_shuzi3 != $mibao_shuzi3) {
                echo Lang::get('money_banben');
                $this->show_warning('cuowu_dongtaimimayanzhengshibai');
                return;
            } else {
                $mibao_array = array(
                    'user_id' => 0,
                    'user_name' => "",
                );

                $money_array = array(
                    'mibao_id' => 0,
                    'mibao_sn' => "",
                );
            }
            $this->my_mibao_mod->edit('id=' . $mibao_id, $mibao_array);
            $this->my_money_mod->edit('user_id=' . $user_id, $money_array);
            $this->show_message('jiechu_chenggong');
        }//POST
        else {//POST
            $this->show_warning('feifacanshu');
            return;
        }//POST
    }

//支付定单
    function payment() {
        $user_id = $this->visitor->get('user_id');
        $zf_pass = trim($_POST['zf_pass']);
        $user_zimuz1 = trim($_POST['user_zimuz1']);
        $user_zimuz2 = trim($_POST['user_zimuz2']);
        $user_zimuz3 = trim($_POST['user_zimuz3']);
        $user_shuzi1 = trim($_POST['user_shuzi1']);
        $user_shuzi2 = trim($_POST['user_shuzi2']);
        $user_shuzi3 = trim($_POST['user_shuzi3']);
        $post_money = trim($_POST['post_money']); //提交过来的 金钱
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0; //提交过来的 定单号码
        if (empty($order_id)) {
            $this->show_warning('feifacanshu');
            return;
        }
        if ($_POST) {//检测是否提交
//读取moneylog 为了检测提交不重复
            $moneylog_row = $this->my_moneylog_mod->getrow("select order_id from " . DB_PREFIX . "my_moneylog where user_id='$user_id' and order_id='$order_id' and caozuo='10'");
            if ($moneylog_row['order_id'] == $order_id) {
                $this->show_warning('cuowu_gaidingdanyijingzhufule');
                return; //定单已经支付
            }
//读取买家SQL
            $buyer_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
            $buyer_name = $buyer_row['user_name']; //买家用户名
            $buyer_zf_pass = $buyer_row['zf_pass']; //支付密码
            $buyer_money = $buyer_row['money']; //当前用户的原始金钱
            $buyer_money_dj = $buyer_row['money_dj']; //当前用户的原始金钱
//从定单中 读取卖家信息
            $order_row = $this->order_mod->getrow("select * from " . DB_PREFIX . "order where order_id='$order_id'");
            $order_order_sn = $order_row['order_sn']; //定单号
            /* 判断订单是否选择了代发，如果有代发，则把订单资金给代发，由代发拿现金去店铺拿货。如果没有代发，则默认为店铺配送，订单资金给店铺。  start  by tanaiquan */
            $exist_order_behalf = false; //为了下面的订单说明，加上代发说明
            //$order_behalfs_mod = & m('orderbehalfs');
            //$order_behalf = $order_behalfs_mod->get('order_id = ' . $order_id);
            if (!empty($order_row['bh_id'])) {
                $order_seller_id = $order_row['bh_id']; //代发ID
                $exist_order_behalf = true;
            } else {
                $order_seller_id = $order_row['seller_id']; //定单里的 卖家ID
            }
            /* end  by tanaiquan */
            $order_money = $order_row['order_amount']; //定单里的 最后定单总价格
//读取卖家SQL
            $seller_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$order_seller_id'");
            $seller_id = $seller_row['user_id']; //卖家ID
            $seller_name = $seller_row['user_name']; //卖家用户名
            $seller_money_dj = $seller_row['money_dj']; //卖家的原始冻结金钱
            $seller_money = $seller_row['money']; //卖家的原始冻结金钱
//读取密保卡资料
            $mibao_row = $this->my_mibao_mod->getrow("select * from " . DB_PREFIX . "my_mibao where user_id='$user_id'");
            $mibao_user_id = $mibao_row['user_id'];
            $mibao_shuzi1 = $mibao_row[$user_zimuz1];
            $mibao_shuzi2 = $mibao_row[$user_zimuz2];
            $mibao_shuzi3 = $mibao_row[$user_zimuz3];
            if ($mibao_user_id) {
//检测提交的密保信息 是否于读取用户的相符
                if ($user_shuzi1 != $mibao_shuzi1 or $user_shuzi2 != $mibao_shuzi2 or $user_shuzi3 != $mibao_shuzi3) { //检测密保相符 开始
                    echo Lang::get('money_banben');
                    $this->show_warning('cuowu_dongtaimimayanzhengshibai');
                    return;
                }
//检测密保 否则 结束
//同时检测是否使用支付密码 开始
                $new_zf_pass = md5($zf_pass);
                if ($new_zf_pass != $buyer_zf_pass) { //支付密码 错误 开始
                    $this->show_warning('cuowu_zhifumimayanzhengshibai');
                    return;
                }
//支付密码 错误 结束
            } else {
//检测是否使用支付密码 开始
                $new_zf_pass = md5($zf_pass);
                if ($new_zf_pass != $buyer_zf_pass) { //支付密码 错误 开始
                    $this->show_warning('cuowu_zhifumimayanzhengshibai');
                    return;
                }
//支付密码 错误 结束
            }



//检测余额是否足够
            if ($buyer_money < $order_money) {   //检测余额是否足够 开始
                $this->show_warning('cuowu_zhanghuyuebuzu', 'lijichongzhi', 'index.php?app=my_money&act=paylist'
                );
                return;
            } //检测余额是否足够 结束
//金额是否相同
            if ($post_money != $order_money) {   //检测密保相符 开始
                $this->show_warning('fashengcuowu_jineshujukeyi');
                return;
            } //金额是否相同 结束
//检测SESSION 是否存为空
            if ($_SESSION['session_order_sn'] != $order_order_sn) {//检测SESSION 开始
                //更新扣除买家的金钱
//                $buyer_array = array(
//                    'money' => $buyer_money - $order_money,
//                );
                $this->my_money_mod->edit('user_id=' . $user_id, 'money = money -'.$order_money);

                //更新卖家的冻结金钱
//                $seller_array = array(
//                    'money_dj' => $seller_money_dj + $order_money,
//                );
                $seller_edit = $this->my_money_mod->edit('user_id=' . $seller_id,  'money_dj = money_dj +'.$order_money);
                //买家添加日志
                /* 如果买家选择了代发，则说明是从代发处购买 */
                if ($exist_order_behalf) {
                    $buyer_log_text = Lang::get('goumaishangpin_daifa') . $order_row['bh_id'];
                } else {
                    $buyer_log_text = Lang::get('goumaishangpin_dianzhu') . $seller_name;
                }
                $buyer_add_array = array(
                    'user_id' => $user_id,
                    'user_name' => $buyer_name,
                    'order_id ' => $order_id,
                    'order_sn ' => $order_order_sn,
                    'seller_id' => $seller_id,
                    'seller_name' => $seller_name,
                    'buyer_id' => $user_id,
                    'buyer_name' => $buyer_name,
                    'add_time' => gmtime(),
                    'admin_time' => gmtime(),
                    'leixing' => 20,
                    'money_zs' => "-" . $order_money,
                    'money' => $order_money,
                    'log_text' => $buyer_log_text,
                    'caozuo' => 10,
                    's_and_z' => 2,
                    'moneyleft' => $buyer_money - $order_money + $buyer_money_dj,
                );
                $this->my_moneylog_mod->add($buyer_add_array);
                //卖家添加日志
                $seller_log_text = Lang::get('chushoushangpin_maijia') . $buyer_name;
                $seller_add_array = array(
                    'user_id' => $seller_id,
                    'user_name' => $seller_name,
                    'order_id ' => $order_id,
                    'order_sn ' => $order_order_sn,
                    'seller_id' => $seller_id,
                    'seller_name' => $seller_name,
                    'buyer_id' => $user_id,
                    'buyer_name' => $buyer_name,
                    'add_time' => gmtime(),
                    'admin_time' => gmtime(),
                    'leixing' => 10,
                    'money_zs' => $order_money,
                    'money' => $order_money,
                    'log_text' => $seller_log_text,
                    'caozuo' => 10,
                    's_and_z' => 1,
                    'moneyleft' => $seller_money_dj + $order_money + $seller_money,
                );
                $this->my_moneylog_mod->add($seller_add_array);
                //改变定单为 已支付等待卖家确认  status10改为20
                $payment_code = "sft";
                //更新定单状态
                $order_edit_array = array(
                    'payment_name' => Lang::get('shangfutong'),
                    'payment_code' => $payment_code,
                    'pay_time' => gmtime(),
                    'out_trade_sn' => $order_sn,
                    'status' => 20, //20就是 待发货了
                );
                $this->order_mod->edit($order_id, $order_edit_array);
                //$edit_data['status']    =   ORDER_ACCEPTED;//定义 为 20 待发货
                //$order_model->edit($order_id, $edit_data);//直接更改为 20 待发货
                //支付成功
                /* 如果匹配到的话，修改第三方订单状态 */
                $ordervendor_mod = &m('ordervendor');
                $ordervendor_mod->edit("ecm_order_id={$order_id}", array(
                    'status' => VENDOR_ORDER_ACCEPTED,
                ));

                $this->show_message('zhifu_chenggong', 'sanmiaohouzidongtiaozhuandaodingdanliebiao', 'index.php?app=buyer_order', 'chankandingdan', 'index.php?app=buyer_order', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                );
//定义SESSION值
                $_SESSION['session_order_sn'] = $order_order_sn;
            }//检测SESSION为空 执行完毕
            else {//检测SESSION为空 否则//检测SESSION为空 否则 开始
                $this->show_warning('jinggao_qingbuyaochongfushuaxinyemian');
                return;
            }//检测SESSION为空 否则 结束
        } else {
            $this->show_warning('feifacanshu');
            return;
        }
    }

//筛选充值方式
    function czfs() {
        if ($_POST) {
            $user_id = $this->visitor->get('user_id');
            $user_name = $this->visitor->get('user_name');
            $cz_money = trim($_POST['cz_money']);
            $czfs = trim($_POST['czfs']);

            $pay_row = $this->my_paysetup_mod->getrow("select * from " . DB_PREFIX . "my_paysetup");
            $v_mid = $pay_row['chinabank_mid'];
            $v_url = $pay_row['chinabank_url'];
            $key = $pay_row['chinabank_key'];

            if ($czfs == 'chinabank') {
                $v_oid = date('Ymd-His', time()) . "-" . $user_id . "-" . $cz_money;      //网银定单号,不加商号了
                $v_moneytype = "CNY";                                            //币种
                $text = $cz_money . $v_moneytype . $v_oid . $v_mid . $v_url . $key;        //md5加密拼凑串,注意顺序不能变
                //充值金额+CMY+定单号+URL地址+KEY密匙
                $v_md5info = strtoupper(md5($text));                             //md5函数加密并转化成大写字母
                ?>
                <body onLoad="javascript:document.E_FORM.submit()">
                    <form method="post" name="E_FORM" action="https://pay3.chinabank.com.cn/PayGate">
                        <input type="hidden" name="v_mid"         value="<?php echo $v_mid; ?>">
                        <input type="hidden" name="v_oid"         value="<?php echo $v_oid; ?>">
                        <input type="hidden" name="v_amount"      value="<?php echo $cz_money; ?>">
                        <input type="hidden" name="v_moneytype"   value="<?php echo $v_moneytype; ?>">
                        <input type="hidden" name="v_url"         value="<?php echo $v_url; ?>">
                        <input type="hidden" name="v_md5info"     value="<?php echo $v_md5info; ?>">
                        <input type="hidden" name="remark1"       value="<?php echo $remark1; ?>">
                        <input type="hidden" name="remark2"       value="<?php echo $remark2; ?>">
                    </form>
                </body>
                <?php
                return; //网银充值转向结束
            } else if ($czfs == 'yeepay') {//易宝支付
                $p1_MerId = $pay_row['yeepay_mid'];
                $p2_Order = date('Ymd-His', time()) . "-" . $user_id . "-" . $cz_money; //给易宝的定单号
                $p3_Amt = trim($_POST['cz_money']); //给易宝的提交金额
                $p8_Url = $pay_row['yeepay_url']; //给易宝的返回URL
                //pr_NeedResponse是返回机制0不需要  1需要
                ?>
                <body onLoad="document.yeepay.submit();">
                    <form name='yeepay' action='yeepay/req.php' method='post'>
                        <input type='hidden' name='p1_MerId'				value='<?php echo $p1_MerId; ?>'>
                        <input type='hidden' name='p2_Order'				value='<?php echo $p2_Order; ?>'>
                        <input type='hidden' name='p3_Amt'					value='<?php echo $p3_Amt; ?>'>
                        <input type='hidden' name='p5_Pid'					value=''>
                        <input type='hidden' name='p6_Pcat'					value=''>
                        <input type='hidden' name='p7_Pdesc'				value=''>
                        <input type='hidden' name='p8_Url'					value='<?php echo $p8_Url; ?>'>
                        <input type='hidden' name='p9_SAF'					value='0'>
                        <input type='hidden' name='pa_MP'					value='<?php echo $user_name; ?>'>
                        <input type='hidden' name='pd_FrpId'				value=''>
                        <input type='hidden' name='pr_NeedResponse'         value='1'>
                    </form>
                </body>
                <?php
                return;
            } else if ($czfs == 'alipay') {
                require_once("app/alipay/alipay_config.php"); //支付宝即时到配置帐文件
                ?>
                <body onLoad="javascript:document.ALI_FORM.submit()">
                    <form method="post" name="ALI_FORM" action="app/alipay/alipayto.php">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="user_name" value="<?php echo $user_name; ?>">
                        <input type="hidden" name="aliorder" value="<?php echo $mainname . $cz_money; ?>">
                        <input type="hidden" name="alimoney" value="<?php echo $cz_money; ?>">
                    </form>
                </body>
                <?
                return;
            } else if ($czfs == 'tenpay') {
                ?>
                <body onLoad="javascript:document.TENPAY_FORM.submit()">
                    <form method="post" name="TENPAY_FORM" action="app/tenpay-js-php/tenpay.php">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="user_name" value="<?php echo $user_name; ?>">
                        <input type="hidden" name="tenorder" value="<?php echo $mainname . $cz_money; ?>">
                        <input type="hidden" name="tenmoney" value="<?php echo $cz_money; ?>">
                    </form>
                </body>
                <?
                return;
            } else if ($czfs != 'chinabank') {
                $this->show_warning('kaifazhong');
                return;
            }
        } else {
            //不是提交的，直接跳到充值页，重新提交
            header("Location: index.php?app=my_money&act=paylist");
            return;
        }
    }

//财付通充值成功 返回通知页面
    function ten_return_url() {
        require_once ("app/tenpay-js-php/classes/PayResponseHandler.class.php");
        require_once ("app/tenpay-js-php/tenpay_config.php");

        /* 密钥 */
        $key = $tenpaykey;

        /* 创建支付应答对象 */
        $resHandler = new PayResponseHandler();
        $resHandler->setKey($key);

        //判断签名
        if ($resHandler->isTenpaySign()) {

            //交易单号
            $transaction_id = $resHandler->getParameter("transaction_id");

            //金额,以分为单位
            $total_fee = $resHandler->getParameter("total_fee");

            //支付结果
            $pay_result = $resHandler->getParameter("pay_result");

            $sp_billno = $resHandler->getParameter("sp_billno");

            if ("0" == $pay_result) {


                /* 商付通读取数据库 验证 */
                $user_id = $this->visitor->get('user_id');
                $user_name = $this->visitor->get('user_name');

                $dingdan = $sp_billno;  //获取订单号
                $total_fee = $total_fee / 100;   //获取总价格

                $log_row = $this->my_moneylog_mod->getrow("select * from " . DB_PREFIX . "my_moneylog where user_id='$user_id' and					order_sn='$dingdan'");
                if (empty($log_row['caozuo'])) {
                    $sOld_trade_status = 0;
                } else {
                    $sOld_trade_status = $log_row['caozuo'];
                }

                $verify_resultShow = "验证成功";

                if ($sOld_trade_status < 2) {
                    $user_row = $this->my_money_mod->getrow("select money from " . DB_PREFIX . "my_money where user_id='$user_id'");
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
                    $this->my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
                    $this->my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);
                    //添加日志
                    $log_text = $this->visitor->get('user_name') . "通过财付通充值" . $total_fee . Lang::get('yuan');

                    $add_mymoneylog = array(
                        'user_id' => $user_id,
                        'user_name' => $this->visitor->get('user_name'),
                        'buyer_name' => "财付通",
                        'seller_id' => $user_id,
                        'seller_name' => $this->visitor->get('user_name'),
                        'order_sn ' => $dingdan,
                        'add_time' => gmtime(),
                        'leixing' => 30,
                        'money_zs' => $total_fee,
                        'money' => $total_fee,
                        'log_text' => $log_text,
                        'caozuo' => 4,
                        's_and_z' => 1,
                    );
                    $this->my_moneylog_mod->add($add_mymoneylog);
                    $this->show_message('chongzhi_chenggong_jineyiruzhang', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                    );
                } else {//避免重复刷新
                    $this->show_warning('jinggao_qingbuyaochongfushuaxinyemian', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                    );
                    return;
                }

                $resHandler->doShow($tenpay_show_url);
            } else {
                echo "<br/>" . "pay fail！" . "<br/>";
            }
        } else {
            echo "<br/>" . "sign fail！" . "<br/>";
        }
    }

//支付宝充值成功 返回通知页面
    function return_url() {
        require_once("app/alipay/class/alipay_notify.php");
        require_once("app/alipay/alipay_config.php");
        $_GET['app'] = "";
        $_GET['act'] = "";
//构造通知函数信息
        $alipay = new alipay_notify($partner, $security_code, $sign_type, $_input_charset, $transport);
//计算得出通知验证结果
        $verify_result = $alipay->return_verify();

//print_r($alipay);
        if ($verify_result) {
            /* 商付通读取数据库 验证 */
            $user_id = $this->visitor->get('user_id');
            $user_name = $this->visitor->get('user_name');
            //验证成功
            //获取支付宝的通知返回参数
            $dingdan = $_GET['out_trade_no'];  //获取订单号
            $total_fee = $_GET['total_fee'];   //获取总价格
            $log_row = $this->my_moneylog_mod->getrow("select * from " . DB_PREFIX . "my_moneylog where user_id='$user_id' and order_sn='$dingdan'");
            if (empty($log_row['caozuo'])) {
                $sOld_trade_status = 0;
            } else {
                $sOld_trade_status = $log_row['caozuo'];
            }

            $verify_resultShow = "验证成功";

            /* 假设：
              sOld_trade_status="0"	表示订单未处理；
              sOld_trade_status="1"	表示买家已在支付宝交易管理中产生了交易记录，但没有付款
              sOld_trade_status="2"	表示买家已在支付宝交易管理中产生了交易记录且付款成功，但卖家没有发货
              sOld_trade_status="3"	表示卖家已经发了货，但买家还没有做确认收货的操作
              sOld_trade_status="4"	表示买家已经确认收货，这笔交易完成
             */
            if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {
                $this->show_warning('qingbuyaoshiyongdanbaozhifu');
                return;
            } else if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {

                //放入订单交易完成后的数据库更新程序代码，请务必保证echo出来的信息只有success
                //为了保证不被重复调用，或重复执行数据库更新程序，请判断该笔交易状态是否是订单未处理状态
                if ($sOld_trade_status < 2) {
                    //当$_GET['trade_status'] 为WAIT_SELLER_SEND_GOODS，则说明买家用的支付方式是担保交易付款
                    //当$_GET['trade_status'] 为TRADE_FINISHED，则说明买家用的支付方式是即时到帐付款
                    //根据订单号更新订单，把订单处理成交易成功


                    $user_row = $this->my_money_mod->getrow("select money from " . DB_PREFIX . "my_money where user_id='$user_id'");
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
                    $this->my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
                    $this->my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);
                    //添加日志
                    $log_text = $this->visitor->get('user_name') . Lang::get('tongguoalipaychongzhi') . $total_fee . Lang::get('yuan');

                    $add_mymoneylog = array(
                        'user_id' => $user_id,
                        'user_name' => $this->visitor->get('user_name'),
                        'buyer_name' => Lang::get('alipay'),
                        'seller_id' => $user_id,
                        'seller_name' => $this->visitor->get('user_name'),
                        'order_sn ' => $dingdan,
                        'add_time' => gmtime(),
                        'leixing' => 30,
                        'money_zs' => $total_fee,
                        'money' => $total_fee,
                        'log_text' => $log_text,
                        'caozuo' => 4,
                        's_and_z' => 1,
                    );
                    $this->my_moneylog_mod->add($add_mymoneylog);
                    $this->show_message('chongzhi_chenggong_jineyiruzhang', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                    );
                } else {//避免重复刷新
                    $this->show_warning('jinggao_qingbuyaochongfushuaxinyemian', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                    );
                    return;
                }
            } else {
                echo "trade_status=" . $_GET['trade_status'];
            }
        } else {
            //验证失败
            //如要调试，请看alipay_notify.php页面的return_verify函数，比对sign和mysign的值是否相等，或者检查$veryfy_result有没有返回true
            $verify_resultShow = "验证失败";
            $this->show_warning('feifacanshu', 'guanbiyemian', 'index.php?app=my_money&act=exits'
            );
            return;
        }
    }

    function shenghuo() {
        $this->show_message('dxw', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog');
    }

    function searchtrade() {
        $tradeno = empty($_POST['tradeno']) ? $_GET['tradeno'] : $_POST['tradeno'];
        $tradeno = trim($tradeno);
        $totalfee = empty($_POST['totalfee']) ? $_GET['totalfee'] : $_POST['totalfee'];
        $title = empty($_POST['title']) ? $_GET['title'] : $_POST['title'];
        $len = strlen($tradeno);
        if ($len != 28) {
//            echo 'tradno is error';
        }
        $user_id = $this->visitor->get('user_id');
        $user_name = $this->visitor->get('user_name');
        $user_name = urlencode($user_name);
        if (defined('OEM')) {
            $user_name.='&oem=' . OEM;
        }
        if (!empty($title)) {
            $user_name.='&title=' . $title;
            $this->keepSerial($title, 0);
        }
        $url = "http://" . MONEYSITE . "/index.php?app=default&act=searchtrade&tradeno=" . $tradeno . "&totalfee=" . $totalfee . "&user_id=" . $user_id . "&user_name=" . $user_name;
//        header("location: " .$url);

        $res = @json_decode($this->Get($url), true);

        if ($res['result'] == 'success') {
            $this->show_message('chongzhi_chenggong_jineyiruzhang', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits');
        } else if ($res['result'] == 'auth') {
            header("location: " . $res['info']);
        } else if ($res['result'] == 'error' || $res['result'] == 'refresh') {
            //$this->show_message('chongzhi_shibai_qingchongxintijiao', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits');
            $this->show_warning($res['info'], 'guanbiyemian', 'index.php?app=my_money&act=exits');
        } else {
//            print_r($res);
//            echo 'the $res is null';
            $this->show_message('chongzhi_chenggong_jineyiruzhang', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits');
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

    function notify_url_222() {
        require_once("app/alipay/class/alipay_notify.php");
        require_once("app/alipay/alipay_config.php");
        $_POST['app'] = "";
        $_POST['act'] = "";
        $_GET['app'] = "";
        $_GET['act'] = "";

        $user_id = $this->visitor->get('user_id');
        $user_name = $this->visitor->get('user_name');
        $log_row = $this->my_moneylog_mod->getrow("select * from " . DB_PREFIX . "my_moneylog");
        //验证成功
        //获取支付宝的反馈参数
        $dingdan = $_POST['out_trade_no']; //获取支付宝传递过来的订单号
        $total_fee = $_POST['price'];   //获取支付宝传递过来的总价格

        $user_row = $this->my_money_mod->getrow("select money from " . DB_PREFIX . "my_money where user_id='$user_id'");
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
        $this->my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
        $this->my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);
    }

//支付宝充值成功 返回执行数据
    function notify_url_111() {
        require_once("app/alipay/class/alipay_notify.php");
        require_once("app/alipay/alipay_config.php");
        $_POST['app'] = "";
        $_POST['act'] = "";
        $_GET['app'] = "";
        $_GET['act'] = "";
        $alipay = new alipay_notify($partner, $security_code, $sign_type, $_input_charset, $transport);    //构造通知函数信息
        $verify_result = $alipay->notify_verify();  //计算得出通知验证结果

        if ($verify_result) {
            /* 商付通读取数据库 验证 */
            $user_id = $this->visitor->get('user_id');
            $user_name = $this->visitor->get('user_name');
            $log_row = $this->my_moneylog_mod->getrow("select * from " . DB_PREFIX . "my_moneylog");
            //验证成功
            //获取支付宝的反馈参数
            $dingdan = $_POST['out_trade_no']; //获取支付宝传递过来的订单号
            $total_fee = $_POST['total_fee'];   //获取支付宝传递过来的总价格
            $sOld_trade_status = $log_row['caozuo'];  //获取商户数据库中查询得到该笔交易当前的交易状态
            /* 假设：
              sOld_trade_status="0"	表示订单未处理；
              sOld_trade_status="1"	表示买家已在支付宝交易管理中产生了交易记录，但没有付款
              sOld_trade_status="2"	表示买家已在支付宝交易管理中产生了交易记录且付款成功，但卖家没有发货
              sOld_trade_status="3"	表示卖家已经发了货，但买家还没有做确认收货的操作
              sOld_trade_status="4"	表示买家已经确认收货，这笔交易完成
             */
            if ($_POST['trade_status'] == 'WAIT_BUYER_PAY') {
                //表示买家已在支付宝交易管理中产生了交易记录，但没有付款
                //放入订单交易完成后的数据库更新程序代码，请务必保证response.Write出来的信息只有success
                //为了保证不被重复调用，或重复执行数据库更新程序，请判断该笔交易状态是否是订单未处理状态
                //注：该交易状态下，也可不做数据库更新程序，此时，建议把该状态的通知信息记录到商户通知日志数据库表中。
                if ($sOld_trade_status == 0) {
                    //根据订单号更新订单，把订单处理成交易成功
                }
                echo "success";

                //调试用，写文本函数记录程序运行情况是否正常
                //log_result("这里写入想要调试的代码变量值，或其他运行的结果记录");
            } else if ($_POST['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {
                //表示买家已在支付宝交易管理中产生了交易记录且付款成功，但卖家没有发货
                //放入订单交易完成后的数据库更新程序代码，请务必保证response.Write出来的信息只有success
                //为了保证不被重复调用，或重复执行数据库更新程序，请判断该笔交易状态是否是WAIT_BUYER_PAY状态
                if (sOld_trade_status == 1 || sOld_trade_status == 0) {
                    //根据订单号更新订单，把订单处理成交易成功
                }

                echo "success"; //请不要修改或删除
                //调试用，写文本函数记录程序运行情况是否正常
                log_result("222");
            } else if ($_POST['trade_status'] == 'WAIT_BUYER_CONFIRM_GOODS') {
                //表示卖家已经发了货，但买家还没有做确认收货的操作
                //放入订单交易完成后的数据库更新程序代码，请务必保证response.Write出来的信息只有success
                //为了保证不被重复调用，或重复执行数据库更新程序，请判断该笔交易状态是否是WAIT_SELLER_SEND_GOODS状态
                if (sOld_trade_status == 2) {
                    //根据订单号更新订单，把订单处理成交易成功
                }

                echo "success"; //请不要修改或删除
                //调试用，写文本函数记录程序运行情况是否正常
                log_result("333");
            } else if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
                //表示买家已经确认收货，这笔交易完成
                //放入订单交易完成后的数据库更新程序代码，请务必保证response.Write出来的信息只有success
                //为了保证不被重复调用，或重复执行数据库更新程序，请判断该笔交易状态是否是WAIT_BUYER_CONFIRM_GOODS状态
                if (sOld_trade_status == 3 || sOld_trade_status < 2) {
                    //当sOld_trade_status=3，则说明买家用的支付方式是担保交易付款
                    //当sOld_trade_status<2，则说明买家用的支付方式是即时到帐付款
                    //根据订单号更新订单，把订单处理成交易成功
                    //更新商付通余额数据，sOld_trade_status 改变为 =4 ******************************************************************
//检测定单是否重复提交
                    $order_row = $this->my_moneylog_mod->getrow("select * from " . DB_PREFIX . "my_moneylog where user_id='$user_id' and order_sn='$dingdan'");
                    // if ($dingdan != $order_row['order_sn'])

                    $user_row = $this->my_money_mod->getrow("select money from " . DB_PREFIX . "my_money where user_id='$user_id'");
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
                    $this->my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
                    $this->my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);
                    //添加日志
                    $log_text = $this->visitor->get('user_name') . Lang::get('tongguoalipaychongzhi') . $total . Lang::get('yuan');

                    $add_mymoneylog = array(
                        'user_id' => $user_id,
                        'user_name' => $this->visitor->get('user_name'),
                        'buyer_name' => Lang::get('alipay') . $total,
                        'seller_id' => $user_id,
                        'seller_name' => $this->visitor->get('user_name'),
                        'order_sn ' => $dingdan,
                        'add_time' => gmtime(),
                        'leixing' => 30,
                        'money_zs' => $v_amount,
                        'money' => $total,
                        'log_text' => $log_text,
                        'caozuo' => 4,
                        's_and_z' => 1,
                    );
                    $this->my_moneylog_mod->add($add_mymoneylog);
                    $this->show_message('chongzhi_chenggong_jineyiruzhang', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                    );
                }

                echo "success"; //请不要修改或删除
                //调试用，写文本函数记录程序运行情况是否正常
                log_result("444");
            } else {
                echo "success";  //其他状态判断。普通即时到帐中，其他状态不用判断，直接打印success。
                //调试用，写文本函数记录程序运行情况是否正常
                //log_result ("这里写入想要调试的代码变量值，或其他运行的结果记录");
            }
        } else {
            //验证失败
            echo "fail";

            //调试用，写文本函数记录程序运行情况是否正常
            //log_result ("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

//易宝支付返回数据 进行站内冲值
    /* 可以用，但暂时不开放，
      function yee_pay()
      {
      include('yeepay/yeepayCommon.php');
      #	只有支付成功时易宝支付才会通知商户.
      ##支付成功回调有两次，都会通知到在线支付请求参数中的p8_Url上：浏览器重定向;服务器点对点通讯.
      #	解析返回参数.
      $return = getCallBackValue($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);
      #	判断返回签名是否正确（True/False）
      $bRet = CheckHmac($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);
      #	以上代码和变量不需要修改.
      #	校验码正确.
      if($bRet){
      if($r1_Code=="1"){
      #	需要比较返回的金额与商家数据库中订单的金额是否相等，只有相等的情况下才认为是交易成功.
      #	并且需要对返回的处理进行事务控制，进行记录的排它性处理，防止对同一条交易重复发货的情况发生.
      if($r9_BType=="1")
      {
      $user_id = $this->visitor->get('user_id');
      //读取汇率
      $paysetup=$this->my_paysetup_mod->getrow("select * from ".DB_PREFIX."my_paysetup where id='1'");
      $rb_BankId	=$_GET["rb_BankId"];//读取易宝返回的银行编码，判定什么接口
      //判断使用银行的  计算汇率
      if($rb_BankId=="ICBC-NET" or $rb_BankId=="ICBC-WAP" or $rb_BankId=="CMBCHINA-NET" or $rb_BankId=="CMBCHINA-WAP" or $rb_BankId=="ABC-NET" or $rb_BankId=="CCB-NET" or $rb_BankId=="CCB-PHONE" or $rb_BankId=="BCCB-NET" or $rb_BankId=="BOCO-NET" or $rb_BankId=="CIB-NET" or $rb_BankId=="NJCB-NET" or $rb_BankId=="CMBC-NET" or $rb_BankId=="CEB-NET" or $rb_BankId=="BOC-NET" or $rb_BankId=="PINGANBANK-NET" or $rb_BankId=="CBHB-NET" or $rb_BankId=="HKBEA-NET" or $rb_BankId=="ECITIC-NET" or $rb_BankId=="SDB-NET" or $rb_BankId=="SPDB-NET" or $rb_BankId=="POST-NET" or $rb_BankId=="1000000-NET")
      {
      //银行 一般99%
      //$r3_Amt=$r3_Amt / 100 * $paysetup['yeepay_bank'];
      //sprintf("%0.2f",值) 是取0.00格式
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_bank']);
      }
      //骏网一卡通
      else if($rb_BankId=="JUNNET-NET")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_junnet']);
      }
      //盛大卡
      else if($rb_BankId=="SNDACARD-NET")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_sndacard']);
      }
      //神州行
      else if($rb_BankId=="SZX-NET")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_szx']);
      }
      //征途卡
      else if($rb_BankId=="ZHENGTU-NET")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_zhengtu']);
      }
      //Q币卡
      else if($rb_BankId=="QQCARD-NET")
      {

      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_qqcard']);
      }
      //联通卡
      else if($rb_BankId=="UNICOM-NET")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_unicon']);
      }
      //久游卡
      else if($rb_BankId=="JIUYOU-NET")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_jiuyou']);
      }
      //易宝一卡通
      else if($rb_BankId=="YPCARD-NET")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_ypcard']);
      }
      //联华OK卡
      else if($rb_BankId=="LIANHUAOKCARD-NET")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_lianhuaokcard']);
      }
      //网易卡
      else if($rb_BankId=="NETEASE-NET")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_netease']);
      }
      //完美卡
      else if($rb_BankId=="WANMEI")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_wanmei']);
      }
      //搜狐卡
      else if($rb_BankId=="SOHU")
      {
      $r3_Amt= sprintf("%0.2f", $r3_Amt / 100 * $paysetup['yeepay_sohu']);
      }
      //充值成功，出现错误，请联系管理员
      else
      {
      $this->show_warning('yeepaychenggongdanchuxiancuowuqinglianxiadmin');
      return;
      }

      //检测定单是否重复提交
      $order_row=$this->my_moneylog_mod->getrow("select order_sn from ".DB_PREFIX."my_moneylog where user_id='$user_id' and order_sn='$r6_Order'");

      if ($r6_Order != $order_row['order_sn'])

      {
      //支付成功，可进行逻辑处理！
      //商户系统的逻辑处理（例如判断金额，判断支付状态，更新订单状态等等）......
      $user_row=$this->my_money_mod->getrow("select money from ".DB_PREFIX."my_money where user_id='$user_id'");
      $user_money=$user_row['money'] ;

      $new_money=$user_money+$r3_Amt;
      $edit_mymoney=array(
      'money'=>$new_money,
      );
      $this->my_money_mod->edit('user_id='.$this->visitor->get('user_id'), $edit_mymoney);
      //添加日志
      $log_text =$this->visitor->get('user_name').Lang::get('tongguoyeepaychongzhi').$r3_Amt.Lang::get('yuan');

      $add_mymoneylog=array(
      'user_id'=>$user_id,
      'user_name'=>$this->visitor->get('user_name'),
      'buyer_name'=>Lang::get('yeepay'),
      'seller_id'=>$user_id,
      'seller_name'=>$this->visitor->get('user_name'),
      'order_sn '=>$r2_TrxId,
      'add_time'=>time(),
      'leixing'=>30,
      'money_zs'=>$r3_Amt,
      'money'=>$r3_Amt,
      'log_text'=>$log_text,
      'caozuo'=>50,
      's_and_z'=>1,
      );
      $this->my_moneylog_mod->add($add_mymoneylog);
      }
      else
      {
      $this->show_warning('jinggao_qingbuyaochongfushuaxinyemian',
      'guanbiyemian',  'index.php?app=my_money&act=exits'
      );
      return;
      }

      }
      elseif($r9_BType=="2"){
      #如果需要应答机制则必须回写流,以success开头,大小写不敏感.
      $this->show_warning('success');
      return;

      }
      }



      $this->show_message('chongzhi_chenggong_jineyiruzhang',
      'chakancicichongzhi',  'index.php?app=my_money&act=paylog',
      'guanbiyemian', 'index.php?app=my_money&act=exits'
      );


      }else{
      $this->show_warning('feifacanshu');
      return;
      }

      }
      易宝支付暂时关闭 */

//网银支付返回数据 进行站内冲值
    function chinabank_pay() {
        $user_id = $this->visitor->get('user_id');
        if ($_POST) {
            $pay_row = $this->my_paysetup_mod->getrow("select * from " . DB_PREFIX . "my_paysetup where id='1'");
            $key = $pay_row['chinabank_key'];

            $v_oid = trim($_POST['v_oid']);       // 商户发送的v_oid定单编号
            $v_pmode = trim($_POST['v_pmode']);    // 支付方式（字符串）
            $v_pstatus = trim($_POST['v_pstatus']);   //  支付状态 ：20（支付成功）；30（支付失败）
            $v_pstring = trim($_POST['v_pstring']);   //提示中文"支付成功"字符串

            $v_amount = trim($_POST['v_amount']);     // 订单实际支付金额
            $v_moneytype = trim($_POST['v_moneytype']); //订单实际支付币种
            $remark1 = trim($_POST['remark1']);      //备注字段1
            $remark2 = trim($_POST['remark2']);     //备注字段2
            $v_md5str = trim($_POST['v_md5str']);   //拼凑后的MD5校验值
//重新计算md5的值
            $md5string = strtoupper(md5($v_oid . $v_pstatus . $v_amount . $v_moneytype . $key));
            if ($v_md5str == $md5string) {//校验MD5 开始//校验MD5 IF括号
                if ($v_pstatus == "20") {
//检测定单是否重复提交
                    $order_row = $this->my_moneylog_mod->getrow("select order_sn from " . DB_PREFIX . "my_moneylog where user_id='$user_id' and order_sn='$v_oid'");

                    if ($v_oid != $order_row['order_sn']) {
                        //支付成功，可进行逻辑处理！
                        //商户系统的逻辑处理（例如判断金额，判断支付状态，更新订单状态等等）......
                        $user_row = $this->my_money_mod->getrow("select money from " . DB_PREFIX . "my_money where user_id='$user_id'");
                        $user_money = $user_row['money'];
                        $user_jifen = $user_row['jifen'];

                        $new_money = $user_money + $v_amount;
                        $new_jifen = $user_jifen + $v_amount;
                        $edit_mymoney = array(
                            'money' => $new_money,
                        );
                        $edit_myjifen = array(
                            'jifen' => $new_jifen,
                        );
                        $this->my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
                        $this->my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);
                        //添加日志
                        $log_text = $this->visitor->get('user_name') . Lang::get('tongguowangyinjishichongzhi') . $v_amount . Lang::get('yuan');

                        $add_mymoneylog = array(
                            'user_id' => $user_id,
                            'user_name' => $this->visitor->get('user_name'),
                            'buyer_name' => Lang::get('chinabankzhifu') . $v_pmode,
                            'seller_id' => $user_id,
                            'seller_name' => $this->visitor->get('user_name'),
                            'order_sn ' => $v_oid,
                            'add_time' => gmtime(),
                            'leixing' => 30,
                            'money_zs' => $v_amount,
                            'money' => $v_amount,
                            'log_text' => $log_text,
                            'caozuo' => 50,
                            's_and_z' => 1,
                        );
                        $this->my_moneylog_mod->add($add_mymoneylog);
                        $this->show_message('chongzhi_chenggong_jineyiruzhang', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                        );
                    } else {
                        $this->show_warning('jinggao_qingbuyaochongfushuaxinyemian', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                        );
                        return;
                    }
                } else {
                    $this->show_warning('chongzhi_shibai_qingchongxintijiao', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                    );
                    return;
                }
            } else { //否则 校验MD5
                $this->show_warning('wangyinshujuxiaoyanshibai_shujukeyi', 'guanbiyemian', 'index.php?app=my_money&act=exits'
                );
                return;
            }//否则 校验MD5  结束
        } else {
            $this->show_warning('feifacanshu', 'guanbiyemian', 'index.php?app=my_money&act=exits'
            );
            return;
        }
    }

//冲值卡
    function card_cz() {
        $user_name = trim($_POST['user_name2']);
        $card_sn = trim($_POST['card_sn']);
        $card_pass = trim($_POST['card_pass']);
        if ($_POST) {//检测有提交//检测有提交
            if (preg_match("/[^0.-9]/", $card_pass)) {
                $this->show_warning('cuowu_nishurudebushishuzilei');
                return;
            }
            //充值对象不能为空
            if (empty($user_name)) {
                $this->show_warning('cuowu_mubiaoyonghubucunzai');
                return;
            }


            $user_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_name='$user_name'");
            $user_money = $user_row['money'];
            $user_jifen = $user_row['jifen'];
            $user_id = $user_row['user_id'];
            if (empty($user_id)) {
                $this->show_warning('cuowu_mubiaoyonghubucunzai');
                return;
            }
//$card_row=$this->my_card_mod->getrow("select * from ".DB_PREFIX."my_card where card_pass='$card_pass'");
            $card_row = $this->my_card_mod->getrow("select * from " . DB_PREFIX . "my_card where card_pass='$card_pass' and card_sn='$card_sn'");
            $card_id = $card_row['id'];
            //读取空 提示卡号、密码错误
            if (empty($card_row)) {
                $this->show_warning('cuowu_card_pass');
                return;
            }
            //检测过期时间小于现在时间，则提示已经过期
            if ($card_row['guoqi_time'] < time()) {
                $this->show_warning('cuowu_cardyijingguoqi');
                return;
            }
            if ($card_row['user_id'] != 0) {
                $this->show_warning('cuowu_cardyijingshiyongguole');
                return;
            } else {
                //添加日志
                $log_text = $user_name;
                $add_mymoneylog = array(
                    'user_id' => $user_id,
                    'user_name' => $user_name,
                    'buyer_id' => $this->visitor->get('user_id'),
                    'buyer_name' => $this->visitor->get('user_name'),
                    'seller_id' => $user_id,
                    'seller_name' => $user_name,
                    'order_sn ' => $card_sn,
                    'add_time' => gmtime(),
                    'leixing' => 30,
                    'money_zs' => $card_row['money'],
                    'money' => $card_row['money'],
                    'log_text' => $log_text,
                    'caozuo' => 50,
                    's_and_z' => 1,
                );
                //写入日志
                $this->my_moneylog_mod->add($add_mymoneylog);
                //定义新资金
                $new_user_money = $user_money + $card_row['money'];
                $new_user_jifen = $user_jifen + $card_row['money'];
                //定义资金数组
                $add_money = array('money' => $new_user_money,);
                $add_jifen = array('jifen' => $new_user_jifen,);
                //更新该用户资金
                $this->my_money_mod->edit('user_id=' . $user_id, $add_money);
                $this->my_money_mod->edit('user_id=' . $user_id, $add_jifen);
                //改变充值卡信息 已使用
                $add_cardlog = array(
                    'user_id' => $user_id,
                    'user_name' => $user_name,
                    'cz_time' => time(),
                );
                $this->my_card_mod->edit('id=' . $card_id, $add_cardlog);
                //提示语言
                $this->show_message('chongzhi_chenggong_jineyiruzhang', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits');
                return;
            }
        } else {//检测提交 否则//检测提交 开始
            header("Location: index.php?app=my_money");
            return;
        }//检测提交 结束
    }

//余额转帐
    function to_user() {
        $to_user = trim($_POST['to_user']);
        $to_money = trim($_POST['to_money']);
        $user_id = $this->visitor->get('user_id');
        if ($_POST) {//检测有提交//检测有提交
            if (preg_match("/[^0.-9]/", $to_money)) {
                $this->show_warning('cuowu_nishurudebushishuzilei');
                return;
            }


            $to_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_name='$to_user'");
            $to_user_id = $to_row['user_id'];
            $to_user_name = $to_row['user_name'];
            $to_user_money = $to_row['money'];

            if ($to_user_id == $user_id) {
                $this->show_warning('cuowu_bunenggeizijizhuanzhang');
                return;
            }

            if (empty($to_user_id)) {
                $this->show_warning('cuowu_mubiaoyonghubucunzai');
                return;
            }
            $user_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
            $user_money = $user_row['money'];
            $user_zf_pass = $user_row['zf_pass'];
            $user_mibao_id = $user_row['mibao_id'];
            if (empty($user_mibao_id)) {
                $zf_pass = md5(trim($_POST['zf_pass']));
                if ($user_zf_pass != $zf_pass) {
                    $this->show_warning('cuowu_zhifumimayanzhengshibai');
                    return;
                }
            } else {
//读取密保卡资料
                $user_zimuz1 = trim($_POST['user_zimuz1']);
                $user_zimuz2 = trim($_POST['user_zimuz2']);
                $user_zimuz3 = trim($_POST['user_zimuz3']);
                $user_shuzi1 = trim($_POST['user_shuzi1']);
                $user_shuzi2 = trim($_POST['user_shuzi2']);
                $user_shuzi3 = trim($_POST['user_shuzi3']);
                if (empty($user_shuzi1) or empty($user_shuzi2) or empty($user_shuzi3)) {
                    $this->show_warning('cuowu_dongtaimimayanzhengshibai');
                    return;
                }
                $mibao_row = $this->my_mibao_mod->getrow("select * from " . DB_PREFIX . "my_mibao where user_id='$user_id'");
                $mibao_shuzi1 = $mibao_row[$user_zimuz1];
                $mibao_shuzi2 = $mibao_row[$user_zimuz2];
                $mibao_shuzi3 = $mibao_row[$user_zimuz3];

                if ($user_shuzi1 != $mibao_shuzi1 or $user_shuzi2 != $mibao_shuzi2 or $user_shuzi3 != $mibao_shuzi3) { //检测密保相符 开始
                    echo Lang::get('money_banben');
                    $this->show_warning('cuowu_dongtaimimayanzhengshibai');
                    return;
                } //检测密保 否则 结束
            }


            $order_id = date('Ymd-His', time()) . '-' . $to_money;
            if ($user_money < $to_money) {
                $this->show_warning('cuowu_zhanghuyuebuzu');
                return;
            } else {
                //添加日志
                $log_text = $this->visitor->get('user_name') . Lang::get('gei') . $to_user . Lang::get('zhuanchujine') . $to_money . Lang::get('yuan');

                $add_mymoneylog = array(
                    'user_id' => $user_id,
                    'user_name' => $this->visitor->get('user_name'),
                    'buyer_name' => $this->visitor->get('user_name'),
                    'seller_name' => $to_user_name,
                    'order_sn ' => $order_id,
                    'add_time' => gmtime(),
                    'leixing' => 21,
                    'money_zs' => '-' . $to_money,
                    'money' => $to_money,
                    'log_text' => $log_text,
                    'caozuo' => 50,
                    's_and_z' => 2,
                );
                $this->my_moneylog_mod->add($add_mymoneylog);


                $log_text_to = $this->visitor->get('user_name') . Lang::get('gei') . $to_user_name . Lang::get('zhuanrujine') . $to_money . Lang::get('yuan');
                $add_mymoneylog_to = array(
                    'user_id' => $to_user_id,
                    'user_name' => $to_user_name,
                    'order_sn ' => $order_id,
                    'buyer_name' => $this->visitor->get('user_name'),
                    'seller_name' => $to_user_name,
                    'add_time' => gmtime(),
                    'leixing' => 11,
                    'money_zs' => $to_money,
                    'money' => $to_money,
                    'log_text' => $log_text_to,
                    'caozuo' => 50,
                    's_and_z' => 1,
                );
                $this->my_moneylog_mod->add($add_mymoneylog_to);

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

                $this->show_message('zhuanzhangchenggong');
                return;
            }
        } else {//检测提交 否则//检测提交 开始
            header("Location: index.php?app=my_money");
            return;
        }//检测提交 结束
    }

    /**
     * @to_user目标账户
     * @to_money 目标金额
     * 
     * @return type 
     */
    function to_user_withdraw($to_user, $to_money, $order_id, $order_sn, $zf_pass) {
        /*  $to_user = trim($_POST['to_user']);
          $to_money = trim($_POST['to_money']);
          $order_id = trim($_POST['order_id']); */
		$visitor = & env('visitor');
        $user_id = $visitor->get('user_id');

        if (preg_match("/[^0.-9]/", $to_money)) {
            //$this->show_warning('cuowu_nishurudebushishuzilei');
            return 'cuowu_nishurudebushishuzilei';
            //return false;
        }


        $to_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_name='$to_user'");
        $to_user_id = $to_row['user_id'];
        $to_user_name = $to_row['user_name'];
        $to_user_money = $to_row['money'];
        $to_user_money_dj = $to_row['money_dj'];
        if ($to_user_id == $user_id) {
            //$this->show_warning('cuowu_bunenggeizijizhuanzhang');
            return 'cuowu_bunenggeizijizhuanzhang';
            //return false;
        }

        if (empty($to_user_id)) {
            //$this->show_warning('cuowu_mubiaoyonghubucunzai');
            return 'cuowu_mubiaoyonghubucunzai';
        }
        $user_row = $this->my_money_mod->getrow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
        $user_money = $user_row['money'];
        $user_money_dj = $user_row['money_dj'];
        $user_zf_pass = $user_row['zf_pass'];
        $zf_pass = md5(trim($zf_pass));
        if ($user_zf_pass != $zf_pass) {
            //$this->show_warning('cuowu_zhifumimayanzhengshibai');
            return 'cuowu_zhifumimayanzhengshibai';
        }


//            $order_id = date('Ymd-His', time()) . '-' . $to_money;
        if ($user_money < $to_money) {
            //$this->show_warning('cuowu_zhanghuyuebuzu');
            return 'cuowu_zhanghuyuebuzu';
        } else {
            //添加日志
            $log_text = $visitor->get('user_name') . Lang::get('gei') . $to_user . Lang::get('zhuanchujine') . $to_money . Lang::get('yuan');

            $add_mymoneylog = array(
                'user_id' => $user_id,
                'user_name' => $visitor->get('user_name'),
                'buyer_name' => $visitor->get('user_name'),
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
            $this->my_moneylog_mod->add($add_mymoneylog);


            $log_text_to = $visitor->get('user_name') . Lang::get('gei') . $to_user_name . Lang::get('zhuanrujine') . $to_money . Lang::get('yuan');
            $add_mymoneylog_to = array(
                'user_id' => $to_user_id,
                'user_name' => $to_user_name,
                'order_id' => $order_id,
                'order_sn' => $order_sn,
                'buyer_name' => $visitor->get('user_name'),
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
            $this->my_moneylog_mod->add($add_mymoneylog_to);

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

            //$this->show_message('zhuanzhangchenggong');
            return true;
        }
    }
    

    /**
     * 功能：代发退款之后，需要关闭订单；最初退款时 代发用的是 活动资金，且用户下单后，订单资金进入了冻结，现在关闭订单，则需要解冻这部分资金。
     * 这种业务只适用于  订单全额退款，因为全额退款时，才需要关闭订单
     * @param 代发id $bh_id
     * @param 解冻金额  $jd_money
     * @param 订单编号 $order_sn
     */
    function jd_behalf_refund($bh_id, $jd_money, $order_sn) {
        $behalf_money_row = $this->my_money_mod->getRow("select * from " . DB_PREFIX . "my_money where user_id='$bh_id'");
        $sell_money = $behalf_money_row['money']; //卖家的资金
        $sell_money_dj = $behalf_money_row['money_dj']; //卖家的冻结资金
        $new_money = $sell_money + $jd_money;
        $new_money_dj = $sell_money_dj - $jd_money;
        if ($new_money_dj < 0) {
            return false;
        }
        $visitor = & env('visitor');
        //更新数据
        $new_money_array = array(
            'money' => $new_money,
            'money_dj' => $new_money_dj,
        );
        $this->my_money_mod->edit('user_id=' . $bh_id, $new_money_array);
       
        $jd_behalf_moneylog = array(
            'user_id' => $bh_id,
            'user_name' => $visitor->get('user_name'),
            'order_sn' => $order_sn, //基于订单，便于对账
            'add_time' => gmtime(),
            'leixing' => 90, //特殊标记，代发全额退款解冻资金
            'money_zs' => $jd_money,
            'money' => $jd_money,
            'log_text' => 'behalf refund,thaw capital!', //代发全额退款之后，解冻资金
            'caozuo' => 90, //特殊标记，代发全额退款解冻资金
            's_and_z' => 3, //特殊标记，代发全额退款解冻资金
        );
        $this->my_moneylog_mod->add($jd_behalf_moneylog);
       
        return true;
    }

    function notifyurl_51() {
        require_once("data/config.alipay.php");
        // 计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        if ($verify_result) {
//            $out_trade_no = $_POST ['out_trade_no'];
            $total_fee = $_POST ['total_fee'];
            $trade_no = $_POST ['out_trade_no'];
            $title = $_GET['subject'];
            $order_no = $_GET['trade_no'];
            if ($_POST ['trade_status'] == 'TRADE_FINISHED') {
                $trade_status = 2;

                // 注意：
                // 该种交易状态只在两种情况下出现
                // 1、开通了普通即时到账，买家付款成功后。
                // 2、开通了高级即时到账，从该笔算起交易成功时间，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。
                // 调试用，写文本函数记录程序运行情况是否正常
                // logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            } else if ($_POST ['trade_status'] == 'TRADE_SUCCESS') {
                $trade_status = 1;
                // 判断是否已经做过处理
                // 注意：
                // 该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。
                // 调试用，写文本函数记录程序运行情况是否正常
                // logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            }
            $user_id = $this->visitor->get('user_id');
            $user_name = $this->visitor->get('user_name');
//            header("location: http://yjsk.51zwd.com/index.php?app=default&act=mymoney&tradeno=" . $tradeno . "&totalfee=" . $totalfee . "&user_id=" . $user_id . "&user_name=" . $user_name);
            $this->mymoney($user_id, $user_name, $trade_no, $total_fee, 0, $title, $order_no);
            echo "success"; // 请不要修改或删除
        } else {
            // 验证失败
            echo "fail";
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
            $title = $_GET['subject'];
            $order_no = $_GET['trade_no'];

            if ($_GET ['trade_status'] == 'TRADE_FINISHED' || $_GET ['trade_status'] == 'TRADE_SUCCESS') {
                if ($_GET ['trade_status'] == 'TRADE_SUCCESS') {
                    $trade_status = 1;
                } else if ($_GET ['trade_status'] == 'TRADE_FINISHED') {
                    $trade_status = 2;
                }
                $user_id = $this->visitor->get('user_id');
                $user_name = $this->visitor->get('user_name');
//                header("location: http://yjsk.51zwd.com/index.php?app=default&act=mymoney&tradeno=" . $trade_no . "&totalfee=" . $total_fee . "&user_id=" . $user_id . "&user_name=" . $user_name);
                $this->mymoney($user_id, $user_name, $trade_no, $total_fee, 0, $title, $order_no);
            }
        } else {
            echo 'it is wrong!';
        }
    }

    public function doalipayBank_51() {
        require_once("data/config.alipay.php");
        $payment_type = "1";
        $notify_url = $alipay['notify_url'];
        $return_url = $alipay['return_url'];
        $seller_email = $alipay['seller_email'];
        $out_trade_no = $_POST ['WIDout_trade_no'];
        $subject = $_POST ['WIDsubject'];
        $total_fee = $_POST ['WIDtotal_fee'];
//                exit( $notify_url.'<br>'.$total_fee.$alipay_config['partner']) ;
        // 防钓鱼时间戳，若要使用请调用类文件submit中的query_timestamp函数
        $anti_phishing_key = "";
        // 客户端的IP地址,非局域网的外网IP地址，如：221.0.0.1
        $exter_invoke_ip = "";
        $body = "";
        $show_url = "";
        $submit_type = 1;
        // 网银接口参数
        if ($submit_type == 1) {
            // 默认支付方式
            $paymethod = "bankPay";
            // 必填
            // 默认网银
            if ($_POST ['defaultbank']) {
                $defaultbank = $_POST ['defaultbank'];
            } else {
                $defaultbank = $_POST ['WIDdefaultbank'];
            }

            // 必填，银行简码请参考接口技术文档
        }
        $user_id = $this->visitor->get('user_id');
        $user_name = $this->visitor->get('user_name');
//        $subject = $user_name . '网银充值' . time();
        $out_trade_no = 'ZSerial:' . $user_id . '_' . date('y_m_d_H_i_s', time());
        $subject = $out_trade_no;
        // 构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "create_direct_pay_by_user",
            "partner" => trim($alipay_config ['partner']),
            "payment_type" => $payment_type,
            "notify_url" => $notify_url,
            "return_url" => $return_url,
            "seller_email" => $seller_email,
            "out_trade_no" => $out_trade_no, //商户的订单号
            "subject" => $subject, //商品名称
            "total_fee" => $total_fee,
            "body" => $body,
            "show_url" => $show_url,
            "anti_phishing_key" => $anti_phishing_key,
            "exter_invoke_ip" => $exter_invoke_ip,
            "_input_charset" => trim(strtolower($alipay_config ['input_charset']))
        );
        // 网银接口参数
        if ($submit_type == 1) {
            $parameter ["paymethod"] = $paymethod;
            $parameter ["defaultbank"] = $defaultbank;
        }

        // 建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        header("Content-type: text/html; charset= utf-8");
        $html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
        // echo " 正在进入支付宝收银台。。

        $this->mymoney($user_id, $user_name, $out_trade_no, $total_fee, 2, $subject, null);
        echo $html_text;
    }

    function mytest() {
        $this->mymoney(4, '$user_name', '$tradeno', 0.01, $user_log_del = 0);
    }

    /**
     * @param type $user_log_del=0 增加的；1删除; 2 完成一半的
     * @param type $user_id
     * @param type $user_name
     * @param type $tradeno
     * @param type $total_fee
     */
    function mymoney($user_id, $user_name, $tradeno, $total_fee, $user_log_del = 0, $subject, $order_no) {
        $my_money_mod = & m('my_money');
        $my_moneylog_mod = & m('my_moneylog');

        //添加日志
        $log_text = '网银充值'; //$this->visitor->get('user_name') . Lang::get('tongguoalipaychongzhi') . $total_fee . Lang::get('yuan');
        if ($user_log_del == 2) {
            $add_mymoneylog = array(
                'user_id' => $user_id,
                'user_name' => $user_name,
                'buyer_name' => Lang::get('alipay'),
                'seller_id' => $user_id,
                'seller_name' => $user_name,
                'order_sn ' => $tradeno,
                'add_time' => gmtime(),
                'admin_time' => gmtime(),
                'leixing' => 30,
                'money_zs' => $total_fee,
                'money' => $total_fee,
                'log_text' => $log_text,
                'user_log_del' => $user_log_del,
                'caozuo' => 4,
                's_and_z' => 1,
            );
            $my_moneylog_mod->add($add_mymoneylog);
            //添加到paylog里方便稽核
            $paylogmodel = &m('paylog');
            $condition ['out_trade_no'] = $subject;
            $condition ['total_fee'] = $total_fee;
            $condition ['createtime'] = date("Y-m-d H:i:s", time());
            $condition ['endtime'] = date("Y-m-d H:i:s", time());
            $condition ['trade_status'] = 0; //未成交
            $condition ['customer_id'] = $user_id;
            $condition ['customer_name'] = $user_name;
            $condition ['type'] = 0;
            $condition ['trade_no'] = $subject;
            $paylogmodel->add($condition);
        } else if ($user_log_del == 0) {
            $paylogmodel = &m('paylog');
            $sql = 'SELECT out_trade_no from  ' . $paylogmodel->table . ' where out_trade_no="' . $order_no . '"';
            $results = $paylogmodel->db->getOne($sql);
            if ($results && !empty($results)) {
                $finalresult['result'] = 'error';
                $finalresult['info'] = '该交易号已经在站内被充值过！';
                exit(json_encode($finalresult));
            }

            $user_row = $my_money_mod->getrow("select money from ecm_my_money where user_id=" . $user_id);

            $user_money = $user_row['money'];
            $user_money_dj = $user_row['money_dj'];
            $user_jifen = $user_row['jifen'];
            $new_money = $user_money + $total_fee;
            $new_jifen = $user_jifen + $total_fee;
            $edit_mymoney = array(
                'money' => $new_money,
            );
            $edit_myjifen = array(
                'jifen' => $new_jifen,
            );
            $res = $my_moneylog_mod->edit('order_sn=\'' . $tradeno . '\' and money=' . $total_fee, array('user_log_del' => 0
                , 'admin_time' => gmtime(), 'order_sn' => $order_no, 'moneyleft' => $new_money + $user_money_dj));
            if (!$res) {
                $this->show_message('failed for check in!');
                return;
            }

            $my_money_mod->edit('user_id=' . $user_id, $edit_mymoney);
            $my_money_mod->edit('user_id=' . $user_id, $edit_myjifen);

            //

            $sql = 'SELECT trade_no from ecm_paylog where trade_no="' . $subject . '"';
            $results = $paylogmodel->db->getOne($sql);
            if ($results) {
                $updateOuttrade = array(
                    'out_trade_no' => $order_no,
                    'trade_status' => 1,
                );
                $paylogmodel->edit('trade_no="' . $subject . '"', $updateOuttrade);
            } else {
                $condition ['out_trade_no'] = $order_no;
                $condition ['total_fee'] = $total_fee;
                $condition ['createtime'] = date("Y-m-d H:i:s", time());
                $condition ['endtime'] = date("Y-m-d H:i:s", time());
                $condition ['trade_status'] = 1;
                $condition ['customer_id'] = $user_id;
                $condition ['type'] = 0;
                $condition ['trade_no'] = $subject;
                $paylogmodel->add($condition);
            }
            $this->show_message('chongzhi_chenggong_jineyiruzhang', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits'
            );
        }

        //   $this->show_message('chongzhi_chenggong_jineyiruzhang', 'chakancicichongzhi', 'index.php?app=my_money&act=paylog', 'guanbiyemian', 'index.php?app=my_money&act=exits'
        //   );
    }

}
?>
