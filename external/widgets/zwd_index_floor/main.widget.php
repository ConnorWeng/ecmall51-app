<?php

/**
 * 新版首页楼层挂件
 *
 * @return  array  
 */
class Zwd_index_floorWidget extends BaseWidget
{
    var $_name = 'zwd_index_floor';
	var $_ttl  = 1800;
	
    function _get_data()
    {
		$cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
			$recom_mod =& m('recommend');
			$goods_list= $recom_mod->get_recommended_goods($this->options['img_recom_id'], 
					isset($this->options['goods_total'])?intval($this->options['goods_total']):30, true, $this->options['img_cate_id']);			
			
			$cate_txt = !empty($this->options['cate_txt']) ? explode(',',$this->options['cate_txt']) : array();
			$cate_url = !empty($this->options['cate_url']) ? explode(',',$this->options['cate_url']) : array();
			foreach($cate_txt as $key => $txt)
			{
				$cate[$key]['txt'] = $txt;
				$cate[$key]['url'] = $cate_url[$key];
			}			
			
			$data = array(
				'model_id'			=> mt_rand()+time(),
				'floor_name'	 	=> $this->options['floor_name'],
				'model_name'	 	=> $this->options['model_name'],
				'goods_list'	 	=> $goods_list,				
				'cate'              => $cate,
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
		if(empty($input['goods_total']))
		{
			$input['goods_total'] = 30;
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
		$this->assign('styles',$this->styles);
    }
}

?>