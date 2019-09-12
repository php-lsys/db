<?php
use LSYS\Database;
use LSYS\Database\DI;
include __DIR__."/Bootstarp.php";
$db =DI::get()->db("database.mysqli");
$db = Database::factory(LSYS\Config\DI::get()->config("database.mysqli"));


//得到完整表名
$table_name=$db->quoteTable("order");
$sql="select * from {$table_name} where id in :id";
$result= $db->query($sql,[":id"=>Database::expr("(1,2)")]);
echo $db->lastQuery();
