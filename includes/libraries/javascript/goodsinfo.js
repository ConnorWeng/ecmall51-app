/* spec对象 */
function spec(id, spec1, spec2, price, stock)
{
    this.id    = id;
    this.spec1 = spec1;
    this.spec2 = spec2;
    this.price = price;
    this.stock = stock;
}

/* goodsspec对象 */
function goodsspec(specs, specQty, defSpec)
{
    this.specs = specs;
    this.specQty = specQty;
    this.defSpec = defSpec;
    this.spec1 = null;
    this.spec2 = null;
    if (this.specQty >= 1)
    {
        for(var i = 0; i < this.specs.length; i++)
        {
            if (this.specs[i].id == this.defSpec)
            {
                this.spec1 = this.specs[i].spec1;
                if (this.specQty >= 2)
                {
                    this.spec2 = this.specs[i].spec2;
                }
                break;
            }
        }
    }

    // 取得某字段的不重复值，如果有spec1，以此为条件
    this.getDistinctValues = function(field, spec1)
    {
        var values = new Array();
        for (var i = 0; i < this.specs.length; i++)
        {
            var value = this.specs[i][field];
            if (spec1 != '' && spec1 != this.specs[i].spec1) continue;
            if ($.inArray(value, values) < 0)
            {
                values.push(value);
            }
        }
        return (values);
    }

    // 取得选中的spec
    this.getSpec = function()
    {
    	var spec_ids = new Array();
        for (var i = 0; i < this.specs.length; i++)
        {
            if (this.specQty >= 1 && this.specs[i].spec1 == this.spec1) 
            {
            	spec_ids.push(this.specs[i].id);
            }
            else if(this.specQty < 1)
            {
            	return this.specs[i].id;
            }
            //if (this.specQty >= 2 && this.specs[i].spec2 != this.spec2) continue;
            //return this.specs[i];
        }
        if(spec_ids.length > 0)
        	return spec_ids;
        return null;
    }

    // 初始化
    this.init = function()
    {    	
        if (this.specQty >= 1)
        {
            var spec1Values = this.getDistinctValues('spec1', '');
            for (var i = 0; i < spec1Values.length; i++)
            {
                if (spec1Values[i] == this.spec1)
                {
                    $(".handle ul:eq(0)").append("<li class='solid' onclick='selectSpec(1, this)'>" + spec1Values[i] + "</li>");
                }
                else
                {
                    $(".handle ul:eq(0)").append("<li class='dotted' onclick='selectSpec(1, this)'>" + spec1Values[i] + "</li>");
                }
            }
        }
        if (this.specQty >= 2)
        {
        	for (var i = 0; i < this.specs.length; i++)
        	{
        		if (this.specs[i].spec1 == this.spec1) 
                {
        			tr = "<tr>"+
           	             "<td align='center' class='strong'>" + this.specs[i].spec2 + "</td>"+
           	             "<td align='center'>" + number_format(this.specs[i].price,2) + "</td>"+
           	             "<td align='center'>" + this.specs[i].stock + "</td>"+
           	             "<td align='center'>" + '<input type="text" class="spinnerExample" name="" id="quantity'+this.specs[i].id+'" value="0" />' + "</td>"
                         "</tr>";
           	
           	        $(".handle .spec2_table tbody").append(tr);        			
                }
        	}    
            
        }
        if(this.specQty == 1) //add by tiq 2015-07-27
        {
        	for (var i = 0; i < this.specs.length; i++)
        	{
        		if (this.specs[i].spec1 == this.spec1) 
                {
        			tr = "<tr>"+
           	             "<td align='center' class='strong'>均码</td>"+
           	             "<td align='center'>" + number_format(this.specs[i].price,2) + "</td>"+
           	             "<td align='center'>" + this.specs[i].stock + "</td>"+
           	             "<td align='center'>" + '<input type="text" class="spinnerExample" name="" id="quantity'+this.specs[i].id+'" value="0" />' + "</td>"
                         "</tr>";
           	
           	        $(".handle .spec2_table tbody").append(tr);        			
                }
        	}    
        }
      
    }
}

