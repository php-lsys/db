<?php
return array(
	"mysqli"=>array(
		"type"=>\LSYS\Database\MYSQLi::class,
		"charset"=>"UTF8",
		"table_prefix"=>"l_",
		"connection"=>array(
			'database' => 'test',
			'hostname' => '127.0.0.1',
			'username' => 'root',
			'password' => '',
			'persistent' => FALSE,
			"variables"=>array(
			),
		),
	    'slave_connection'=>array(
	        array(
	            'database' => 'test',
	            'hostname' => '127.0.0.1',
	            'username' => 'root',
	            'password' => '',
	            'persistent' => FALSE,
	            "variables"=>array(
	            ),
	        )
	    ),
	),
    "pdo_mysql"=>array(
        //PDO MYSQL 配置
        "type"=>\LSYS\Database\PDO\MYSQL::class,
        "charset"=>"UTF8",
        "table_prefix"=>"l_",
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
        //读写分离中只读数据库
        'slave_connection'=>array(
            array(
                'dsn'        => 'mysql:host=127.0.0.1;dbname=test;',
                'username'   => 'root',
                'password'   => "",
                'weight'	 => 1,
                'persistent' => FALSE,
                "variables"=>array(
                )
            )
        ),
    ),
);