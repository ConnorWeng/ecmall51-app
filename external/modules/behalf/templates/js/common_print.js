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
		"drawCallback":function(setting){
			$("#order_list_table tbody .inpTd").editable('index.php?module=behalf&act=save_invoiceno',{
				//'target':'index.php?module=behalf&act=save_invoiceno',
				'tooltip':'数字或字母10-20位单号',//
				'placeholder':'',
				'width':'200px',
				'height':'24px',
				'type':'text',
				'name':'invoiceno',
				//'submit':'OK',
				'indicator':'处理中……',
				'onblur':'submit',//cancel,submit,ignore
				/*'ajaxoptions':{
					dataType:'json',
					url:'index.php?module=behalf&act=save_invoiceno',
					success:function(data,status){
						if(data.done)
						{
							$(self).html(data.retval);
						}else
						{
							bootbox.alert(data.msg);
							
							$(self).html('ee');
						}
					}
				},*/
				'onsubmit':function(setting,original){
					var invoice = $(this).find('input').val();
					if(!(/^([a-z]|[A-Z]|\d){10,20}$/.test(invoice)))
					{
						bootbox.alert('请输入10-20位数字或字母快递单号！',function(){
						});
						//$(original).html(setting.id);					
		               
						return false;
					}
					if(invoice != null && invoice != '' && invoice != undefined)
					{
						$.ajax({
							type:'POST',
							url:'index.php?module=behalf&act=check_invoiceno',
							data:{'invoiceno':invoice},
							dataTyep:'json',
							async:false,
							cache:false,
							success:function(data){
								if(!data)
								{
									bootbox.alert('单号 '+invoice+' 已存在！');
									return false;
								}
							
						    }
						});
					}
					else
					{
						return false;
					}
					    
					
					
				},
				'callback':function(value,setting){
					location.reload(true);
				}
			});
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
 * 打印选中
 */
function print_all()
{
	var empty_invoiceno = 0;//没有快递单号的订单数码,整个打印任务
	var sRowsData = [];
	$('#order_list_table tbody tr').each(function(i){
		if($(this).find("input[name='orders']:checked").val())
		{
			sRowsData.push((dt.data())[i]);
		}
	});
	
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
		 var template_name = '';
	    if(printFlag == 'yto')
	    {
	    	template_name = "套打圆通的模板";	  
	    }
	    if(printFlag == 'zto')
	    {
	    	template_name = "套打中通的模板";		 
	    }
	    if(printFlag == 'sto')
	    {
	    	template_name = "套打申通的模板";		  
	    }	
	    LODOP.PRINT_INITA(0,0,"230mm","127mm",template_name);	
	    LODOP.SET_PRINT_PAGESIZE(1,2300,1270,'');	  
	    LODOP.SET_SHOW_MODE("NP_NO_RESULT",true);
	    //LODOP.SET_SHOW_MODE("BKIMG_IN_PREVIEW",1);
		for(var $i=0;$i<sLen;$i++)
		{		
			CreatePrintPage(printFlag,jjname,sRowsData[$i][9],jjtel,sRowsData[$i][12],sRowsData[$i][2],sRowsData[$i][3]+sRowsData[$i][4],sRowsData[$i][5],sRowsData[$i][3],sRowsData[$i][13]);
		}
		
		LODOP.PREVIEW();
		
	}else{return;}});
	
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
		/*bootbox.confirm(data.msg,function(r){
			if(r) location.reload(true);
		});*/
		
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




