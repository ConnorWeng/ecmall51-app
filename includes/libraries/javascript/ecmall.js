jQuery.extend({
    getCookie : function(sName) {
        var aCookie = document.cookie.split("; ");
        for (var i=0; i < aCookie.length; i++){
            var aCrumb = aCookie[i].split("=");
            if (sName == aCrumb[0]) return decodeURIComponent(aCrumb[1]);
        }
        return '';
    },
    setCookie : function(sName, sValue, sExpires) {
        var sCookie = sName + "=" + encodeURIComponent(sValue);
        if (sExpires != null) sCookie += "; expires=" + sExpires;
        document.cookie = sCookie;
    },
    removeCookie : function(sName) {
        document.cookie = sName + "=; expires=Fri, 31 Dec 1999 23:59:59 GMT;";
    }
});
function drop_confirm(msg, url){
    if(confirm(msg)){
        window.location = url;
    }
}

/* 显示Ajax表单 */
function ajax_form(id, title, url, width, style, opacity)
{
    if (!width)
    {
        width = 400;
    }
    var d = DialogManager.create(id);
    d.setTitle(title);
    d.setContents('ajax', url);
    d.setWidth(width);
    if(style)
    {
        d.setStyle(style);
    }
    if(opacity)
    {
        ScreenLocker.style.opacity = opacity;
    }
    d.show('center');

    return d;
}
function go(url){
    window.location = url;
}
/*随机数组tiq*/
function shuffle(arr){
    for(var j,x,i=arr.length;i;j=parseInt(Math.random()*i),x=arr[--i],arr[i]=arr[j],arr[j]=x);
    return arr;
}
/*得到1-n随机数*/
function getRandom(n){
    return Math.floor(Math.random()*n+1);
}
function change_captcha(jqObj){
    jqObj.attr('src', 'index.php?app=captcha&' + Math.round(Math.random()*10000));
}

/* 格式化金额 */
function price_format(price){
    if(typeof(PRICE_FORMAT) == 'undefined'){
        PRICE_FORMAT = '&yen;%s';
    }
    price = number_format(price, 2);

    return PRICE_FORMAT.replace('%s', price);
}

function number_format(num, ext){
    if(ext < 0){
        return num;
    }
    num = Number(num);
    if(isNaN(num)){
        num = 0;
    }
    var _str = num.toString();
    var _arr = _str.split('.');
    var _int = _arr[0];
    var _flt = _arr[1];
    if(_str.indexOf('.') == -1){
        /* 找不到小数点，则添加 */
        if(ext == 0){
            return _str;
        }
        var _tmp = '';
        for(var i = 0; i < ext; i++){
            _tmp += '0';
        }
        _str = _str + '.' + _tmp;
    }else{
        if(_flt.length == ext){
            return _str;
        }
        /* 找得到小数点，则截取 */
        if(_flt.length > ext){
            _str = _str.substr(0, _str.length - (_flt.length - ext));
            if(ext == 0){
                _str = _int;
            }
        }else{
            for(var i = 0; i < ext - _flt.length; i++){
                _str += '0';
            }
        }
    }

    return _str;
}
function jbox_close(str)
{
    jBox.close();
    jBox.info(str,null);
}

/* 收藏商品 */
function collect_goods(id)
{
    var url = SITE_URL + '/index.php?app=my_favorite&act=add&type=goods&ajax=1';
    $.getJSON(url, {'item_id':id}, function(data){
        //alert(data.msg);
        /*custom tiq*/
        if(data.done)
        {
            $('#collect_goods_em_'+id).html('<a href="javascript:;" class="inline-block pointer clearfix" style="border:1px solid green;border-radius:3px;padding:1px 5px;height:16px;line-height:16px;" title=""><strong class="font-normal inline-block float-left" style="height:16px;"><font color=green>已收藏</font></strong></a>');
            $('#collect_wgoods_em_'+id).html('<a href="javascript:;" class="inline-block pointer clearfix" style="border:1px solid green;border-radius:3px;padding:1px 5px;height:16px;line-height:16px;" title=""><strong class="font-normal inline-block float-left" style="height:16px;"><font color=green>已收藏</font></strong></a>');
        }
        else
        {
            if(data.retval == 'user_not_login')
            {
                //ajax_form('user_login_form','123',SITE_URL + '/index.php?app=default&act=loginWithAjax');
                jBox.open('iframe:'+SITE_URL+'/index.php?app=default&act=loginWithAjax',null,440,448,{ buttons: {}});
                return false;
            }
            else
            {
                alert(data.msg);
            }
        }

    });
}

