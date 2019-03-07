<?php
include __DIR__."/Bootstarp.php";
//--------------- 自定义DI----------------------
// LSYS\Config\DatabaseDepend::set(function(){
//     LSYS\DI::get()->databaseConfigDb(new LSYS\DI\MethodCallback(function(){
//         return \LSYS\Database\DI::get()->db("database.mysqli");
//     }));
//     return LSYS\Config\DatabaseDepend::get();
// });
//-------------------------------------------------------

$config = new LSYS\Config\Database("aaa");
var_dump(serialize($config));
var_dump($config->get("bbb"));
var_dump(unserialize(serialize($config))->asArray());








