数据库层
===

[![Build Status](https://travis-ci.com/php-lsys/db.svg?branch=master)](https://travis-ci.com/php-lsys/db)
[![Coverage Status](https://coveralls.io/repos/github/php-lsys/db/badge.svg?branch=master)](https://coveralls.io/github/php-lsys/db?branch=master)

> 目的:使数据库层有统一封装,从而使数据库的调用保持一致的接口,如果需要更加自动化的数据库层的操作,
	请使用 [ https://github.com/lsys/model ] 或　[ https://github.com/lsys/orm ]
　

> 本库未实现任何可用的数据库适配,请根据实际需求引入以下包:

	"lsys/db-mysqli":"~1.0.0", [支持读写分离,异步查询`lsys/dbasync-mysqli`]
	"lsys/db-pdo-mysql":"~1.0.0", [支持读写分离]
	"lsys/db-pdo-postgresql":"~1.0.0", [支持读写分离]
	"lsys/db-pdo-sqlite":"~1.0.0",
	"lsys/db-postgresql":"~1.0.0",

> 使用读写分离时,有更改延时对从库进行查询操作数据存放使用依赖包:
	
	"lsys/db-rwcache-memcache":"~1.0.0",
	"lsys/db-rwcache-memcached":"~1.0.0",
	"lsys/db-rwcache-redis":"~1.0.0"


1. 执行SQL时需指定SQL的类型 [DBMS] ,除 Database::DQL 返回 Result 对象外,其他返回处理状态:
	*. Database::DQL 表示此SQL为数据库查询语句 如 select show desc　语句
	*. Database::DML 表示此SQL为数据库操纵语句 如 insert update delete 语句
	*. Database::DDL 表示此SQL为数据库定义语句 如 create 语句
	*. Database::DCL 表示此SQL为数据库控制语句 如 grant revoke commit 语句


2. 数据库配置使用 [https://github.com/lsys/config] 请参考 lconfig 文档
> 通过修改 \LSYS\Database\DI::$config="database.mysqli"; 默认使用那个配置

3. 可自行实现具体的数据库操作接口,示例可参考以上已实现包或邮件我

以MYSQL为例使用示例
---

> 需要更详细方法参考代码及代码注释

```
//得到数据库单例
$db =\LSYS\Database\DI::get()->db();
```

```
//得到完整表名
$table_name=$db->quote_table("order");
```
#####提示
1. 为了防止SQL 注入,请使用预编译SQL进行数据库操作
2. 对于MYSQLI 和 PDO 的预编译的接口做了统一,保持一致调用
3. 如果你更喜欢相对老式的SQL语句操作,请参考 : /dome/base.php 的示例
4. 示例用的表在 /dome/test_db.sql 中,需测试示例,请先导入并配置 /dome/config/database.php


#####预编译SQL使用示例

```
//------------------------------------查询---------------------------------------
$prepare=$db->prepare(Database::DQL, "select * from {$table_name} where sn=:sn");
$prepare->bindValue("sn",'SN001');
//OR 多个绑定
//$prepare->bindValue(array("sn"=>"SN001"));
$result=$prepare->execute();
//$result 和 db->query 返回保持一致
//$result->set_fetch_mode(Database::FETCH_OBJ);
foreach ($result as $v){
	print_r($v);
}
```
```
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
```
```
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
```
```
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
```

