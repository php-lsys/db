<?php
use LSYS\Database;
use LSYS\Database\DI;
include __DIR__."/Bootstarp.php";
$db =DI::get()->db("database.mysqli");
$db = Database::factory(LSYS\Config\DI::get()->config("database.pdo_mysql"));


//得到完整表名
$table_name=$db->quoteTable("order");
$sql="select * from {$table_name} where id>=:id";
$result= $db->query($sql,[":id"=>"764"]);

//测试断线重连.这时候重启数据库
$result= $db->query($sql,[":id"=>"764"]);

echo $result->count();
