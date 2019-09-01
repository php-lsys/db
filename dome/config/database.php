<?php
return array(
	"mysqli"=>array(
		"type"=>\LSYS\Database\MYSQLi::class,
		"charset"=>"UTF8",
		"table_prefix"=>"l_",
		"connection"=>array(
			'database' => 'test',
			'hostname' => 'localhost',
			'username' => 'root',
			'password' => '110',
			'persistent' => FALSE,
			"variables"=>array(
			),
		),
	    //读写分离中只读数据库
	    'slave_connection'=>array(
	        array(
	            'database' => 'test',
	            'hostname' => '127.0.0.1',
	            'username' => 'root',
	            'password' => '110',
	            'persistent' => FALSE,
	            "variables"=>array(
	            ),
	        )
	    ),
	)
);