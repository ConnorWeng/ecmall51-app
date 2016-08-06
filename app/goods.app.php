<?php

/* 商品 */
class GoodsApp extends StorebaseApp
{
    var $_goods_mod;
    function __construct()
    {
        $this->GoodsApp();
    }
    function GoodsApp()
    {
        parent::__construct();
        $this->_goods_mod =& m('goods');
    }

    function index()
    {
        /* 参数 id */
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }
        /* 可缓存数据 */
        $data = $this->_get_common_info($id);
        
        if ($data === false)
        {
            return;
        }
        else
        {
            $this->_assign_common_info($data);
        }

        /* 淘宝属性 */
        $goodsAttrModel = &m('goodsattr');
        $attrs = $goodsAttrModel->find(array(
            'conditions' => "goods_id = '$id'"
        ));
        $this->assign('attrs', $attrs);

        //商家编码 tiq
        if(is_array($attrs))
        {
        	foreach ($attrs as $value)
        	{
        		if($value['attr_id'] == 1)
        		{
        			$this->assign("goods_seller_bm",$value['attr_value']);
        		}
        	}
        }
        /* 更新浏览次数 */
        $this->_update_views($id);

        //是否开启验证码
        if (Conf::get('captcha_status.goodsqa'))
        {
            $this->assign('captcha', 1);
        }
        
        /*左侧推荐宝贝*/
        if(defined('GOODS_DETAIL_RECOMMENDED_TYPE') && GOODS_DETAIL_RECOMMENDED_TYPE)
        {
        	$recom_mod =& m('recommend');
        	$rgoods_list= $recom_mod->get_my_recommended_goods(GOODS_DETAIL_RECOMMENDED_TYPE);
        	$this->assign('rgoods_list',$rgoods_list);
        } 
		
		// psmb 
		/* $region_mod = &m('region');
		$area = $region_mod->get_province_city();
		$this->assign('area', $area); */

