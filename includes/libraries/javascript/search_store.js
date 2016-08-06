$(function(){
	/*$('[ectype="region"]').click(function(){
		if(this.id=='' || this.id==undefined){
			dropParam('region_id');
			return false;
		}
		replaceParam('region_id', this.id);
        return false;
	});*/
	
	$.scrollIt();
	
	//custom all
	$('[ectype="market"]').click(function(){
		if(this.id=='' || this.id==undefined){
			dropParam_mk('mkid');
			return false;
		}		
		replaceParam_mk('mkid', this.id);
        return false;
	});
	
	$('[ectype="floor"]').click(function(){
		if(this.id=='' || this.id==undefined){
			dropParam('fid');
			return false;
		}
		replaceParam('fid', this.id);
        return false;
	});
	
	$('[ectype="cate"]').click(function(){
		if(this.id=='' || this.id==undefined){
			dropParam('cate_id');
			return false;
		}
		replaceParam('cate_id', this.id);
        return false;
	});
	
	$('*[ectype="service_toggle"] a').click(function(){
		if($(this).hasClass('on'))
		{
			dropParam($(this).attr('ectype'));
		}
		else
		{
			replaceParam($(this).attr('ectype'),1);
		}
		return false;
	});
	
	$('.select-param .tan li').click(function(){
		var key = $(this).parent().attr('ectype');
		var value = $(this).attr('v');
		if(value=='' || value==undefined){
			dropParam(key);
			return false;
		}
		replaceParam(key,value);
        return false;
	});
	
});


/* 替换参数 */
function replaceParam(key, value)
{
    var params = location.search.substr(1).split('&');
    var found  = false;
    for (var i = 0; i < params.length; i++)
    {
        param = params[i];
        arr   = param.split('=');
        pKey  = arr[0];
        if (pKey == 'page')
        {
            params[i] = 'page=1';
        }
        if (pKey == key)
        {
            params[i] = key + '=' + value;
            found = true;
        }
    }
    if (!found)
    {
        params.push(key + '=' + encodeURIComponent(value));
    }
    location.assign(SITE_URL + '/index.php?' + params.join('&'));
}
/* 删除参数 */
function dropParam(key)
{
    var params = location.search.substr(1).split('&');
    for (var i = 0; i < params.length; i++)
    {
        param = params[i];
        arr   = param.split('=');
        pKey  = arr[0];
        if (pKey == 'page')
        {
            params[i] = 'page=1';
        }
        if (pKey == key)
        {
            params.splice(i, 1);
        }
    }
    location.assign(SITE_URL + '/index.php?' + params.join('&'));
}
/* 替换参数,改写为点市场时，清除所有的楼层 */
function replaceParam_mk(key, value)
{
    var params = location.search.substr(1).split('&');
    var found  = false;
    for (var i = 0; i < params.length; i++)
    {
        param = params[i];
        arr   = param.split('=');
        pKey  = arr[0];
        if (pKey == 'page')
        {
            params[i] = 'page=1';
        }
        if (pKey == key)
        {
            params[i] = key + '=' + value;
            found = true;
        }
        if(pKey == 'fid')
        {
        	params.splice(i, 1);
        }
    }
    if (!found)
    {
        params.push(key + '=' + encodeURIComponent(value));
    }
    location.assign(SITE_URL + '/index.php?' + params.join('&'));
}
/*替换参数,改写为点市场时，清除所有的楼层  */
function dropParam_mk(key)
{
    var params = location.search.substr(1).split('&');
    for (var i = 0; i < params.length; i++)
    {
        param = params[i];
        arr   = param.split('=');
        pKey  = arr[0];
        if (pKey == 'page')
        {
            params[i] = 'page=1';
        }
        if (pKey == 'fid')
        {
            params.splice(i, 1);
        }
        if (pKey == key)
        {
            params.splice(i, 1);
        }
        
    }
    location.assign(SITE_URL + '/index.php?' + params.join('&'));
}

    function init()
	{
		// 创建街景
		var pano = new qq.maps.Panorama(document.getElementById('pano_holder'), {
	        //设置默认的场景
	        pano: $("#pano_holder_span").attr('data-pano-id'),
	        //设置查看视角
	        pov: {
	            heading: parseFloat($("#pano_holder_span").attr('data-heading')),  //查看器视线与正北方的水平夹角，以度为单位。
	            pitch: parseFloat($("#pano_holder_span").attr('data-pitch')),   //查看器视线与地面的夹角, 以度为单位。
	        },
	        //设置缩放级别，0<zoom<5
	        zoom: parseInt($("#pano_holder_span").attr('data-zoom')),
	        //显示移动箭头显示状态，false为显示，true为不显示
	        disableMove: false,
	        //隐藏罗盘显示状态，false为显示，true为不显示
	        disableCompass: false,
	        //设置鼠标滚轮的禁用状态，false为不可使用滚轮，默认为true
	        scrollwheel: true,
	        //设置显示街景地址控件，true为显示，默认为false，
	        addressControl: false,
	        //设置街景地址控件位置相对右上角对齐，向左排列
	        addressControlOptions: {
	            position: qq.maps.ControlPosition.TOP_RIGHT
	        },
	        //设置显示街景拍摄事件控件，true为显示，默认为false
	        photoTimeControl: true,				
			disableFullScreen:false,
	    });
	}    
    
    /**/
    function load_TecentMap(id)
    {
    	var script_TecentMap = document.createElement("script");
    	script_TecentMap.type = "text/javascript";
    	script_TecentMap.src = "http://map.qq.com/api/js?v=2.exp&key=4KJBZ-OUPE4-BR6UA-DBSZ6-ZPSHE-BNBIE&callback=init";
    	document.body.appendChild(script_TecentMap);
    }
   