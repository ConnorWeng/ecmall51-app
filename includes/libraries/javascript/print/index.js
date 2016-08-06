$(function(){
	initLeftMenu();
	$('body').layout();
});

function initLeftMenu()
{
	$('.easyui-accordion li a').click(function(){
		var tabTitle = $(this).text();
		var url = $(this).attr('href');
		addTab(tabTitle,url);
		$('.easyui-accordion li div').removeClass('selected');
		$(this).parent().addClass('selected');
	}).hover(function(){
		$(this).parent().addClass('hover');
	},function(){
		$(this).parent().removeClass('hover');
	});
}

function addTab(subtitle,url)
{
	if(!$('#tabs').tabs('exists',subtitle))
	{
		$('#tabs').tabs('add',{
			title:subtitle,
			content:createFrame(url),
			closable:true,
			width:$('#mainPanel').width() - 10,
			height:$('#mainPanel').height()-26
		});
	}
	else
	{
		$('#tabs').tabs('select',subtitle);
	}
}

function createFrame(url)
{
	var str = '<iframe name="mainFrame" scrolling="no"  frameborder="0" src="' + url + '" style="width:100%;height:100%;"></iframe>';
	return str;
}

