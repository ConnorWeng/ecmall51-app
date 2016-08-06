<?php

/**
 *    Desc
 *
 *    @author    Garbin
 *    @usage    none
 */
class MemberApp extends MemberbaseApp
{
    var $_feed_enabled = false;
    function __construct()
    {
        $this->MemberApp();
    }
    function MemberApp()
    {
        parent::__construct();
        $ms =& ms();
        $this->_feed_enabled = $ms->feed->feed_enabled();
        $this->assign('feed_enabled', $this->_feed_enabled);
    }
    function index()
    {
        /* 清除新短消息缓存 */
        $cache_server =& cache_server();
        $cache_server->delete('new_pm_of_user_' . $this->visitor->get('user_id'));

        $user = $this->visitor->get();
        $user_mod =& m('member');
        $info = $user_mod->get_info($user['user_id']);
        $user['portrait'] = portrait($user['user_id'], $info['portrait'], 'middle');
        $this->assign('user', $user);

        /* 店铺信用和好评率 */
        if ($user['has_store'])
        {
            $store_mod =& m('store');
            $store = $store_mod->get_info($user['has_store']);
            $step = intval(Conf::get('upgrade_required'));
            $step < 1 && $step = 5;
            $store['credit_image'] = $this->_view->res_base . '/images/' . $store_mod->compute_credit($store['credit_value'], $step);
            $this->assign('store', $store);
            $this->assign('store_closed', STORE_CLOSED);
        }
        $goodsqa_mod = & m('goodsqa');
        $groupbuy_mod = & m('groupbuy');
        /* 买家提醒：待付款、待确认、待评价订单数 */
        $order_mod =& m('order');
        $sql1 = "SELECT COUNT(*) FROM {$order_mod->table} WHERE buyer_id = '{$user['user_id']}' AND status = '" . ORDER_PENDING . "'";
        $sql2 = "SELECT COUNT(*) FROM {$order_mod->table} WHERE buyer_id = '{$user['user_id']}' AND status = '" . ORDER_SHIPPED . "'";
        $sql3 = "SELECT COUNT(*) FROM {$order_mod->table} WHERE buyer_id = '{$user['user_id']}' AND status = '" . ORDER_FINISHED . "' AND evaluation_status = 0";
        $sql4 = "SELECT COUNT(*) FROM {$goodsqa_mod->table} WHERE user_id = '{$user['user_id']}' AND reply_content !='' AND if_new = '1' ";
        $sql5 = "SELECT COUNT(*) FROM " . DB_PREFIX ."groupbuy_log AS log LEFT JOIN {$groupbuy_mod->table} AS gb ON gb.group_id = log.group_id WHERE log.user_id='{$user['user_id']}' AND gb.state = " .GROUP_CANCELED;
        $sql6 = "SELECT COUNT(*) FROM " . DB_PREFIX ."groupbuy_log AS log LEFT JOIN {$groupbuy_mod->table} AS gb ON gb.group_id = log.group_id WHERE log.user_id='{$user['user_id']}' AND gb.state = " .GROUP_FINISHED;
        $buyer_stat = array(
            'pending'  => $order_mod->getOne($sql1),
            'shipped'  => $order_mod->getOne($sql2),
            'finished' => $order_mod->getOne($sql3),
            'my_question' => $goodsqa_mod->getOne($sql4),
            'groupbuy_canceled' => $groupbuy_mod->getOne($sql5),
            'groupbuy_finished' => $groupbuy_mod->getOne($sql6),
        );
        $sum = array_sum($buyer_stat);
        $buyer_stat['sum'] = $sum;
        $this->assign('buyer_stat', $buyer_stat);

        /* 卖家提醒：待处理订单和待发货订单 */
        if ($user['has_store'])
        {

            $sql7 = "SELECT COUNT(*) FROM {$order_mod->table} WHERE seller_id = '{$user['user_id']}' AND bh_id=0 AND status = '" . ORDER_SUBMITTED . "'";
            $sql8 = "SELECT COUNT(*) FROM {$order_mod->table} WHERE seller_id = '{$user['user_id']}' AND bh_id=0 AND status = '" . ORDER_ACCEPTED . "'";
            $sql9 = "SELECT COUNT(*) FROM {$goodsqa_mod->table} WHERE store_id = '{$user['user_id']}' AND reply_content ='' ";
            $sql10 = "SELECT COUNT(*) FROM {$groupbuy_mod->table} WHERE store_id='{$user['user_id']}' AND state = " .GROUP_END;
            $seller_stat = array(
                'submitted' => $order_mod->getOne($sql7),
                'accepted'  => $order_mod->getOne($sql8),
                'replied'   => $goodsqa_mod->getOne($sql9),
                'groupbuy_end'   => $goodsqa_mod->getOne($sql10),
            );

            $this->assign('seller_stat', $seller_stat);
        }
        /* 卖家提醒： 店铺等级、有效期、商品数、空间 */
        if ($user['has_store'])
        {
            $store_mod =& m('store');
            $store = $store_mod->get_info($user['has_store']);

            $grade_mod = & m('sgrade');
            $grade = $grade_mod->get_info($store['sgrade']);

            $goods_mod = &m('goods');
            $goods_num = $goods_mod->get_count_of_store($user['has_store']);
            $uploadedfile_mod = &m('uploadedfile');
            $space_num = $uploadedfile_mod->get_file_size($user['has_store']);
            $sgrade = array(
                'grade_name' => $grade['grade_name'],
                'add_time' => empty($store['end_time']) ? 0 : sprintf('%.2f', ($store['end_time'] - gmtime())/86400),
                'goods' => array(
                    'used' => $goods_num,
                    'total' => $grade['goods_limit']),
                'space' => array(
                    'used' => sprintf("%.2f", floatval($space_num)/(1024 * 1024)),
                    'total' => $grade['space_limit']),
                    );
            $this->assign('sgrade', $sgrade);

        }

        /* 待审核提醒 */
        if ($user['state'] != '' && $user['state'] == STORE_APPLYING)
        {
            $this->assign('applying', 1);
        }

        /*代发提醒：待发货订单 和 待处理订单*/
        if($user['has_behalf'] && $user['pass_behalf'])
        {
            $sql10 = "SELECT COUNT(*) FROM {$order_mod->table} WHERE bh_id = '{$user['has_behalf']}' AND status = '" . ORDER_SUBMITTED . "'";
            $sql11 = "SELECT COUNT(*) FROM {$order_mod->table} WHERE bh_id = '{$user['has_behalf']}' AND status = '" . ORDER_ACCEPTED . "'";
            $behalf_stat = array(
                    'submitted' => $order_mod->getOne($sql10),
                    'accepted'  => $order_mod->getOne($sql11),
            );

            $this->assign('behalf_stat', $behalf_stat);
        }


        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'),    url('app=member'),
                         LANG::get('overview'));

