//mode bill print
var selected = [];//dt tr selected

$(function(){
	getPrinterList();
	
	dt = $("#order_list_table").DataTable({
		"ordering":false,
	    "paging":false,
	    "info":true,
	    "searching":true,
	    "dom":'<"row" <"col-xs-1" <"#mytools">><"col-xs-7" B><"col-xs-4" f>>t',
	    "buttons":[
			       'copy','excel','colvis'
			     ],
		"language":{
			"sProcessing": "处理中...",
	        "sLengthMenu": "显示 _MENU_ 项结果",
	        "sZeroRecords": "没有匹配结果",
	        "sInfo": "显示第 _START_ 至 _END_ 项结果，共 _TOTAL_ 项",
	        "sInfoEmpty": "显示第 0 至 0 项结果，共 0 项",
	        "sInfoFiltered": "(由 _MAX_ 项结果过滤)",
	        "sInfoPostFix": "",
	        "sSearch": "结果中搜索:",
	        "sUrl": "",
	        "sEmptyTable": "表中数据为空",
	        "sLoadingRecords": "载入中...",
	        "sInfoThousands": ",",
	        "oPaginate": {
	            "sFirst": "首页",
	            "sPrevious": "上页",
	            "sNext": "下页",
	            "sLast": "末页"
	        },
	        "buttons":{
				"copy":"复制表格",
				"excel":"导出为EXCEL",
				"print":"打印表格",
				"colvis":"隐藏/显示列"
			},
	        "oAria": {
	            "sSortAscending": ": 以升序排列此列",
	            "sSortDescending": ": 以降序排列此列"
	        }
		},
		"pagingType":'simple_numbers',
		"columnDefs":[
		              {
		            	  "orderable":false,
		            	  "targets":[0]
		              },
		             /* {
		            	  "render":function(data,type,row){
		            		  //data = data.trim();
		            		  //alert(data.indexOf('中国'));
		            		  len = data.strLen();
		            		  if(data.indexOf('中国') >= 0)
		            			  return data.subCHStr(11,len);
		            		  else 
		            			  return data;
		            	  },
		            	  "targets":[3]
		              },*/
		              {"visible":false,"targets":[12]},
		              {"visible":false,"targets":[13]}
		              
		],
		"rowCallback":function(row,data){
			if ( $.inArray(data.DT_RowId, selected) !== -1 ) {
                //$(row).addClass('selected');
            }
		},
		initComplete:initComplete
	});
	
	$('#order_list_table tbody').on('click', 'tr', function () {
        var id = this.id;
        var index = $.inArray(id, selected);
 
        if ( index === -1 ) {
            selected.push( id );
        } else {
            selected.splice( index, 1 );
        }
 
       
        
    } );
	
	
	
	
	$('#add_time_from_wrapper').datetimepicker({format:'YYYY-MM-DD HH:mm:ss'});
	$('#add_time_to_wrapper').datetimepicker({format:'YYYY-MM-DD HH:mm:ss'});
	
	jQuery('#checkall').on('click',function(){
		var checkall = this.checked;
		//console.log('all:'+checkall);
		jQuery('#order_list_table').find("input[type='checkbox'][name='orders']").each(function(i){
			this.checked = checkall;
			//console.log(this);
		});
	});
});

/**
 * datatables
 */
function initComplete()
{
	var str= '<div class="btn-group">'+
	  '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">显示结果行数 <span class="caret"></span></button>'+
	  '<ul class="dropdown-menu"><li><a href="javascript:replaceParam(\'rows\',10)">10行</a></li><li role="separator" class="divider"></li><li><a href="javascript:replaceParam(\'rows\',25)">25行</a></li><li><a href="javascript:replaceParam(\'rows\',50)">50行</a></li><li role="separator" class="divider"></li><li><a href="javascript:replaceParam(\'rows\',100)">100行</a></li><li><a href="javascript:replaceParam(\'rows\',200)">200行</a></li></ul>'+
	'</div>';
	$("#mytools").html(str);

}
/**
 * 获取面单号
 */
function restore_invoice()
{
	var sRowsData = jQuery('#order_list_table').find("input[type='checkbox'][name='orders']:checked");
	var sLen = sRowsData.length;
	if(sLen == 0)
	{
		bootbox.alert('未选中任何订单!');
		return;
	}
	var idsarr = new Array();
	for(var $i=0;$i<sLen;$i++)
	{
		idsarr.push(sRowsData[$i].value);
	}
	//console.log(idsarr);
	$.post('index.php?module=behalf&act=get_invoice_no',{ids:idsarr},function(data){
		//$('#dg').datagrid('reload');
		data = $.parseJSON(data);
		if(data.done == false)
		{
			bootbox.alert(data.msg);
			return;
		}
		location.reload(true);
	});
}

