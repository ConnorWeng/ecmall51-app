<?php

return array(
    'dashboard' => array(
        'text'      => Lang::get('dashboard'),
        'subtext'   => Lang::get('offen_used'),
        'default'   => 'welcome',
        'children'  => array(
            'welcome'   => array(
                'text'  => Lang::get('welcome_page'),
                'url'   => 'index.php?act=welcome',
            ),
            'aboutus'   => array(
                'text'  => Lang::get('aboutus_page'),
                'url'   => 'index.php?act=aboutus',
            ),
            'base_setting'  => array(
                'parent'=> 'setting',
                'text'  => Lang::get('base_setting'),
                'url'   => 'index.php?app=setting&act=base_setting',
            ),
            'user_manage' => array(
                'text'  => Lang::get('user_manage'),
                'parent'=> 'user',
                'url'   => 'index.php?app=user',
            ),
            'store_manage'  => array(
                'text'  => Lang::get('store_manage'),
                'parent'=> 'store',
                'url'   => 'index.php?app=store',
            ),
            'market_manage'  => array(
                'text'  => Lang::get('market_manage'),
                'parent'=> 'store',
                'url'   => 'index.php?app=market',
            ),
            'behalf_manage' => array(
                    'text'  => Lang::get('behalf_manage'),
                    'parent'=> 'behalf',
                    'url'   => 'index.php?app=behalf',
            ),
            'goods_manage'  => array(
                'text'  => Lang::get('goods_manage'),
                'parent'=> 'goods',
                'url'   => 'index.php?app=goods',
            ),
            'order_manage' => array(
                'text'  => Lang::get('order_manage'),
                'parent'=> 'trade',
                'url'   => 'index.php?app=order'
            ),
            'makehtml_homepage' => array(
                 'text' => Lang::get('makehtml_homepage'),
                 'parent' => '',
                 'url' => SITE_URL.'/index.php?act=makehtml_homepage',
            ),
            'sphinx_info' => array(
                 'text' =>'sphinx信息',
                 'parent' => '',
                 'url' => SITE_URL.'/admin518/index.php?module=my_check&act=index',
            ),
        ),
    ),
    // 设置
    'setting'   => array(
        'text'      => Lang::get('setting'),
        'default'   => 'base_setting',
        'children'  => array(
            'base_setting'  => array(
                'text'  => Lang::get('base_setting'),
                'url'   => 'index.php?app=setting&act=base_setting',
            ),
            'region' => array(
                'text'  => Lang::get('region'),
                'url'   => 'index.php?app=region',
            ),
            'payment' => array(
                'text'  => Lang::get('payment'),
                'url'   => 'index.php?app=payment',
            ),
            'theme' => array(
                'text'  => Lang::get('theme'),
                'url'   => 'index.php?app=theme',
            ),
            'template' => array(
                'text'  => Lang::get('template'),
                'url'   => 'index.php?app=template',
            ),
            'mailtemplate' => array(
                'text'  => Lang::get('noticetemplate'),
                'url'   => 'index.php?app=mailtemplate',
            ),
        ),
    ),
    // 商品
    'goods' => array(
        'text'      => Lang::get('goods'),
        'default'   => 'goods_manage',
        'children'  => array(
            'gcategory' => array(
                'text'  => Lang::get('gcategory'),
                'url'   => 'index.php?app=gcategory',
            ),
            'brand' => array(
                'text'  => Lang::get('brand'),
                'url'   => 'index.php?app=brand',
            ),
            'goods_manage' => array(
                'text'  => Lang::get('goods_manage'),
                'url'   => 'index.php?app=goods',
            ),
            'recommend_type' => array(
                'text'  => LANG::get('recommend_type'),
                'url'   => 'index.php?app=recommend'
            ),

        ),
    ),
    // 店铺
    'store'     => array(
        'text'      => Lang::get('store'),
        'default'   => 'store_manage',
        'children'  => array(
            'sgrade' => array(
                'text'  => Lang::get('sgrade'),
                'url'   => 'index.php?app=sgrade',
            ),
            'scategory' => array(
                'text'  => Lang::get('scategory'),
                'url'   => 'index.php?app=scategory',
            ),
            'market' => array(
                'text'  => Lang::get('market'),
                'url'   => 'index.php?app=market',
            ),
            //by cengnlaeng
            'ultimate_store'     =>array(
                'text'  => Lang::get('ultimate_store'),
                'url'   => 'index.php?app=ultimate_store',
            ),
            //end
            'store_manage'  => array(
                'text'  => Lang::get('store_manage'),
                'url'   => 'index.php?app=store',
            ),
            'store_manage_closed'  => array(
                        'text'  => Lang::get('store_manage_closed'),
                        'url'   => 'index.php?app=store&act=index_closed',
            ),
            'store_behalf_area'  => array(
                        'text'  => Lang::get('store_behalf_area'),
                        'url'   => 'index.php?app=store&act=behalf_area',
            ),
            'store_reality_zone'  => array(
                        'text'  => Lang::get('store_reality_zone'),
                        'url'   => 'index.php?app=store&act=reality_zone',
            ),
            'store_brand_area'  => array(
                        'text'  => Lang::get('store_brand_area'),
                        'url'   => 'index.php?app=store&act=brand_area',
            ),
            'store_exposed'=>array(
                'text'=>Lang::get('store_exposed'),
                'url'=>'index.php?app=store&act=expose_store'
            ),
            'store_restore' => array(
                'text' => Lang::get('store_restore'),
                'url' => 'index.php?app=store&act=restore_store'),
        ),
    ),
    //代发
    'behalf' => array(
            'text'      => Lang::get('behalf'),
            'default'   => 'behalf_manage',
            'children'  => array(
                    'behalf_manage'  => array(
                            'text'  => Lang::get('behalf_manage'),
                            'url'   => 'index.php?app=behalf',
                    ),
                    'delivery_manage'  => array(
                            'text'  => Lang::get('delivery_manage'),
                            'url'   => 'index.php?app=delivery',
                    ),
                    'behalf_discount'=>array(
                        'text' => Lang::get('behalf_discount'),
                        'url' => 'index.php?app=behalf&act=behalf_discount',
                    )
            ),
    ),
    // 会员
    'user' => array(
        'text'      => Lang::get('user'),
        'default'   => 'user_manage',
        'children'  => array(
            'user_manage' => array(
                'text'  => Lang::get('user_manage'),
                'url'   => 'index.php?app=user',
            ),
            'admin_manage' => array(
                'text' => Lang::get('admin_manage'),
                 'url'   => 'index.php?app=admin',
             ),
             'user_notice' => array(
                'text' => Lang::get('user_notice'),
                'url'  => 'index.php?app=notice',
             ),
        ),
    ),
    // 交易
    'trade' => array(
        'text'      => Lang::get('trade'),
        'default'   => 'order_manage',
        'children'  => array(
            'order_manage' => array(
                'text'  => Lang::get('order_manage'),
                'url'   => 'index.php?app=order'
            ),
        ),
    ),
    // 网站
    'website' => array(
        'text'      => Lang::get('website'),
        'default'   => 'acategory',
        'children'  => array(
            'acategory' => array(
                'text'  => Lang::get('acategory'),
                'url'   => 'index.php?app=acategory',
            ),
            'article' => array(
                'text'  => Lang::get('article'),
                'url'   => 'index.php?app=article',
            ),
            'partner' => array(
                'text'  => Lang::get('partner'),
                'url'   => 'index.php?app=partner',
            ),
            'navigation' => array(
                'text'  => Lang::get('navigation'),
                'url'   => 'index.php?app=navigation',
            ),
            'db' => array(
                'text'  => Lang::get('db'),
                'url'   => 'index.php?app=db&amp;act=backup',
            ),
            'groupbuy' => array(
                'text' => Lang::get('groupbuy'),
                'url'  => 'index.php?app=groupbuy',
            ),
            'consulting' => array(
                'text'  =>  LANG::get('consulting'),
                'url'   => 'index.php?app=consulting',
            ),
            'share_link' => array(
                'text'  =>  LANG::get('share_link'),
                'url'   => 'index.php?app=share',
            ),
        ),
    ),
    // 扩展
    'extend' => array(
        'text'      => Lang::get('extend'),
        'default'   => 'plugin',
        'children'  => array(
            'plugin' => array(
                'text'  => Lang::get('plugin'),
                'url'   => 'index.php?app=plugin',
            ),
            'module' => array(
                'text'  => Lang::get('module'),
                'url'   => 'index.php?app=module&act=manage',
            ),
            'widget' => array(
                'text'  => Lang::get('widget'),
                'url'   => 'index.php?app=widget',
            ),
        ),
    ),
    // 洞悉
    'analyzer' => array(
        'text'      => Lang::get('analyzer'),
        'default'   => 'yjsc_analyzer',
        'children'  => array(
            'yjsc_analyzer' => array(
                'text'  => Lang::get('yjsc_analyzer'),
                'url'   => 'index.php?app=analyzer&act=yjsc_analyzer',
            ),
        ),
    ),
);

?>
