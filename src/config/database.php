<?php
/**
 * lsys database 
 * 配置示例 未引入
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
return array(
	"dome"=>array(
		//PDO MYSQL 配置
		"type"=>'class',
		"charset"=>"UTF8",
		"table_prefix"=>"",
		"connection"=>array(
			
		),
	    //读写分离中只读数据库
	    'slave_connection'=>array(
	        array(
	           
	        )
	    ),
	),
);