/**
 * 打印选中
 */
function print_all()
{
	
	var empty_invoiceno = 0;//没有快递单号的订单数码,整个打印任务
	var sRowsData = [];
	$('#order_list_table tbody tr').each(function(i){
		
		if($(this).find("input[name='orders']:checked").val())
		{
			for(j=0;j<dt.data().length;j++)
	        {
				if($(this).attr('id') == (dt.data()[j]).DT_RowId) 
				{
					//console.log($(this).attr('id'));
					sRowsData.push((dt.data())[j]);
				}
	        }
		}
	});
	
	
	//console.log(dt.data());
	//var sRowsData = jQuery('#order_list_table').find("input[type='checkbox'][name='orders']:checked");
	var sLen = sRowsData.length;
	var print_step = 10;//多少页为一个打印任务
	//var print_task_rest = sLen % print_step;
	//var print_task_num = Math.floor(sLen / print_step);//floor 向下取整，ceil向上取整,round 四舍五入
	if(sLen == 0)
	{
		bootbox.alert('未选中任何订单!');
		return;
	}
	bootbox.confirm('您选择了'+sLen+'个订单，确定直接打印？',function(r){if(r){
		var printFlag = $("#template_select").val();
		var printer = $("#printer_combobox").val();
		//console.log(printFlag + "#"+printer);
		if(printer == null || printer == undefined)
		{
			bootbox.alert('请连接打印设备，稍后再试！');
			return;
		}
		if(printFlag == 'yto')
		{
		    LODOP.PRINT_INITA(0,0,"100mm","180mm","套打圆通的模板"); 
			LODOP.SET_PRINT_PAGESIZE(1,'100mm','180mm','');	
			LODOP.SET_SHOW_MODE("BKIMG_IN_PREVIEW",1);
			LODOP.SET_SHOW_MODE("NP_NO_RESULT",true);
			var local_invoiceno = 0;
		    for(var $i=0; $i < sLen; $i++)
		    {
				if(sRowsData[$i][8] != null && sRowsData[$i][8] != '' && sRowsData[$i][8] != undefined)
				{
					CreatePrintPage(jjname,jjtel,sRowsData[$i][2],sRowsData[$i][5],jjadr,sRowsData[$i][3]+' '+sRowsData[$i][4],sRowsData[$i][12],sRowsData[$i][8],sRowsData[$i][3],sRowsData[$i][9],sRowsData[$i][13]);
				}
				else
				{
					empty_invoiceno++ ;
					local_invoiceno++;
				}
				if((parseInt($i+1) % print_step == 0) && (local_invoiceno < print_step))
				{
					LODOP.SET_PRINT_MODE("CUSTOM_TASK_NAME","套打圆通的模板" + parseInt($i+1)/print_step);//为每个打印单独设置任务名
					LODOP.SET_PRINTER_INDEX(printer);
					LODOP.PRINT();
					local_invoiceno = 0;
				}			
				
		    }
		   
		   if((sLen % print_step != 0) && (local_invoiceno < (sLen % print_step)))
		   {
			   LODOP.SET_PRINT_MODE("CUSTOM_TASK_NAME","套打圆通的模板" + parseInt($i+1) / print_step);//为每个打印单独设置任务名
			   LODOP.SET_PRINTER_INDEX(printer);
			   LODOP.PRINT();
		   }
			
		}
		else if(printFlag == 'zto')
		{
			LODOP.PRINT_INITA("-2mm","-2mm","100mm","180mm","套打中通的模板"); 
			LODOP.SET_PRINT_PAGESIZE(1,'100mm','180mm','');	
			LODOP.SET_SHOW_MODE("BKIMG_IN_PREVIEW",1);
			LODOP.SET_SHOW_MODE("NP_NO_RESULT",true);
			var local_invoiceno = 0;
			for(var $i=0; $i < sLen; $i++)
			{
				if(sRowsData[$i][8] != null && sRowsData[$i][8] != '' && sRowsData[$i][8] != undefined)//invoice_no
				{
					createZtoPrintPage(jjname,jjtel,sRowsData[$i][2],sRowsData[$i][5],jjadr,sRowsData[$i][3]+' '+sRowsData[$i][4],sRowsData[$i][12],sRowsData[$i][8],sRowsData[$i][3],sRowsData[$i][9],sRowsData[$i][13]);
				}
				else
				{
					empty_invoiceno++ ;
					local_invoiceno++ ;
				}
				if((parseInt($i+1) % print_step == 0) && (local_invoiceno < print_step) )
				{
					console.log($i+'in:'+local_invoiceno);
					LODOP.SET_PRINT_MODE("CUSTOM_TASK_NAME","套打中通的模板" + parseInt($i+1) / print_step);//为每个打印单独设置任务名
					LODOP.SET_PRINTER_INDEX(printer);
					LODOP.PRINT();
					local_invoiceno = 0;
				}
				
			}
			if((sLen % print_step != 0) && (local_invoiceno < (sLen % print_step)))
			{
				 //console.log($i+'out:'+local_invoiceno);
				 LODOP.SET_PRINT_MODE("CUSTOM_TASK_NAME","套打圆通的模板" + parseInt($i+1) / print_step);//为每个打印单独设置任务名
				 LODOP.SET_PRINTER_INDEX(printer);
				 LODOP.PRINT();
			}
		}		
		if( empty_invoiceno != 0)
		{
			bootbox.alert("您有 "+ empty_invoiceno +" 个订单快递单号为空，请检查！");
		}
		//LODOP.PREVIEW();
		
	}else{return;}});
	
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
	//console.log(strJJName+"#"+strJJTel+"#"+strSJName+"#"+strSJTel+"#"+strJJAdr+"#"+strSJAdr+"#"+strOrder_sn+"#"+strInvocie+"#"+strDestCity+"#"+strGoodsInfo+"#"+strPaytime);
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

function async_ship()
{
	var sRowsData = jQuery('#order_list_table').find("input[type='checkbox'][name='orders']:checked");
	var sLen = sRowsData.length;
	if(sLen == 0)
	{
		bootbox.alert('未选中任何订单!');
		return;
	}
	var idsarr = new Array();
	for(var $i=0;$i<sLen;$i++)
	{
		idsarr.push(sRowsData[$i].value);
	}
	
	$.post('index.php?module=behalf&act=async_shipped',{ids:idsarr},function(data){
		data = $.parseJSON(data);
		if(data.done == false)
		{
			bootbox.alert(data.msg);
			return;
		}
		else
		{
			location.reload(true);
		}
				
		/*$('#dg').datagrid('reload');
		data = eval('('+data+')');
		if(!data.done)
	    {			
			$.messager.alert('警告',data.msg,'warning');
	    }*/
		
	});
}

/**
 * 读取本地打印机列表
 */
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
	var listHtml = "<label class='input-group-addon'>当前打印机</label><select name='printer' id='printer_combobox' class='form-control selectpicker' >";
	for(var $count=0;$count<iCount;$count++)
	{
		listHtml += "<option value="+ $count +">" + printer_list[$count]+"</option>";
	}
	listHtml += "</select>";
	$("#printer_equipment").html(listHtml);	
	//$("#printer_combobox").combobox();
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

//计算字符串长度
String.prototype.strLen = function() {
    var len = 0;
    for (var i = 0; i < this.length; i++) {
        if (this.charCodeAt(i) > 255 || this.charCodeAt(i) < 0) len += 2; else len ++;
    }
    return len;
}
//将字符串拆成字符，并存到数组中
String.prototype.strToChars = function(){
    var chars = new Array();
    for (var i = 0; i < this.length; i++){
        chars[i] = [this.substr(i, 1), this.isCHS(i)];
    }
    String.prototype.charsArray = chars;
    return chars;
}
//判断某个字符是否是汉字
String.prototype.isCHS = function(i){
    if (this.charCodeAt(i) > 255 || this.charCodeAt(i) < 0) 
        return true;
    else
        return false;
}
//截取字符串（从start字节到end字节）
String.prototype.subCHString = function(start, end){
    var len = 0;
    var str = "";
    this.strToChars();
    for (var i = 0; i < this.length; i++) {
        if(this.charsArray[i][1])
            len += 2;
        else
            len++;
        if (end < len)
            return str;
        else if (start < len)
            str += this.charsArray[i][0];
    }
    return str;
}
//截取字符串（从start字节截取length个字节）
String.prototype.subCHStr = function(start, length){
    return this.subCHString(start, start + length);
}