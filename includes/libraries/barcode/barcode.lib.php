<?php

/**
 * 生成条码
 * @date 2015-07-16
 */

if (!defined('IN_ECM'))
{
    die('Hacking attempt');
}

class barcodeprocessor
{
	var $default_value = array();
	var $text ='';
	var $uid=0;
	var $file_name = '';
	var $file_path='';
	var $code = 'BCGcode39';
	
	var $classFile = 'BCGcode39.barcode.php';
	var $className = 'BCGcode39';
	var $baseClassFile = 'BCGBarcode1D.php';
	var $codeVersion = '5.0.2';
	
	
	function __barcodeprocessor()
	{
		$this->barcodeprocessor();
	}
	
	function barcodeprocessor()
	{
		$this->default_value['filetype'] = 'PNG';
		$this->default_value['dpi'] = 72;
		$this->default_value['scale'] = 1;//1,2,3,4
		$this->default_value['rotation'] = 0;
		$this->default_value['font_family'] = 'Arial.ttf';
		$this->default_value['font_size'] = 8;
	}
	
	public function getText()
	{
		return $this->text;
	}
	
	public function setText($text)
	{
		$this->text = trim($text);
		$this->default_value['text'] = empty($this->text)?'1234567890':trim($this->text);
	}
	
	public function setUid($uid)
	{
		$this->uid = $uid;		
		$this->file_path = ROOT_PATH.'/temp/barcode'.$uid.'/';		
		if(!is_dir($this->file_path))
		{
			if (!mkdir($this->file_path))
			{
				/* 创建目录失败 */
				$this->showError();
				return false;
			}
		}
		
	}
	
	function delfiles($uid)
	{
		$this->file_path = ROOT_PATH.'/temp/barcode'.$uid.'/';
		if(!is_dir($this->file_path))
		{
			return;
		}
		$files = $this->getDirFile($this->file_path);
		if(!empty($files))
		{
			foreach ($files as $tmp)
			{
				unlink($tmp);
			}
		}
	}

	function showError() {
		header('Content-Type: image/png');
		readfile('error.png');
		exit;
	}
	
	function convertText($text) {
		$text = stripslashes($text);
		if (function_exists('mb_convert_encoding')) {
			$text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
		}
	
		return $text;
	}
	
	
	
	function generate()
	{
		if(!empty($this->text))
		{
			$this->file_name = $this->file_path.$this->default_value['text'].'.'.strtolower($this->default_value['filetype']);
		}
		else 
		{
			die('create file fail!');
			exit;
		}
		
		
		// Check if the code is valid
		/* if (!file_exists(dirname(__FILE__).'/config' . DIRECTORY_SEPARATOR . $this->code . '.php')) {
			showError();
		}
		
		include_once(dirname(__FILE__).'/config' . DIRECTORY_SEPARATOR . $this->code . '.php'); */
		
		$class_dir = dirname(__FILE__).'/class';
		require_once($class_dir . DIRECTORY_SEPARATOR . 'BCGColor.php');
		require_once($class_dir . DIRECTORY_SEPARATOR . 'BCGBarcode.php');
		require_once($class_dir . DIRECTORY_SEPARATOR . 'BCGDrawing.php');
		require_once($class_dir . DIRECTORY_SEPARATOR . 'BCGFontFile.php');
		
		
		if (!include_once($class_dir . DIRECTORY_SEPARATOR . $this->classFile)) {
			showError();
		} 
		
		
		include_once(dirname(__FILE__).'/config' . DIRECTORY_SEPARATOR . $this->baseClassFile);
		
		$filetypes = array('PNG' => BCGDrawing::IMG_FORMAT_PNG, 'JPEG' => BCGDrawing::IMG_FORMAT_JPEG, 'GIF' => BCGDrawing::IMG_FORMAT_GIF);
		
		$drawException = null;
		try {
			$color_black = new BCGColor(0, 0, 0);
			$color_white = new BCGColor(255, 255, 255);
		
			$code_generated = new $this->className();
		
			if (function_exists('baseCustomSetup')) {
				baseCustomSetup($code_generated, $this->default_value);
			}
		
			if (function_exists('customSetup')) {
				customSetup($code_generated, $this->default_value);
			}
		
			$code_generated->setScale(max(1, min(4, $this->default_value['scale'])));
			$code_generated->setBackgroundColor($color_white);
			$code_generated->setForegroundColor($color_black);
		
			if ($this->text !== '') {
				$text = $this->convertText($this->text);
				$code_generated->parse($this->text);
			}
		} catch(Exception $exception) {
			$drawException = $exception;
		}
		
		$drawing = new BCGDrawing($this->file_name, $color_white);
		if($drawException) {
			$drawing->drawException($drawException);
		} else {
			$drawing->setBarcode($code_generated);
			$drawing->setRotationAngle($this->default_value['rotation']);
			$drawing->setDPI($this->default_value['dpi'] === 'NULL' ? null : max(72, min(300, intval($this->default_value['dpi']))));
			$drawing->draw();
		}
		
		/* switch ($default_value['filetype']) {
		 case 'PNG':
		header('Content-Type: image/png');
		break;
		case 'JPEG':
		header('Content-Type: image/jpeg');
		break;
		case 'GIF':
		header('Content-Type: image/gif');
		break;
		} */
		
		$drawing->finish($filetypes[$this->default_value['filetype']]);
		
	}
	
	function getDirFile($dir)
	{
		$fileArr = array();
		if(false != ($handle = opendir($dir)))
		{
			$i = 0;
			while( false !== ($file = readdir($handle)))
			{
				if($file != '.' && $file != '..' && strpos($file, '.'))
				{
					$fileArr[$i] = $dir.$file;
					if($i == 100)
					{
						break;
					}
					$i++;
				}
			}
			closedir($handle);
		}
		return $fileArr;
	}
	
}



?>