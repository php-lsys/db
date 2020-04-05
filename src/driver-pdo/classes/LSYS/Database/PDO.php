<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\Database\PDO\Prepare as PPrepare;
use LSYS\Database\EventManager\DBEvent;
class PDO extends \LSYS\Database {
	// PDO uses no quoting for identifiers
	protected $identifier = '';
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
		$connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER_MUST);
		$this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionBegin($connent));
		$status=$connent->beginTransaction();
		if (!$status)throw new Exception ($connent->errorInfo(),$connent->errorCode());
		return $status;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::commit()
	 */
	public function commit()
	{
	    $connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER_MUST);
	    $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionCommit($connent));
	    $status=@$connent->commit();
	    if (!$status)throw new Exception ($connent->errorInfo(),$connent->errorCode());
	    return $status;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::rollback()
	 */
	public function rollback()
	{
	    $connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER_MUST);
	    $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionRollback($connent));
	    $status=$connent->rollBack();
	    if (!$status)throw new Exception ($connent->errorInfo(),$connent->errorCode());
		return $status;
	}
	public function quote($value,$value_type=null) {
	    if(is_string($value)||is_numeric($value)){
	        if(is_int($value))return intval($value);
	        if (is_float ( $value )) return sprintf ( '%F', $value );
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
	    return $this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER_MUST)->inTransaction();
	}
}
