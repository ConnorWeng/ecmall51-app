<?php

/**
 * 挂件
 *
 * @return  array  
 */
class Zwd_floor_hideuploadWidget extends BaseWidget
{
    var $_name = 'zwd_floor_hideupload';
	var $_ttl  = 1800;
	//var $styles = array('#E18454','#AC90F1','#F291C0','#75CE6F','#FF8486','#72A4F9','#F0C25C','#FF9262','#4099D1');
	
    function _get_data()
    {
		$cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
			$recom_mod =& m('recommend');
			$goods_list= $recom_mod->get_recommended_goods($this->options['img_recom_id'], 24, true, $this->options['img_cate_id']);
			//$goods_list1= $recom_mod->get_recommended_goods($this->options['img_recom_id1'], 6, true, $this->options['img_cate_id1']);
			/*
			$brand_mod=&m('brand');
			if($this->options['brand']){
				$brands=$brand_mod->find(array('conditions'=>'tag='.'"'.$this->options['brand'].'"','fields'=>'brand_name,brand_logo','limit'=>12));
			}else{
				$brands=$brand_mod->find(array('conditions'=>'','fields'=>'brand_name,brand_logo','limit'=>12));
			}*/
			/* $cate_txt = !empty($this->options['cate_txt']) ? explode(',',$this->options['cate_txt']) : array();
			$cate_url = !empty($this->options['cate_url']) ? explode(',',$this->options['cate_url']) : array();
			foreach($cate_txt as $key => $txt)
			{
				$cate[$key]['txt'] = $txt;
				$cate[$key]['url'] = $cate_url[$key];
			}
			$model_color = $this->options['model_color'] ? $this->options['model_color'] : '#db6e44';
			foreach($this->styles as $k => $val)
			{
				if($model_color==$val)
				{
					$class_name = "floor_".$k;
				}
			} */
			//echo 'ad6_image_url:'.$this->options['ad6_image_url'];
			$data = array(
				'model_id'			=> mt_rand(),
				'model_name'	 	=> $this->options['model_name'],
				'floor_num'         => $this->options['floor_num'],
				'goods_list'	 	=> $goods_list,
				//'goods_list1'	 	=> $goods_list1,
				//'top_title1'  	=> $this->options['top_title1'],
				//'top_title1_url'   	=> $this->options['top_title1_url'],
				//'top_title2'  	=> $this->options['top_title2'],
				//'top_title2_url'   	=> $this->options['top_title2_url'],
				//'ad2_image_url'  	=> $this->options['ad2_image_url'],
				//'ad2_link_url'   	=> $this->options['ad2_link_url'],				
				//'ad4_image_url'  	=> $this->options['ad4_image_url'],
				//'ad4_link_url'   	=> $this->options['ad4_link_url'],
				//'ad5_image_url'  	=> $this->options['ad5_image_url'],
				//'ad5_link_url'   	=> $this->options['ad5_link_url'],
				//'ad6_image_url'  	=> $this->options['ad6_image_url'],
				//'ad6_link_url'   	=> $this->options['ad6_link_url'],
				//'cate'              => $cate,
				'keyword_list'      => explode(' ', $this->options['keyword_list']),
				//'model_color'    	=> $model_color,
				//'brands'			=> $brands,	
				//'class_name'         => $class_name,
			);
        	$cache_server->set($key, $data,$this->_ttl);
        }
        return $data;
    }

    function parse_config($input)
    {
        if ($input['img_recom_id'] >= 0)
        {
            $input['img_cate_id'] = 0;
        }
		
		$images = $this->_upload_image();
        if ($images)
        {
            foreach ($images as $key => $image)
            {
                $input['ad' . $key . '_image_url'] = $image;
            }
        }
        return $input;
    }
	
	function _upload_image()
    {
        import('uploader.lib');
        $images = array();
        for ($i = 1; $i <= 6; $i++)
        {
            $file = $_FILES['ad' . $i . '_image_file'];
            if ($file['error'] == UPLOAD_ERR_OK)
            {
                $uploader = new Uploader();
                $uploader->allowed_type(IMAGE_FILE_TYPE);
                $uploader->addFile($file);
                $uploader->root_dir(ROOT_PATH);
                $images[$i] = $uploader->save('data/files/mall/template', $uploader->random_filename());
            }
        }

        return $images;
    }
	function get_config_datasrc()
    {
         // 取得推荐类型
        $this->assign('recommends', $this->_get_recommends());

        // 取得一级商品分类
        $this->assign('gcategories', $this->_get_gcategory_options(2));
		
		//模块风格
		//$this->assign('styles',$this->styles);
    }
}

?>