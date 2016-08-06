<?php
$filename = ROOT_PATH . '/data/datacall.inc.php';
file_put_contents($filename, "<?php return array(); ?>");
$db=&db();
$db->query("CREATE TABLE ".DB_PREFIX."my_money (

  `id` int(10) unsigned NOT NULL auto_increment,
  `add_time` int(10) unsigned NULL,
  `mibao_id` int(10) NOT NULL default '0',
  `mibao_sn` varchar(30) NULL,
  `user_id` int(10) unsigned NOT NULL default '0',
  `user_name` varchar(100) NULL,
  `bank_sn` varchar(100) NULL,
  `bank_name` varchar(20) NULL,
  `bank_username` varchar(20) NULL,
  `bank_add` varchar(60) NULL,

  `zf_pass` varchar(32) NULL,
  `pass_tw` varchar(60) NULL,
  `pass_hd` varchar(60) NULL,

  `jifen_z` int(10) NOT NULL default '0',
  `jifen` int(10) NOT NULL default '0',

  `money_dj` decimal(10,2) NOT NULL default '0',
  `money` decimal(10,2) NOT NULL default '0',

  PRIMARY KEY  (id)
) 
ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");


$db->query("CREATE TABLE ".DB_PREFIX."my_card (

  `id` int(10) unsigned NOT NULL auto_increment,
  `admin_name` varchar(100) NULL,
  `add_time` int(10) unsigned NULL,
  `cz_time` int(10) unsigned NULL,
  `guoqi_time` int(10) unsigned NULL,
  `user_id` int(10) unsigned NOT NULL default '0',
  `user_name` varchar(100) NULL,
  `card_sn` varchar(30) NULL,
  `card_pass` varchar(30) NULL,
  `money` decimal(10,2) NOT NULL default '0',

  PRIMARY KEY  (id)
) 
ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");

$db->query("CREATE TABLE ".DB_PREFIX."my_jifen (

  `id` int(10) unsigned NOT NULL auto_increment,
  `yes_no` int(10) NOT NULL default '0',
  `ids` int(10) NOT NULL default '0',
  `add_time` int(10) unsigned NULL,
  `jifen` int(10) NOT NULL default '0',
  `wupin_name` varchar(60) NULL,
  `wupin_imgs` varchar(255) NULL,
  `wupin_img` varchar(255) NULL,
  `jiazhi` decimal(10,2) NOT NULL default '0',
  `shuliang` int(10) unsigned NOT NULL default '0', 
  `yiduihuan` int(10) unsigned NOT NULL default '0', 
 
  `user_id` int(10) unsigned NOT NULL default '0',
  `user_name` varchar(100) NULL,
     
  `my_name` varchar(100) NULL,
  `my_add` varchar(255) NULL,
  `my_tel` varchar(255) NULL,
  `my_mobile` varchar(255) NULL,  
  `log_text` TEXT NULL,
  `shenhe` int(10) NOT NULL default '0',
  `wuliu_name` varchar(100) NULL,
  `wuliu_danhao` varchar(100) NULL,

  PRIMARY KEY  (id)
) 
ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");
 
$db->query("CREATE TABLE ".DB_PREFIX."my_moneylog (

  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) NOT NULL default '0',
  `user_name` varchar(50) NULL default '0',
  `order_id` int(10) NOT NULL default '0',
  `order_sn` varchar(50) NULL default '0',

  `seller_id` int(10) unsigned NULL,
  `seller_name` varchar(100) NULL,

  `buyer_id` int(10) unsigned NULL,
  `buyer_name` varchar(100) NULL,

  `coupon_id` int(10) unsigned NULL,
  `coupon_sn` varchar(50) NULL default '0',
  `coupon_amount` decimal(10,2) NOT NULL default '0.00',

  `tx_username` varchar(50) NULL,
  `tx_bankname` varchar(50) NULL,
  `tx_banksn` varchar(50) NULL,
  `tx_add` varchar(255) NULL,

  `add_time` int(10) unsigned NULL,
  `admin_name` varchar(100) NULL,
  `admin_time` int(10) unsigned NULL,
  `leixing` int(3) unsigned NOT NULL default '0',
  `caozuo` int(3) unsigned NOT NULL default '0',
  `s_and_z` int(3) unsigned NULL,
  `user_log_del` int(3) unsigned NULL default '0',

  `money_zs` decimal(10,2) NOT NULL default '0.00',
  `money` decimal(10,2) NOT NULL default '0.00',
  `log_text` varchar(255) NULL,
  
  PRIMARY KEY  (id)
) 
ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");


