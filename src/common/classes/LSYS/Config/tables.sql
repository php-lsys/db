CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(254) CHARACTER SET latin1 DEFAULT NULL COMMENT 'config name',
  `value` varchar(1024) CHARACTER SET latin1 DEFAULT '' COMMENT 'config value',
  `section` tinyint(4) DEFAULT NULL COMMENT 'Node number',
  PRIMARY KEY (`id`),
  KEY `newtable_name_idx` (`name`)
)