<?php

/**
 * api控制器基类
 */
class ApiApp extends ECBaseApp
{
    function _init_visitor()
    {
        $this->visitor =& env('visitor', new ApiVisitor());
    }

    /**
     * 执行登陆操作
     * 这个函数要跟 frontend.base.php 中的 _do_login 保持一致
     */
    function _do_login($user_id)
    {
        $mod_user =& m('member');

        $user_info = $mod_user->get(array(
            'conditions'    => "user_id = '{$user_id}'",
            'join'          => 'has_store',
            'fields'        => 'user_id, user_name, reg_time, last_login, last_ip, store_id, behalf_goods_taker as taker_id',
        ));
        
        /*代发信息*/
        $behalf_info = $mod_user->get(array(
        		'conditions'    => "user_id = '{$user_id}'",
        		'join'          => 'has_behalf',                 //关联查找看看是否有代发
        		'fields'        => 'user_id, bh_id,bh_allowed',
        ));
        
        if(!empty($behalf_info) && !empty($user_info))
        {
        	$user_info['has_behalf'] = $behalf_info['bh_id'];
        	$user_info['pass_behalf'] = $behalf_info['bh_allowed'];
        }
        

        /* 店铺ID */
        $my_store = empty($user_info['store_id']) ? 0 : $user_info['store_id'];

        /* 保证基础数据整洁 */
        unset($user_info['store_id']);

        /* 分派身份 */
        $this->visitor->assign($user_info);

        /* 更新用户登录信息 */
        $mod_user->edit("user_id = '{$user_id}'", "last_login = '" . gmtime()  . "', last_ip = '" . real_ip() . "', logins = logins + 1");

        /* 更新购物车中的数据 */
        $mod_cart =& m('cart');
        $mod_cart->edit("(user_id = '{$user_id}' OR session_id = '" . SESS_ID . "') AND store_id <> '{$my_store}'", array(
            'user_id'    => $user_id,
            'session_id' => SESS_ID,
        ));
    }

    /**
     * 执行退出操作
     */
    function _do_logout()
    {
        $this->visitor->logout();
    }
}

/**
 *    api访问者
 */
class ApiVisitor extends BaseVisitor
{
    var $_info_key = 'user_info';
}

?>