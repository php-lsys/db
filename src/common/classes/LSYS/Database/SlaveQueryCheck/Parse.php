<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\SlaveQueryCheck;
interface Parse{
    /**
     * 解析查询的用到的表 只有查询会进
     * @return array $table
     */
    public function queryParseTable($sql);
    /**
     * 解析执行用到的表 查询也会进,部分数据库支持时查询更新
     * @return array $table
     */
    public function execParseTable($sql);
}