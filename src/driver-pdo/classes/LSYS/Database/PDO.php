<?php
/**
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use \LSYS\Database\Connect\PDO as PDOConnect;
class PDO extends \LSYS\Database {
    /**
     * @var PDOConnect
     */
    protected $master_connection;
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::getConnect()
	 */
	public function getConnect(){
	    return $this->getMasterConnect();
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::getSlaveConnect()
	 */
	public function getSlaveConnect():ConnectSlave{
	    return $this->getMasterConnect();
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
	 * @see \LSYS\Database::disConnect()
	 */
	public function disConnect():bool{
	    $status = TRUE;
	    if (is_object( $this->master_connection )) {
	        $status = $this->master_connection->disConnect();
	        unset($this->master_connection);
	    }
	    $this->master_connection=null;
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
	    return is_object($this->master_connection);
	}
	/**
	 * 创建连接
	 * @param array $link_config
	 * @throws Exception
	 * @return \mysqli
	 */
	protected function connectCreate(array $link_config){
	    return new PDOConnect($this,$this->config,$link_config,$this->event_manager);
	}
}
