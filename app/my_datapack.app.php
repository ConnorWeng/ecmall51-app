<?php

class My_datapackApp extends StoreadminbaseApp {
    var $_store_id;
    var $_store_mod;

    function __construct() {
        parent::__construct();
        $this->_store_id = intval($this->visitor->get('manage_store'));
        $this->_store_mod =& m('store');
    }

    function index() {
        if (!IS_POST) {
            $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('my_im_order'));
            $this->_curlocal(LANG::get('member_center'), 'index.php?app=my_datapack',
                             LANG::get('my_datapack'), 'index.php?app=my_datapack',
                             LANG::get('my_datapack'));
            $this->_curitem('my_datapack');
            $this->_curmenu('my_datapack');
            $store_info = $this->_store_mod->get($this->_store_id);
            $this->assign('store_id', $this->_store_id);
            $this->assign('datapack', $store_info['datapack']);
            $this->display('my_datapack.index.html');
        } else {
            $file = $_FILES['datapack_zip'];
            if ($file['error'] != UPLOAD_ERR_OK) {
                $this->show_warning('select_file');
                return;
            }
            import('uploader.lib');
            $uploader = new Uploader();
            $uploader->allowed_type('zip');
            $uploader->allowed_size(10097152);
            $uploader->addFile($file);
            if (!$uploader->file_info()) {
                $this->show_warning($uploader->get_error());
                return;
            }
            $uploader->root_dir(ROOT_PATH);
            $uploader->save('data/files/store_'.$this->_store_id, 'datapack');
            $this->_store_mod->edit($this->_store_id, array('datapack' => 'data/files/store_'.$this->_store_id.'/datapack.zip'));
            $this->show_message('success', 'back_list', 'index.php?app=my_datapack');
        }
    }

    function delete_datapack() {
        $this->_store_mod->edit($this->_store_id, array('datapack' => NULL));
        unlink(ROOT_PATH.'/data/files/store_'.$this->_store_id.'/datapack.zip');
        $this->show_message('success', 'back_list', 'index.php?app=my_datapack');
    }

    function download_datapack() {
        $store_info = $this->_store_mod->get($_REQUEST['store_id']);
        if ($store_info['datapack']) {
            header("Content-Type: application/force-download");
            header("Content-Disposition: attachment; filename=datapack.zip");
            readfile(ROOT_PATH.'/'.$store_info['datapack']);
            ob_end_flush();
        } else {
            $this->show_warning('没有找到数据包文件');
        }
    }
}