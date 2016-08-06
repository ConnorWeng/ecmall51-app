$(function(){
	
	initDG();
	
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
		        {title:'收件人详细地址',field:'address',width:80,editor:'textbox'},
		        {title:'收件人固话',field:'phone_tel',width:50,resizable:false,hidden:true},
		        {title:'收件人手机',field:'phone_mob',width:50,resizable:false},
		        {title:'收件邮编',field:'zipcode',width:40,resizable:false},
		        {title:'快递公司',field:'dl_name',width:35,resizable:false},
		        {title:'物流单号',field:'invoice_no',width:60,resizable:false,editor:'textbox'},
		        {title:'内装货品',field:'goods_amount',width:150},
		        {title:'内装货品',field:'postscript',width:150,hidden:true},
		        {title:'支付时间',field:'pay_time',width:150,hidden:true},
		        {title:'订单状态',field:'status', width:30,formatter:function(val,row,index){
		        	if(val == 11)	return '已付款';
		        	if(val == 20)	return '待发货';
		        	if(val == 30)	return '已发货';
		        	if(val == 40)	return '已完成';
		        	if(val == 0)	return '已取消';
		        }},
		        {title:'操作',field:'order_id1', width:40 ,formatter: function (val, order, index) {
                        return '<a class="grid_visible" href="javascript:print_row(\'' + jjname+'\',\''+
                        order.goods_amount +'\',\''+ jjtel+'\',\''+ order.order_sn +'\',\''+order.consignee+'\',\''+
                        order.region_id+' '+order.address+'\',\''+order.phone_mob+'/'+order.phone_tel+'\',\''+order.region_id+'\',\''+order.pay_time + '\');" >打印</a>';
                }}
		        
		          ]],
		    toolbar:'#query',
			onDblClickCell: function(index,field,value){
				$(this).datagrid('beginEdit', index);
				var ed = $(this).datagrid('getEditor', {index:index,field:field});
				$(ed.target).focus();
			},
		    onUnselect:function(index,row){
		    	$('#dg').datagrid('acceptChanges');
		    }    
	});
	
}

function CreatePrintPage(Flag,FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime)
{
	  LODOP.NewPage();
	  //LODOP.SET_PRINT_STYLE('Stretch',2);
	  LODOP.SET_SHOW_MODE("BKIMG_WIDTH",869); 
	  LODOP.SET_SHOW_MODE("BKIMG_HEIGHT",480);
	  if(Flag == 'yto')
	  {
		  LODOP.ADD_PRINT_SETUP_BKIMG("<img src='"+SITE_URL+"/data/system/printTemplate/yuantong/default.jpg"+"' border=0/>"); 
		  createYtoPage(FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime);
	  }
	  if(Flag == 'zto')
	  {
		  LODOP.ADD_PRINT_SETUP_BKIMG("<img src='"+SITE_URL+"/data/system/printTemplate/zto/default.jpg"+"' border=0/>"); 
		  createZtoPage(FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime);
	  }
	  if(Flag == 'sto')
	  {
		  LODOP.ADD_PRINT_SETUP_BKIMG("<img src='"+SITE_URL+"/data/system/printTemplate/sto/default.jpg"+"' border=0/>"); 
		  createStoPage(FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime);
	  }
	   
}

/*function createYtoPage(FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime)
{
	LODOP.ADD_PRINT_TEXT(93,123,102,24,FJName);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(117,73,331,104,GoodsInfo);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",11);
	LODOP.ADD_PRINT_TEXT(221,158,107,20,FJTel);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(371,70,321,28,Order_sn);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(106,487,87,26,SJName);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(136,485,282,90,SJAdr);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(225,483,246,24,SJTel);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(299,421,367,161,SJRemark);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",15);
	LODOP.SET_PRINT_STYLEA(0,"Bold",1);
	LODOP.ADD_PRINT_TEXT(235,158,105,20,Paytime);
}*/

