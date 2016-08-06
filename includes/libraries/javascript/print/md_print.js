$(function(){
	
	initDG();
	getPrinterList();
	 //按条件进行查询数据，首先我们得到数据的值
    $("#btnSearch").click(function () {
        //得到用户输入的参数，取值有几种方式：$("#id").combobox('getValue'), $("#id").datebox('getValue'), $("#id").val()
        //字段增加WHC_前缀字符，避免传递如URL这样的Request关键字冲突
        var queryData = {
            order_sn: $("#order_sn").val(),
            type: $("input[name='type']").val(),
            exp_delivery: $("input[name='delivery']").val(),
            goods_seller_bm:$('#seller_bm').val(),
            add_time_from:$("input[name='from_timeto']").val(),
            add_time_to:$("input[name='to_timeto']").val(),
            buyer_name:$('#buyer_name').val()
        };
        //将值传递给
        initDG(queryData);
        return false;
    });
	
	
	
	$('body').layout();
});

function initDG(queryData)
{
	$('#dg').datagrid({
		url:'index.php?app=behalf_print&act=get_orders',
		title:'订单列表',
		iconCls:'icon-table',
		//width:function(){ return document.body.clientWidth * 0.9 },
		//height:560,
		//height:function(){ return document.body.clientHeight * 0.9 },
		fitColumns:true,
		fit:true,
		nowrap:false,
		autoRowHeight:false,
		striped:true,
		collapsible:true,
		pagination:true,
		pagePosition:'bottom',
		pageSize:10,
		pageList:[10,20,50,100,200],
		rownumbers:true,
		//singleSelect:true,
		sortOrder: 'asc',
		remoteSort:false,
		idField:'order_id',
		queryParams:queryData,
		columns:[[
		        {field:'order_id',checkbox:true},
		        {title:'订单编号',field:'order_sn',width:45,resizable:false},
		        {title:'收件人',field:'consignee',width:35,resizable:false},
		        {title:'收件人省市区',field:'region_id',width:80,editor:'textbox'},
		        //{title:'收件人省市区',field:'region_name',width:80,editor:'textbox'},
		        {title:'收件人详细地址',field:'address',width:80,editor:'textbox'},
		        {title:'收件人固话',field:'phone_tel',width:50,resizable:false,hidden:true},
		        {title:'收件人手机',field:'phone_mob',width:50,resizable:false},
		        {title:'收件邮编',field:'zipcode',width:40,resizable:false},
		        {title:'快递公司',field:'dl_name',width:35,resizable:false},
		        {title:'物流单号',field:'invoice_no',width:60,resizable:false},
		        {title:'内装货品',field:'goods_amount',width:150,sortable:true},
		        {title:'支付时间',field:'pay_time',width:150,hidden:true},
		        {title:'订单状态',field:'status', width:30,formatter:function(val,row,index){
		        	if(val == 11)	return '已付款';
		        	if(val == 20)	return '待发货';
		        	if(val == 30)	return '已发货';
		        	if(val == 40)	return '已完成';
		        	if(val == 0)	return '已取消';
		        }},
		        {title:'操作',field:'order_id1', width:40 ,formatter: function (val, order, index) {
                        return '<a class="grid_visible" href="javascript:print_one(\'' + jjname+'\',\''+jjtel+'\',\''+
                        order.consignee +'\',\''+ order.phone_mob+'\',\''+jjadr+'\',\''+order.region_id+' '+order.address+'\',\''+
                        order.order_sn+'\',\''+order.invoice_no+'\',\''+order.region_id+'\',\''+order.goods_amount + '\',\''+ order.pay_time +'\');" >打印预览</a>';
                }}
		          
		          ]],
		    toolbar:'#query',
			onDblClickCell: function(index,field,value){
				$(this).datagrid('beginEdit', index);
				var ed = $(this).datagrid('getEditor', {index:index,field:field});
				$(ed.target).focus();
			},
		    onClickRow:function(index,row){
		    	$('#dg').datagrid('acceptChanges');
		    }    
	});
	
}

