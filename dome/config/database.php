<?php
return array(
	"mysqli"=>array(
		"type"=>\LSYS\Database\MYSQLi::class,
		"charset"=>"UTF8",
		"table_prefix"=>"l_",
		"try_re_num"=>"2",
		"try_re_sleep"=>"1",
		"connection"=>array(
			'database' => 'test',
			'hostname' => 'localhost',
			'username' => 'root',
			'password' => '',
			'persistent' => FALSE,
			"variables"=>array(
			),
		)
	),
    "pdo_mysql"=>array(
        //PDO MYSQL 配置
        "type"=>\LSYS\Database\PDO\MYSQL::class,
        "charset"=>"UTF8",
        "table_prefix"=>"l_",
        "try_re_num"=>"2",
        "try_re_sleep"=>"1",
        "connection"=>array(
            //单数据库使用此配置
            'dsn'        => 'mysql:host=127.0.0.1;dbname=test;',
            'username'   => 'root',
            'password'   => "",
            //下面两参数一般在命令行中运行用到[只支持MYSQL]
            'persistent' => FALSE,
            "variables"=>array(
            ),
        ),
    ),
);