/*function createZtoPage(FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime)
{
	LODOP.ADD_PRINT_TEXT(108,127,112,24,FJName);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(135,72,331,114,GoodsInfo);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",11);
	LODOP.ADD_PRINT_TEXT(252,81,107,20,FJTel);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(381,77,180,28,Order_sn);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(105,478,112,26,SJName);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(140,476,282,75,SJAdr);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(221,480,180,24,SJTel);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(268,438,307,146,SJRemark);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",15);
	LODOP.SET_PRINT_STYLEA(0,"Bold",1);
	LODOP.ADD_PRINT_TEXT(271,95,105,20,Paytime);
	LODOP.ADD_PRINT_TEXT(302,297,100,35,"已验视");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",18);
	LODOP.SET_PRINT_STYLEA(0,"Bold",1);
}

function createStoPage(FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime)
{
	LODOP.ADD_PRINT_TEXT(105,126,112,24,FJName);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(130,74,331,109,GoodsInfo);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",11);
	LODOP.ADD_PRINT_TEXT(241,156,107,20,FJTel);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",10);
	LODOP.ADD_PRINT_TEXT(364,78,201,28,Order_sn);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(105,493,112,26,SJName);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(140,484,282,95,SJAdr);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(245,517,246,24,SJTel);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(272,418,342,141,SJRemark);
	LODOP.SET_PRINT_STYLEA(0,"FontSize",15);
	LODOP.SET_PRINT_STYLEA(0,"Bold",1);
	LODOP.ADD_PRINT_TEXT(256,158,105,20,Paytime);
	LODOP.ADD_PRINT_TEXT(330,283,110,55,"已验视\n2059");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",18);
	LODOP.SET_PRINT_STYLEA(0,"Bold",1);
	LODOP.ADD_PRINT_TEXT(410,699,100,35,"4.6");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",17);


}*/

