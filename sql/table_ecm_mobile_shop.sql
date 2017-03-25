DROP TABLE IF EXISTS `ecm_mobile_shop`;
CREATE TABLE IF NOT EXISTS `ecm_mobile_shop` (
  `user_id` int(10) unsigned NOT NULL,
  `shop_nick` varchar(60) NOT NULL,
  `profit` decimal(8,2) NOT NULL DEFAULT 10.0,
  `percent` int(3) unsigned NOT NULL DEFAULT 100,
  `mobile` varchar(60) NOT NULL DEFAULT '',
  `im_qq` varchar(60) NOT NULL DEFAULT '',
  `im_wx` varchar(60) NOT NULL DEFAULT '',
  `im_ww` varchar(60) NOT NULL DEFAULT '',
  `announcement` varchar(1000) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='手机微店配置表';