$db->query("CREATE TABLE ".DB_PREFIX."my_mibao (

  `id` int(10) unsigned NOT NULL auto_increment,
  `mibao_sn` varchar(50) NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `user_name` varchar(20) NULL,
  `admin_name` varchar(100) NULL,
  `add_time` int(8) unsigned NOT NULL default '0',
  `bd_time` int(8) unsigned NULL,
  `dq_time` int(8) unsigned NULL,
  `ztai` int(3) unsigned NOT NULL default '0',

  `A1` varchar(10) NULL,
  `B1` varchar(10) NULL,
  `C1` varchar(10) NULL,
  `D1` varchar(10) NULL,
  `E1` varchar(10) NULL,
  `F1` varchar(10) NULL,
  `G1` varchar(10) NULL,
  `H1` varchar(10) NULL,

  `A2` varchar(10) NULL,
  `B2` varchar(10) NULL,
  `C2` varchar(10) NULL,
  `D2` varchar(10) NULL,
  `E2` varchar(10) NULL,
  `F2` varchar(10) NULL,
  `G2` varchar(10) NULL,
  `H2` varchar(10) NULL,

  `A3` varchar(10) NULL,
  `B3` varchar(10) NULL,
  `C3` varchar(10) NULL,
  `D3` varchar(10) NULL,
  `E3` varchar(10) NULL,
  `F3` varchar(10) NULL,
  `G3` varchar(10) NULL,
  `H3` varchar(10) NULL,

  `A4` varchar(10) NULL,
  `B4` varchar(10) NULL,
  `C4` varchar(10) NULL,
  `D4` varchar(10) NULL,
  `E4` varchar(10) NULL,
  `F4` varchar(10) NULL,
  `G4` varchar(10) NULL,
  `H4` varchar(10) NULL,

  `A5` varchar(10) NULL,
  `B5` varchar(10) NULL,
  `C5` varchar(10) NULL,
  `D5` varchar(10) NULL,
  `E5` varchar(10) NULL,
  `F5` varchar(10) NULL,
  `G5` varchar(10) NULL,
  `H5` varchar(10) NULL,

  `A6` varchar(10) NULL,
  `B6` varchar(10) NULL,
  `C6` varchar(10) NULL,
  `D6` varchar(10) NULL,
  `E6` varchar(10) NULL,
  `F6` varchar(10) NULL,
  `G6` varchar(10) NULL,
  `H6` varchar(10) NULL,

  `A7` varchar(10) NULL,
  `B7` varchar(10) NULL,
  `C7` varchar(10) NULL,
  `D7` varchar(10) NULL,
  `E7` varchar(10) NULL,
  `F7` varchar(10) NULL,
  `G7` varchar(10) NULL,
  `H7` varchar(10) NULL,

  `A8` varchar(10) NULL,
  `B8` varchar(10) NULL,
  `C8` varchar(10) NULL,
  `D8` varchar(10) NULL,
  `E8` varchar(10) NULL,
  `F8` varchar(10) NULL,
  `G8` varchar(10) NULL,
  `H8` varchar(10) NULL,

  `A9` varchar(10) NULL,
  `B9` varchar(10) NULL,
  `C9` varchar(10) NULL,
  `D9` varchar(10) NULL,
  `E9` varchar(10) NULL,
  `F9` varchar(10) NULL,
  `G9` varchar(10) NULL,
  `H9` varchar(10) NULL,

  PRIMARY KEY  (`id`)
) 
ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");


