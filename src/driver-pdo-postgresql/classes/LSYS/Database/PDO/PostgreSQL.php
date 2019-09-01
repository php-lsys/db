<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\PDO;
class PostgreSQL extends RWSConnectManager {
    /**
     * @return \LSYS\Database\PDO\RWSConnectManager
     */
    public function getConnectManager()
    {
        if(!$this->connection) $this->connection= new \LSYS\Database\PDO\PostgreSQLRWSConnectManager($this->config);
        return $this->connection;
    }
}
