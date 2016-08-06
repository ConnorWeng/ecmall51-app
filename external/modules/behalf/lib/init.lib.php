<?php
class Init_behalf_module
{
	function add_dbfields()
	{
		$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."goods_warehouse" );
		$fields = array( );
		foreach ( $result as $v )
		{
			$fields[] = $v['Field'];
		}
		//拿货时间
		if ( !in_array( "taker_time", $fields ) )
		{
			$sql = "ALTER TABLE `".DB_PREFIX."goods_warehouse` ADD `taker_time` int(10) unsigned NOT NULL  default '0'";
			db( )->query( $sql );
		}
		//换货时间
		if ( !in_array( "rechange_time", $fields ) )
		{
			$sql = "ALTER TABLE `".DB_PREFIX."goods_warehouse` ADD `rechange_time` int(10) unsigned NOT NULL  default '0'";
			db( )->query( $sql );
		}
		//退货时间
		if ( !in_array( "reback_time", $fields ) )
		{
			$sql = "ALTER TABLE `".DB_PREFIX."goods_warehouse` ADD `reback_time` int(10) unsigned NOT NULL  default '0'";
			db( )->query( $sql );
		}
		//代发
		if ( !in_array( "bh_id", $fields ) )
		{
			$sql = "ALTER TABLE `".DB_PREFIX."goods_warehouse` ADD `bh_id` int(10) unsigned NOT NULL  default '0'";
			db( )->query( $sql );
		}
		if(!in_array("zwd51_tobehalf_discount", $fields))
		{
			$sql = "ALTER TABLE `".DB_PREFIX."goods_warehouse` ADD `zwd51_tobehalf_discount` decimal(10,2) NOT NULL DEFAULT '0.00'";
			db()->query($sql);
		}
		
