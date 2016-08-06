<?php

class RestoreApp extends MallbaseApp
{
    function index()
    {
        /* 判断是否开启了店铺申请 */
        if (!Conf::get('store_allow'))
        {
            $this->show_warning('apply_disabled');
            return;
        }

        /* 只有登录的用户才可申请 */
        if (!$this->visitor->has_login)
        {
            $this->login();
            return;
        }

        /* 不是被规则关闭的店铺不能申诉 */
        $store_mod =& m('store');
        $store = $store_mod->get($this->visitor->get('user_id'));
        if ($store)
        {
            if ($store['state'] != '1' && $store['close_reason'] != 'rules')
            {
                $this->show_warning('not_closed_by_rules');
                return;
            }
        }

        if (!IS_POST)
        {
            $this->display('restore.index.html');
        }
        else
        {
            $reason = $_POST['reason'];
            $nowtime = gmtime();
            $sql = "insert into ecm_store_restore(store_id, reason, state, last_update) values ({$this->visitor->get('user_id')}, '{$reason}', 0, {$nowtime}) on duplicate key update reason = '{$reason}', state = 0, last_update = {$nowtime}";
            $rs = $store_mod->db->query($sql);
            if ($rs)
            {
                $this->show_message('apply_restore_success', 'back_member', 'index.php?app=member');
            }
            else
            {
                $this->show_warning('apply_restore_fail');
            }
        }
    }
}

?>