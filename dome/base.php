<?php
use LSYS\Database;
use LSYS\Database\DI;
include __DIR__."/Bootstarp.php";
$db =DI::get()->db("database.mysqli");
$db = Database::factory(LSYS\Config\DI::get()->config("database.mysqli"))
//    ->setEventManager(\LSYS\EventManager\DI::get()->eventManager())
//    ->setSlaveQueryCheck(new LSYS\Database\SlaveQueryCheck(new LSYS\Database\SlaveQueryCheck\Cache\Memcached()))
;


//得到完整表名
$table_name=$db->quoteTable("order");
//========================================基本使用========================================
//得到用于SQL的值
$value=$db->quote("SN001");
//得到用户SQL的字段
// $column=$db->quoteColumn("sn");
//------------------------------------查询---------------------------------------
$sql="select * from {$table_name} where sn={$value}";
$result= $db->query($sql,[]);//DQL返回结果对象,其他返回布尔

//直接拿结果
$record=$result->current();//第一个结果
if ($record===null){
	echo "not find";
}
//遍历结果
foreach ($result as $v){
	print_r($v);
}
//------------------------------------插入-------------------------------------------
$sql="insert into {$table_name} (sn,title,add_time) values ('SN001','".uniqid()."','".time()."') ";
//发送SQL 请求
$result=$db->exec($sql);
if ($result){
	//成功
	echo $db->insertId();//最后插入ID
	echo "\n";
	echo $db->affectedRows();//插入行数
	echo "\n";
}

//------------------------------------更新-------------------------------------------
$id=$db->quote(1);
$sql="update {$table_name} set title='update data' where id={$id}";
$result=$db->exec($sql);
if ($result){
	//成功
	echo $db->affectedRows();//影响行数
	echo "\n";
}

//------------------------------------删除-------------------------------------------
$id=$db->quote(3);
$sql="delete from {$table_name} where id={$id}";
$result=$db->exec($sql);
if ($result){
	//成功
	echo $db->affectedRows();//影响行数
	echo "\n";
}

