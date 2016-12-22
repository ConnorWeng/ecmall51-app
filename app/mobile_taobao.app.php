<?php

class Mobile_taobaoApp extends Mobile_frontendApp {
    private $_API_PREFIX = "http://yjsc.51zwd.com/taobao-upload-multi-store/index.php";
    private $_session_key = null;

    private $_auth_mod = null;

    function __construct($auth_mod = null, $user_id = null) {
        parent::__construct();
        $this->_auth_mod = $auth_mod;
        if ($this->_auth_mod === null) {
            $this->_auth_mod =& m('memberauth');
        }
        $auth_info = $this->_auth_mod->get(array('conditions' => 'vendor = 0 and user_id = '.($user_id === null ? $this->visitor->get('user_id') : $user_id)));
        if (empty($auth_info)) {
            $this->_ajax_error(400, CHECK_TAOBAO_FAILED, '系统中不存在该淘宝用户，请前往www.51zwd.com使用一次淘登录');
            exit ;
        }
        $this->_session_key = $auth_info['access_token'];
    }

    function add_item() {
        $this->_post(
            function () {
                $goods_id = $this->_make_sure_numeric('goods_id', -1);
                $title = $this->_make_sure_string('title', 255, '');
                $price = $this->_make_sure_string('price', 10, '');
                $desc = $this->_make_sure_string('desc', 65536, '');
                if ($goods_id === -1 || empty($title)  || empty($price)|| empty($desc)) {
                    $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
                    return ;
                }
                $this->_add_item($goods_id, $title, $price, $desc);
            });
    }

    function _add_item($goods_id, $title, $price, $desc) {
        $url = $this->_API_PREFIX."?g=Taobao&m=Upload&a=uploadItemFromAndroid";
        $data = array(
            'taobaoItemId' => $goods_id,
            'access_token' => $this->_session_key,
            'title' => $title,
            'price' => $price,
            'desc' => $desc);
        $result = $this->_post_url($url, $data);
        $result = str_replace('"', '', $result);
        if ($result === 'true') {
            echo ecm_json_encode(array('success' => true));
        } else {
            $this->_ajax_error(500, TAOBAO_API_ERROR, '宝贝上传失败: '.$result);
        }
    }

    function make_picture_category() {
        $pcid = file_get_contents($this->_API_PREFIX."?g=Taobao&m=Upload&a=make51PictureCategoryFromAndroid&access_token=".$this->_session_key);
        $pcid = str_replace('"', '', $pcid);
        if (is_numeric($pcid)) {
            echo ecm_json_encode(array('pcid' => $pcid));
        } else {
            $this->_ajax_error(500, TAOBAO_API_ERROR, '获取相册失败, id:'.$pcid);
        }
    }

    function upload_pictures() {
        $this->_post(
            function () {
                $img_urls_param = $this->_make_sure_string('img_urls', 65536, '');
                $pcid = $this->_make_sure_string('pcid', 30, '');
                if (empty($img_urls_param) || empty($pcid)) {
                    $this->_ajax_error(400, PARAMS_ERROR, '参数错误');
                    return ;
                }

                $img_urls = explode(',', $img_urls_param);
                $new_urls = array();
                $error_count = 0;
                foreach ($img_urls as $url) {
                    $upload_result = json_decode($this->_get_url($this->_API_PREFIX."?g=Taobao&m=Upload&a=uploadTaobaoPictureFromAndroid&imgUrl=".urlencode($url)."&pictureCategoryId=".$pcid."&access_token=".$this->_session_key));
                    if (isset($upload_result->error)) {
                        array_push($new_urls, array(
                            'newImgUrl' => '',
                            'oldImgUrl' => $url));
                        $error_count++;
                    } else {
                        array_push($new_urls, $upload_result);
                    }
                }
                if ($error_count > 10) { // 图片搬家失败10张才算整个请求失败，否则直接忽略失败的图片
                    $this->_ajax_error(500, TAOBAO_API_ERROR, '图片搬家失败');
                } else {
                    echo ecm_json_encode($new_urls);
                }
            });
    }
}

?>