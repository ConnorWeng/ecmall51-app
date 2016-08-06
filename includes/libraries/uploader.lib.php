<?php

/**
 *    文件上传辅助类
 *
 *    @author    Garbin
 *    @usage    none
 */
class Uploader extends Object
{
    var $_file              = null;
    var $_allowed_file_type = null;
    var $_allowed_file_size = null;
    var $_root_dir          = null;
	
	var $_file_type_str     = null;

    /**
     *    添加由POST上来的文件
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function addFile($file)
    {
        if (!is_uploaded_file($file['tmp_name']))
        {
            return false;
        }
        $this->_file = $this->_get_uploaded_info($file);
    }

    /**
     *    设定允许添加的文件类型
     *
     *    @author    Garbin
     *    @param     string $type （小写）示例：gif|jpg|jpeg|png
     *    @return    void
     */
    function allowed_type($type)
    {
        $this->_allowed_file_type = explode('|', $type);
		$this->_file_type_str = '|' . $type . '|';
    }
	
	/**
	 * 检查文件类型
	 *
	 * @access      public
	 * @param       string      filename            文件名
	 * @param       string      realname            真实文件名
	 * @param       string      limit_ext_types     允许的文件类型
	 * @return      string
	 */
	function check_file_type($filename, $realname = '', $limit_ext_types = '')
	{
		if ($realname)
		{
			$extname = strtolower(substr($realname, strrpos($realname, '.') + 1));
		}
		else
		{
			$extname = strtolower(substr($filename, strrpos($filename, '.') + 1));
		}

		/* csv文件暂不做验证 */
		if($extname == 'csv')
		{
			return $extname;
		}
	
		if ($limit_ext_types && stristr($limit_ext_types, '|' . $extname . '|') === false)
		{
			return '';
		}

		$str = $format = '';
	
		$file = @fopen($filename, 'rb');
		if ($file)
		{
			$str = @fread($file, 0x400); // 读取前 1024 个字节
			@fclose($file);
		}
		else
		{
			if (stristr($filename, ROOT_PATH) === false)
			{
				if ($extname == 'jpg' || $extname == 'jpeg' || $extname == 'gif' || $extname == 'png' || $extname == 'doc' ||
					$extname == 'xls' || $extname == 'txt'  || $extname == 'zip' || $extname == 'rar' || $extname == 'ppt' ||
					$extname == 'pdf' || $extname == 'rm'   || $extname == 'mid' || $extname == 'wav' || $extname == 'bmp' ||
					$extname == 'swf' || $extname == 'chm'  || $extname == 'sql' || $extname == 'cert'|| $extname == 'pptx' || 
					$extname == 'xlsx' || $extname == 'docx')
				{
					$format = $extname;
				}
			}
			else
			{
				return '';
			}
		}
	
		if ($format == '' && strlen($str) >= 2 )
		{
			if (substr($str, 0, 4) == 'MThd' && $extname != 'txt')
			{
				$format = 'mid';
			}
			elseif (substr($str, 0, 4) == 'RIFF' && $extname == 'wav')
			{
				$format = 'wav';
			}
			elseif (substr($str ,0, 3) == "\xFF\xD8\xFF")
			{
				$format = 'jpg';
			}
			elseif (substr($str ,0, 4) == 'GIF8' && $extname != 'txt')
			{
				$format = 'gif';
			}
			elseif (substr($str ,0, 8) == "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")
			{
				$format = 'png';
			}
			elseif (substr($str ,0, 2) == 'BM' && $extname != 'txt')
			{
				$format = 'bmp';
			}
			elseif ((substr($str ,0, 3) == 'CWS' || substr($str ,0, 3) == 'FWS') && $extname != 'txt')
			{
				$format = 'swf';
			}
			elseif (substr($str ,0, 4) == "\xD0\xCF\x11\xE0")
			{   // D0CF11E == DOCFILE == Microsoft Office Document
				if (substr($str,0x200,4) == "\xEC\xA5\xC1\x00" || $extname == 'doc')
				{
					$format = 'doc';
				}
				elseif (substr($str,0x200,2) == "\x09\x08" || $extname == 'xls')
				{
					$format = 'xls';
				} 
				elseif (substr($str,0x200,4) == "\xFD\xFF\xFF\xFF" || $extname == 'ppt')
				{
					$format = 'ppt';
				}
			}
			elseif (substr($str ,0, 4) == "PK\x03\x04")
			{
				if (substr($str,0x200,4) == "\xEC\xA5\xC1\x00" || $extname == 'docx')
				{
					$format = 'docx';
				}
				elseif (substr($str,0x200,2) == "\x09\x08" || $extname == 'xlsx')
				{
					$format = 'xlsx';
				} 
				elseif (substr($str,0x200,4) == "\xFD\xFF\xFF\xFF" || $extname == 'pptx')
				{
					$format = 'pptx';
				}
				else
				{
					$format = 'zip';
				}
			} 
			elseif (substr($str ,0, 4) == 'Rar!' && $extname != 'txt')
			{
				$format = 'rar';
			} 
			elseif (substr($str ,0, 4) == "\x25PDF")
			{
				$format = 'pdf';
			} 
			elseif (substr($str ,0, 3) == "\x30\x82\x0A")
			{
				$format = 'cert';
			} 
			elseif (substr($str ,0, 4) == 'ITSF' && $extname != 'txt')
			{
				$format = 'chm';
			} 
			elseif (substr($str ,0, 4) == "\x2ERMF")
			{
				$format = 'rm';
			} 
			elseif ($extname == 'sql')
			{
				$format = 'sql';
			} 
			elseif ($extname == 'txt')
			{
				$format = 'txt';
			}
		}

		if ($limit_ext_types && stristr($limit_ext_types, '|' . $format . '|') === false)
		{
			$format = '';
		}
	
		return $format;
	}