function CreatePrintPage(strJJName,strJJTel,strSJName,strSJTel,strJJAdr,strSJAdr,strOrder_sn,strInvocie,strDestCity,strGoodsInfo,strPaytime)
{
	  LODOP.NewPage();
	  LODOP.ADD_PRINT_SETUP_BKIMG("<img src='"+SITE_URL+"/data/system/printTemplate/yuantong/ytmd.jpg"+"' border=0/>"); 
	  LODOP.SET_SHOW_MODE("BKIMG_WIDTH","100mm"); 
	  LODOP.SET_SHOW_MODE("BKIMG_HEIGHT","180mm"); 
	  
	  LODOP.ADD_PRINT_TEXT(4,26,331,56,strDestCity);
	  LODOP.SET_PRINT_STYLEA(0,"FontSize",15);
	  LODOP.SET_PRINT_STYLEA(0,"Bold",1);
	  LODOP.ADD_PRINT_TEXT(81,122,143,25,strOrder_sn);
	  LODOP.SET_PRINT_STYLEA(0,"FontSize",14);
	  LODOP.ADD_PRINT_LINE(116,-5,117,374,2,1);
	  LODOP.ADD_PRINT_BARCODE(126,102,198,43,"Code39",strInvocie);
	  LODOP.ADD_PRINT_LINE(179,-1,180,378,2,1);
	  LODOP.ADD_PRINT_TEXT(189,7,58,20,"收件人：");
	  LODOP.ADD_PRINT_TEXT(190,76,106,25,strSJName);
	  LODOP.SET_PRINT_STYLEA(0,"FontSize",14);
	  LODOP.ADD_PRINT_TEXT(189,185,158,20,strSJTel);
	  LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	  LODOP.ADD_PRINT_LINE(245,-37,246,342,2,1);
	  LODOP.ADD_PRINT_LINE(315,-35,316,344,2,1);
	  LODOP.ADD_PRINT_LINE(185,347,385,348,2,1);
	  LODOP.ADD_PRINT_TEXT(248,353,24,60,"签收联");
	  LODOP.ADD_PRINT_TEXT(259,2,58,20,"寄件人：");
	  LODOP.ADD_PRINT_TEXT(259,67,75,20,strJJName);
	  LODOP.SET_PRINT_STYLEA(0,"FontSize",11);
	  LODOP.ADD_PRINT_TEXT(259,153,157,20,strJJAdr);
	  LODOP.ADD_PRINT_TEXT(285,67,77,20,strJJTel);
	  LODOP.ADD_PRINT_TEXT(324,5,105,20,"收件人/代收人：");
	  LODOP.ADD_PRINT_LINE(318,167,384,168,2,1);
	  LODOP.ADD_PRINT_TEXT(324,173,105,20,"签收时间：");
	  LODOP.ADD_PRINT_TEXT(364,219,105,20,"年  月  日");
	  LODOP.ADD_PRINT_BARCODE(398,162,198,43,"Code39",strInvocie);
	  LODOP.ADD_PRINT_TEXT(455,10,58,20,"寄件人：");
	  LODOP.ADD_PRINT_TEXT(455,75,75,20,strJJName);
	  LODOP.SET_PRINT_STYLEA(0,"FontSize",11);
	  LODOP.ADD_PRINT_TEXT(455,161,142,20,strJJAdr);
	  LODOP.ADD_PRINT_TEXT(473,75,82,20,strJJTel);
	  LODOP.ADD_PRINT_TEXT(503,353,24,60,"收件联");
	  LODOP.ADD_PRINT_LINE(454,347,672,348,2,1);
	  LODOP.ADD_PRINT_LINE(503,-37,504,342,2,1);
	  LODOP.ADD_PRINT_TEXT(506,9,58,20,"收件人：");
	  LODOP.ADD_PRINT_TEXT(507,78,75,20,strSJName);
	  LODOP.SET_PRINT_STYLEA(0,"FontSize",11);
	  LODOP.ADD_PRINT_TEXT(508,155,189,20,strSJTel);
	  LODOP.ADD_PRINT_TEXT(529,78,264,37,strSJAdr);
	  LODOP.ADD_PRINT_TEXT(568,6,338,97,strGoodsInfo);
	  LODOP.ADD_PRINT_TEXT(280,152,100,20,strPaytime);
	  LODOP.ADD_PRINT_TEXT(473,158,100,20,strPaytime);
	  //LODOP.ADD_PRINT_RECT(589,4,335,87,0,1);

	 /*  LODOP.ADD_PRINT_TEXT(83,111,120,26,strJJName); //寄件人姓名
	  LODOP.ADD_PRINT_TEXT(80,282,116,26,strJJTel); //寄件人电话
	  LODOP.ADD_PRINT_TEXT(76,468,118,27,strSJName); //收件人姓名
	  LODOP.ADD_PRINT_TEXT(77,636,111,27,strSJTel); //收货人电话
	  LODOP.ADD_PRINT_TEXT(166,71,328,51,strJJAdr); //寄件人地址
	  LODOP.ADD_PRINT_TEXT(164,435,319,46,strSJAdr);//收件人地址
	  LODOP.ADD_PRINT_TEXT(253,122,152,25,strNJPinm);//内件品名
	  LODOP.ADD_PRINT_TEXT(251,322,69,25,strNJCount); //内件数量 */
}
/*
 * strPaytime 本地当前时间
 */
