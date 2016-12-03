<?php

class Mobile_taobaoApp extends Mobile_frontendApp {
    private $_session_key = null;

    function __construct() {
        parent::__construct();
        $auth_mod =& m('memberauth');
        $auth_info = $auth_mod->get(array('conditions' => 'vendor = 0 and user_id = '.$this->visitor->get('user_id')));
        if (empty($auth_info)) {
            $this->_ajax_error(400, CHECK_TAOBAO_FAILED, '系统中不存在该淘宝用户，请前往www.51zwd.com使用一次淘登录');
            return ;
        }
        $this->_session_key = $auth_info['access_token'];
    }

    function add_item() {
        if (IS_POST) {
            $goods_id = $this->_make_sure_numeric('goods_id', -1);
            if ($goods_id === -1) {
                $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
                return ;
            }
            $this->_add_item($goods_id);
        } else {
            $this->_ajax_error(400, NOT_POST_ACTION, 'not a post action');
            return;
        }
    }

    function _add_item($goods_id) {
        $result = file_get_contents("http://yjsc.51zwd.com/taobao-upload-multi-store/index.php?g=Taobao&m=Upload&a=uploadItemFromAndroid&taobaoItemId=".$goods_id.'&access_token='.$this->_session_key);
        if ($result === 'true') {
            echo ecm_json_encode(array('success' => true));
        } else {
            $this->_ajax_error(500, TAOBAO_API_ERROR, '宝贝上传失败: '.$result);
        }
    }
}

?>