$db->query("CREATE TABLE ".DB_PREFIX."my_paysetup (

  `id` int(10) unsigned NOT NULL auto_increment,

  `chinabank_key` varchar(255) NULL,
  `chinabank_mid` int(32) unsigned NULL,
  `chinabank_url` varchar(255) NULL,
  `chinabank_remark1` varchar(255) NULL,
  `chinabank_remark2` varchar(255) NULL,

  `yeepay_key` varchar(255) NULL,
  `yeepay_mid` int(32) unsigned NULL,
  `yeepay_url` varchar(255) NULL,
  `yeepay_ext1` varchar(255) NULL,
  `yeepay_ext2` varchar(255) NULL,

  `yeepay_bank` int(8) unsigned NOT NULL default '100',
  `yeepay_junnet` int(8) unsigned NOT NULL default '100',
  `yeepay_sndacard` int(8) unsigned NOT NULL default '100',
  `yeepay_szx` int(8) unsigned NOT NULL default '100',
  `yeepay_zhengtu` int(8) unsigned NOT NULL default '100',
  `yeepay_qqcard` int(8) unsigned NOT NULL default '100',
  `yeepay_unicom` int(8) unsigned NOT NULL default '100',
  `yeepay_jiuyou` int(8) unsigned NOT NULL default '100',
  `yeepay_ypcard` int(8) unsigned NOT NULL default '100',
  `yeepay_lianhuaokcard` int(8) unsigned NOT NULL default '100',
  `yeepay_netease` int(8) unsigned NOT NULL default '100',
  `yeepay_wanmei` int(8) unsigned NOT NULL default '100',
  `yeepay_sohu` int(8) unsigned NOT NULL default '100',
  
  `alipay_id` varchar(255) NULL,
  `alipay_key` varchar(255) NULL,
  `alipay_jiekou` varchar(255) NULL,
  `alipay_qubiema` varchar(255) NULL,

  `tenpay_id` varchar(255) NULL,
  `tenpay_key` varchar(255) NULL,
  `tenpay_qianmeng` varchar(255) NULL,
  `tenpay_qubiema` varchar(255) NULL,

  `tenpay2_id` varchar(255) NULL,
  `tenpay2_key` varchar(255) NULL,
  `tenpay2_leixing` varchar(255) NULL,
  `tenpay2_qubiema` varchar(255) NULL,

  `bank_icbc_id` varchar(255) NULL,
  `bank_icbc_name` varchar(255) NULL,
  `bank_icbc_add` varchar(255) NULL,

  `bank_ccb_id` varchar(255) NULL,
  `bank_ccb_name` varchar(255) NULL,
  `bank_ccb_add` varchar(255) NULL,

  `bank_abb_id` varchar(255) NULL,
  `bank_abb_name` varchar(255) NULL,
  `bank_abb_add` varchar(255) NULL,

  `bank_cib_id` varchar(255) NULL,
  `bank_cib_name` varchar(255) NULL,
  `bank_cib_add` varchar(255) NULL,

  `bank_bc_id` varchar(255) NULL,
  `bank_bc_name` varchar(255) NULL,
  `bank_bc_add` varchar(255) NULL,

  PRIMARY KEY  (id)
) 
ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");

//娴奶瘯锁版嵁镄勬坊锷狅纴涓嶉渶瑕佸彨浠ュ垹闄?
$db->query("INSERT INTO ".DB_PREFIX."my_paysetup (`id`, `chinabank_key`, `chinabank_mid`, `chinabank_url`, `chinabank_remark1`, `chinabank_remark2`, `yeepay_key`, `yeepay_mid`, `yeepay_url`, `yeepay_ext1`, `yeepay_ext2`, `yeepay_bank`, `yeepay_junnet`, `yeepay_sndacard`, `yeepay_szx`, `yeepay_zhengtu`, `yeepay_qqcard`, `yeepay_unicom`, `yeepay_jiuyou`, `yeepay_ypcard`, `yeepay_lianhuaokcard`, `yeepay_netease`, `yeepay_wanmei`, `yeepay_sohu`, `alipay_id`, `alipay_key`, `alipay_jiekou`, `alipay_qubiema`, `tenpay_id`, `tenpay_key`, `tenpay_qianmeng`, `tenpay_qubiema`, `tenpay2_id`, `tenpay2_key`, `tenpay2_leixing`, `tenpay2_qubiema`, `bank_icbc_id`, `bank_icbc_name`, `bank_icbc_add`, `bank_ccb_id`, `bank_ccb_name`, `bank_ccb_add`, `bank_abb_id`, `bank_abb_name`, `bank_abb_add`, `bank_cib_id`, `bank_cib_name`, `bank_cib_add`, `bank_bc_id`, `bank_bc_name`, `bank_bc_add`) VALUES (NULL, '350500198704145018', '21391940', 'index.php?app=my_money&act=chinabank_pay', NULL, NULL, '', '0', '', '', '', '100', '100', '100', '100', '100', '100', '100', '100', '100', '100', '100', '100', '100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);");

?>