function createZtoPrintPage(strJJName,strJJTel,strSJName,strSJTel,strJJAdr,strSJAdr,strOrder_sn,strInvocie,strDestCity,strGoodsInfo,strPaytime)
{
	LODOP.NewPage();
	LODOP.ADD_PRINT_SETUP_BKIMG("<img src='"+SITE_URL+"/data/system/printTemplate/zto/md100_180.jpg"+"' border=0/>"); 
	LODOP.SET_SHOW_MODE("BKIMG_WIDTH","100mm"); 
	LODOP.SET_SHOW_MODE("BKIMG_HEIGHT","180mm");
	LODOP.SET_PRINT_STYLE("FontName","微软雅黑");
	LODOP.ADD_PRINT_TEXT(37,15,45,20,"日期：");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",8);
	LODOP.ADD_PRINT_TEXT(37,48,120,20,strPaytime);
	LODOP.ADD_PRINT_TEXT(125,11,75,20,"寄件人：");
	//LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(125,65,95,20,strJJName);
	//LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(125,165,112,20,"联系方式：");
	//LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(125,225,135,20,strJJTel);
	//LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(146,11,65,20,"地址：");
	//LODOP.SET_PRINT_STYLEA(0,"FontSize",11);
	LODOP.ADD_PRINT_TEXT(146,63,235,20,strJJAdr);
	//LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(58,11,75,20,"收件人：");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(58,67,115,20,strSJName);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(58,184,81,20,"电话：");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(58,228,130,25,strSJTel);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(78,67,300,40,strSJAdr);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(78,11,80,20,"地  址：");
	//LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(172,12,361,105,strGoodsInfo);
	//LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(300,20,345,60,strDestCity);
	LODOP.SET_PRINT_STYLEA(0,"FontName","黑体");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",18);
	LODOP.SET_PRINT_STYLEA(0,"Alignment",2);
	//LODOP.SET_PRINT_STYLEA(0,"Bold",1);
	LODOP.ADD_PRINT_BARCODE(363,42,295,45,"Code39",strInvocie);
	LODOP.ADD_PRINT_BARCODE(427,194,182,45,"Code39",strInvocie);
	LODOP.ADD_PRINT_TEXT(554,9,70,20,"寄件人：");
	LODOP.ADD_PRINT_TEXT(554,55,90,20,strJJName);
	LODOP.ADD_PRINT_TEXT(554,204,80,20,"联系方式：");
	LODOP.ADD_PRINT_TEXT(552,259,100,20,strJJTel);
	LODOP.ADD_PRINT_TEXT(570,56,251,20,strJJAdr);
	LODOP.ADD_PRINT_TEXT(570,9,50,20,"地址：");
	LODOP.ADD_PRINT_TEXT(461,15,45,20,"日期：");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",8);
	LODOP.ADD_PRINT_TEXT(461,49,120,20,strPaytime);
	LODOP.ADD_PRINT_TEXT(485,11,75,20,"收件人：");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(485,65,115,20,strSJName);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(485,182,75,20,"电话：");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(485,225,145,20,strSJTel);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(504,63,305,40,strSJAdr);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(504,11,75,20,"地  址：");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(611,10,201,50,"您对此单的签收，代表您已验货，确认货品信息无损，包装完好，没有划痕、破损等表面质量问题。");
	LODOP.ADD_PRINT_TEXT(615,222,100,20,"签收：");
	//LODOP.SET_PRINT_STYLEA(0,"FontSize",9);
	LODOP.ADD_PRINT_LINE(52,6,53,371,0,1);
	LODOP.ADD_PRINT_LINE(119,6,120,371,0,1);
	LODOP.ADD_PRINT_LINE(293,6,294,371,0,1);
	LODOP.ADD_PRINT_LINE(355,6,356,371,0,1);
	LODOP.ADD_PRINT_LINE(481,6,482,371,0,1);
	LODOP.ADD_PRINT_LINE(547,6,548,371,0,1);
	LODOP.ADD_PRINT_LINE(602,5,603,370,0,1);
	LODOP.ADD_PRINT_LINE(673,216,602,216,0,1);
	LODOP.ADD_PRINT_TEXT(18,257,100,35,"已验视");
	LODOP.SET_PRINT_STYLEA(0,"FontName","黑体");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",18);
	//LODOP.SET_PRINT_STYLEA(0,"Bold",1);
	LODOP.ADD_PRINT_LINE(167,9,166,373,0,1);
	LODOP.ADD_PRINT_TEXT(576,203,90,20,"订单号:");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(573,247,120,25,strOrder_sn);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",14);
	LODOP.ADD_PRINT_TEXT(35,145,100,25,"51仓库");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	//LODOP.ADD_PRINT_SHAPE(4,418,5,365,3,0,1,"#000000");

	


}

