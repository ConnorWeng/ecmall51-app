$(function(){	
	/* 显示全部分类 */
   /* $("#show_category").click(function(){
        $("ul[ectype='ul_category'] li").show();
        $(this).hide();
    });*/

    /* 显示全部品牌 */
    /*$("#show_brand").click(function(){
        $("ul[ectype='ul_brand'] li").show();
        $(this).hide();
    });*/

    /* 自定义价格区间 */
    /*$("#set_price_interval").click(function(){
        $("ul[ectype='ul_price'] li").show();
        $(this).hide();
    });*/

    /* 显示全部地区 */
    /*$("#show_region").click(function(){
        $("ul[ectype='ul_region'] li").show();
        $(this).hide();
    });*/

    /* 筛选事件 */
    /*$("ul[ectype='ul_category'] a").click(function(){
        replaceParam('cate_id', this.id);
        return false;
    });*/

   /* $("li[ectype='li_filter'] img").click(function(){
        dropParam(this.title);
        return false;
    });	*/
	
    /*$("[ectype='order_by']").change(function(){
        replaceParam('order', this.value);
        return false;
    });*/
	
    /* 下拉过滤器 */
   /* $("li[ectype='dropdown_filter_title'] a").click(function(){
        var jq_li = $(this).parents("li[ectype='dropdown_filter_title']");
        var status = jq_li.find("img").attr("src") == upimg ? 'off' : 'on';
        switch_filter(jq_li.attr("ecvalue"), status)
    });*/

	/************** by psmb **************/
	
   /* if($.getCookie("goodsDisplayMode")) {
		$(".display_mod #"+$.getCookie('goodsDisplayMode')).addClass('filter-'+$.getCookie("goodsDisplayMode")+'-cur');
	} else {
		$(".display_mod #squares").addClass('filter-squares-cur');
	}*/
	/*$(".display_mod a").click(function(){
		$("div[ectype='current_display_mode']").attr("class",this.id + " clearfix");
		$(".display_mod a").each(function(){
			$(this).removeClass('filter-'+this.id+'-cur');
		});
		$(".display_mod #"+this.id).addClass('filter-'+this.id+'-cur');
		$.setCookie('goodsDisplayMode', this.id);
	});*/
	
	/*$('.sub-images img').hover(function(){
		$(this).parent().find('img').each(function(){
			$(this).css('border','1px #ddd solid');
		});
		$(this).css('border','1px #BF1B30 solid');
		$('.dl-'+$(this).attr('goods_id')).find('dt img').attr('src',$(this).attr('image_url'));
	});*/
	//为了适用瀑布流加载多图切换
	/*$('.sub-images img').live('mouseover',function(){
		$(this).parent().find('img').each(function(){
			$(this).css('border','1px #ddd solid');
		});
		$(this).css('border','1px #BF1B30 solid');
		$('.dl-'+$(this).attr('goods_id')).find('dt img').attr('src',$(this).attr('data-ks-lazyload'));
	});*/
	
	/*$("*[ectype='ul_cate'] a").click(function(){
        replaceParam('cate_id', this.id);
        return false;
    });*/
    /*$("*[ectype='ul_brand'] a").click(function(){
        replaceParam('brand', this.id);
        return false;
    });*/
	
    /*$("*[ectype='ul_price'] a").click(function(){
        replaceParam('price', this.id);
        return false;
    });*/
    
    $("*[ectype='ul_search_category'] a").click(function(){
        replaceParam('cate_id', this.id);
        return false;
    });
	//custom mall
	 //market
     $("*[ectype='ul_market'] a").click(function(){
        replaceParam('mkid', this.id);
        return false;
     });
	 //market floor
	 $("*[ectype='ul_market_floor'] a").click(function(){
        replaceParam('fid', this.id);
        return false;
     });
	//time choice
	 $("*[ectype='ul_time'] a.nstyle").click(function(){
		if(this.id == '111')
		{
			dropParam('qt');
			return false;
		}
        replaceParam('qt', this.id);
        return false;
     });
	 //color choice
	 $("$[ectype='ul_color'] a").click(function(){
		if(this.id == 'all_color_id')
		{
			dropParam('color');
			return false;
		}
		replaceParam('color',$(this).text());
		return false;
	 });
	 $("$[ectype='ul_color'] input[type='button']").click(function(){
		 var color_keyword = $(this).siblings("input[name='color_kw']").val();
		 if( color_keyword == "")
		 {
			 alert("关键字不能为空");
			 return false;
		 }
		 replaceParam('color',color_keyword);
		 return false;
	 });
	 //size choice
	 $("$[ectype='ul_size'] a").click(function(){
			if(this.id == 'all_size_id')
			{
				dropParam('dim');
				return false;
			}
			replaceParam('dim',$(this).text());
			return false;
		 });
		 $("$[ectype='ul_size'] input[type='button']").click(function(){
			 var size_keyword = $(this).siblings("input[name='color_sz']").val();
			 if( size_keyword == "")
			 {
				 alert("关键字不能为空");
				 return false;
			 }
			 replaceParam('dim',size_keyword);
			 return false;
		 });
	 //service type
     $("*[ectype='ul_service_type'] a").click(function(){

		if($(this).hasClass('on')){
           dropParam(this.id);
		}
		else{			
			replaceParam(this.id,$(this).attr('ectype'));
		}
        return false;
     });
     //新款日期
     $("*[ectype='ul_newstyletime'] a").click(function(){
    	 replaceParam('qt',$(this).text());
    	 return false;
     });
	 //show more dates
	 /*$('#show-more-dates').click(function(){
	   $(this).siblings('.toggle').toggle();
	 });

    $("*[ectype='ul_date'] a.date").click(function(){
        replaceParam('agt', this.id);
        return false;
    });*/


/*
    $("#search_by_price").click(function(){
        replaceParam('price', $(this).siblings("input:first").val() + '-' + $(this).siblings("input:last").val());
        return false;
    });
    $("*[ectype='ul_region'] a").click(function(){
        replaceParam('region_id', this.id);
        return false;
    });*/
	
	$(".selected-attr a").click(function(){
		dropParam(this.id);
		return false;
	});
	
	$('.filter-price .ui-btn-s-primary').click(function(){
		start_price = number_format($(this).parent().find('input[name="start_price"]').val(),0);
		end_price   = number_format($(this).parent().find('input[name="end_price"]').val(),0);	
		
		if(Number(start_price) >= Number(end_price)){
			end_price = Number(start_price) + 200;
		}
		
		replaceParam('price', start_price+'-'+end_price);       
		return false;
	});

	
	/*************** by psmb -- end ***************/
	
});

/** 打开/关闭过滤器
 *  参数 filter 过滤器   brand | price | region
 *  参数 status 目标状态 on | off
 */
function switch_filter(filter, status)
{
    $("li[ectype='dropdown_filter_title']").attr('class', 'normal');
    $("li[ectype='dropdown_filter_title'] img").attr('src', downimg);
    $("div[ectype='dropdown_filter_content']").hide();

    if (status == 'on')
    {
        $("li[ectype='dropdown_filter_title'][ecvalue='" + filter + "']").attr('class', 'active');
        $("li[ectype='dropdown_filter_title'][ecvalue='" + filter + "'] img").attr('src', upimg);
        $("div[ectype='dropdown_filter_content'][ecvalue='" + filter + "']").show();
    }
}

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
        value = transform_char(value);
        params.push(key + '=' + value);
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
