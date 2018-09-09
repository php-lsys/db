-- 示例用表SQL语句

CREATE TABLE IF NOT EXISTS `l_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sn` char(12) NOT NULL,
  `title` varchar(32) NOT NULL,
  `add_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