function print_row(FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime)
{
	var Flag = $("input[name='template']").val();
	print_one(Flag,FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime);
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
function print_one(Flag,FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime)
{
	  if(Flag == 'yto')
	  {
		  LODOP.PRINT_INITA(0,0,"230mm","127mm","套打圆通的模板");		  
	  }
	  if(Flag == 'zto')
	  {
		  LODOP.PRINT_INITA(0,0,"230mm","127mm","套打中通的模板");		 
	  }
	  if(Flag == 'sto')
	  {
		  LODOP.PRINT_INITA(0,0,"230mm","127mm","套打申通的模板");		  
	  }
	  LODOP.SET_PRINT_PAGESIZE(1,2300,1270,'');
	  CreatePrintPage(Flag,FJName,GoodsInfo,FJTel,Order_sn,SJName,SJAdr,SJTel,SJRemark,Paytime);
	  //LODOP.SET_SHOW_MODE("BKIMG_IN_PREVIEW",1);
	  LODOP.PREVIEW();
	  //LODOP.PRINT_DESIGN();
}

function print_all()
{
	var sRowsData = $('#dg').datagrid('getChecked');
	var sLen = sRowsData.length;
	if(sLen == 0)
	{
		$.messager.alert('警告','未选中任何项!','warning');
		return;
	}
	
	$.messager.confirm('提示','您选择了'+sLen+'个订单，确定打印？',function(r){if(r){
	    var Flag = $("input[name='template']").val();
	    var template_name = '';
	    if(Flag == 'yto')
	    {
	    	template_name = "套打圆通的模板";	  
	    }
	    if(Flag == 'zto')
	    {
	    	template_name = "套打中通的模板";		 
	    }
	    if(Flag == 'sto')
	    {
	    	template_name = "套打申通的模板";		  
	    }	
	    LODOP.PRINT_INITA(0,0,"230mm","127mm",template_name);	
	    LODOP.SET_PRINT_PAGESIZE(1,2300,1270,'');	  
	    //LODOP.SET_SHOW_MODE("BKIMG_IN_PREVIEW",1);
		for(var $i=0;$i<sLen;$i++)
		{		
			CreatePrintPage(Flag,jjname,sRowsData[$i].goods_amount,jjtel,sRowsData[$i].order_sn,sRowsData[$i].consignee,sRowsData[$i].region_id+' '+sRowsData[$i].address,sRowsData[$i].phone_mob+'/'+sRowsData[$i].phone_tel,sRowsData[$i].region_id,sRowsData[$i].pay_time);
		}
		
		LODOP.PREVIEW();
	}else{return false;}});
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

function save_invoice()
{
	$('#dg').datagrid('acceptChanges');
	var sRowsData = $('#dg').datagrid('getChecked');
	var sLen = sRowsData.length;	
	if(sLen == 0)
	{
		$.messager.alert('警告','未选中任何项!','warning');
		return;
	}
	var idsarr = new Array();
	var invoicearr = new Array();
	//var regPattern = /\d{6,}/;
	for(var $i=0;$i<sLen;$i++)
	{
		
		if(sRowsData[$i].invoice_no != null &&  sRowsData[$i].invoice_no != '' && sRowsData[$i].invoice_no != undefined)
	    {
			idsarr.push(sRowsData[$i].order_id);
			invoicearr.push(sRowsData[$i].invoice_no);
	    }
		
	}
	
	$.post('index.php?app=behalf_print&act=save_invoice',{ids:idsarr,ins:invoicearr},function(data){
		$('#dg').datagrid('reload');
		data = eval('('+data+')');
		if(!data.done)
	    {			
			$.messager.alert('警告',data.msg,'warning');
	    }
		
	});
}

//步长递增
function add_inslist()
{
	var sRowsData = $('#dg').datagrid('getChecked');
	var sLen = sRowsData.length;	
	if(sLen == 0)
	{
		$.messager.alert('警告','未选中任何项!','warning');
		return;
	}
	var prefix_invoice = $('#prefix_invoice').val();
	var first_invoice_num = $('#first_invoice').val();
	var add_step = $("input[name='add_step']").val();
	var reg = /^[1-9]\d*$/;
	if(null == first_invoice_num.match(reg))
    {
		$.messager.alert('警告','起始单号应该填写纯数字，且不能以0开头!','warning');
		return;
    }	
	for(var $i=0;$i<sLen;$i++)
	{
		$('#dg').datagrid('updateRow',{
			index:$('#dg').datagrid('getRowIndex',sRowsData[$i]),
			row:{				
				invoice_no:$.trim(prefix_invoice)+(parseInt(first_invoice_num)+($i)*parseInt(add_step))
			}
		})
	}
}


function clearForm()
{
	$('#query_form').form('clear');
	$('#btnSearch').click();
}

function show_zto_template()
{
	//$('#dialog_template').dialog('open');
	edit_zto_template();
	var zto_design_result = LODOP.PRINT_DESIGN();
	$.messager.confirm('提示', '确定保存？', function(r){
		if (r){
			$.post('index.php?app=behalf_print&act=save_print_template',{f:'zto',result:zto_design_result},function(data){
				data = eval('('+data+')');
				if(data.done)
			    {			
					$.messager.alert('警告',data.msg,'warning');
			    }
			});
		}
	});
}
function show_sto_template()
{
	edit_sto_template();
	var sto_design_result = LODOP.PRINT_DESIGN();
	$.messager.confirm('提示', '确定保存？', function(r){
		if (r){
			$.post('index.php?app=behalf_print&act=save_print_template',{f:'sto',result:sto_design_result},function(data){
				data = eval('('+data+')');
				if(data.done)
			    {			
					$.messager.alert('警告',data.msg,'warning');
			    }
			});
		}
	});
}
function show_yto_template()
{
	edit_yto_template();
	var yto_design_result = LODOP.PRINT_DESIGN();
	//保存
	var ItemCount = LODOP.GET_VALUE('ItemCount',0);
	var ItemClass = LODOP.GET_VALUE('ItemClass',1);
	var ItemClassName = LODOP.GET_VALUE('ItemClassName',1);
	
	
	
	$.messager.confirm('提示', '确定保存？', function(r){
		if (r){
			$.post('index.php?app=behalf_print&act=save_print_template',{f:'yto',result:yto_design_result},function(data){
				data = eval('('+data+')');
				if(data.done)
			    {			
					$.messager.alert('警告',data.msg,'warning');
			    }
			});
		}
	});
	//alert(yto_design_result);
}

function edit_zto_template()
{
	LODOP.PRINT_INITA(0,0,"230mm","127mm","套打中通的模板");
	LODOP.ADD_PRINT_SETUP_BKIMG("<img src='"+SITE_URL+"/data/system/printTemplate/zto/default.jpg"+"' border=0/>"); 
	LODOP.SET_SHOW_MODE("BKIMG_WIDTH","230mm"); 
	LODOP.SET_SHOW_MODE("BKIMG_HEIGHT","127mm");
	LODOP.SET_PRINT_MODE("PROGRAM_CONTENT_BYVAR",true);//生成程序时，内容参数有变量用变量，无变量用具体值
	
	LODOP.ADD_PRINT_TEXT(108,127,112,24,"发件人姓名");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","FJName");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(135,72,331,114,"内装货品");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","GoodsInfo");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",11);
	LODOP.ADD_PRINT_TEXT(252,81,162,37,"发件人电话");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","FJTel");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(381,77,180,28,"订单号码");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","Order_sn");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(105,478,112,26,"收件人姓名");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJName");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(140,476,282,75,"收件人详细地址");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJAdr");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(221,480,180,24,"收件人手机");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJTel");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(268,438,307,146,"备注");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJRemark");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",15);
	LODOP.ADD_PRINT_TEXT(302,297,100,35,"已验视");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",18);
	LODOP.SET_PRINT_STYLEA(0,"Bold",1);

	//LODOP.SET_SHOW_MODE("DESIGN_IN_BROWSE",1);
	//LODOP.SET_SHOW_MODE("SETUP_ENABLESS","11111111000000");//隐藏关闭(叉)按钮
	LODOP.SET_SHOW_MODE("HIDE_GROUND_LOCK",true);//隐藏纸钉按钮
	LODOP.SET_PRINT_MODE("POS_BASEON_PAPER",true);
	//LODOP.PRINT_DESIGN();
}

