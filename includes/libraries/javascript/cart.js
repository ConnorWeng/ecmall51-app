function drop_cart_item(store_id, rec_id){
    var tr = $('#cart_item_' + rec_id);
    var amount_span = $('#cart' + store_id + '_amount');
    var cart_goods_kinds = $('#cart_goods_kinds');
    $.getJSON('index.php?app=cart&act=drop&rec_id=' + rec_id, function(result){
        if(result.done){
            //删除成功
            if(result.retval.cart.quantity == 0){
                window.location.reload();    //刷新
            }
            else{
                tr.remove();        //移除
                amount_span.html(price_format(result.retval.amount));  //刷新总费用
                cart_goods_kinds.html(result.retval.cart.kinds);       //刷新商品种类
				
				// psmb
				$(".mini-cart .ac strong").html(result.retval.cart.kinds);
				$("#cart_goods"+rec_id).remove();
				// end
            }
        }
    });
}
// tyioocom 批量收藏，为了避免弹出多个确认框
function batch_move_favorite(store_id,rec_id,goods_id,alt) {
	$.getJSON('index.php?app=my_favorite&act=add&type=goods&item_id=' + goods_id, function(result){
        if(result.done){
           if(alt){ // 批量收藏的时候，只弹出一次确认对话框
			   alert(result.msg);
		   }
        }
        else{
            alert(result.msg);
        }

    });
}

function move_favorite(store_id, rec_id, goods_id){
    var tr = $('#cart_item_' + rec_id);
    $.getJSON('index.php?app=my_favorite&act=add&type=goods&item_id=' + goods_id, function(result){
        //没有做收藏后的处理，比如从购物车移除
        if(result.done){
            //drop_cart_item(store_id, rec_id);
            alert(result.msg);
        }
        else{
            alert(result.msg);
        }

    });
}
function change_quantity(store_id, rec_id, spec_id, input, orig){
    var subtotal_span = $('#item' + rec_id + '_subtotal');
    var amount_span = $('#cart' + store_id + '_amount');
    //暂存为局部变量，否则如果用户输入过快有可能造成前后值不一致的问题
    var _v = input.value;
	if(_v < 1 || isNaN(_v)) {alert(lang.invalid_quantity); $(input).val($(input).attr('orig'));return false}
    $.getJSON('index.php?app=cart&act=update&spec_id=' + spec_id + '&quantity=' + _v, function(result){
        if(result.done){
            //更新成功
            $(input).attr('changed', _v);
            subtotal_span.html(price_format(result.retval.subtotal));
            subtotal_span.attr('data-price',result.retval.subtotal);
            //amount_span.html(price_format(result.retval.amount));
            resize_cart_price();
            calcu_store_fee();
        }
        else{
            //更新失败
            alert(result.msg);
            $(input).val($(input).attr('changed'));
        }
    });
}
function decrease_quantity(rec_id){
    var item = $('#input_item_' + rec_id);
    var orig = Number(item.val());
    if(orig > 1){
        item.val(orig - 1);
        item.keyup();
    }
}
function add_quantity(rec_id){
    var item = $('#input_item_' + rec_id);
    var orig = Number(item.val());
    item.val(orig + 1);
    item.keyup();
}
/**
 * 去除数组重复元素
 */
function uniqueArray(data){  
  data = data || [];  
  var a = {};  
  for (var i=0; i<data.length; i++) {  
      var v = data[i];  
      if (typeof(a[v]) == 'undefined'){  
           a[v] = 1;  
      }  
  };  
  data.length=0;  
  for (var i in a){  
       data[data.length] = i;  
  }  
  return data;  
}  

/**
 * 去除数组重复元素
 */
function uniqueArray(data){  
  data = data || [];  
  var a = {};  
  for (var i=0; i<data.length; i++) {  
      var v = data[i];  
      if (typeof(a[v]) == 'undefined'){  
           a[v] = 1;  
      }  
  };  
  data.length=0;  
  for (var i in a){  
       data[data.length] = i;  
  }  
  return data;  
}  

function resize_cart_price()
{
	if($(".goods-each input:checked").size() > 0)
	{   
		var cart_total_goods_ids ='';
		var tmp_gids=new Array();
		var cart_total_goods_amount = 0;
		$.each($(".goods-each input:checked"),function(i,h){
			cart_total_goods_amount += parseInt($(this).parent('.select').siblings('dd.subtotal').attr('data-price'));
			tmp_gids.push($(h).attr('name'));
			cart_total_goods_ids += $(this).val()+",";
		});
		//$('#cart_goods_quantity').html($(".goods-each input:checked").size());
		$('#cart_merge_amount').html(price_format(cart_total_goods_amount));
		//$('#form_order input').val(cart_total_goods_ids);
		//$('#submit_order_form').removeClass('btn-gray').addClass('btn');
		tmp_gids=uniqueArray(tmp_gids);
		if(tmp_gids.length>1)
		{
			$('.pay-together-wrapper p').show();
		}
		else
		{
			$('.pay-together-wrapper p').hide();
		}
		$("#gids").val(cart_total_goods_ids);
	}
	else
	{
		//$('#cart_goods_quantity').html(0);
		$('#cart_merge_amount').html(price_format(0));
		//$('#form_order input').val('');
		//$('#submit_order_form').removeClass('btn').addClass('btn-gray');
		$('#gids').val('');
	}	
}
function cancel_selectAll()
{
	$.each($(".goods-each input[type='checkbox']"),function(i,h){
		if(!this.checked)
			$("input[name='sellectAllGoods']").attr('checked',this.checked);
		if($(".goods-each input[type='checkbox']").size() == $(".goods-each input:checked").size())
			$("input[name='sellectAllGoods']").attr('checked',this.checked);
	});
}
/* 替换参数 */
function singleSubmit(sid,key,value)
{    
	//stop 
	if(value=='' || value== null)
	{
		alert('你未选择任何项');
		return false;
	}
	$('#order_form').submit();
    //window.open(SITE_URL + '/index.php?app=order&goods=cart&store_id='+ sid + '&'+key+"="+encodeURIComponent(value),"_blank");
    return false;
}
function multiSubmit(key,value)
{
	//stop
	if(value=='' || value== null)
	{
		alert('你未选择任何项');
		return false;
	}
	$('#order_form').submit();
    //window.open(SITE_URL + '/index.php?app=order&act=merge_order_pay&'+key+"="+encodeURIComponent(value),"_blank");
    return false;
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
