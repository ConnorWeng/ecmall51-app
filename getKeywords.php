<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
   function Myencoding($source){
        $encode = mb_detect_encoding($source, array("GBK","UTF-8","GB2312","BIG5"));
        if($encode=='CP936'){
            $source=iconv("GBK", "UTF-8//IGNORE", $source);
            //$meta用于DOM判断编码
            $meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            $source=$meta.$source;
        }
        return $source;
    }
    $log = 'log.txt';
  $file = file_get_contents('http://item.taobao.com/item.htm?spm=686.1000925.1000774.13.Odmgnd&id=44366422146');
  echo $file;
  $f  = file_put_contents($log, $file,FILE_APPEND);
  if($f){// 这个函数支持版本(PHP 5) 
  echo "写入成功。<br />";
 }
  $pos = strpos( Myencoding(file_get_contents($log)), '图片上传中');
  if($pos === false){
      echo ' the file is ok;';
  }else{
      echo 'the file is not ok';
  }
?>
