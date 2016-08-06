<?php

/**
 * 店铺图片广告挂件
 *
 * @param   string  $image_url  图片地址
 * @param   string  $link_url   链接地址
 */
class Zwd_store_adsWidget extends BaseWidget
{
    var $_name = 'zwd_store_ads';

    function _get_data()
    {
        $recom_mod =& m('recommend');
        $goods_list= $recom_mod->get_recommended_goods($this->options['img_recom_id'],8, true, 0);
        return array(
            'model_id'			=> mt_rand()+time(),
            'ad_image_url'  => $this->options['ad_image_url'],//店铺图片
            'ad_link_url'   => $this->options['ad_link_url'],//店铺链接
			'ad_name'      => $this->options['ad_name'],//店铺名称
			'goods_list'     => $goods_list
        );
    }

    function parse_config($input)
    {
        $image = $this->_upload_image();
        if ($image)
        {
            $input['ad_image_url'] = $image;
        }

        return $input;
    }

    function _upload_image()
    {
        import('uploader.lib');
        $file = $_FILES['ad_image_file'];
        if ($file['error'] == UPLOAD_ERR_OK)
        {
            $uploader = new Uploader();
            $uploader->allowed_type(IMAGE_FILE_TYPE);
            $uploader->addFile($file);
            $uploader->root_dir(ROOT_PATH);
            return $uploader->save('data/files/mall/template', $uploader->random_filename());
        }

        return '';
    }
    
    function get_config_datasrc()
    {
        // 取得推荐类型
        $this->assign('recommends', $this->_get_recommends());   
    }
}

?>