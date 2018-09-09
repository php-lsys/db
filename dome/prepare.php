<?php
use LSYS\Database\DI;
use LSYS\Database;
include __DIR__."/Bootstarp.php";
$db =DI::get()->db("database.mysqli");
//$db = Database::factory(LSYS\Config\DI::get()->config("database.mysqli"));

//得到完整表名
$table_name=$db->quote_table("order");

//为了防止SQL 注入,请劲量使用此方法进行数据库操作
//对于MYSQLI 和 PDO 的预编译的接口做了统一,保持一致调用
//================================预编译SQL使用示例========================================

//------------------------------------查询---------------------------------------
$prepare=$db->prepare(Database::DQL, "select * from {$table_name} where sn=:sn");
$prepare->bindValue("sn",'SN001');
//OR 多个绑定
//$prepare->bindValue(array("sn"=>"SN001"));
$result=$prepare->execute();
//$result 和 db->query 返回保持一致
//$result->set_fetch_mode(Database::FETCH_OBJ);
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
$sql="insert into {$table_name} (`sn`, `title`, `add_time`) values (:sn, :title, :add_time)";
//发送SQL 请求
$prepare=$db->prepare(Database::DML, $sql);
$prepare->bindValue(array(
	'sn'=>'SN001',
	'title'=>'title'.uniqid(),
	'add_time'=>time(),
));
if ($prepare->execute()){
	//成功
	echo $prepare->insert_id();//最后插入ID
	echo "\n";
	echo $prepare->affected_rows();//插入行数
	echo "\n";
}

//------------------------------------更新-------------------------------------------
$sql="update {$table_name} set title=:title where id=:id";
$prepare=$db->prepare(Database::DML, $sql);
$prepare->bindValue(array(
	'id'=>1,
	'title'=>'title'.uniqid(),
));
if ($prepare->execute()){
	//成功
	echo $prepare->affected_rows();//影响行数
	echo "\n";
}

//------------------------------------删除-------------------------------------------
$sql="delete from {$table_name} where id=:id";
$prepare=$db->prepare(Database::DML, $sql);
$prepare->bindValue(array(
	'id'=>3,
));
if ($prepare->execute()){
	//成功
	echo $prepare->affected_rows();//影响行数
	echo "\n";
}



