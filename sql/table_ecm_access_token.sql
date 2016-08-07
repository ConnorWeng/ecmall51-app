DROP TABLE IF EXISTS `ecm_access_token`;
CREATE TABLE IF NOT EXISTS `ecm_access_token` (
  `user_id` int(10) unsigned NOT NULL,
  `access_token` varchar(64) NOT NULL,
  `add_time` int(10) unsigned NOT NULL,
  `last_update` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='access token 认证方式';