/**
 * 打印
 * @param jjname
 * @param jjtel
 * @param consignee
 * @param phone_mob
 * @param jjadr
 * @param address
 * @param order_sn
 * @param invoice_no
 * @param region_name
 * @param goods_amount
 */
function print_one(jjname,jjtel,consignee,phone_mob,jjadr,address,order_sn,invoice_no,region_name,goods_amount,pay_time)
{
	var printFlag = $("input[name='template']").val();
	if(printFlag == 'yto')
	{
		LODOP.PRINT_INITA(0,0,"100mm","180mm","套打圆通的模板"); 
		LODOP.SET_PRINT_PAGESIZE(1,'100mm','180mm','');	
		LODOP.SET_SHOW_MODE("BKIMG_IN_PREVIEW",1);
		CreatePrintPage(jjname,jjtel,consignee,phone_mob,jjadr,address,order_sn,invoice_no,region_name,goods_amount,pay_time);
	}
	else if(printFlag == 'zto')
	{
		LODOP.PRINT_INITA("-2mm","-2mm","100mm","180mm","套打中通的模板"); 
		LODOP.SET_PRINT_PAGESIZE(1,'100mm','180mm','');	
		LODOP.SET_SHOW_MODE("BKIMG_IN_PREVIEW",1);
		//LODOP.SET_PRINT_MODE("POS_BASEON_PAPER",true);
		//LODOP.SET_PRINT_STYLEA(0,'Stretch',1);
		createZtoPrintPage(jjname,jjtel,consignee,phone_mob,jjadr,address,order_sn,invoice_no,region_name,goods_amount,pay_time);
	}
	
	LODOP.PREVIEW();
	//LODOP.PRINT_DESIGN();
}

