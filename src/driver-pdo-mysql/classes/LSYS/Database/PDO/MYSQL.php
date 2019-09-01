<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\PDO;
class MYSQL extends RWSPDO {
    public function getConnectManager()
    {
        if(!$this->connection) $this->connection= new \LSYS\Database\PDO\MYSQLRWSConnectManager($this->config);
        return $this->connection;
    }
}
