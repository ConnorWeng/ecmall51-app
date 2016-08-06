<?php
/**
 * Tester
 * @author Administrator
 *
 */
class AtestApp extends MallbaseApp
{
    function index()
    {
        
    }
    
    
    function show_time()
    {
        $today_now = gmtime();
        $today_start = mktime(0,0,0,date('m',$today_now),date('d',$today_now),date('Y',$today_now));
        echo "gmtoday_start=".$today_start."=".date('Y-m-d H:i:s',$today_start)."<br>";
        echo "gmnow=".$today_now."=".date('Y-m-d H:i:s',$today_now)."<br><br>";
        
        
        $today_now = time();
        $today_start = mktime(0,0,0,date('m',$today_now),date('d',$today_now),date('Y',$today_now));
        echo "today_start_local=".$today_start."=".date('Y-m-d H:i:s',$today_start)."<br>";
        echo "now_local=".$today_now."=".date('Y-m-d H:i:s',$today_now)."<br>";
        
        echo server_timezone()."<br>";
        echo date('Z');
        //$today_start = gmstr2time(date('Y-m-d',$today_start));
    	
        /* $goods_image = 'http://img.alicdn.com/bao/uploaded/i3/791596228/TB2tM9SrVXXXXcMXXXXXXXXXXXX_!!791596228.jpg_180x180.jpg';
        
        $result = change_taobao_imgsize($goods_image);
        
        dump($result); */
        
    }
    
    
}

?>