function print_all()
{
	var sRowsData = $('#dg').datagrid('getChecked');
	var sLen = sRowsData.length;
	var print_step = 2;
	var print_task_rest = sLen % print_step;
	var print_task_num = parseInt(sLen / print_step);
	if(sLen == 0)
	{
		$.messager.alert('警告','未选中任何项!','warning');
		return;
	}
	$.messager.confirm('提示','您选择了'+sLen+'个订单，确定直接打印？',function(r){if(r){
		var printFlag = $("input[name='template']").val();
		var printer = $("input[name='printer']").val();
		if(printer == null || printer == undefined)
		{
			$.messager.alert('警告','没有打印设备，连接后再试！',"warning");
			return;
		}
		if(printFlag == 'yto')
		{
		   for(var $t=0; $t <= print_task_num; $t++)
		   {
			    LODOP.PRINT_INITA(0,0,"100mm","180mm","套打圆通的模板"); 
				LODOP.SET_PRINT_PAGESIZE(1,'100mm','180mm','');	
				LODOP.SET_SHOW_MODE("BKIMG_IN_PREVIEW",1);
				$start_print = $t * print_step;
				$end_print = $t == print_task_num ? ($t * print_step + print_task_rest):($t * print_step + print_step);
				for(var $i= $start_print;$i< $end_print; $i++)
				{
					if(sRowsData[$i].invoice_no != null && sRowsData[$i].invoice_no != ' ')
						CreatePrintPage(jjname,jjtel,sRowsData[$i].consignee,sRowsData[$i].phone_mob+'/'+sRowsData[$i].phone_tel,jjadr,sRowsData[$i].region_id+' '+sRowsData[$i].address,sRowsData[$i].order_sn,sRowsData[$i].invoice_no,sRowsData[$i].region_id,sRowsData[$i].goods_amount,sRowsData[$i].pay_time);
				}
				LODOP.SET_PRINT_MODE("CUSTOM_TASK_NAME","套打圆通的模板" + $t);//为每个打印单独设置任务名
				LODOP.SET_PRINTER_INDEX(printer);
				LODOP.PRINT();
		   }
			
		}
		else if(printFlag == 'zto')
		{
			for(var $t=0; $t <= print_task_num; $t++)
			{
				LODOP.PRINT_INITA("-2mm","-2mm","100mm","180mm","套打中通的模板"); 
				LODOP.SET_PRINT_PAGESIZE(1,'100mm','180mm','');	
				LODOP.SET_SHOW_MODE("BKIMG_IN_PREVIEW",1);
				$start_print = $t * print_step;
				$end_print = $t == print_task_num ? ($t * print_step + print_task_rest):($t * print_step + print_step);
				for(var $i= $start_print;$i< $end_print; $i++)
				{
					if(sRowsData[$i].invoice_no != null && sRowsData[$i].invoice_no != ' ')
						createZtoPrintPage(jjname,jjtel,sRowsData[$i].consignee,sRowsData[$i].phone_mob,jjadr,sRowsData[$i].region_id+' '+sRowsData[$i].address,sRowsData[$i].order_sn,sRowsData[$i].invoice_no,sRowsData[$i].region_id,sRowsData[$i].goods_amount,sRowsData[$i].pay_time);
				}
				LODOP.SET_PRINT_MODE("CUSTOM_TASK_NAME","套打中通的模板" + $t);//为每个打印单独设置任务名
				//LODOP.SET_PRINT_MODE("POS_BASEON_PAPER",true);
				//LODOP. SELECT_PRINTER();
				LODOP.SET_PRINTER_INDEX(printer);
				LODOP.PRINT();
			}
		}		
		
		//LODOP.PREVIEW();
		
	}else{return false;}});
	
}

