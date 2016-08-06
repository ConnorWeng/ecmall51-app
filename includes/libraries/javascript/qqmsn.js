<!--


function WriteQqStr_OK()
{
	document.write('<DIV id=backi_OK style="RIGHT: 0px; OVERFLOW: visible; POSITION: absolute; TOP: 160px; text-align:left;">');
	document.write('<table border="0" cellpadding="0" cellspacing="0" width="55">');
	document.write('<tr><td style="padding:0"><a href="javascript:close_float_left_OK();void(0);" title="close"><IMG src="http://demo.psmoban.com/taobao/themes/mall/taobao/styles/default/images/1.gif" border=0></a></td></tr>');
	
	document.write('<tr><td style="padding:0"><a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=332105640&site=qq&menu=yes"><img border=\"0\" SRC=\'http://demo.psmoban.com/taobao/themes/mall/taobao/styles/default/images/3.gif\' alt=\"QQ咨询\"></a></td></tr>');
	
	document.write('<tr><td style="padding:0"><a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=1533808788&site=qq&menu=yes"><img border=\"0\" SRC=\'http://demo.psmoban.com/taobao/themes/mall/taobao/styles/default/images/3.gif\' alt=\"QQ咨询\"></a></td></tr>');
	
	document.write('<tr><td style="padding:0"><a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=544500267&site=qq&menu=yes"><img border=\"0\" SRC=\'http://demo.psmoban.com/taobao/themes/mall/taobao/styles/default/images/3.gif\' alt=\"QQ咨询\"></a></td></tr>');
	
	document.write('<tr><td style="padding:0"><a target=_blank href="http://amos.im.alisoft.com/msg.aw?v=2&uid=tyioocom&site=cntaobao&s=1&charset=utf-8" ><IMG src="http://demo.psmoban.com/taobao/themes/mall/taobao/styles/default/images/5.gif" border=0 alt="旺旺咨询"></a></td></tr>');
	document.write('<tr><td style="padding:0"><A href="http://www.psmoban.com"><IMG src="http://demo.psmoban.com/taobao/themes/mall/taobao/styles/default/images/6.gif" border=0 alt="ecmall模板"></A></td></tr>');
        document.write('<tr><td style="padding:0"><A href="javascript:window.scroll(0,0)"><IMG src="http://demo.psmoban.com/taobao/themes/mall/taobao/styles/default/images/7.gif" border=0></A></td></tr>');
	document.write('</table>');
	document.write('</DIV>');
}

function close_float_left_OK()
{document.getElementById("backi_OK").style.display='none';}


lastScrollY_OK=0; 
function heartBeat_OK(){ 
var diffY_OK;
if (document.documentElement && document.documentElement.scrollTop)
    diffY_OK = document.documentElement.scrollTop;
else if (document.body)
    diffY_OK = document.body.scrollTop
else
    {}
percent_OK=.1*(diffY_OK-lastScrollY_OK); 
if(percent_OK>0)percent_OK=Math.ceil(percent_OK); 
else percent_OK=Math.floor(percent_OK); 
document.getElementById("backi_OK").style.top=parseInt(document.getElementById("backi_OK").style.top)+percent_OK+"px";
lastScrollY_OK=lastScrollY_OK+percent_OK; 
} 
if (!document.layers) {WriteQqStr_OK();window.setInterval("heartBeat_OK()",1) }

//
//-->