<?php

/* 后台资金处理　 */

class FakeMoneyApp extends MallbaseApp {

    function __construct() {
        $this->FakeMoneyApp();
    }

    function FakeMoneyApp() {
        parent::__construct();
        $this->_moneylog_mod = & m('my_moneylog');
        $this->my_money_mod = & m('my_money');
        $this->order = & m('order');
        $this->orderlog = &m('order_log');
    }

    function fullfillshiptime() {
        $bh_id = $_GET['bh_id'];
        if (!empty($bh_id)) {
            $extra = ' and o.bh_id=' . $bh_id;
        } else {
            echo 'you should put bh_id ,or I cannot execute!';
//            return;
        }
        $sqla = 'select o.invoice_no,o.ship_time,o.status,o.order_sn,o.order_id,o.bh_id,l.log_time as shiptime  from ecm_order o ,ecm_order_log l where (o.status=30 or o.status=40) and (o.ship_time is null or o.ship_time < 10000) and o.order_id = l.order_id ';
        $sqla.=$extra;

        $resulta = $this->order->getAll($sqla);
        foreach ($resulta as $r) {
            if (!empty($r['shiptime'])) {
                $edit_data = array('ship_time' => $r['shiptime']);
                Log::write('fullfill:' . $r['bh_id'] . '--' . $r['order_id'] . '--' . var_export($edit_data, true));
                echo '<br> fullfill:' . $r['bh_id'] . '--' . $r['order_id'] . '--' . var_export($edit_data, true);
                if (!empty($bh_id)) {
                    $affect_rows = $this->order->edit(intval($r['order_id']), $edit_data);
                }
            }
        }
    }

    /** 前台
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
            //return ' the change should between one and behalf!';
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

            $add_jia = ' money = money +' . $to_money;
            $this->my_money_mod->edit('user_id=' . $to_user_id, $add_jia);
            $add_jian = ' money = money -' . $to_money;
            $this->my_money_mod->edit('user_id=' . $user_id, $add_jian);

            return true;
        }
    }

    /**
     * 手动冻结
     * @param type $user_id
     * @param type $jd_money
     * @return boolean 
     */
    function manuFro($user_id, $jd_money) {
        //add by tanaiquan 2016-03-11
        $visitor = & env('visitor');
        //end
        $behalf_money_row = $this->my_money_mod->getRow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
        $sell_money = $behalf_money_row['money']; //卖家的资金
        $sell_money_dj = $behalf_money_row['money_dj']; //卖家的冻结资金
        $new_money = $sell_money - $jd_money;
        $new_money_dj = $sell_money_dj + $jd_money;
        if ($new_money < 0) {
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
            'user_name' => $visitor->get('user_name'),
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

    /**
     * 手动解冻
     * @param type $user_id
     * @param type $jd_money
     * @return boolean 
     */
    function manuRefro($user_id, $jd_money) {
        //add by tanaiquan 2016-03-11
        $visitor = & env('visitor');
        //end
        $behalf_money_row = $this->my_money_mod->getRow("select * from " . DB_PREFIX . "my_money where user_id='$user_id'");
        $sell_money = $behalf_money_row['money']; //卖家的资金
        $sell_money_dj = $behalf_money_row['money_dj']; //卖家的冻结资金
        $new_money = $sell_money + $jd_money;
        $new_money_dj = $sell_money_dj - $jd_money;
        if ($new_money_dj < 0) {
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
            'user_name' => $visitor->get('user_name'),
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

}

?>
