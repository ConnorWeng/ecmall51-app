// 导航栏配置文件
var outlookbar=new outlook();
var t;
t=outlookbar.addtitle('常用操作','管理首页',1);
outlookbar.additem('欢迎页面',t,'index.php?module=behalf&act=defaultmain');
outlookbar.additem('拿货单管理',t,'index.php?module=behalf&act=gen_taker_list');
outlookbar.additem('商品入库',t,'index.php?module=behalf&act=enter_warehouse');

t=outlookbar.addtitle('基本设置','系统设置',1);
outlookbar.additem('查看个人资料',t,'index.php?module=behalf&act=defaultmain&m=look');
outlookbar.additem('修改个人资料',t,'index.php?module=behalf&act=set_behalf');
//outlookbar.additem('设置可发快递',t,'javascript:;');
//outlookbar.additem('设置快递费用',t,'javascript:;');
//outlookbar.additem('管理拿货市场',t,'javascript:;');
//outlookbar.additem('管理支付方式',t,'javascript:;');

t=outlookbar.addtitle('账号管理','系统设置',1);
outlookbar.additem('设置面单账号',t,'index.php?module=behalf&act=set_mbaccount');

t=outlookbar.addtitle('配货管理','系统设置',1);
outlookbar.additem('设置配货市场',t,'index.php?module=behalf&act=set_markettaker');
outlookbar.additem('管理拿货员',t,'index.php?module=behalf&act=manage_goodstaker');

t=outlookbar.addtitle('订单管理','订单管理',1);
outlookbar.additem('订单列表',t,'index.php?module=behalf&act=order_list');

t=outlookbar.addtitle('配货管理','订单管理',1);
outlookbar.additem('拿货单管理',t,'index.php?module=behalf&act=gen_taker_list');
outlookbar.additem('商品入库',t,'index.php?module=behalf&act=enter_warehouse');

t=outlookbar.addtitle('订单统计','订单管理',1);
outlookbar.additem('发货统计',t,'index.php?module=behalf&act=stat_shipped_order');

t=outlookbar.addtitle('面单打印','打印管理',1);
outlookbar.additem('面单打印',t,'index.php?module=behalf&act=mb_print');
outlookbar.additem('面单模板',t,'index.php?module=behalf&act=mb_template');

t=outlookbar.addtitle('普通打印','打印管理',1);
outlookbar.additem('普通打印',t,'index.php?module=behalf&act=common_print');
outlookbar.additem('普通模板',t,'index.php?module=behalf&act=common_template');

t=outlookbar.addtitle('标签打印','打印管理',1);
outlookbar.additem('标签打印',t,'index.php?module=behalf&act=tag_print');

t=outlookbar.addtitle('市场管理','其他管理',1);
outlookbar.additem('市场列表',t,'index.php?module=behalf&act=market_list');