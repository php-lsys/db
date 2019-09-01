<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\Database\PDO\Prepare as PPrepare;
class PDO extends \LSYS\Database {
	// PDO uses no quoting for identifiers
	protected $_identifier = '';
	protected $connection;
	/**
	 * @return \LSYS\Database\PDO\ConnectManager
	 */
	public function getConnectManager()
	{
	    if(!$this->connection) $this->connection= new \LSYS\Database\PDO\ConnectManager($this->config);
	    return $this->connection;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::prepare()
	 */
	public function prepare($sql){
	    return new PPrepare($this, $sql,$this->event_manager,$this->slave_check);
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::beginTransaction()
	 */
	public function beginTransaction($mode = NULL)
	{
		$connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER);
		return $connent->beginTransaction();
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::commit()
	 */
	public function commit()
	{
	    $connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER);
		return $connent->commit();
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::rollback()
	 */
	public function rollback()
	{
	    $connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER);
		return $connent->rollBack();
	}
	public function quote($value,$value_type=null) {
	    if(is_string($value)||is_numeric($value)){
	        $connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_AUTO);
	        return $connent->quote($value);
	    }
	    return parent::quote($value,$value_type);
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::escape()
	 */
	public function escape($value)
	{
	    $connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_AUTO);
	    $value=$connent->quote($value);
	    if(is_string($value))$value=trim($value,"'");
	    return $value;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::inTransaction()
	 */
	public function inTransaction(){
	    return $this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER)->inTransaction();
	}
}
