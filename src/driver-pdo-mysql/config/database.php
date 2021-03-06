<?php
/**
 * lsys database 
 * 配置示例 未引入
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
return array(
	"pdo_mysql"=>array(
		//PDO MYSQL 配置
		"type"=>\LSYS\Database\PDO\MYSQL::class,
		"charset"=>"UTF8",
		"table_prefix"=>"",
	    'try_re_num' => 0,//连接断开尝试重连次数 -1 不限制,默认为0 不重连
	    'try_re_sleep' => 0,//连接断开重连时暂停秒数,默认为:0 最好不要为0 如果mysql出问题为0可能导致连接数用光问题 
		"connection"=>array(
			//单数据库使用此配置
			'dsn'        => 'mysql:host=127.0.0.1;dbname=lsys;',
			'username'   => 'root',
			'password'   => "110",
			//下面两参数一般在命令行中运行用到[只支持MYSQL]
			'persistent' => FALSE,
			"variables"=>array(
			),
		),
	    //读写分离中只读数据库
	    'slave_connection'=>array(
	        array(
	            'dsn'        => 'mysql:host=127.0.0.1;dbname=lsys;',
	            'username'   => 'root',
	            'password'   => "110",
	            'weight'	 => 1,
	            'persistent' => FALSE,
	            "variables"=>array(
	            )
	        )
	    ),
	),
);