function restore_invoice()
{
	var sRowsData = $('#dg').datagrid('getChecked');
	var sLen = sRowsData.length;
	if(sLen == 0)
	{
		$.messager.alert('警告','未选中任何项!','warning');
		return;
	}
	var idsarr = new Array();
	for(var $i=0;$i<sLen;$i++)
	{
		idsarr.push(sRowsData[$i].order_id);
	}
	$.post('index.php?app=behalf_print&act=get_invoice_no',{ids:idsarr},function(data){
		$('#dg').datagrid('reload');
		data = eval('('+data+')');
		$.messager.alert('警告',data.msg,'warning');	  
	});
}

function async_ship()
{
	var sRowsData = $('#dg').datagrid('getChecked');
	var sLen = sRowsData.length;
	if(sLen == 0)
	{
		$.messager.alert('警告','未选中任何项!','warning');
		return;
	}
	var idsarr = new Array();
	for(var $i=0;$i<sLen;$i++)
	{
		idsarr.push(sRowsData[$i].order_id);
	}
	$.post('index.php?app=behalf_print&act=async_shipped',{ids:idsarr},function(data){
		$('#dg').datagrid('reload');
		data = eval('('+data+')');
		if(!data.done)
	    {			
			$.messager.alert('警告',data.msg,'warning');
	    }
		
	});
}

function template_setup()
{
	$('#dlg_template_setup').dialog('open');
}

function get_failinfo()
{
	var sRowsData = $('#dg').datagrid('getChecked');
	var sLen = sRowsData.length;
	if(sLen == 0)
	{
		$.messager.alert('警告','未选中任何项!','warning');
		return;
	}
	if(sLen > 1)
	{
		$.messager.alert('警告','只能选择一项!','warning');
		return;
	}
	$.post('index.php?app=behalf_print&act=get_failinfo',{ids:sRowsData[0].order_id},function(data){
		data = eval('('+data+')');
		$.messager.alert('警告',data.msg,'warning');
	    
	});
}

function cancel_invoice()
{
	var sRowsData = $('#dg').datagrid('getChecked');
	var sLen = sRowsData.length;
	if(sLen == 0)
	{
		$.messager.alert('警告','未选中任何项!','warning');
		return;
	}
	var idsarr = new Array();
	for(var $i=0;$i<sLen;$i++)
	{
		idsarr.push(sRowsData[$i].order_id);
	}
	$.post('index.php?app=behalf_print&act=cancel_invoice',{ids:idsarr},function(data){
		$('#dg').datagrid('reload');
		data = eval('('+data+')');
		$.messager.alert('警告',data.msg,'warning');
	});
}

function clearForm()
{
	$('#query_form').form('clear');
	$('#btnSearch').click();
}

//当前时间
function CurentTime()
{ 
    var now = new Date();
   
    var year = now.getFullYear();       //年
    var month = now.getMonth() + 1;     //月
    var day = now.getDate();            //日
   
    var hh = now.getHours();            //时
    var mm = now.getMinutes();          //分
   
    var clock = year + "-";
   
    if(month < 10)
        clock += "0";
   
    clock += month + "-";
   
    if(day < 10)
        clock += "0";
       
    clock += day + " ";
   
    if(hh < 10)
        clock += "0";
       
    clock += hh + ":";
    if (mm < 10) clock += '0'; 
    clock += mm; 
    return(clock); 
} 

function getPrinterList()
{
	var iCount=LODOP.GET_PRINTER_COUNT();
	if(iCount == 0)
	{
		return;
	}
	var printer_list = new Array();
	for(var $i=0; $i<iCount;$i++)
	{
		printer_list.push(LODOP.GET_PRINTER_NAME($i));
	}
	var listHtml = "当前打印机:<select name='printer' id='printer_combobox' style='line-height:22px;' >";
	for(var $count=0;$count<iCount;$count++)
	{
		listHtml += "<option value="+ $count +">" + printer_list[$count]+"</option>";
	}
	listHtml += "</select>";
	$("#printer_equipment").html(listHtml);	
	$("#printer_combobox").combobox();
}


