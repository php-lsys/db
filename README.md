数据库层
===

[![Build Status](https://travis-ci.com/php-lsys/db.svg?branch=master)](https://travis-ci.com/php-lsys/db)
[![Coverage Status](https://coveralls.io/repos/github/php-lsys/db/badge.svg?branch=master)](https://coveralls.io/github/php-lsys/db?branch=master)

> 目的:使数据库层有统一封装,从而使数据库的调用保持一致的接口,如果需要更加自动化的数据库层的操作,
	请使用 [ https://github.com/lsys/model ] 或　[ https://github.com/lsys/orm ]


1. 数据库配置使用 [https://github.com/lsys/config] 请参考 lconfig 文档
> 通过修改 \LSYS\Database\DI::$config="database.mysqli"; 默认使用那个配置

2. 可自行实现具体的数据库操作接口,示例可参考以上已实现包或邮件我

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

使用示例参见/dome/
