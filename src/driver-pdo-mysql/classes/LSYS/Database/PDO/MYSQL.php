<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\PDO;
use LSYS\Database\Exception;

class MYSQL extends RWSPDO {
    protected $identifier = '`';
    public function getConnectManager()
    {
        if(!$this->connection) $this->connection= new \LSYS\Database\PDO\MYSQLRWSConnectManager($this->config);
        return $this->connection;
    }
    public function rollback()
    {
        try {
            return parent::rollback();
        } catch (Exception $e) {
            if(in_array(intval($e->getCode()), [2006,2013])&&strpos($e->getMessage(), 'has gone away')!==false){
                return true;
            }
            throw $e;
        }
    }
}
