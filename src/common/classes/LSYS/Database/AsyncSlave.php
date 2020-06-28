<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
interface AsyncSlave{
    /**
     * 添加异步查询
     * @param ConnectSlave $connect 连接模板对象
     * @param string $sql
     * @param array $value
     * @param array $value_type
     * @return int
     */
    public function asyncQuery(ConnectSlave $connect,string $sql,array $value=[],array $value_type=[]):int;
    /**
     * 执行异步查询
     * @return AsyncResult
     */
    public function asyncExecute():AsyncResult;
}