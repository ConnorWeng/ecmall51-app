$(function(){
	brandscroll(1,2,2,'.ibox-2','.showbox-2','.go-left','.go-right');
})

function brandscroll(i,len,pages,ibox_id,showbox_id,go_left,go_right)
{
    var $cur = 1;//初始化显示的版面
    var $i = i;//每版显示数
    var $len = len;//计算列表总长度(个数)
    var $pages = pages;//计算展示版面数量
    var $w = $(ibox_id).width();//取得展示区外围宽度
    var $showbox = $(showbox_id);
    var $pre = $(go_left);
    var $next = $(go_right);
	
	//var $autoFun;
	//调用自动滚动
    autoSlide()

 	//向前滚动
    $pre.click(function(){
        if (!$showbox.is(':animated')) {  //判断展示区是否动画
            if ($cur == 1) {   //在第一个版面时,再向前滚动到最后一个版面
                $showbox.animate({
                    left: '-=' + $w * ($pages - 1)
                }, 1500); //改变left值,切换显示版面,500(ms)为滚动时间,下同
                $cur = $pages; //初始化版面为最后一个版面
            }
            else { 
                $showbox.animate({
                    left: '+=' + $w
                }, 1500); //改变left值,切换显示版面
                $cur--; //版面累减
            }
        }
    });
    //向后滚动
    $next.click(function(){
        if (!$showbox.is(':animated')) { //判断展示区是否动画
            if ($cur == $pages) {  //在最后一个版面时,再向后滚动到第一个版面
                $showbox.animate({
                    left: 0
                }, 1500); //改变left值,切换显示版面,500(ms)为滚动时间,下同
                $cur = 1; //初始化版面为第一个版面
            }
            else {
                $showbox.animate({
                    left: '-=' + $w
                }, 1500);//改变left值,切换显示版面
                $cur++; //版面数累加
            }
        }
    });
	
	//停止滚动
    clearFun($showbox);
    clearFun($pre);
    clearFun($next);
    //事件划入时停止自动滚动
    function clearFun(elem){
        elem.hover(function(){
            clearAuto();
        }, function(){
            autoSlide();
        });
    }
    //自动滚动
    function autoSlide(){
        $next.trigger('click');
        $autoFun = setTimeout(autoSlide, 10000);//此处不可使用setInterval,setInterval是重复执行传入函数,这会引起第二次划入时停止失效
    }
    //清除自动滚动
    function clearAuto(){
        clearTimeout($autoFun);
    }
}