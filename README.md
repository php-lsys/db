数据库层
===

[![Build Status](https://travis-ci.com/php-lsys/db.svg?branch=master)](https://travis-ci.com/php-lsys/db)
[![Coverage Status](https://coveralls.io/repos/github/php-lsys/db/badge.svg?branch=master)](https://coveralls.io/github/php-lsys/db?branch=master)

> 目的:使数据库层有统一封装,从而使数据库的调用保持一致的接口,如果需要更加自动化的数据库层的操作,请使用 [ https://github.com/lsys/model ]


1. 数据库配置使用 [https://github.com/lsys/config] 请参考 lconfig 文档

> 通过修改 \LSYS\Database\DI::$config="database.mysqli"; 默认使用那个配置

2. 可自行实现具体的数据库操作接口,示例可参考以上已实现包或邮件我

> 需要更详细方法参考代码及代码注释

跟多使用示例参见/dome/ 或 /tests