        $this->assign('guest_comment_enable', Conf::get('guest_comment'));
        $this->display('goods.index.html');
    }

    /* 商品评论 */
    function comments()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }

        $data = $this->_get_common_info($id);
        if ($data === false)
        {
            return;
        }
        else
        {
            $this->_assign_common_info($data);
        }
        
        /*左侧推荐宝贝*/
        if(defined('GOODS_DETAIL_RECOMMENDED_TYPE') && GOODS_DETAIL_RECOMMENDED_TYPE)
        {
        	$recom_mod =& m('recommend');
        	$rgoods_list= $recom_mod->get_my_recommended_goods(10);
        	$this->assign('rgoods_list',$rgoods_list);
        }

        /* 赋值商品评论 */
        $data = $this->_get_goods_comment($id, 10);
        $this->_assign_goods_comment($data);

        $this->display('goods.comments.html');
    }

    /* 销售记录 */
    function saleslog()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }

        $data = $this->_get_common_info($id);
        if ($data === false)
        {
            return;
        }
        else
        {
            $this->_assign_common_info($data);
        }
        
        /*左侧推荐宝贝*/
        if(defined('GOODS_DETAIL_RECOMMENDED_TYPE') && GOODS_DETAIL_RECOMMENDED_TYPE)
        {
        	$recom_mod =& m('recommend');
        	$rgoods_list= $recom_mod->get_my_recommended_goods(10);
        	$this->assign('rgoods_list',$rgoods_list);
        }

        /* 赋值销售记录 */
        $data = $this->_get_sales_log($id, 10);
        $this->_assign_sales_log($data);

        $this->display('goods.saleslog.html');
    }
    function qa()
    {
        $goods_qa =& m('goodsqa');
         $id = intval($_GET['id']);
         if (!$id)
         {
            $this->show_warning('Hacking Attempt');
            return;
         }
        if(!IS_POST)
        {
            $data = $this->_get_common_info($id);
            if ($data === false)
            {
                return;
            }
            else
            {
                $this->_assign_common_info($data);
            }
            $data = $this->_get_goods_qa($id, 10);
            $this->_assign_goods_qa($data);
            
            /*左侧推荐宝贝*/
            if(defined('GOODS_DETAIL_RECOMMENDED_TYPE') && GOODS_DETAIL_RECOMMENDED_TYPE)
            {
            	$recom_mod =& m('recommend');
            	$rgoods_list= $recom_mod->get_recommended_goods(intval(GOODS_DETAIL_RECOMMENDED_TYPE),10, true);
            	$this->assign('rgoods_list',$rgoods_list);
            }

            //是否开启验证码
            if (Conf::get('captcha_status.goodsqa'))
            {
                $this->assign('captcha', 1);
            }
            $this->assign('guest_comment_enable', Conf::get('guest_comment'));
            /*赋值产品咨询*/
            $this->display('goods.qa.html');
        }
        else
        {
            /* 不允许游客评论 */
            if (!Conf::get('guest_comment') && !$this->visitor->has_login)
            {
                $this->show_warning('guest_comment_disabled');

                return;
            }
            $content = (isset($_POST['content'])) ? trim($_POST['content']) : '';
            //$type = (isset($_POST['type'])) ? trim($_POST['type']) : '';
            $email = (isset($_POST['email'])) ? trim($_POST['email']) : '';
            $hide_name = (isset($_POST['hide_name'])) ? trim($_POST['hide_name']) : '';
            if (empty($content))
            {
                $this->show_warning('content_not_null');
                return;
            }
            //对验证码和邮件进行判断

            if (Conf::get('captcha_status.goodsqa'))
            {
                if (base64_decode($_SESSION['captcha']) != strtolower($_POST['captcha']))
                {
                    $this->show_warning('captcha_failed');
                    return;
                }
            }
            if (!empty($email) && !is_email($email))
            {
                $this->show_warning('email_not_correct');
                return;
            }
            $user_id = empty($hide_name) ? $_SESSION['user_info']['user_id'] : 0;
            $conditions = 'g.goods_id ='.$id;
            $goods_mod = & m('goods');
            $ids = $goods_mod->get(array(
                'fields' => 'store_id,goods_name',
                'conditions' => $conditions
            ));
            extract($ids);
            $data = array(
                'question_content' => $content,
                'type' => 'goods',
                'item_id' => $id,
                'item_name' => addslashes($goods_name),
                'store_id' => $store_id,
                'email' => $email,
                'user_id' => $user_id,
                'time_post' => gmtime(),
            );
            if ($goods_qa->add($data))
            {
                header("Location: index.php?app=goods&act=qa&id={$id}#module\n");
                exit;
            }
            else
            {
                $this->show_warning('post_fail');
                exit;
            }
        }
    }

    /**
     * 取得公共信息
     *
     * @param   int     $id
     * @return  false   失败
     *          array   成功
     */
    function _get_common_info($id)
    {
        $cache_server =& cache_server();
        $key = 'page_of_goods_' . $id;
        $data = $cache_server->get($key);
        $cached = true;
        if ($data === false)
        {
            $cached = false;
            $data = array('id' => $id);

            /* 商品信息 */
            $goods = $this->_goods_mod->get_info($id); 
            if (!$goods || $goods['if_show'] == 0 || $goods['closed'] == 1 || $goods['state'] != 1)
            {
                $this->show_warning('goods_not_exist');
                return false;
            }
            
            //
            $goods['rates'] = get_goods_rates_in_common($id);
            
            /* 商品图片懒加载 tiq */
            if( $goods['description'])
            {
            	$goods_description = $goods['description'];
            	$goods['description'] = preg_replace('/(<img\s+.*\b)src(\s*=.*>)/iU',"\${1}src='data/system/empty.gif' data-ks-lazyload\${2}",$goods_description);
            }
            $goods['tags'] = $goods['tags'] ? explode(',', trim($goods['tags'], ',')) : array();   
            /* sku tiq*/
            if($goods['_specs'])
            {
            	foreach ($goods['_specs'] as $spec)
            	{
            		$goods['sku'] = $spec['sku'];
            	}
            	//goods_name preg_match
            	/* $goods_AttrModel = &m('goodsattr');
            	$attrs = $goods_AttrModel->find(array(
            			'conditions' => "goods_id = ".$goods['goods_id']." AND attr_id = 13021751",
            	)); */
            	$goods['sku'] = getHuoHao($goods['goods_name']);
            	if(!$goods['sku'])
            	{
            		$goods_AttrModel = &m('goodsattr');
            		$attrs = $goods_AttrModel->get(array(
            				'conditions' => "goods_id = ".$goods['goods_id']." AND attr_id = 13021751",
            		));
            		$goods['sku'] = $attrs['attr_value'];
            	}
            	//计算价格区间
            	$min_price = $max_price = $goods['price'];
            	
        	    foreach ($goods['_specs'] as $_spec)
        	    {
        	        $_spec['price'] > $max_price && $max_price = $_spec['price'];
        	        $_spec['price'] < $min_price && $min_price = $_spec['price'];
        	    }
            	
            	$goods['min_price'] = $min_price;
            	$goods['max_price'] = $max_price;
            }   
            $goods['views'] = intval($goods['views']) * 50 + 100 + rand(1,9);
            

            $data['goods'] = $goods;

            /* 店铺信息 */
            if (!$goods['store_id'])
            {
                $this->show_warning('store of goods is empty');
                return false;
            }
            $this->set_store($goods['store_id']);
            $data['store_data'] = $this->get_store_data();
            /*根据现价和see_price 得到淘宝价*/
            if($data['goods']['price'])
            {
            	$data['goods']['tb_price'] = make_price($data['goods']['price'], $data['store_data']['see_price']);
            }

            /* 当前位置 */
            $data['cur_local'] = $this->_get_curlocal($goods['cate_id']);
            $data['goods']['related_info'] = $this->_get_related_objects($data['goods']['tags']);
            /* 分享链接 */
            $data['share'] = $this->_get_share($goods);
            

            $cache_server->set($key, $data, 30);
        }
        if ($cached)
        {
            $this->set_store($data['goods']['store_id']);
        }

        return $data;
    }

    function _get_related_objects($tags)
    {
        if (empty($tags))
        {
            return array();
        }
        $tag = $tags[array_rand($tags)];
        $ms =& ms();

        return $ms->tag_get($tag);
    }

    /* 赋值公共信息 */
    function _assign_common_info($data)
    {
        /* 商品信息 */
        $goods = $data['goods'];
        $this->assign('goods', $goods);
        $this->assign('sales_info', sprintf(LANG::get('sales'), $goods['sales'] ? $goods['sales'] : 0));
        $this->assign('comments', sprintf(LANG::get('comments'), $goods['comments'] ? $goods['comments'] : 0));

        //微信二维码
        if($data['store_data']['im_wx'])
        {
        	if(file_exists(ROOT_PATH.'/data/qrcode/zwd51_s'.$data['store_data']['store_id'].'.png'))
        	{
        		$data['store_data']['im_wx'] = SITE_URL.'/data/qrcode/zwd51_s'.$data['store_data']['store_id'].'.png';
        	}
        	else
        	{
        		$success = generateQRfromQRCode($data['store_data']['im_wx'], 's'.$data['store_data']['store_id']);
        		$success && $data['store_data']['im_wx'] = SITE_URL.'/data/qrcode/zwd51_s'.$data['store_data']['store_id'].'.png';
        	}
        }
        //主营
        if(!$data['store_data']['business_scope'])
        {
        	$mod_store =& m('store');
        	$business_scope = $mod_store->getRelatedData('has_scategory',$data['store_data']['store_id']);
        	if($business_scope)
        	{
        		$business_scope = array_values($business_scope);
        		$data['store_data']['business_scope'] = $business_scope[0]['cate_name'];
        	}
        }
        
        /* 店铺信息 */
        $this->assign('store', $data['store_data']);

        /* 浏览历史 */
        $this->assign('goods_history', $this->_get_goods_history($data['id']));

        /* 默认图片 */
        $this->assign('default_image', Conf::get('default_goods_image'));

        /* 当前位置 */
        $this->_curlocal($data['cur_local']);

        /* 配置seo信息 */
        $this->_config_seo($this->_get_seo_info($data['goods']));

        /* 商品分享 */
        $this->assign('share', $data['share']);
        
        /*收藏信息*/
        $this->assign('favorites',$this->_get_favorite($goods));
        /* $this->import_resource(array(
            'script' => 'jquery.jqzoom.js',
            'style' => 'res:jqzoom.css'
        )); */
    }

    /* 取得浏览历史 */
    function _get_goods_history($id, $num = 9)
    {
        $goods_list = array();
        $goods_ids  = ecm_getcookie('goodsBrowseHistory');
        $goods_ids  = $goods_ids ? explode(',', $goods_ids) : array();
        if ($goods_ids)
        {
            $rows = $this->_goods_mod->find(array(
                'conditions' => $goods_ids,
                'fields'     => 'goods_name,default_image',
            ));
            foreach ($goods_ids as $goods_id)
            {
                if (isset($rows[$goods_id]))
                {
                    empty($rows[$goods_id]['default_image']) && $rows[$goods_id]['default_image'] = Conf::get('default_goods_image');
                    $goods_list[] = $rows[$goods_id];
                }
            }
        }
        $goods_ids[] = $id;
        if (count($goods_ids) > $num)
        {
            unset($goods_ids[0]);
        }
        ecm_setcookie('goodsBrowseHistory', join(',', array_unique($goods_ids)));

        return $goods_list;
    }

    /* 取得销售记录 */
    function _get_sales_log($goods_id, $num_per_page)
    {
        $data = array();

        $page = $this->_get_page($num_per_page);
        $order_goods_mod =& m('ordergoods');
        $sales_list = $order_goods_mod->find(array(
            'conditions' => "goods_id = '$goods_id' AND order_alias.status = '" . ORDER_FINISHED . "'",
            'join'  => 'belongs_to_order',
            'fields'=> 'buyer_id, buyer_name, add_time, anonymous, goods_id, specification, price, quantity, evaluation',
            'count' => true,
            'order' => 'add_time desc',
            'limit' => $page['limit'],
        ));
        $data['sales_list'] = $sales_list;

        $page['item_count'] = $order_goods_mod->getCount();
        $this->_format_page($page);
        $data['page_info'] = $page;
        $data['more_sales'] = $page['item_count'] > $num_per_page;

        return $data;
    }

    /* 赋值销售记录 */
    function _assign_sales_log($data)
    {
        $this->assign('sales_list', $data['sales_list']);
        $this->assign('page_info',  $data['page_info']);
        $this->assign('more_sales', $data['more_sales']);
    }

    /* 取得商品评论 */
    function _get_goods_comment($goods_id, $num_per_page)
    {
        $data = array();

        $page = $this->_get_page($num_per_page);
        $order_goods_mod =& m('ordergoods');
        $comments = $order_goods_mod->find(array(
            'conditions' => "goods_id = '$goods_id' AND evaluation_status = '1'",
            'join'  => 'belongs_to_order',
            'fields'=> 'buyer_id, buyer_name, anonymous, evaluation_time, comment, evaluation',
            'count' => true,
            'order' => 'evaluation_time desc',
            'limit' => $page['limit'],
        ));
        $data['comments'] = $comments;

        $page['item_count'] = $order_goods_mod->getCount();
        $this->_format_page($page);
        $data['page_info'] = $page;
        $data['more_comments'] = $page['item_count'] > $num_per_page;

        return $data;
    }

    /* 赋值商品评论 */
    function _assign_goods_comment($data)
    {
        $this->assign('goods_comments', $data['comments']);
        $this->assign('page_info',      $data['page_info']);
        $this->assign('more_comments',  $data['more_comments']);
    }

    /* 取得商品咨询 */
    function _get_goods_qa($goods_id,$num_per_page)
    {
        $page = $this->_get_page($num_per_page);
        $goods_qa = & m('goodsqa');
        $qa_info = $goods_qa->find(array(
            'join' => 'belongs_to_user',
            'fields' => 'member.user_name,question_content,reply_content,time_post,time_reply',
            'conditions' => '1 = 1 AND item_id = '.$goods_id . " AND type = 'goods'",
            'limit' => $page['limit'],
            'order' =>'time_post desc',
            'count' => true
        ));
        $page['item_count'] = $goods_qa->getCount();
        $this->_format_page($page);

        //如果登陆，则查出email
        if (!empty($_SESSION['user_info']))
        {
            $user_mod = & m('member');
            $user_info = $user_mod->get(array(
                'fields' => 'email',
                'conditions' => '1=1 AND user_id = '.$_SESSION['user_info']['user_id']
            ));
            extract($user_info);
        }

        return array(
            'email' => $email,
            'page_info' => $page,
            'qa_info' => $qa_info,
        );
    }

    /* 赋值商品咨询 */
    function _assign_goods_qa($data)
    {
        $this->assign('email',      $data['email']);
        $this->assign('page_info',  $data['page_info']);
        $this->assign('qa_info',    $data['qa_info']);
    }

    /* 更新浏览次数 */
    function _update_views($id)
    {
        $goodsstat_mod =& m('goodsstatistics');
        $affect_rows_num = $goodsstat_mod->edit($id, "views = views + 1");
        if(empty($affect_rows_num))
        {
        	$goods = $goodsstat_mod->get($id);
        	if(empty($goods))
        	{
        		$goodsstat_mod->add(array('goods_id'=>$id,'views'=>1));
        	}
        }
        if($goodsstat_mod->has_error())
        {
        	$this->show_warning($goodsstat_mod->get_error());
        }
       
    }

    /**
     * 取得当前位置
     *
     * @param int $cate_id 分类id
     */
    function _get_curlocal($cate_id)
    {
        $parents = array();
        if ($cate_id)
        {
            $gcategory_mod =& bm('gcategory');
            $parents = $gcategory_mod->get_ancestor($cate_id, true);
        }

        $curlocal = array(
            array('text' => LANG::get('all_categories'), 'url' => url('app=category')),
        );
        foreach ($parents as $category)
        {
            $curlocal[] = array('text' => $category['cate_name'], 'url' => url('app=search&cate_id=' . $category['cate_id']));
        }
        $curlocal[] = array('text' => LANG::get('goods_detail'));

        return $curlocal;
    }

    function _get_share($goods)
    {
        $m_share = &af('share');
        $shares = $m_share->getAll();
        $shares = array_msort($shares, array('sort_order' => SORT_ASC));
        $goods_name = ecm_iconv(CHARSET, 'utf-8', $goods['goods_name']);
        $goods_url = urlencode(SITE_URL . '/' . str_replace('&amp;', '&', url('app=goods&id=' . $goods['goods_id'])));
        $site_title = ecm_iconv(CHARSET, 'utf-8', Conf::get('site_title'));
        $share_title = urlencode($goods_name . '-' . $site_title);
        foreach ($shares as $share_id => $share)
        {
            $shares[$share_id]['link'] = str_replace(
                array('{$link}', '{$title}'),
                array($goods_url, $share_title),
                $share['link']);
        }
        return $shares;
    }
    
    function _get_favorite($goods)
    {
        $favoriteArr = array();
        if($this->visitor->get('user_id'))
        {
            $collect_goods = $this->_goods_mod->find(array(
                'conditions'=>'collect.user_id = ' . $this->visitor->get('user_id')." AND goods_id=".$goods['goods_id'],
                'join'=>'be_collect',
            ));
            $favoriteArr['goods_collect'] = count($collect_goods) > 0 ?true:false;
            $mod_store = & m('store');
            $collect_stores = $mod_store->find(array(
                'conditions'=>'collect.user_id = ' . $this->visitor->get('user_id')." AND store_id=".$goods['store_id'],
                'join'=>'be_collect',
            ));
            $favoriteArr['store_collect'] = count($collect_stores) > 0 ?true:false;
        }
        
        return $favoriteArr;
    }

    function _get_seo_info($data)
    {
        $seo_info = $keywords = array();
        $seo_info['title'] = $data['goods_name'] . ' - ' . Conf::get('site_title');
        $keywords = array(
            $data['brand'],
            $data['goods_name'],
            $data['cate_name']
        );
        $seo_info['keywords'] = implode(',', array_merge($keywords, $data['tags']));
        $seo_info['description'] = sub_str(strip_tags($data['description']), 10, true);
        return $seo_info;
    }
    
}

?>
