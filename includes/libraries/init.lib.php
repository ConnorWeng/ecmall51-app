<?php
class Psmb_init
{
    public function Delivery_templateModel_format_template( $region_mod, $delivery_template, $need_dest_ids = FALSE )
    {
        if ( !is_array( $delivery_template ) )
        {
            return array( );
        }
        $data = $deliverys = array( );
        foreach ( $delivery_template as $template )
        {
            $data = array( );
            $data['template_id'] = $template['template_id'];
            $data['name'] = $template['name'];
            $data['created'] = $template['created'];
            $data['price_type'] = $template['price_type'];
            $template_types = explode( ";", $template['template_types'] );
            $template_dests = explode( ";", $template['template_dests'] );
            $template_start_standards = explode( ";", $template['template_start_standards'] );
            $template_start_fees = explode( ";", $template['template_start_fees'] );
            $template_add_standards = explode( ";", $template['template_add_standards'] );
            $template_add_fees = explode( ";", $template['template_add_fees'] );
            $i = 0;
            foreach ( $template_types as $key => $type )
            {
                $dests = explode( ",", $template_dests[$key] );
                $start_standards = explode( ",", $template_start_standards[$key] );
                $start_fees = explode( ",", $template_start_fees[$key] );
                $add_standards = explode( ",", $template_add_standards[$key] );
                $add_fees = explode( ",", $template_add_fees[$key] );
                foreach ( $dests as $k => $v )
                {
                    $data['area_fee'][$i] = array(
                        "type" => $type,
                        "dests" => $region_mod->get_region_name( $v ),
                        "start_standards" => $start_standards[$k],
                        "start_fees" => $start_fees[$k],
                        "add_standards" => $add_standards[$k],
                        "add_fees" => $add_fees[$k]
                    );
                    if ( $need_dest_ids )
                    {
                        $data['area_fee'][$i]['dest_ids'] = $v;
                    }
                    $i++;
                }
            }
            $deliverys[] = $data;
        }
        return $deliverys;
    }
    public function Delivery_templateModel_format_template_foredit( $delivery_template, $region_mod )
    {
        $data[] = $delivery_template;
        $delivery = $this->Delivery_templateModel_format_template( $region_mod, $data, TRUE );
        $delivery = current($delivery );
        $area_fee_list = array( );
        foreach ( $delivery['area_fee'] as $key => $val )
        {
            $type = $val['type'];
            $area_fee_list[$type][] = $val;
        }
        $delivery['area_fee'] = $area_fee_list;
        foreach ( $delivery['area_fee'] as $key => $val )
        {
            $default_fee = TRUE;
            foreach ( $val as $k => $v )
            {
                if ( $default_fee )
                {
                    $delivery['area_fee'][$key]['default_fee'] = $v;
                    $default_fee = FALSE;
                }
                else
                {
                    $delivery['area_fee'][$key]['other_fee'][] = $v;
                }
                unset( $delivery['area_fee'][$key][$k] );
            }
        }
        return $delivery;
    }
    public static function create_table( )
    {
        $result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."store" );
        $fields = array( );
        foreach ( $result as $v )
        {
            $fields[] = $v['Field'];
        }
        if ( !in_array( "pic_slides", $fields ) )
        {
            $sql = "ALTER TABLE `".DB_PREFIX."store` ADD `pic_slides` TEXT NOT NULL AFTER `im_msn`";
            db( )->query( $sql );
        }
        if ( !in_array( "hotline", $fields ) )
        {
            $sql = "ALTER TABLE `".DB_PREFIX."store` ADD `hotline` VARCHAR( 255 ) NOT NULL AFTER `im_msn`";
            db( )->query( $sql );
        }
        if ( !in_array( "online_service", $fields ) )
        {
            $sql = "ALTER TABLE `".DB_PREFIX."store` ADD `online_service` VARCHAR( 255 ) NOT NULL AFTER `im_msn`";
            db( )->query( $sql );
        }
        if ( !in_array( "hot_search", $fields ) )
        {
            $sql = "ALTER TABLE `".DB_PREFIX."store` ADD `hot_search` VARCHAR( 255 ) NOT NULL AFTER `im_msn`";
            db( )->query( $sql );
        }
        if ( !in_array( "business_scope", $fields ) )
        {
            $sql = "ALTER TABLE `".DB_PREFIX."store` ADD `business_scope` VARCHAR( 50 ) NOT NULL AFTER `hot_search`";
            db( )->query( $sql );
        }
        $result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."groupbuy" );
        $fields = array( );
        foreach ( $result as $v )
        {
            $fields[] = $v['Field'];
        }
        if ( !in_array( "group_image", $fields ) )
        {
            $sql = "ALTER TABLE `".DB_PREFIX."groupbuy` ADD `group_image` VARCHAR( 255 ) NOT NULL AFTER `group_name` ";
            db( )->query( $sql );
        }
        $result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."navigation" );
        $fields = array( );
        foreach ( $result as $v )
        {
            $fields[] = $v['Field'];
        }
        if ( !in_array( "hot", $fields ) )
        {
            $sql = "ALTER TABLE `".DB_PREFIX."navigation` ADD  `hot` TINYINT( 3 ) NOT NULL DEFAULT  '0' ";
            db( )->query( $sql );
        }
        $sql = " CREATE TABLE IF NOT EXISTS `".DB_PREFIX."ultimate_store` (\r\n\t  `ultimate_id` int(255) NOT NULL AUTO_INCREMENT,\r\n\t  `brand_id` int(50) NOT NULL,\r\n\t  `keyword` varchar(20) NOT NULL,\r\n\t  `cate_id` int(50) NOT NULL,\r\n\t  `store_id` int(50) NOT NULL,\r\n\t  `status` tinyint(1) NOT NULL DEFAULT '0',\r\n\t  `description` varchar(255) DEFAULT NULL,\r\n\t  PRIMARY KEY (`ultimate_id`)\r\n\t) ENGINE = MYISAM DEFAULT CHARSET=".str_replace( "-", "", CHARSET ).";";
        db( )->query( $sql );
        $result = db( )->getAll( "SHOW COLUMNS FROM ".DB_PREFIX."goods" );
        $fields = array( );
        foreach ( $result as $v )
        {
            $fields[] = $v['Field'];
        }
        if ( !in_array( "delivery_template_id", $fields ) )
        {
            $sql = "ALTER TABLE `".DB_PREFIX."goods` ADD `delivery_template_id` INT (11) NOT NULL ";
            db( )->query( $sql );
        }
        $sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."delivery_template` (\r\n  \t\t\t`template_id` int(11) NOT NULL AUTO_INCREMENT,\r\n  \t\t\t`name` varchar(50) NOT NULL,\r\n  \t\t\t`store_id` int(10) NOT NULL,\r\n  \t\t\t`template_types` text NOT NULL,\r\n  \t\t\t`template_dests` text NOT NULL,\r\n  \t\t\t`template_start_standards` text NOT NULL,\r\n  \t\t\t`template_start_fees` text NOT NULL,\r\n  \t\t\t`template_add_standards` text NOT NULL,\r\n  \t\t\t`template_add_fees` text NOT NULL,\r\n  \t\t\t`created` int(10) NOT NULL,\r\n  \t\t\tPRIMARY KEY (`template_id`)\r\n\t\t) ENGINE = MYISAM DEFAULT CHARSET=".str_replace( "-", "", CHARSET ).";";
        db( )->query( $sql );
    }
    public static function addColumInDelivery_template()
    {
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."delivery_template");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("price_type", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."delivery_template` ADD `price_type` TINYINT(1) NOT NULL DEFAULT '1' AFTER `name`";
            db()->query($sql);
        }
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."goods");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("delivery_weight", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."goods` ADD `delivery_weight` decimal(10,2) NOT NULL DEFAULT '0.00'";
            db()->query($sql);
        }
    }
    public function addColumnOrder()
    {
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."order");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("bh_id", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order` ADD `bh_id` INT(10) NOT NULL DEFAULT '0' AFTER `pay_alter`";
            db()->query($sql);
            $result = db()->getAll("select order_id,bh_id from `".DB_PREFIX."order_behalfs`");
            foreach ($result as $va)
            {
                db()->query("UPDATE `".DB_PREFIX."order` SET bh_id=".$va['bh_id']." WHERE order_id=".$va['order_id'].";");
            }
        }
        if(!in_array("logistics", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order` ADD `logistics` varchar(255) AFTER `invoice_no`";
            db()->query($sql);          
        }
        if(!in_array("seller_message_flag", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order` ADD `seller_message_flag` tinyint(2) AFTER `postscript`";
            db()->query($sql);
        }
        if(!in_array("seller_message", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order` ADD `seller_message` varchar(255) AFTER `postscript`";
            db()->query($sql);
        }
        /*navigation*/
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."navigation");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("margin", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."navigation` ADD `margin` varchar(30) AFTER `hot`";
            db()->query($sql);
        }
        if(!in_array("fontcolor", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."navigation` ADD `fontcolor` varchar(6) AFTER `hot`";
            db()->query($sql);
        }
        /*ecm_store*/
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."store");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("im_wx", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."store` ADD `im_wx` varchar(60) AFTER `im_ww`";
            db()->query($sql);
        }
        /*ecm_behalf*/
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."behalf");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("owner_name", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."behalf` ADD `owner_name` varchar(30) AFTER `bh_name`";
            db()->query($sql);
        }
        //$sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."order_refund` (\r\n  \t\t\t`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,\r\n  \t\t\t`order_id` INT( 10 ) UNSIGNED NOT NULL ,\r\n  \t\t\t`order_sn` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,\r\n  \t\t\t`sender_id` INT( 10 ) UNSIGNED NOT NULL ,\r\n  \t\t\t`sender_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,\r\n  \t\t\t`receiver_id` INT( 10 ) UNSIGNED NOT NULL ,\r\n  \t\t\t`receiver_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,\r\n  \t\t\t`refund_reason` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,\r\n  \t\t\t`refund_amount` DECIMAL( 10, 2 ) NOT NULL ,\r\n  \t\t\t`refund_intro` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci ,\r\n  \t\t\t`create_time` INT( 10 ) UNSIGNED NOT NULL ,\r\n  \t\t\t`pay_time` INT UNSIGNED NOT NULL ,\r\n  \t\t\t`apply_amount` DECIMAL( 10, 2 ) NOT NULL ,\r\n  \t\t\t`status` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT '0',\r\n  \t\t\t`closed` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT '0',\r\n  \t\t\t`type` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT '0',\r\n  \t\t\t PRIMARY KEY ( `id` ) ,\r\n  \t\t\tINDEX ( `order_id` , `order_sn` , `sender_id` , `receiver_id`,`status` )\r\n  \t\t\t) ENGINE = MYISAM DEFAULT CHARSET=".str_replace( "-", "", CHARSET ).";";
        //db( )->query( $sql );
        /*ecm_article*/
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."article");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("add_red", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."article` ADD `add_red` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0'";
            db()->query($sql);
        }
    }
    public static function addColumnOrder1()
    {
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."order_refund");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("invoice_no", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_refund` ADD `invoice_no` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci";
            db()->query($sql);
        }
        if(!in_array("dl_id", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_refund` ADD `dl_id` int(10) UNSIGNED NOT NULL DEFAULT '0'";
            db()->query($sql);
        }
        if(!in_array("dl_code", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_refund` ADD `dl_code` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci";
            db()->query($sql);
        }
        if(!in_array("dl_name", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_refund` ADD `dl_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci";
            db()->query($sql);
        }
        //order_goods
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."order_goods");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("attr_value", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_goods` ADD `attr_value` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci";
            db()->query($sql);
        }
        if(!in_array("store_id", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_goods` ADD `store_id` int(10) UNSIGNED NOT NULL DEFAULT '0'";
            db()->query($sql);
        }
        if(!in_array("oos_value", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_goods` ADD `oos_value` tinyint(1) UNSIGNED NOT NULL DEFAULT '1'";
            db()->query($sql);
        }
        if(!in_array("oos_reason", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_goods` ADD `oos_reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci";
            db()->query($sql);
        }
        //20150923
        if(!in_array("behalf_to51_discount", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_goods` ADD `behalf_to51_discount` decimal(10,2) NOT NULL DEFAULT '0.00'";
            db()->query($sql);
        }
        if(!in_array("zwd51_tobehalf_discount", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_goods` ADD `zwd51_tobehalf_discount` decimal(10,2) NOT NULL DEFAULT '0.00'";
            db()->query($sql);
        }
        //members
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."member");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("upload_goods", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."member` ADD `upload_goods` int(10) UNSIGNED NOT NULL DEFAULT '0'";
            db()->query($sql);
        }
        if(!in_array("upload_goods_time", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."member` ADD `upload_goods_time` int(10) UNSIGNED NOT NULL DEFAULT '0'";
            db()->query($sql);
        }
        //behalf_delivery
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."behalf_delivery");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(in_array("dl_fee", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."behalf_delivery` CHANGE `dl_fee` `first_amount` tinyint(3) UNSIGNED NOT NULL DEFAULT '1'";
            db()->query($sql);
        }
        if(!in_array("first_price", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."behalf_delivery` ADD `first_price` decimal(10,2)  NOT NULL DEFAULT '0'";
            db()->query($sql);
        }
        if(!in_array("step_price", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."behalf_delivery` ADD `step_price` decimal(10,2)  NOT NULL DEFAULT '0'";
            db()->query($sql);
        }
        if(!in_array("step_amount", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."behalf_delivery` ADD `step_amount` tinyint(3) UNSIGNED NOT NULL DEFAULT '1'";
            db()->query($sql);
        }
        //behalf_delivery
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."goods_statistics");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("oos", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."goods_statistics` ADD `oos` int(10) UNSIGNED NOT NULL DEFAULT '0'";
            db()->query($sql);
        }
        //behalf
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."behalf");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("bh_notice", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."behalf` ADD `bh_notice` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci";
            db()->query($sql);
        }

        //order_modeb
        $sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."order_modeb` (\r\n  \t\t\t`order_id` int(10) NOT NULL,\r\n  \t\t\t`md_content` text ,\r\n  \t\t\tPRIMARY KEY (`order_id`)\r\n\t\t) ENGINE = MYISAM DEFAULT CHARSET=".str_replace( "-", "", CHARSET ).";";
        db( )->query( $sql );

        //order_modeb
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."order_modeb");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("name", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_modeb` ADD `name` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci";
            db()->query($sql);
        }
        //20150923
        $sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."store_discount` (\r\n  \t\t\t`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,\r\n  \t\t\t`store_id` int(10) UNSIGNED NOT NULL default '0' ,\r\n  \t\t\t`type` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci,\r\n  \t\t\t`first_price` decimal(10,2) NOT NULL DEFAULT '0.00',\r\n  \t\t\t`end_price` decimal(10,2) NOT NULL DEFAULT '0.00',\r\n  \t\t\t`discount` decimal(10,2) NOT NULL DEFAULT '0.00',\r\n  \t\t\t`sort_order` tinyint(3) unsigned NOT NULL DEFAULT '255',\r\n  \t\t\tPRIMARY KEY (`id`)\r\n\t\t) ENGINE = MYISAM DEFAULT CHARSET=".str_replace( "-", "", CHARSET ).";";
        db()->query( $sql );
        //order
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."order");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("behalf_discount", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order` ADD `behalf_discount` decimal(10,2) NOT NULL DEFAULT '0.00'";
            db()->query($sql);
        }
        //order_refund
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."order_refund");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        //退货时标注
        if(!in_array("goods_ids", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."order_refund` ADD `goods_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci";
            db()->query($sql);
        }

    }

    public static function addDBTable20151106()
    {
        //behalf
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."behalf");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("max_orders", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."behalf` ADD `max_orders` int(6) UNSIGNED NOT NULL default '0'";
            db()->query($sql);
        }
    }

    public static function addDBTable20160214()
    {
        //代发区
        $sql ="CREATE TABLE IF NOT EXISTS `".DB_PREFIX."store_behalfarea` (\r\n  \t\t\t
          `store_id` int(10) unsigned NOT NULL ,\r\n  \t\t\t
          `state` tinyint(3) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          PRIMARY KEY (`store_id`)\r\n  \t\t\t
        ) ENGINE=MyISAM  DEFAULT CHARSET=".str_replace( "-", "", CHARSET ). " ;";
        db()->query($sql);
        //品牌区
        $sql ="CREATE TABLE IF NOT EXISTS `".DB_PREFIX."store_brandarea` (\r\n  \t\t\t
          `store_id` int(10) unsigned NOT NULL ,\r\n  \t\t\t
          `state` tinyint(3) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          PRIMARY KEY (`store_id`)\r\n  \t\t\t
        ) ENGINE=MyISAM  DEFAULT CHARSET=".str_replace( "-", "", CHARSET ). " ;";
        db()->query($sql);
        //品牌区商品
        $sql ="CREATE TABLE IF NOT EXISTS `".DB_PREFIX."store_brandarea_goods` (\r\n  \t\t\t
          `goods_id` int(10) unsigned NOT NULL ,\r\n  \t\t\t
          `state` tinyint(3) unsigned NOT NULL DEFAULT '1',\r\n  \t\t\t
          PRIMARY KEY (`goods_id`)\r\n  \t\t\t
        ) ENGINE=MyISAM  DEFAULT CHARSET=".str_replace( "-", "", CHARSET ). " ;";
        db()->query($sql);
        //实拍区
        $sql ="CREATE TABLE IF NOT EXISTS `".DB_PREFIX."store_realityzone` (\r\n  \t\t\t
          `store_id` int(10) unsigned NOT NULL ,\r\n  \t\t\t
          `state` tinyint(3) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `category` varchar(10) DEFAULT NULL ,\r\n  \t\t\t
          PRIMARY KEY (`store_id`)\r\n  \t\t\t
        ) ENGINE=MyISAM  DEFAULT CHARSET=".str_replace( "-", "", CHARSET ). " ;";
        db()->query($sql);
        //behalf
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."store_behalfarea");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("category", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."store_behalfarea` ADD `category` varchar(10)";
            db()->query($sql);
        }
        //brand
        $result = db()->getAll("SHOW COLUMNS FROM ".DB_PREFIX."store_brandarea");
        $fields = array();
        foreach ($result as $v)
        {
            $fields[] = $v['Field'];
        }
        if(!in_array("category", $fields))
        {
            $sql = "ALTER TABLE `".DB_PREFIX."store_brandarea` ADD `category` varchar(10)";
            db()->query($sql);
        }

        //档口退货退款表
        $sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."order_store_refund` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `order_id` int(10) unsigned NOT NULL,
                  `store_id` int(10) unsigned NOT NULL DEFAULT '0',
                  `applicant_id` int(10) unsigned NOT NULL DEFAULT '0',
                  `is_receive_goods` varchar(3) NOT NULL DEFAULT 'yes',
                  `is_reback_goods` varchar(3) NOT NULL DEFAULT 'yes',
                  `refund_category` varchar(60) NOT NULL,
                  `refund_delivery_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
                  `refund_goods_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
                  `refund_intro` varchar(400) DEFAULT NULL,
                  `goods_info` varchar(200) DEFAULT NULL,
                  `refund_status` tinyint(3) unsigned NOT NULL DEFAULT '0',
                  `refuse_reason` varchar(250) DEFAULT NULL,
                  `refund_closed` tinyint(1) unsigned NOT NULL DEFAULT '0',
                  `close_reason` varchar(250) DEFAULT NULL,
                  `apply_time` int(10) unsigned DEFAULT '0',
                  `ship_time` int(10) unsigned zerofill DEFAULT NULL,
                  `pay_time` int(10) unsigned zerofill DEFAULT NULL,
                  `th_addr` varchar(200) DEFAULT NULL,
                  `th_invoice` varchar(30) DEFAULT NULL,
                  `th_deli_id` int(10) unsigned DEFAULT NULL,
                  `th_deli_name` varchar(60) DEFAULT NULL,
                  `th_detail` varchar(200) DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `order_id` (`order_id`,`store_id`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=".str_replace( "-", "", CHARSET ). "  COMMENT='档口退货退款表' AUTO_INCREMENT=1 ;";
        db()->query($sql);
        //代发档口黑名单
        $sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."behalf_store_blacklist` (
                `bh_id` int(10) unsigned NOT NULL DEFAULT '0',
                 `store_id` int(10) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`bh_id`,`store_id`),
                KEY `store_id` (`store_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=".str_replace( "-", "", CHARSET ). ";";
        db()->query($sql);

    }

    public function addDBTable20151023()
    {
        //创建库存订单表
        $sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."stock_order` (\r\n  \t\t\t
          `order_id` int(10) unsigned NOT NULL AUTO_INCREMENT,\r\n  \t\t\t
          `order_sn` varchar(20) NOT NULL DEFAULT '',\r\n  \t\t\t
          `type` varchar(10) NOT NULL DEFAULT 'material',\r\n  \t\t\t
          `extension` varchar(10) NOT NULL DEFAULT '',\r\n  \t\t\t
          `seller_id` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `seller_name` varchar(100) DEFAULT NULL,\r\n  \t\t\t
          `buyer_id` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `buyer_name` varchar(100) DEFAULT NULL,\r\n  \t\t\t
          `buyer_email` varchar(60) NOT NULL DEFAULT '',\r\n  \t\t\t
          `status` tinyint(3) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `add_time` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `payment_id` int(10) unsigned DEFAULT NULL,\r\n  \t\t\t
          `payment_name` varchar(100) DEFAULT NULL,\r\n  \t\t\t
          `payment_code` varchar(20) NOT NULL DEFAULT '',\r\n  \t\t\t
          `out_trade_sn` varchar(20) NOT NULL DEFAULT '',\r\n  \t\t\t
          `pay_time` int(10) unsigned DEFAULT NULL,\r\n  \t\t\t
          `pay_message` varchar(255) NOT NULL DEFAULT '',\r\n  \t\t\t
          `ship_time` int(10) unsigned DEFAULT NULL,\r\n  \t\t\t
          `invoice_no` varchar(255) DEFAULT NULL,\r\n  \t\t\t
          `finished_time` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `goods_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',\r\n  \t\t\t
          `discount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',\r\n  \t\t\t
          `order_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',\r\n  \t\t\t
          `evaluation_status` tinyint(1) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `evaluation_time` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `anonymous` tinyint(3) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `postscript` varchar(255) NOT NULL DEFAULT '', \r\n  \t\t\t
          `pay_alter` tinyint(1) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `bh_id` int(10) NOT NULL DEFAULT '0',\r\n  \t\t\t
          PRIMARY KEY (`order_id`),\r\n  \t\t\t
          KEY `order_sn` (`order_sn`,`seller_id`),\r\n  \t\t\t
          KEY `seller_name` (`seller_name`),\r\n  \t\t\t
          KEY `buyer_name` (`buyer_name`),\r\n  \t\t\t
          KEY `add_time` (`add_time`)\r\n  \t\t\t
        ) ENGINE=MyISAM  DEFAULT CHARSET=".str_replace( "-", "", CHARSET )."  AUTO_INCREMENT=1;" ;
            db()->query($sql);


        $sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."stock_order_goods` (\r\n  \t\t\t
          `rec_id` int(10) unsigned NOT NULL AUTO_INCREMENT,\r\n  \t\t\t
          `order_id` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `goods_id` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `goods_name` varchar(255) NOT NULL DEFAULT '',\r\n  \t\t\t
          `spec_id` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `specification` varchar(255) DEFAULT NULL,\r\n  \t\t\t
          `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',\r\n  \t\t\t
          `quantity` int(10) unsigned NOT NULL DEFAULT '1',\r\n  \t\t\t
          `goods_image` varchar(255) DEFAULT NULL,\r\n  \t\t\t
          `evaluation` tinyint(1) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `comment` varchar(255) NOT NULL DEFAULT '',\r\n  \t\t\t
          `credit_value` tinyint(1) NOT NULL DEFAULT '0',\r\n  \t\t\t
          `is_valid` tinyint(1) unsigned NOT NULL DEFAULT '1',\r\n  \t\t\t
          `attr_value` varchar(255) DEFAULT NULL,\r\n  \t\t\t
          `store_id` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `oos_value` tinyint(1) unsigned NOT NULL DEFAULT '1',\r\n  \t\t\t
          `oos_reason` varchar(255) DEFAULT NULL,\r\n  \t\t\t
          PRIMARY KEY (`rec_id`),\r\n  \t\t\t
          KEY `order_id` (`order_id`,`goods_id`)\r\n  \t\t\t
        ) ENGINE=MyISAM  DEFAULT CHARSET=".str_replace( "-", "", CHARSET ). " AUTO_INCREMENT=1 ;";
            db()->query($sql);


        $sql ="CREATE TABLE IF NOT EXISTS `".DB_PREFIX."stock_order_log` (\r\n  \t\t\t
          `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,\r\n  \t\t\t
          `order_id` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          `operator` varchar(60) NOT NULL DEFAULT '',\r\n  \t\t\t
          `order_status` varchar(60) NOT NULL DEFAULT '',\r\n  \t\t\t
          `changed_status` varchar(60) NOT NULL DEFAULT '',\r\n  \t\t\t
          `remark` varchar(255) DEFAULT NULL,\r\n  \t\t\t
          `log_time` int(10) unsigned NOT NULL DEFAULT '0',\r\n  \t\t\t
          PRIMARY KEY (`log_id`),\r\n  \t\t\t
          KEY `order_id` (`order_id`)\r\n  \t\t\t
        ) ENGINE=MyISAM  DEFAULT CHARSET=".str_replace( "-", "", CHARSET ). " AUTO_INCREMENT=1 ;";
            db()->query($sql);


    }
}
class Init_FrontendApp
{
    public function _get_carts_top( $sess_id, $user_id = 0 )
    {
        $where_user_id = $user_id ? " AND user_id=".$user_id : "";
        $carts = array( );
        $cart_model =& m( "cart" );
        $cart_items = $cart_model->find( array(
            "conditions" => "session_id = '".$sess_id."'".$where_user_id,
            "fields" => ""
        ) );
        return $cart_items;
    }
    public function _get_header_gcategories( $amount, $position, $brand_is_recommend = 1 )
    {
        $gcategory_mod =& bm( "gcategory", array( "_store_id" => 0 ) );
        $gcategories = array( );
        $gcategories = $gcategory_mod->get_list( -1, TRUE );
        /* if ( !$amount['amount'] )
        {
            $gcategories = $gcategory_mod->get_list( -1, TRUE );
        }
        else
        {
            $gcategory = $gcategory_mod->get_list( 0, TRUE );
            $gcategories = $gcategory;
            foreach ( $gcategory as $val )
            {
                $result = $gcategory_mod->get_list( $val['cate_id'], TRUE );
                $result = array_slice( $result, 0, $amount['amount'] );
                //添加第三级
                if(!empty($result))
                {
                    foreach ($result as $cate_val)
                    {
                        $result = array_merge($result,$gcategory_mod->get_list( $cate_val['cate_id'], TRUE ));
                    }
                }
                $gcategories = array_merge( $gcategories, $result );
            }
        } */
        //cate_mname赋给cate_name
        if($gcategories)
        {
            foreach ($gcategories as $key=>$value)
            {
                if($value['cate_mname'])
                {
                    $gcategories[$key]['cate_name'] = $value['cate_mname'];
                }
            }
        }

        import( "tree.lib" );
        $tree = new Tree( );
        $tree->setTree( $gcategories, "cate_id", "parent_id", "cate_name" );
        $gcategory_list = $tree->getArrayList( 0 );

        if($amount['f_amount'])
        {
            $gcategory_list = array_slice( $gcategory_list, 0, $amount['f_amount'] );
        }

        if($amount['amount'])
        {
            foreach ($gcategory_list as $key=>$value)
            {
                $gcategory_list[$key]['children'] = array_slice($value['children'],0,$amount['amount']);
            }
        }

        if($amount['t_amount'])
        {
            foreach ($gcategory_list as $key=>$value)
            {
                foreach ($value['children'] as $key1=>$value1)
                {
                    $gcategory_list[$key]['children'][$key1]['children'] = array_slice($value1['children'], 0,$amount['t_amount']);
                }
            }
        }

       /*  $i = 0;
        foreach ( $gcategory_list as $k => $v )
        {
            $gcategory_list[$k]['top'] = isset( $position[$i] ) ? $position[$i] : "0px";
            $i++;
        } */
        /* $brand_mod =& m( "brand" );
        $conditions = "";
        if ( !empty( $brand_is_recommend ) )
        {
            $conditions = "recommended=1";
        }
        $brands = $brand_mod->find( array(
            "conditions" => $conditions
        ) ); */
        //dump($gcategory_list);
        return array(
            "gcategories" =>array_slice($gcategory_list,0,7),
            //"brands" => $brands,
        );
    }
}
class Init_SearchApp
{
    public function _get_group_by_info_by_brands( $by_brands, $param )
    {
        if ( !empty( $param['brand'] ) )
        {
            unset( $by_brands[$param['brand']] );
        }
        return $by_brands;
    }
    public function _get_group_by_info_by_region( $sql, $param )
    {
        $goods_mod =& m( "goods" );
        $by_regions = $goods_mod->getAll( $sql );
        if ( !empty( $param['region_id'] ) )
        {
            foreach ( $by_regions as $k => $v )
            {
                if ( $v['region_id'] == $param['region_id'] )
                {
                    unset( $by_regions[$k] );
                }
            }
        }
        return $by_regions;
    }
    public function _get_ultimate_store( $conditions, $brand )
    {
        $store = array( );
        $us_mod =& m( "ultimate_store" );
        $store_mod =& m( "store" );
        $ultimate_store = $us_mod->get( array(
            "conditions" => "status=1 ".$conditions,
            "fields" => "store_id,description"
        ) );
        if ( $ultimate_store )
        {
            $store = $store_mod->get( array(
                "conditions" => "store_id=".$ultimate_store['store_id'],
                "fields" => "store_logo,store_name"
            ) );
            if ( empty( $store['store_logo'] ) )
            {
                $store['store_logo'] = Conf::get( "default_store_logo" );
            }
            if ( $brand && !empty( $brand['brand_logo'] ) )
            {
                $store['store_logo'] = $brand['brand_logo'];
            }
            $store = array(
                array_merge( $ultimate_store, $store )
            );
        }
        return $store;
    }
}
class Init_OrderApp
{
    public function get_available_coupon( $store_id )
    {
        $time = gmtime( );
        $model_cart =& m( "cart" );
        $item_info = $model_cart->find( "store_id=".$store_id." AND session_id='".SESS_ID."'" );
        $price = 0;
        foreach ( $item_info as $val )
        {
            $price += $val['price'] * $val['quantity'];
        }
        $coupon = $model_cart->getAll( "SELECT *FROM ".DB_PREFIX."coupon_sn couponsn LEFT JOIN ".DB_PREFIX."coupon coupon ON couponsn.coupon_id=coupon.coupon_id LEFT JOIN ".DB_PREFIX."user_coupon user_coupon ON user_coupon.coupon_sn=couponsn.coupon_sn WHERE coupon.store_id = ".$store_id." AND couponsn.remain_times >=1 AND user_coupon.user_id=".$store_id." AND coupon.start_time <= ".$time." AND coupon.end_time >= ".$time." AND coupon.min_amount <= ".$price );
        return $coupon;
    }
}
class Init_Ymall_articleWidget
{
    public $options = NULL;
    public function _get_data( )
    {
        $acategory_mod =& m( "acategory" );
        $cate_ids = $acategory_mod->get_descendant( $this->options['cate_id'] );
        if ( $cate_ids )
        {
            $conditions = " AND cate_id ".db_create_in( $cate_ids );
            return $conditions;
        }
        $conditions = "";
        return $conditions;
    }
}

