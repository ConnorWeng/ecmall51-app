<?php

class Mobile_settingApp extends Mobile_frontendApp {
    function __construct() {
        parent::__construct();
    }

    function shop_settings() {
        $user_id = $this->visitor->get('user_id');
        if (IS_POST) {
            $profit = $this->_make_sure_numeric('profit', -1);
            $shop_nick = $this->_make_sure_string('shop_nick', 60, '');
            $mobile = $this->_make_sure_numeric('mobile', 0);
            $im_qq = $this->_make_sure_string('im_qq', 60, '');
            $im_wx = $this->_make_sure_string('im_wx', 60, '');
            $im_ww = $this->_make_sure_string('im_ww', 60, '');
            $announcement = $this->_make_sure_string('announcement', 1000, '');

            if ($profit < 0 || $shop_nick === '' || ($mobile === 0 && $im_qq === '' && $im_wx === '' && $im_ww === '')) {
                $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
                return ;
            }

            $this->_save_shop_settings($user_id, $profit, $shop_nick, $mobile, $im_qq, $im_wx, $im_ww, $announcement);
        } else {
            $this->_get_shop_settings($user_id);
        }
    }

    function _get_shop_settings($user_id) {
        $mobileshop_mod =& m('mobileshop');
        $setting = $mobileshop_mod->get_info($user_id);
        if ($setting) {
            echo ecm_json_encode($setting);
        } else {
            $this->_ajax_error(500, DB_ERROR, '找不到微店配置');
        }
    }

    function _save_shop_settings($user_id, $profit, $shop_nick, $mobile, $im_qq, $im_wx, $im_ww, $announcement) {
        $mobileshop_mod =& m('mobileshop');
        $result = $mobileshop_mod->add(array(
            'user_id' => $user_id,
            'shop_nick' => $shop_nick,
            'profit' => $profit,
            'mobile' => $mobile,
            'im_qq' => $im_qq,
            'im_wx' => $im_wx,
            'im_ww' => $im_ww,
            'announcement' => $announcement), true);
        if ($result) {
            echo ecm_json_encode(array('success' => true));
        } else {
            $this->_ajax_error(500, DB_ERROR, '保存失败');
        }
    }
}

?>