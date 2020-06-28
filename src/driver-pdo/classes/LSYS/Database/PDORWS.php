<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use \LSYS\Database\Connect\PDO as PDOConnect;
trait PDORWS{
    /**
     * @var PDOConnect
     */
    protected $read_connection;
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::getConnect()
     */
    public function getConnect(){
        if ($this->read_connection)return $this->read_connection;
        if ($this->master_connection)return $this->master_connection;
        return $this->getSlaveConnect();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::getMasterConnect()
     */
    public function getMasterConnect():ConnectMaster{
        if ($this->master_connection) return $this->master_connection;
        $config=$this->config->get("connection",array());
        $this->master_connection=$this->connectCreate($config);
        return $this->master_connection;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::getSlaveConnect()
     */
    public function getSlaveConnect():ConnectSlave{
        if ($this->read_connection) return $this->read_connection;
        $config=$this->config->get("slave_connection",array());
        $config=$this->weightGetConfig($config);
        if(!empty($config)){
            $this->read_connection=$this->connectCreate($config);
            return $this->read_connection;
        }
        return $this->getMasterConnect();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::disConnect()
     */
    public function disConnect():bool{
        $status = TRUE;
        if (is_object( $this->master_connection )) {
            $status = $this->master_connection->disConnect();
            unset($this->master_connection);
        }
        if (is_object ( $this->read_connection )) {
            $status = $status&&$this->read_connection->disConnect();
            unset($this->read_connection);
        }
        $this->master_connection=$this->read_connection=null;
        return $status;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::isMasterConnected()
     */
    public function isMasterConnected(): bool
    {
        return is_object($this->master_connection);
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::isSlaveConnected()
     */
    public function isSlaveConnected(): bool
    {
        return is_object($this->read_connection);
    }
}