define( "LOCK_FILE", ROOT_PATH."/data/init.lock" );
//增加物流重量
define("WEIGHT_LOCK_FILE",ROOT_PATH."/data/init_weight.lock");
//ecm_order 加入 bh_id代发id,logistics物流公司名称
define("BEHALF_ORDER",ROOT_PATH."/data/init_behalf_order.lock");
if ( !file_exists( LOCK_FILE ) )
{
    Psmb_init::create_table( );
    file_put_contents( LOCK_FILE, 1 );
}
if(!file_exists(WEIGHT_LOCK_FILE))
{
    Psmb_init::addColumInDelivery_template();
    file_put_contents(WEIGHT_LOCK_FILE, 1);
}
/* if(!file_exists(BEHALF_ORDER) || trim(file_get_contents(BEHALF_ORDER)) != 'logistics_navgation_wx_bowner_order_refund_arc')
{
    Psmb_init::addColumnOrder();
    file_put_contents(BEHALF_ORDER, 'logistics_navgation_wx_bowner_order_refund_arc');
} */
if(!file_exists(BEHALF_ORDER) || trim(file_get_contents(BEHALF_ORDER)) != 'refund_member_delivery20160621')
{
    Psmb_init::addColumnOrder1();
    //Psmb_init::addDBTable20151023();
    Psmb_init::addDBTable20151106();
    Psmb_init::addDBTable20160214();
    file_put_contents(BEHALF_ORDER, 'refund_member_delivery20160621');
}

?>