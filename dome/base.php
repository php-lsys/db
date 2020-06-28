<?php
use LSYS\Database;
use LSYS\Database\DI;
use LSYS\Database\ConnectSchema;
include __DIR__."/Bootstarp.php";


$db =DI::get()->db("database.mysqli");
$db = Database::factory(LSYS\Config\DI::get()->config("database.mysqli"));


//针对查询强制请求从库
$conn=$db->getMasterConnect();
if ($conn instanceof ConnectSchema) {
    $conn->useSchema('test');
}
//得到完整表名
// $sql="select * from {$table_name} where id in :id";
// $result= $conn->query($sql,[":id"=>Database::expr("(1,2)")]);
/**
 * @var \LSYS\Database\AsyncMaster|Database $db 
 */
$a=$db->asyncQuery($db->getMasterConnect(), "select * from l_order_1");
$b=$db->asyncExec($db->getMasterConnect(), "INSERT INTO l_order_1 (sn, title, add_time)VALUES('aaaa', 'bbbbbbb', 0);");
$res=$db->asyncExecute();
var_dump($res->result($a)->asArray());
var_dump($res->result($b));



