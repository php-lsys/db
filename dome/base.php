<?php
use LSYS\Database;
use LSYS\Database\DI;
use LSYS\Database\ConnectManager;
include __DIR__."/Bootstarp.php";
$db =DI::get()->db("database.mysqli");
$db = Database::factory(LSYS\Config\DI::get()->config("database.mysqli"));

//针对查询强制请求从库
//$db->getConnectManager()->setQuery(ConnectManager::QUERY_SLAVE);

//得到完整表名
$table_name=$db->quoteTable("order");
$sql="select * from {$table_name} where id in :id";
$result= $db->query($sql,[":id"=>Database::expr("(1,2)")]);
echo $db->lastQuery();
