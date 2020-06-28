<?php
/**
 * lsys database 
 * 配置示例 未引入
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
return array(
	"pdo_postgresql"=>array(
		//PDO Postgresql 配置
		"type"=>\LSYS\Database\PDO\PostgreSQL::class,
		"charset"=>"UTF8",
		"table_prefix"=>"",
		"connection"=>array(
			//单数据库使用此配置
			'dsn'        => 'pgsql:host=127.0.0.1;dbname=tt;',
			'username'   => 'postgres',
			'password'   => "123456",
			'schema'   => "why",
			'persistent' => FALSE,
			"variables"=>array(
			),
		),
	    //读写分离中只读数据库
	    'slave_connection'=>array(
	        array(
	            'dsn'        => 'pgsql:host=127.0.0.1;dbname=lsys;',
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