function edit_sto_template()
{
	LODOP.PRINT_INITA(0,0,"230mm","127mm","套打申通的模板");
	LODOP.ADD_PRINT_SETUP_BKIMG("<img src='"+SITE_URL+"/data/system/printTemplate/sto/default.jpg"+"' border=0/>"); 
	LODOP.SET_SHOW_MODE("BKIMG_WIDTH","230mm"); 
	LODOP.SET_SHOW_MODE("BKIMG_HEIGHT","127mm");
	LODOP.SET_PRINT_MODE("PROGRAM_CONTENT_BYVAR",true);//生成程序时，内容参数有变量用变量，无变量用具体值
	
	LODOP.ADD_PRINT_TEXT(105,126,112,24,"发件人姓名");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","FJName");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(130,74,331,109,"内装货品");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","GoodsInfo");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",11);
	LODOP.ADD_PRINT_TEXT(241,156,162,27,"发件人电话");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","FJTel");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(364,78,201,28,"订单号码");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","Order_sn");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(105,493,112,26,"收件人姓名");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJName");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(140,484,282,95,"收件人详细地址");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJAdr");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(245,517,246,24,"收件人手机及固话");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJTel");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXT(272,418,342,141,"备注");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJRemark");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",15);
	LODOP.ADD_PRINT_TEXT(330,283,110,55,"已验视");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",18);
	LODOP.SET_PRINT_STYLEA(0,"Bold",1);
	LODOP.ADD_PRINT_TEXT(410,699,100,35,"4.6");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",17);

	
	//LODOP.SET_SHOW_MODE("DESIGN_IN_BROWSE",1);
	//LODOP.SET_SHOW_MODE("SETUP_ENABLESS","11111111000000");//隐藏关闭(叉)按钮
	LODOP.SET_SHOW_MODE("HIDE_GROUND_LOCK",true);//隐藏纸钉按钮
	LODOP.SET_PRINT_MODE("POS_BASEON_PAPER",true);
	//LODOP.PRINT_DESIGN();
}


function edit_yto_template()
{
	LODOP.PRINT_INITA(0,0,"230mm","127mm","套打圆通的模板");
	LODOP.ADD_PRINT_SETUP_BKIMG("<img src='"+SITE_URL+"/data/system/printTemplate/yuantong/default.jpg' border=0/>"); 
	LODOP.SET_SHOW_MODE("BKIMG_WIDTH","230mm"); 
	LODOP.SET_SHOW_MODE("BKIMG_HEIGHT","127mm");
	LODOP.SET_PRINT_MODE("PROGRAM_CONTENT_BYVAR",true);//生成程序时，内容参数有变量用变量，无变量用具体值
	
	LODOP.ADD_PRINT_TEXTA('FJName',93,123,102,24,"发件人姓名");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","FJName");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXTA('GoodsInfo',117,73,331,104,"内装货品");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","GoodsInfo");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",11);
	LODOP.ADD_PRINT_TEXTA('FJTel',221,158,122,27,"发件人电话");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","FJTel");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXTA('Order_sn',371,70,321,28,"订单号码");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","Order_sn");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXTA('SJName',106,487,87,26,"收件人姓名");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJName");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXTA('SJAdr',136,485,282,90,"收件人详细地址");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJAdr");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXTA('SJTel',225,483,246,24,"收件人手机");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJTel");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",12);
	LODOP.ADD_PRINT_TEXTA('SJRemark',299,421,367,161,"备注");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","SJRemark");
	LODOP.SET_PRINT_STYLEA(0,"FontSize",15);
	LODOP.SET_PRINT_STYLEA(0,"Bold",1);
	LODOP.ADD_PRINT_TEXTA('Paytime',235,158,105,20,"下单时间");
	LODOP.SET_PRINT_STYLEA(0,"ContentVName","Paytime");
	
	//LODOP.SET_SHOW_MODE("DESIGN_IN_BROWSE",1);
	//LODOP.SET_SHOW_MODE("SETUP_ENABLESS","11111111000000");//隐藏关闭(叉)按钮
	LODOP.SET_SHOW_MODE("HIDE_GROUND_LOCK",true);//隐藏纸钉按钮
	LODOP.SET_PRINT_MODE("POS_BASEON_PAPER",true);
	//LODOP.PRINT_DESIGN();
	//alert(LODOP.GET_VALUE('ItemCount',0));
}