		db()->query("
				CREATE TABLE IF NOT EXISTS `".DB_PREFIX."goods_taker_inventory` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
				  `name` varchar(50) NOT NULL COMMENT '拿货单名称',
				  `content` text NOT NULL COMMENT '内容',
				  `goods_count` smallint(6) unsigned NOT NULL COMMENT '商品数量',
				  `goods_amount` decimal(10,2) NOT NULL COMMENT '商品金额',
				  `store_bargin` decimal(10,2) NOT NULL COMMENT '档口优惠',
				  `mk_ids` varchar(50) NOT NULL COMMENT '市场ID',
				  `mk_names` varchar(50) NOT NULL COMMENT '市场名称',
				  `createtime` int(10) unsigned NOT NULL COMMENT '创建时间',
				  `deal_time` int(10) unsigned NOT NULL COMMENT '处理时间',
				  `taker_id` int(10) unsigned NOT NULL COMMENT '拿货员',
				  `taker_name` varchar(100) NOT NULL,
				  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
				  `visible` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '显示否',
				  `bh_id` int(10) unsigned NOT NULL COMMENT '代发',
				  PRIMARY KEY (`id`),
				  KEY `name` (`name`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='拿货单' AUTO_INCREMENT=1 ;
				");
		//代发FAQ help
		db()->query("
					CREATE TABLE IF NOT EXISTS `".DB_PREFIX."behalf_helper` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`title` varchar(200) NOT NULL,
					`content` varchar(2000) DEFAULT NULL,
					`login_id` int(10) unsigned NOT NULL,
					`login_name` varchar(60) DEFAULT NULL,
					`create_time` int(10) unsigned NOT NULL,
					`login_ip` varchar(20) NOT NULL,
					`pid` int(10) NOT NULL DEFAULT '0',
					PRIMARY KEY (`id`),
					KEY `title` (`title`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
		");
		//代发备忘录
		db()->query("
				CREATE TABLE IF NOT EXISTS `".DB_PREFIX."behalf_ordernote` (
				  `order_id` int(10) unsigned NOT NULL,
				  `content` varchar(2000) NOT NULL,
				  `create_time` int(10) unsigned NOT NULL,
				  `login_id` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`order_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;
				");
		//代发客户关系
		db()->query("
				CREATE TABLE IF NOT EXISTS `".DB_PREFIX."behalf_member_relation` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `user_id` int(10) unsigned NOT NULL,
				  `user_name` varchar(50) NOT NULL,
				  `bh_id` int(10) unsigned NOT NULL,
				  `relation` varchar(10) DEFAULT NULL COMMENT 'vip or black',
				  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
				  `remark` varchar(1000) DEFAULT NULL COMMENT '备注',
				  PRIMARY KEY (`id`),
				  KEY `user_id` (`user_id`,`bh_id`,`relation`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='代发客户关系' AUTO_INCREMENT=1 ;
				");
	}
	
	function alter_dbfields()
	{
	    $sql =  "ALTER TABLE `".DB_PREFIX."goods_taker_inventory` CHANGE `search_time` `search_time` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
	    db()->query($sql);
	    $sql =  "ALTER TABLE `".DB_PREFIX."goods_warehouse` CHANGE `store_bargin` `store_bargin` decimal(10,2) NOT NULL DEFAULT '0'";
	    db()->query($sql);
	}
	
	function add_dbfields1()
	{
		$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."order_refund" );
		$fields = array( );
		foreach ( $result as $v )
		{
			$fields[] = $v['Field'];
		}
		//拒绝原因
		if ( !in_array( "refuse_reason", $fields ) )
		{
			$sql = "ALTER TABLE `".DB_PREFIX."order_refund` ADD `refuse_reason` varchar(250) DEFAULT NULL";
			db( )->query( $sql );
		}
		//0 为goods_id ,1 为 goods_no
		if ( !in_array( "goods_ids_flag", $fields ) )
		{
			$sql = "ALTER TABLE `".DB_PREFIX."order_refund` ADD `goods_ids_flag` tinyint(3) unsigned NOT NULL DEFAULT '0'";
			db( )->query( $sql );
		}
		
		$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."goods_warehouse" );
		$fields = array( );
		foreach ( $result as $v )
		{
		    $fields[] = $v['Field'];
		}
		//关联退款订单
		if ( !in_array( "refund_id", $fields ) )
		{
		    $sql = "ALTER TABLE `".DB_PREFIX."goods_warehouse` ADD `refund_id` int(10) unsigned NOT NULL DEFAULT '0'";
		    db( )->query( $sql );
		}
		//返分润2016-03-22
		if ( !in_array( "behalf_to51_discount", $fields ) )
		{
		    $sql = "ALTER TABLE `".DB_PREFIX."goods_warehouse` ADD `behalf_to51_discount` decimal(10,2) NOT NULL DEFAULT '0' COMMENT '商品分润'";
		    db( )->query( $sql );
		}
		
		$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."goods_taker_inventory" );
		$fields = array( );
		foreach ( $result as $v )
		{
		    $fields[] = $v['Field'];
		}
		//搜索时间
		if ( !in_array( "search_time", $fields ) )
		{
		    $sql = "ALTER TABLE `".DB_PREFIX."goods_taker_inventory` ADD `search_time` varchar(30) DEFAULT NULL";
		    db( )->query( $sql );
		}
		//搜索快递
		if ( !in_array( "search_delivery", $fields ) )
		{
		    $sql = "ALTER TABLE `".DB_PREFIX."goods_taker_inventory` ADD `search_delivery` varchar(20) DEFAULT NULL";
		    db( )->query( $sql );
		}
		
		$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."market_behalf" );
		$fields = array( );
		foreach ( $result as $v )
		{
		    $fields[] = $v['Field'];
		}
		//关联退款订单
		if ( !in_array( "sort_ord", $fields ) )
		{
		    $sql = "ALTER TABLE `".DB_PREFIX."market_behalf` ADD `sort_ord` tinyint(3) unsigned NOT NULL DEFAULT '255'";
		    db( )->query( $sql );
		}
		
		$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."navigation" );
		$fields = array( );
		foreach ( $result as $v )
		{
		    $fields[] = $v['Field'];
		}
		//关联退款订单
		if ( !in_array( "cust_icon", $fields ) )
		{
		    $sql = "ALTER TABLE `".DB_PREFIX."navigation` ADD `cust_icon` varchar(30) DEFAULT NULL ";
		    db( )->query( $sql );
		}
		
		$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."goods_statistics" );
		$fields = array( );
		foreach ( $result as $v )
		{
		    $fields[] = $v['Field'];
		}
		//关联退款订单
		if ( !in_array( "backs", $fields ) )
		{
		    $sql = "ALTER TABLE `".DB_PREFIX."goods_statistics` ADD `backs` int(10) unsigned NOT NULL DEFAULT '0' ";
		    db( )->query( $sql );
		}
		
		 // 本地数据库已生成，但服务器未生成
		$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."order" );
		$fields = array( );
		foreach ( $result as $v )
		{
		    $fields[] = $v['Field'];
		}
		//代发费
		if ( !in_array( "behalf_fee", $fields ) )
		{
		    $sql = "ALTER TABLE `".DB_PREFIX."order` ADD `behalf_fee` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '代发费' ";
		    db( )->query( $sql );
		}
		
		$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."order_goods" );
		$fields = array( );
		foreach ( $result as $v )
		{
		    $fields[] = $v['Field'];
		}
		//代发费
		if ( !in_array( "behalf_fee", $fields ) )
		{
		    $sql = "ALTER TABLE `".DB_PREFIX."order_goods` ADD `behalf_fee` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '代发费' ";
		    db( )->query( $sql );
		}
		
		$result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."goods_warehouse" );
		$fields = array( );
		foreach ( $result as $v )
		{
		    $fields[] = $v['Field'];
		}
		//代发费
		if ( !in_array( "behalf_fee", $fields ) )
		{
		    $sql = "ALTER TABLE `".DB_PREFIX."goods_warehouse` ADD `behalf_fee` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '代发费' ";
		    db( )->query( $sql );
		}
		
		//代发 主动退缺货款 和 赔偿运费
		db()->query("
		    CREATE TABLE IF NOT EXISTS `".DB_PREFIX."order_compensation_behalf` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `order_id` int(10) unsigned NOT NULL DEFAULT '0',
              `order_sn` varchar(20) NOT NULL,
              `bh_id` int(10) unsigned NOT NULL DEFAULT '0',
              `create_time` int(10) unsigned NOT NULL,
              `pay_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
              `type` varchar(4) NOT NULL DEFAULT 'lack' COMMENT '缺货或运费',
              PRIMARY KEY (`id`),
              KEY `order_id` (`order_id`),
              KEY `order_sn` (`order_sn`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='代发退缺货或赔运费' AUTO_INCREMENT=1 ;
		
		    ");
	}
}

define('LOCK_BEHALF_MODULE_FILE',ROOT_PATH.'/data/lock_behalf_module.lock');

if(!file_exists(LOCK_BEHALF_MODULE_FILE) || trim(file_get_contents(LOCK_BEHALF_MODULE_FILE)) != '20160704')
{
	//Init_behalf_module::add_dbfields();
	Init_behalf_module::add_dbfields1();
	Init_behalf_module::alter_dbfields();
	file_put_contents(LOCK_BEHALF_MODULE_FILE, '20160704');
}