<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
interface AsyncMaster extends AsyncSlave{
     /**
     * 添加异步执行
     * @param ConnectSlave $connect 连接模板对象
     * @param string $sql
     * @param array $value
     * @param array $value_type
     * @return int
     */
    public function asyncExec(ConnectMaster $connect,string $sql,array $value=[],array $value_type=[]):int;
}