/* 收藏店铺 */
function collect_store(id)
{
    var url = SITE_URL + '/index.php?app=my_favorite&act=add&type=store&jsoncallback=?&ajax=1';
    $.getJSON(url, {'item_id':id}, function(data){
        alert(data.msg);
    });
}

/* 签约代发 */
function collect_sbehalf(id)
{
    var url = SITE_URL + '/index.php?app=my_favorite&act=add&type=sbehalf&jsoncallback=?&ajax=1';
    $.getJSON(url, {'item_id':id}, function(data){
        window.location.reload(true);
        alert(data.msg);
    });
}
/* 收藏代发 */
function collect_behalf(id)
{
    var url = SITE_URL + '/index.php?app=my_favorite&act=add&type=behalf&jsoncallback=?&ajax=1';
    $.getJSON(url, {'item_id':id}, function(data){
        window.location.reload(true);
        alert(data.msg);
    });
}
/* 火狐下取本地全路径 */
function getFullPath(obj)
{
    if(obj)
    {
        //ie
        if (window.navigator.userAgent.indexOf("MSIE")>=1)
        {
            obj.select();
            if(window.navigator.userAgent.indexOf("MSIE") == 25){
                obj.blur();
            }
            return document.selection.createRange().text;
        }
        //firefox
        else if(window.navigator.userAgent.indexOf("Firefox")>=1)
        {
            if(obj.files)
            {
                //return obj.files.item(0).getAsDataURL();
                return window.URL.createObjectURL(obj.files.item(0));
            }
            return obj.value;
        }

        return obj.value;
    }
}

/**
 *    启动邮件队列
 *
 *    @author    Garbin
 *    @param     string req_url
 *    @return    void
 */
function sendmail(req_url)
{
    $(function(){
        var _script = document.createElement('script');
        _script.type = 'text/javascript';
        _script.src  = req_url;
        document.getElementsByTagName('head')[0].appendChild(_script);
    });
}
/* 转化JS跳转中的 ＆ */
function transform_char(str)
{
    if(str.indexOf('&'))
    {
        str = str.replace(/&/g, "%26");
    }
    return str;
}

$(function(){
    $("#site-nav .city-nav").hover(function(){
        $(this).find("dd").show();
    },function(){
        $(this).find("dd").hide();
    });
    $("#site-nav .login-span").hover(function(){
        $(this).find("ul").show();
    },function(){
        $(this).find("ul").hide();
    });

    $('#footer .footer-fixed li').hover(function(){
        $(this).find('.nav-content').show();
        $(this).find('.right_arrow').show();
    },function(){
        $(this).find('.nav-content').hide();
        $(this).find('.right_arrow').hide();
    });
});
/*设为首页*/
function SetHome(obj){
    try{
        obj.style.behavior='url(#default#homepage)';
        obj.setHomePage('http://www.51zwd.com');
    }catch(e){
        if(window.netscape){
            try{
                netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
            }catch(e){
                alert("抱歉，此操作被浏览器拒绝！\n\n请在浏览器地址栏输入“about:config”并回车然后将[signed.applets.codebase_principal_support]设置为'true'");
            }
        }else{
            alert("抱歉，您所使用的浏览器无法完成此操作。\n\n您需要手动将'http://www.51zwd.com/'设置为首页。");
        }
    }
}
/*加入收藏*/
function AddFavorite(sURL, sTitle){
    try{
        window.external.addFavorite(sURL, sTitle);
    }
    catch(e){
        try{
            window.sidebar.addPanel(sTitle, sURL, "");
        }
        catch(e){
            alert("加入收藏失败，请使用Ctrl+D进行添加");
        }
    }
}
