<?php

/**
 * 图片挂件
 *
 * @return  array   $image_list
 */
class Zwd_imageadvsWidget extends BaseWidget
{
    var $_name = 'zwd_imageadvs';
	var $_ttl  = 1800;

    function _get_data()
    {
		$cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
			$images=$this->options['images'];
			$cate_txt = !empty($this->options['cate_txt']) ? explode(',',$this->options['cate_txt']) : array();
			$cate_url = !empty($this->options['cate_url']) ? explode(',',$this->options['cate_url']) : array();
			foreach($cate_txt as $key => $txt)
			{
				$cate[$key]['txt'] = $txt;
				$cate[$key]['url'] = $cate_url[$key];
			}
			$data=array(
				'images'=>$images,
				'cate'  => $cate,
				'model_name'=>$this->options['model_name']
			);

			$cache_server->set($key, $data,$this->_ttl);
        }
        return $data;
    }

    function parse_config($input)
    {
        $result = array();
        $num    = isset($input['ad_link_url']) ? count($input['ad_link_url']) : 0;
        if ($num > 0)
        {
            $images = $this->_upload_image($num);
            for ($i = 0; $i < $num ; $i++)
            {
                if (!empty($images[$i]))
                {
                    $input['ad_image_url'][$i] = $images[$i];
                }
    
                if (!empty($input['ad_image_url'][$i]))
                {
                    $result['images'][] = array(
                        'ad_image_url' => $input['ad_image_url'][$i],
                        'ad_link_url'  => $input['ad_link_url'][$i],
						'ad_title_url'  => $input['ad_title_url'][$i],
                    );
                }
            }
        }
        $input=$input+$result;
        return $input;
    }

    function _upload_image($num)
    {
        import('uploader.lib');

        $images = array();
        for ($i = 0; $i < $num; $i++)
        {
            $file = array();
            foreach ($_FILES['ad_image_file'] as $key => $value)
            {
                $file[$key] = $value[$i];
            }

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
}

?>