        /* 当前用户中心菜单 */
        $this->_curitem('overview');
        $this->_config_seo('title', Lang::get('member_center'));
        $this->display('member.index.html');
    }

    /**
     *    注册一个新用户
     *
     *    @author    Garbin
     *    @return    void
     */
    function register()
    {
        if ($this->visitor->has_login)
        {
            $this->show_warning('has_login');

            return;
        }
        if (!IS_POST)
        {
            if (!empty($_GET['ret_url']))
            {
                $ret_url = trim($_GET['ret_url']);
            }
            else
            {
                if (isset($_SERVER['HTTP_REFERER']))
                {
                    $ret_url = $_SERVER['HTTP_REFERER'];
                }
                else
                {
                    $ret_url = SITE_URL . '/index.php';
                }
            }
            $this->assign('ret_url', rawurlencode($ret_url));
            $this->_curlocal(LANG::get('user_register'));
            $this->_config_seo('title', Lang::get('user_register') . ' - ' . Conf::get('site_title'));

            if (Conf::get('captcha_status.register'))
            {
                $this->assign('captcha', 1);
            }

            /* 导入jQuery的表单验证插件  tyioocom */
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js,jquery.plugins/poshy_tip/jquery.poshytip.js',
                'style'  => 'jquery.plugins/poshy_tip/tip-yellowsimple/tip-yellowsimple.css')
                        );
            $this->display('member.register.html');
        }
        else
        {
            if (!$_POST['agree'])
            {
                $this->show_warning('agree_first');

                return;
            }
            if (Conf::get('captcha_status.register') && base64_decode($_SESSION['captcha']) != strtolower($_POST['captcha']))
            {
                $this->show_warning('captcha_failed');
                return;
            }
            if ($_POST['password'] != $_POST['password_confirm'])
            {
                /* 两次输入的密码不一致 */
                $this->show_warning('inconsistent_password');
                return;
            }

            /* 注册并登陆 */
            $user_name = trim($_POST['user_name']);
            $password  = $_POST['password'];
            $email     = trim($_POST['email']);
            $passlen = strlen($password);
            $user_name_len = iconv_strlen($user_name, UC_CHARSET);
            if ($user_name_len < 3 || $user_name_len > 35)
            {
                $this->show_warning('user_name_length_error');

                return;
            }
            if ($passlen < 6 || $passlen > 20)
            {
                $this->show_warning('password_length_error');

                return;
            }
            if (!is_email($email))
            {
                $this->show_warning('email_error');

                return;
            }

            $ms =& ms(); //连接用户中心
            $user_id = $ms->user->register($user_name, $password, $email);

            if (!$user_id)
            {
                $this->show_warning($ms->user->get_error());

                return;
            }
            $this->_hook('after_register', array('user_id' => $user_id));
            //登录
            $this->_do_login($user_id);

            /* 同步登陆外部系统 */
            $synlogin = $ms->user->synlogin($user_id);

            #TODO 可能还会发送欢迎邮件
            if($_POST['batchcreate']) {
                echo $user_id;
//                exit($user_id);
                return;

            }
            $this->show_message(Lang::get('register_successed') . $synlogin,
                'back_before_register', rawurldecode($_POST['ret_url']),
                'enter_member_center', 'index.php?app=member',
                'apply_store', 'index.php?app=apply'
            );
        }
    }


    /**
     *    检查用户是否存在
     *
     *    @author    Garbin
     *    @return    void
     */
    function check_user()
    {
        $user_name = empty($_GET['user_name']) ? null : trim($_GET['user_name']);
        if (!$user_name)
        {
            echo ecm_json_encode(false);

            return;
        }
        $ms =& ms();

        echo ecm_json_encode($ms->user->check_username($user_name));
    }

    /**
     *    修改基本信息
     *
     *    @author    Hyber
     *    @usage    none
     */
    function profile(){

        $user_id = $this->visitor->get('user_id');
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),  'index.php?app=member',
                             LANG::get('basic_information'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_profile');

            /* 当前所处子菜单 */
            $this->_curmenu('basic_information');

            $ms =& ms();    //连接用户系统
            $edit_avatar = $ms->user->set_avatar($this->visitor->get('user_id')); //获取头像设置方式

            $model_user =& m('member');
            $profile    = $model_user->get_info(intval($user_id));
            $profile['portrait'] = portrait($profile['user_id'], $profile['portrait'], 'middle');
            $this->assign('profile',$profile);
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js',
            ));
            $this->assign('edit_avatar', $edit_avatar);
            $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('my_profile'));
            $this->display('member.profile.html');
        }
        else
        {
            $data = array(
                'real_name' => $_POST['real_name'],
                'gender'    => $_POST['gender'],
                'birthday'  => $_POST['birthday'],
                'im_aliww'    => $_POST['im_aliww'],
                'im_qq'     => $_POST['im_qq'],
                'phone_tel'=>$_POST['phone_tel'],
                'phone_mob'=>$_POST['phone_mob'],
            );

            if (!empty($_FILES['portrait']))
            {
                $portrait = $this->_upload_portrait($user_id);
                if ($portrait === false)
                {
                    return;
                }
                $data['portrait'] = $portrait;
            }

            $model_user =& m('member');
            $model_user->edit($user_id , $data);
            if ($model_user->has_error())
            {
                $this->show_warning($model_user->get_error());

                return;
            }

            $this->show_message('edit_profile_successed');
        }
    }
    /**
     *    修改密码
     *
     *    @author    Hyber
     *    @usage    none
     */
    function password(){
        $user_id = $this->visitor->get('user_id');
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),  'index.php?app=member',
                             LANG::get('edit_password'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_profile');

            /* 当前所处子菜单 */
            $this->_curmenu('edit_password');
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js',
            ));
            $this->_config_seo('title', Lang::get('user_center') . ' - ' . Lang::get('edit_password'));
            $this->display('member.password.html');
        }
        else
        {
            /* 两次密码输入必须相同 */
            $orig_password      = $_POST['orig_password'];
            $new_password       = $_POST['new_password'];
            $confirm_password   = $_POST['confirm_password'];
            if ($new_password != $confirm_password)
            {
                $this->show_warning('twice_pass_not_match');

                return;
            }
            if (!$new_password)
            {
                $this->show_warning('no_new_pass');

                return;
            }
            $passlen = strlen($new_password);
            if ($passlen < 6 || $passlen > 20)
            {
                $this->show_warning('password_length_error');

                return;
            }

            /* 修改密码 */
            $ms =& ms();    //连接用户系统
            $result = $ms->user->edit($this->visitor->get('user_id'), $orig_password, array(
                'password'  => $new_password
            ));
            if (!$result)
            {
                /* 修改不成功，显示原因 */
                $this->show_warning($ms->user->get_error());

                return;
            }

            $this->show_message('edit_password_successed');
        }
    }
    /**
     *    修改电子邮箱
     *
     *    @author    Hyber
     *    @usage    none
     */
    function email(){
        $user_id = $this->visitor->get('user_id');
        $user_name=$this->visitor->get('user_name');
        $ignorepwd = false;
        if(strpos($user_name,'51t_') !== false && strpos($user_name,'51t_') == 0)
        {
            $ignorepwd = true;
        }
        if(strpos($user_name,'51a_') !== false && strpos($user_name,'51a_') == 0)
        {
            $ignorepwd = true;
        }
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),  'index.php?app=member',
                             LANG::get('edit_email'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_profile');

            /* 当前所处子菜单 */
            $this->_curmenu('edit_email');
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js',
            ));
            $this->_config_seo('title', Lang::get('user_center') . ' - ' . Lang::get('edit_email'));
            $this->assign('ignorepwd',$ignorepwd);
            $this->display('member.email.html');
        }
        else
        {
            $orig_password  = $_POST['orig_password'];
            $email          = isset($_POST['email']) ? trim($_POST['email']) : '';
            if (!$email)
            {
                $this->show_warning('email_required');

                return;
            }
            if (!is_email($email))
            {
                $this->show_warning('email_error');

                return;
            }




            $ms =& ms();    //连接用户系统
            $result = $ms->user->edit($this->visitor->get('user_id'), $orig_password, array(
                'email' => $email
            ),$ignorepwd);

            if (!$result)
            {
                $this->show_warning($ms->user->get_error());

                return;
            }

            $this->show_message('edit_email_successed');
        }
    }

    /**
     * Feed设置
     *
     * @author Garbin
     * @param
     * @return void
     **/
    function feed_settings()
    {
        if (!$this->_feed_enabled)
        {
            $this->show_warning('feed_disabled');
            return;
        }
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),  'index.php?app=member',
                             LANG::get('feed_settings'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_profile');

            /* 当前所处子菜单 */
            $this->_curmenu('feed_settings');
            $this->_config_seo('title', Lang::get('user_center') . ' - ' . Lang::get('feed_settings'));

            $user_feed_config = $this->visitor->get('feed_config');
            $default_feed_config = Conf::get('default_feed_config');
            $feed_config = !$user_feed_config ? $default_feed_config : unserialize($user_feed_config);

            $buyer_feed_items = array(
                'store_created' => Lang::get('feed_store_created.name'),
                'order_created' => Lang::get('feed_order_created.name'),
                'goods_collected' => Lang::get('feed_goods_collected.name'),
                'store_collected' => Lang::get('feed_store_collected.name'),
                'goods_evaluated' => Lang::get('feed_goods_evaluated.name'),
                'groupbuy_joined' => Lang::get('feed_groupbuy_joined.name')
            );
            $seller_feed_items = array(
                'goods_created' => Lang::get('feed_goods_created.name'),
                'groupbuy_created' => Lang::get('feed_groupbuy_created.name'),
            );
            $feed_items = $buyer_feed_items;
            if ($this->visitor->get('manage_store'))
            {
                $feed_items = array_merge($feed_items, $seller_feed_items);
            }
            $this->assign('feed_items', $feed_items);
            $this->assign('feed_config', $feed_config);
            $this->display('member.feed_settings.html');
        }
        else
        {
            $feed_settings = serialize($_POST['feed_config']);
            $m_member = &m('member');
            $m_member->edit($this->visitor->get('user_id'), array(
                'feed_config' => $feed_settings,
            ));
            $this->show_message('feed_settings_successfully');
        }
    }

     /**
     *    三级菜单
     *
     *    @author    Hyber
     *    @return    void
     */
    function _get_member_submenu()
    {
        $submenus =  array(
            array(
                'name'  => 'basic_information',
                'url'   => 'index.php?app=member&amp;act=profile',
            ),
            array(
                'name'  => 'edit_password',
                'url'   => 'index.php?app=member&amp;act=password',
            ),
            array(
                'name'  => 'edit_email',
                'url'   => 'index.php?app=member&amp;act=email',
            ),
        );
        if ($this->_feed_enabled)
        {
            $submenus[] = array(
                'name'  => 'feed_settings',
                'url'   => 'index.php?app=member&amp;act=feed_settings',
            );
        }

        return $submenus;
    }

    /**
     * 上传头像
     *
     * @param int $user_id
     * @return mix false表示上传失败,空串表示没有上传,string表示上传文件地址
     */
    function _upload_portrait($user_id)
    {
        $file = $_FILES['portrait'];
        if ($file['error'] != UPLOAD_ERR_OK)
        {
            return '';
        }
        import('uploader.lib');
        $uploader = new Uploader();
        $uploader->allowed_type(IMAGE_FILE_TYPE);
        $uploader->addFile($file);
        if ($uploader->file_info() === false)
        {
            $this->show_warning($uploader->get_error(), 'go_back', 'index.php?app=member&amp;act=profile');
            return false;
        }
        $uploader->root_dir(ROOT_PATH);
        return $uploader->save('data/files/mall/portrait/' . ceil($user_id / 500), $user_id);
    }

    function getAlibabaAuthUrl($state) {
        $appKey = '1024213';
        $appSecret = 'pF0aLFfUr7w8';
        $redirectUrl = urlencode(SITE_URL.'/index.php?app=member&act=alibabaAuthBack');
        $stateEncoded = urlencode($state);

        $code_arr = array(
            'client_id' => $appKey,
            'site' => 'china',
            'redirect_uri' => SITE_URL.'/index.php?app=member&act=alibabaAuthBack',
            'state' => $state);
        ksort($code_arr);
        foreach ($code_arr as $key=>$val)
                $sign_str .= $key . $val;
        $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));

        $get_code_url = "http://gw.open.1688.com/auth/authorize.htm?client_id={$appKey}&site=china&state={$stateEncoded}&redirect_uri={$redirectUrl}&_aop_signature={$code_sign}";

        return $get_code_url;
    }

    function loginWithAlibaba() {
        header('location: '.$this->getAlibabaAuthUrl('alibabalogin::'));
    }

    function alibabaAuthBack() {
        $nick = $this->getAlibabaNick($_REQUEST['code']);
        $this->authBack($nick, '51a_');
    }

    function getAlibabaNick($code) {
        $url = 'https://gw.open.1688.com/openapi/http/1/system.oauth2/getToken/1024213?grant_type=authorization_code&need_refresh_token=true&client_id=1024213&client_secret=pF0aLFfUr7w8&redirect_uri='.urlencode(SITE_URL.'/index.php?app=member&act=alibabaAuthBack').'&code='.$code;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        $dataObject = json_decode($data);
        $nick = $dataObject->memberId;
        return urldecode($nick);
    }

    function loginWithTaobao() {
        /* oauth with taobao and call back then register or login */
        /* header('location: https://oauth.taobao.com/authorize?response_type=code&client_id=21610927&redirect_uri=http://'.urlencode('yjsc.51zwd.com/index.php').'&state=51zwd'.urlencode('*taobaologin::'.SITE_URL).'&view=web'); */
        header('location: https://oauth.taobao.com/authorize?response_type=code&client_id=21336502&redirect_uri=http://'.urlencode('112.124.54.204/taobao').'&state=51zwd'.urlencode('*taobaologin::'.SITE_URL).'&view=web');
    }

    function taobaoAuthBack() {
        if ($_SESSION['code'] === $_REQUEST['code'] && isset($_SESSION['taobao_nick'])) {
            $this->authBack($_SESSION['taobao_nick'], '51t_');
        } else {
            $_SESSION['code'] = $_REQUEST['code'];
            $auth_info = $this->getAuthInfo($_REQUEST['code']);
            if ($auth_info) {
                $nick = urldecode($auth_info->taobao_user_nick);
                $_SESSION['taobao_nick'] = $nick;
                $this->authBack($nick, '51t_', $auth_info);
            }
        }
    }

    function _login($user_id, $auth_info) {
        $ms =& ms();
        $this->_do_login($user_id);
        $synlogin = $ms->user->synlogin($user_id);
        if ($auth_info) {
            $this->saveAuthInfo($user_id, 0, $auth_info);
        }
        $this->show_message(
            Lang::get('login_successed').$synlogin,
            'back_before_login', '',
            'enter_member_center', 'index.php?app=member');
        exit;
    }

    function loginWithStoreIfPossible($nick, $auth_info) {
        $store_model =& m('store');
        $store = $store_model->get(array(
            'conditions' => "im_ww='".$nick."' and state = 1"));
        if ($store) {
            $user_id = $store['store_id'];
            $this->_login($user_id, $auth_info);
        }
    }

    function loginWithTaobaoIfPossible($nick, $username, $password, $auth_info) {
        $ms =& ms();
        $user_id = $ms->user->auth($username, $password);
        if ($user_id) {
            $this->_login($user_id, $auth_info);
        }
    }

    function registerWithTaobaoIfPossible($nick, $username, $password, $auth_info) {
        $registerUrl = SITE_URL.'/index.php?app=member&act=register';
        $params = array(
            'agree' => 'true',
            'password' => $password,
            'password_confirm' => $password,
            'user_name' => $username,
            'email' => time().'@qq.com',
            'batchcreate' => '1',
                        );
        $user_id = $this->makePostRequest($registerUrl, $params);
        if (is_numeric($user_id)) {
            $this->_hook('after_register', array('user_id' => $user_id));
            $this->_login($user_id, $auth_info);
        } else {
            Log::write('taobao register failed! nick:'.$nick.' username:'.$username.' userId:'.$user_id);
            $this->show_warning('taobao register failed! nick:'.$nick.' username:'.$username.' userId:'.$user_id);
        }
    }

    function authBack($nick, $fixed_prefix, $auth_info = null) {
        if ($nick) {
            $username = $fixed_prefix.$nick;
            $fixed_password = FIXED_PASSWORD;
            $this->loginWithStoreIfPossible($nick, $auth_info);
            $this->loginWithTaobaoIfPossible($nick, $username, $fixed_password, $auth_info);
            $this->registerWithTaobaoIfPossible($nick, $username, $fixed_password, $auth_info);
        }
        $this->show_warning('taobao login failed! nick:'.$nick);
    }

    function getAuthInfo($code) {
        $url = 'https://oauth.taobao.com/token';
        $appKey = '21336502';
        $secretKey = '608b67adaba42db656de726b7cf2549f';
        $params = array(
            'client_id' => $appKey,
            'client_secret' => $secretKey,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'http://112.124.54.204/taobao',
        );
        $data = $this->makePostRequest($url, $params);
        $dataObject = json_decode($data);
        if (isset($dataObject->access_token)) {
            $_SESSION['taobao_access_token'] = $dataObject->access_token;
            $_SESSION['taobao_app_key'] = $appKey;
            $_SESSION['taobao_secret_key'] = $secretKey;
            return $dataObject;
        } else {
            Log::write('[getAuthInfo error] ip:'.real_ip().' '.$data);
            $this->show_warning('淘宝登录失败: '.$data);
            return false;
        }
    }

    function getTaobaoNick($code) {
        $url = 'https://oauth.taobao.com/token';
        /* $appKey = '21610927'; */
        $appKey = '21336502';
        /* $secretKey = 'e8c369cf5a2a20cb4c29e44726c863cd'; */
        $secretKey = '608b67adaba42db656de726b7cf2549f';
        $params = array(
            'client_id' => $appKey,
            'client_secret' => $secretKey,
            'grant_type' => 'authorization_code',
            'code' => $code,
            /* 'redirect_uri' => 'http://yjsc.51zwd.com', */
            'redirect_uri' => 'http://112.124.54.204/taobao',
        );
        $dataObject = json_decode($this->makePostRequest($url, $params));
        $_SESSION['taobao_access_token'] = $dataObject->access_token;
        $_SESSION['taobao_app_key'] = $appKey;
        $_SESSION['taobao_secret_key'] = $secretKey;
        $nick = urldecode($dataObject->taobao_user_nick);
        $_SESSION['taobao_nick'] = $nick;
        return $nick;
    }

    function saveAuthInfo($userId, $vendor, $auth_info) {
        $member_auth_mode =& m('memberauth');
        $member_auth_mode->add(array(
            'user_id' => $userId,
            'vendor' => $vendor,
            'access_token' => $auth_info->access_token,
            'expires_in' => $auth_info->expires_in,
            'refresh_token' => $auth_info->refresh_token,
            're_expires_in' => $auth_info->re_expires_in,
            'r1_expires_in' => $auth_info->r1_expires_in,
            'r2_expires_in' => $auth_info->r2_expires_in,
            'w1_expires_in' => $auth_info->w1_expires_in,
            'w2_expires_in' => $auth_info->w2_expires_in,
            'vendor_user_nick' => urldecode($auth_info->taobao_user_nick),
            'vendor_user_id' => $auth_info->taobao_user_id,
            'sub_vendor_user_id' => $auth_info->sub_taobao_user_id,
            'sub_vendor_user_nick' => urldecode($auth_info->sub_taobao_user_nick)), true);
    }

    function makePostRequest($url, $params) {
        foreach($params as $key=>$value) { $paramsString .= $key.'='.$value.'&'; }
        rtrim($paramsString, '&');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }


}

?>
