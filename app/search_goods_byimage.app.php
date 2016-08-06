<?php
/**
 * 图片搜索模块已经完成了，麻烦@小T 对接了，目前放在121.41.172.78聚石塔服务器上，调用方式如下：
 比如我要找https://img.alicdn.com/bao/uploaded/i1/TB1dwv0GXXXXXXGXVXXXXXXXXXX_!!0-item_pic.jpg相近的宝贝，
 访问http://121.41.172.78:30004/SimilarImages?
 url=http%3A%2F%2Fimg.alicdn.com%2Fbao%2Fuploaded%2Fi3%2FTB15tsRHpXXXXaiXXXXXXXXXXXX_!!0-item_pic.jpg_240x240.jpg即可，
 返回结果是json数组，类似['114284','16180','105108','127949','83137','75887']，是一串goodsId组成的array，按照相似度从高到低排列
 */
/**
 * 以图搜款
 * @author tanaiquan
 * @date 2016-01-03
 */
define('NUM_PER_PAGE', 40);        // 每页显示数量
class Search_goods_byimageApp extends MallbaseApp
{
    var $_requestUrl;
    
    function __construct()
    {
        $this->Search_goods_byimageApp();    
    }
    
    function Search_goods_byimageApp()
    {
        //$this->_requestUrl = 'http://121.41.172.78:30004/SimilarImages';
        $this->_requestUrl = 'http://115.29.221.120:30004/SimilarImages';
        parent::__construct();
    } 
    
    function index()
    {
        $this->import_resource(array(
            'style'=>'pace/themes/blue/pace-theme-loading-bar.css',
            'script'=>'pace/pace.min.js'
        ));
        $this->display("search_goods_byimage.index.html");
    }
    
    function search()
    {
        $url = $this->_requestUrl."?url=".urlencode(trim($_GET['img']));       
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $retval = curl_exec($curl);
        curl_close($curl);
        $retval = str_replace("'", '"', $retval);
        $retval = ecm_json_decode($retval); //array
        
        $goods_list = $this->_get_goods_list($retval);
        
        
        $this->assign('goods_list',$goods_list);
        
        $this->display("search_goods_byimage.search.html");
    }
    
    private function _get_goods_list($goods_ids)
    {
        /* 组成查询条件 */
        $conditions = " g.if_show = 1 AND g.closed = 0 AND g.default_spec > 0 AND s.state = 1 "; // 上架且没有被禁售，店铺是开启状态,
        if(!empty($goods_ids))  $conditions .= " AND ".db_create_in($goods_ids,'g.goods_id');
        
        $page = $this->_get_page(NUM_PER_PAGE);
        
        $goods_mod = & m('goods');
        
        if(empty($goods_ids))
        {
            $goods_list = $goods_mod->get_list2(array(
                'conditions' => $conditions,
                'order' => isset($_GET['order']) && isset($orders[trim(str_replace('asc', '', str_replace('desc', '', $_GET['order'])))]) ? $_GET['order'] : 'sort_order asc', 
                'fields' => 's.praise_rate,s.im_qq,s.im_ww,', // tyioocom
                'limit' => $page['limit'],
            ), null, false, true, $total_found,$backkey);
        }
        else 
        {
            $goods_list = $goods_mod->get_list(array(
                'conditions' => $conditions,
                'order' => isset($_GET['order']) && isset($orders[trim(str_replace('asc', '', str_replace('desc', '', $_GET['order'])))]) ? $_GET['order'] : 'sort_order asc',
                'fields' => 's.praise_rate,s.im_qq,s.im_ww,', // tyioocom
                'limit' => $page['limit'],
            )); 
            
        }
        
        /* 
        $page['item_count'] = $total_found;
       
        $this->_format_page($page);
        $this->assign('page_info', $page); */
        if(empty($goods_ids)){
            $this->assign("kw_search_tips",'对不起您要的款式没有找到,帮您推荐:'.$backkey);
        }
        return $goods_list;
    }
    
    /**
     * 通过商品标题搜款
     */
    function search_byname()
    {
        $goods_name = trim($_GET['name']);
        //$goods_name = "实拍 8278# 新款2016秋装新款女装长袖时尚女款T恤衫宽松";
        
        $goods_list = $this->_get_goods_list_bygoods_name($goods_name);
        $this->assign('goods_list',$goods_list);
        
        $this->display("search_goods_byimage.index.html");
        
    }
    
    private function _get_goods_list_bygoods_name($goods_name)
    {
        $keyword = $this->_trimSpec($goods_name);
        if ($keyword != '') {
            //$keyword = preg_split("/[\s," . Lang::get('comma') . Lang::get('whitespace') . "]+/", $keyword);
            $tmp = str_replace(array(Lang::get('comma'), Lang::get('whitespace'), ' '), ',', $keyword);
            $keyword = explode(',', $tmp);
            sort($keyword);
            $res['keyword'] = $keyword;
        }
        //print_r($res['keyword']);
        /* 组成查询条件 */
        $conditions = " g.if_show = 1 AND g.closed = 0 AND g.default_spec > 0 AND s.state = 1 "; // 上架且没有被禁售，店铺是开启状态,
    
        $page = $this->_get_page(NUM_PER_PAGE);
    
        $goods_mod = & m('goods');
    
         $goods_list = $goods_mod->get_list2(array(
                'conditions' => $conditions,
                'order' => isset($_GET['order']) && isset($orders[trim(str_replace('asc', '', str_replace('desc', '', $_GET['order'])))]) ? $_GET['order'] : 'sort_order asc',
                'fields' => 's.praise_rate,s.im_qq,s.im_ww,', // tyioocom
                'limit' => $page['limit'],
                'conditions_tt' => $res['keyword'],
            ), null, false, true, $total_found,$backkey);
      
    
       
         $page['item_count'] = $total_found;
          
         $this->_format_page($page);
         $this->assign('page_info', $page); 
         
        if(empty($total_found)){
            $this->assign("kw_search_tips",'对不起您要的款式没有找到,帮您推荐:'.$backkey);
        }
        return $goods_list;
    }
    
    private function _trimSpec($str)
    {
        $qian=array(" ","　","\t","\n","\r","实拍","（","）","(",")","\5b9e\62cd");//\5b9e\62cd 实拍unicode
        $hou=array(" "," "," "," "," "," "," "," "," "," "," ");
        $str = str_replace($qian,$hou,$str);
        return strval(preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0|#+|\d+)/", " ", strip_tags($str)));
    }
        
    public function get_behalfarea()
    {
        $conditions = " g.if_show = 1 AND g.closed = 0 AND g.default_spec > 0 AND s.state = 1 AND s.store_id in ('5867')"; // 上架且没有被禁售，店铺是开启状态,
        
        $page = $this->_get_page(NUM_PER_PAGE);
        
        $goods_mod = & m('goods');
        
        
        $goods_list = $goods_mod->get_list2(array(
            'conditions' => $conditions,
            'order' => isset($_GET['order']) && isset($orders[trim(str_replace('asc', '', str_replace('desc', '', $_GET['order'])))]) ? $_GET['order'] : 'sort_order asc',
            'fields' => 's.praise_rate,s.im_qq,s.im_ww,', // tyioocom
            'limit' => $page['limit'],
        ), null, false, true, $total_found,$backkey);
       
        
        /*
         $page['item_count'] = $total_found;
          
         $this->_format_page($page);
         $this->assign('page_info', $page); */
        
        dump($goods_list);
       /*  if(empty($goods_ids)){
            $this->assign("kw_search_tips",'对不起您要的款式没有找到,帮您推荐:'.$backkey);
        }
        return $goods_list; */
    }
    
    
}

?>