    /**
     *    允许的大小
     *
     *    @author    Garbin
     *    @param     mixed $size
     *    @return    void
     */
    function allowed_size($size)
    {
        $this->_allowed_file_size = $size;
    }
    function _get_uploaded_info($file)
    {
        $pathinfo = pathinfo($file['name']);
        $file['extension'] = $pathinfo['extension'];
        $file['filename']     = $pathinfo['basename'];
        if (!$this->_is_allowd_type($file['extension']) || !$this->check_file_type($file['tmp_name'],$file['name'],$this->_file_type_str))
        {
            $this->_error('not_allowed_type', $file['extension']);

            return false;
        }
        if (!$this->_is_allowd_size($file['size']))
        {
            $this->_error('not_allowed_size', $file['size']);

            return false;
        }

        return $file;
    }
    function _is_allowd_type($type)
    {
        if (!$this->_allowed_file_type)
        {
            return true;
        }
        return in_array(strtolower($type), $this->_allowed_file_type);
    }
    function _is_allowd_size($size)
    {
        if (!$this->_allowed_file_size)
        {
            return true;
        }

        return is_numeric($this->_allowed_file_size) ?
                ($size <= $this->_allowed_file_size) :
                ($size >= $this->_allowed_file_size[0] && $size <= $this->_allowed_file_size[1]);
    }
    /**
     *    获取上传文件的信息
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function file_info()
    {
        return $this->_file;
    }

    /**
     *    若没有指定root，则将会按照所指定的path来保存，但是这样一来，所获得的路径就是一个绝对或者相对当前目录的路径，因此用Web访问时就会有问题，所以大多数情况下需要指定
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function root_dir($dir)
    {
        $this->_root_dir = $dir;
    }
    function save($dir, $name = false)
    {
        if (!$this->_file)
        {
            return false;
        }
        if (!$name)
        {
            $name = $this->_file['filename'];
        }
        else
        {
            $name .= '.' . $this->_file['extension'];
        }
        $path = $dir . '/' . $name;

        return $this->move_uploaded_file($this->_file['tmp_name'], $path);
    }

    /**
     *    将上传的文件移动到指定的位置
     *
     *    @author    Garbin
     *    @param     string $src
     *    @param     string $target
     *    @return    bool
     */
    function move_uploaded_file($src, $target)
    {
        $abs_path = $this->_root_dir ? $this->_root_dir . '/' . $target : $target;
        $dirname = dirname($target);
        if (!ecm_mkdir(ROOT_PATH . '/' . $dirname))
        {
            $this->_error('dir_doesnt_exists');

            return false;
        }

        if (move_uploaded_file($src, $abs_path))
        {
            @chmod($abs_path, 0666);
            return $target;
        }
        else
        {
            return false;
        }
    }

    /**
     * 生成随机的文件名
     */
    function random_filename()
    {
        $seedstr = explode(" ", microtime(), 5);
        $seed    = $seedstr[0] * 10000;
        srand($seed);
        $random  = rand(1000,10000);

        return date("YmdHis", time()) . $random;
    }
}

/**
 *    FtpUploader
 *
 *    @author    Garbin
 *    @usage    none
 */
class FtpUploader extends Uploader
{
    var $_ftp_server = null;
    function __construct(&$_ftp_server)
    {
        $this->_ftp_server = $_ftp_server;
    }
    function move_uploaded_file($src, $target)
    {
        if (!$this->_ftp_server)
        {
            $this->_error('no_ftp_server');
            return false;
        }
        $dir = dirname($target);
        $this->_chdir($dir);

        return  $this->_ftp_server->put($src, basename($target)) ? $target : false;
    }
    function _chdir($dir)
    {
        restore_error_handler();

        $dirs = explode('/', $dir);
        if (empty($dirs))
        {
            return true;
        }
        /* 循环创建目录 */
        foreach ($dirs as $d)
        {
            if (!@$this->_ftp_server->chdir($d))
            {
                $this->_ftp_server->mkdir($d);
                $this->_ftp_server->chmod($d);
                $this->_ftp_server->chdir($d);
                $this->_ftp_server->put(ROOT_PATH . '/data/index.html', 'index.html');
            }
        }

        reset_error_handler();

        return true;
    }
}

?>