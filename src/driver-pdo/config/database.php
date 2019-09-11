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
        "type"=>\LSYS\Database\PDO::class,
        "charset"=>"UTF8",
        "table_prefix"=>"",
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
    ),
    "pdo_sqlite"=>array(
        //PDO Sqlite 配置
        "type"=>\LSYS\Database\PDO::class,
        "table_prefix"=>"",
        "connection"=>array(
            'dsn'        => 'sqlite:/your_sqlite_path/dbname',
            'persistent' => FALSE,
            "variables"=>array(
            )
        ),
    ),
);