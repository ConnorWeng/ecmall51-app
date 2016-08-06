<?php

/**
 * 这里可以放一些安装模块时需要执行的代码，比如新建表，新建目录、文件之类的
 */

//拿货仓库
db()->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."goods_warehouse` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`goods_no` varchar(50) NOT NULL COMMENT '拿货商品编码',
			`goods_id` int(10) unsigned NOT NULL COMMENT '商品ID',
			`goods_name` varchar(255) NOT NULL COMMENT '商品名称',
			`goods_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品价格',
			`goods_quantity` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单此规格数量',
			`goods_sku` varchar(60) DEFAULT NULL COMMENT '货号',
			`goods_attr_value` varchar(255) DEFAULT NULL COMMENT '商家编码',
			`goods_image` varchar(255) DEFAULT NULL COMMENT '商品图片',
			`goods_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '商品状态如备货中明天',
			`goods_spec_id` int(10) unsigned NOT NULL COMMENT '规格ID',
			`goods_specification` varchar(255) DEFAULT NULL COMMENT '颜色尺寸',
			`store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '店铺ID',
			`store_name` varchar(100) DEFAULT NULL COMMENT '店铺名称',
		    `store_address` varchar(100) DEFAULT NULL COMMENT '档口地址',
			`store_bargin` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '店铺每件优惠',
			`market_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '市场ID',
			`market_name` varchar(100) DEFAULT NULL COMMENT '市场名称',
			`floor_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '楼层ID',
			`floor_name` varchar(100) DEFAULT NULL COMMENT '楼层名称',
			`order_id` int(10) unsigned NOT NULL COMMENT '订单ID',
			`order_sn` varchar(20) NOT NULL COMMENT '订单编号',
			`order_goods_quantity` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单商品数量',
			`order_add_time` int(10) unsigned NOT NULL COMMENT '下单时间',
			`order_pay_time` int(10) unsigned NOT NULL COMMENT '支付时间',
			`order_postscript` varchar(255) DEFAULT NULL COMMENT '买家留言',
			`delivery_id` int(10) unsigned NOT NULL COMMENT '快递ID',
			`delivery_name` varchar(60) DEFAULT NULL COMMENT '快递名称',
			`taker_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '拿货人',
			PRIMARY KEY (`id`),
			UNIQUE KEY `goods_no` (`goods_no`),
			KEY `goods_spec_id` (`goods_spec_id`,`order_id`,`order_sn`),
			KEY `taker_id` (`taker_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='配货处理' AUTO_INCREMENT=1 ;"
);
//拿货员拿货市场
db()->query(
			"CREATE TABLE IF NOT EXISTS `".DB_PREFIX."market_taker` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`mt_name` varchar(100) NOT NULL,
			`mk_ids` varchar(255) DEFAULT NULL,
			`mk_names` varchar(255) DEFAULT NULL,
			`bh_id` int(10) unsigned NOT NULL DEFAULT '0',
			 PRIMARY KEY (`id`),
			 KEY `bh_id` (`bh_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;"
);
//拿货员拿货市场中间表，多对多
db()->query(
			"CREATE TABLE IF NOT EXISTS `".DB_PREFIX."markettaker_member` (
			  `mt_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '拿货市场编号',
			  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员编号',
			  PRIMARY KEY (`mt_id`,`user_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;"
);




$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."member" );
$fields = array( );
foreach ( $result as $v )
{
	$fields[] = $v['Field'];
}
//代发商品拿货员
if ( !in_array( "behalf_goods_taker", $fields ) )
{
	$sql = "ALTER TABLE `".DB_PREFIX."member` ADD `behalf_goods_taker` int(10) unsigned NOT NULL  default '0'";
	db( )->query( $sql );
}


//拿货单表:配货，备货，退货，标签打印，出入库
/* 
ecm_goods_warehouse

id int10  主键
goods_no 商品编码（每个商品唯一，order_sn + spec_id + 订单商品数量序号）varchar 50
goods_id int(10)  商品id
goods_name varchar 255 商品名称
goods_price    decimal(10,2)    商品价格
goods_quantity int10    订单中此规格商品数量
goods_sku varchar 60 货号
goods_attr_vale varchar 255  商家编码
goods_image varchar 255  商品图片
goods_status tinyint(3)  商品状态，备货中、已备好、明天有、已下架、未出货、缺货、退货
goods_spec_id   int(10)        商品规格id
goods_specification varchar 255  商品规格（颜色尺寸）
store_id int(10)  所属店铺id
store_name varchar 100  店铺名称
store_bargin tinyint(3) 店铺每件优惠价格
market_id int 10 市场id
market_name varchar 100 市场名称
floor_id  int 10 楼层id
floor_name varchar 100 楼层名称
order_id int(10) 订单id
order_sn varchar 20 订单编号
order_goods_quantity  int10 订单商品总数
order_add_time int 10 下单时间
order_pay_time int 10 支付时间
order_postscript varchar 255 订单买家留言
order_status tinyint 3 订单状态
delivery_id int 10 所发快递id
delivery_name varchar 60 快递名称

 */




?>