/* 选中某规格 num=1,2 */
function selectSpec(num, liObj)
{
    goodsspec['spec' + num] = $(liObj).html();
    $(liObj).attr("class", "solid");
    $(liObj).siblings(".solid").attr("class", "dotted");

    // 当有2种规格并且选中了第一个规格时，刷新第二个规格
    if (num == 1 && goodsspec.specQty >= 1)
    {       
        $(".handle .spec2_table tbody").html("");
        
        for (var i = 0; i < this.specs.length; i++)
    	{
    		if (this.specs[i].spec1 == goodsspec['spec1']) 
            {
    			tr = "<tr>"+
       	             "<td align='center' class='strong'>" + this.specs[i].spec2 + "</td>"+
       	             "<td align='center'>" + number_format(this.specs[i].price,2) + "</td>"+
       	             "<td align='center'>" + this.specs[i].stock + "</td>"+
       	             "<td align='center'>" + '<input type="text" class="spinnerExample" name="" id="quantity'+this.specs[i].id+'" value="0" />' + "</td>"
                     "</tr>";
       	
       	        $(".handle .spec2_table tbody").append(tr);        			
            }
    	}    
        
    }  
    $('.spinnerExample').spinner({});
}
function slideUp_fn()
{
    $('.ware_cen').slideUp('slow');
}
$(function(){
    goodsspec.init();

    //放大镜效果/
    /*if ($(".jqzoom img").attr('jqimg'))
    {
        $(".jqzoom").jqueryzoom({ xzoom: 590, yzoom: 460 });
    }*/

    // 图片替换效果
    $('.ware_box li').mouseover(function(){
        $('.ware_box li').removeClass();
        $(this).addClass('ware_pic_hover');
        $('.big_pic img').attr('src', $(this).children('img:first').attr('src'));
        $('.big_pic img').attr('jqimg', $(this).attr('bigimg'));
    });

    //点击后移动的距离
    var left_num = -61;

    //整个ul超出显示区域的尺寸
    var li_length = ($('.ware_box li').width() + 6) * $('.ware_box li').length - 425;

    $('.right_btn').click(function(){
        var posleft_num = $('.ware_box ul').position().left;
        if($('.ware_box ul').position().left > -li_length){
            $('.ware_box ul').css({'left': posleft_num + left_num});
        }
    });

    $('.left_btn').click(function(){
        var posleft_num = $('.ware_box ul').position().left;
        if($('.ware_box ul').position().left < 0){
            $('.ware_box ul').css({'left': posleft_num - left_num});
        }
    });

    // 加入购物车弹出层
    $('.close_btn').click(function(){
        $('.ware_cen').slideUp('slow');
    });
	
	// tyioocom delivery 
	/*$('.postage-cont').hover(function(){
		$(this).find('.postage-area').show();
	},function(){
		$(this).find('.postage-area').hide();
	});
	$('.province a').click(function(){
		$('.cities').find('div').hide();
		$('.cities .city_'+this.id).show();		
		$('.province').find('a').attr('class','');
		$(this).attr('class','selected');
	});
	$('.cities a').click(function(){
		$('.cities').find('a').attr('class','');
		$(this).attr('class','selected');
						
		delivery_template_id = $(this).attr('delivery_template_id');
		city_id 	= $(this).attr('city_id');
		store_id    = $(this).attr('store_id');
			
		//  加载指定城市的运费
		//load_city_logist(delivery_template_id,store_id,city_id); //传递 store_id,是为了在delivery_templaet_id 为0 的情况下，获取店铺的默认运费模板
	});*/

});

//  加载城市的运费(指定城市id或者根据ip自动判断城市id)
function load_city_logist(delivery_template_id,store_id,city_id)
{
	html = '';
	if(city_id==undefined) {
		city_id = '';
	}
	var url = SITE_URL + '/index.php?app=logist&delivery_template_id='+delivery_template_id+'&store_id='+store_id+'&city_id='+city_id;
		$.getJSON(url,function(data){
			if (data.done){
				data = data.retval;
				$.each(data.logist_fee,function(n,v){
					html += v.name+':'+v.start_fees+'元 ';
				});
				$('#selected_city').html('至&nbsp;'+data.city_name);
				$('.postage-info').html(html);
				$('.postage-area').hide();
			}
			else
			{
				$('#selected_city').html('至&nbsp;全国');
				$('.postage-info').html(data.msg);
				$('.postage-area').hide();
			}
	});
}

;(function ($) {
	  $.fn.spinner = function (opts) {
	    return this.each(function () {
	      var defaults = {value:0, min:0}
	      var options = $.extend(defaults, opts)
	      var keyCodes = {up:38, down:40}
	      var container = $('<div></div>')
	      container.addClass('spinner')
	      var textField = $(this).addClass('value').attr('maxlength', '3').val(options.value)
	        .bind('keyup paste change', function (e) {
	          var field = $(this)
	          if (e.keyCode == keyCodes.up) changeValue(1)
	          else if (e.keyCode == keyCodes.down) changeValue(-1)
	          else if (getValue(field) != container.data('lastValidValue')) validateAndTrigger(field)
	        })
	      textField.wrap(container)

	      var increaseButton = $('<button class="increase">+</button>').click(function () { changeValue(1) })
	      var decreaseButton = $('<button class="decrease">-</button>').click(function () { changeValue(-1) })

	      validate(textField)
	      container.data('lastValidValue', options.value)
	      textField.before(decreaseButton)
	      textField.after(increaseButton)

	      function changeValue(delta) {
	        textField.val(getValue() + delta)
	        validateAndTrigger(textField)
	      }

	      function validateAndTrigger(field) {
	        clearTimeout(container.data('timeout'))
	        var value = validate(field)
	        if (!isInvalid(value)) {
	          textField.trigger('update', [field, value])
	        }
	      }

	      function validate(field) {
	        var value = getValue()
	        if (value <= options.min) decreaseButton.attr('disabled', 'disabled')
	        else decreaseButton.removeAttr('disabled')
	        field.toggleClass('invalid', isInvalid(value)).toggleClass('passive', value === 0)

	        if (isInvalid(value)) {
	          var timeout = setTimeout(function () {
	            textField.val(container.data('lastValidValue'))
	            validate(field)
	          }, 500)
	          container.data('timeout', timeout)
	        } else {
	          container.data('lastValidValue', value)
	        }
	        return value
	      }

	      function isInvalid(value) { return isNaN(+value) || value < options.min; }

	      function getValue(field) {
	        field = field || textField;
	        return parseInt(field.val() || 0, 10)
	      }
	    })
	  }
	})(jQuery)
	