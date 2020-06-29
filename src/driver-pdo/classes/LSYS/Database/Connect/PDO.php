<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\Connect;
use LSYS\Database\EventManager\DBEvent;
use LSYS\Database\ConnectRetry;
use LSYS\Database\PDOException;
class PDO extends \LSYS\Database\ConnectMaster {
	// PDO uses no quoting for identifiers
	protected $identifier = '';
	/**
	 * @var \PDO
	 */
	protected $connection;
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectSlave::disConnect()
	 */
	public function disConnect():bool{
	    $this->connection=null;
	    return true;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectSlave::isConnected()
	 */
	public function isConnected():bool{
	    if (!is_object($this->connection))return false;
	    return true;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectSlave::connect()
	 */
	public function connect():bool{
	    if (!is_object($this->connection)){
	        $this->connection=$this->connectCreate();
	    }
	    return is_object($this->connection);
	}
	public function escape($value):?string
	{
	    $this->connect();
	    $value=$this->connection->quote($value);
	    if(is_string($value))$value=trim($value,"'");
	    return $value;
	}
	/**
	 * 创建一个连接
	 * @param array $link_config
	 * @throws PDOException
	 * @return \PDO
	 */
	protected function connectCreate(){
	    /**
		 * @var bool $persistent
		 * @var string $password
		 * @var string $username
		 * @var string $dsn
		 * @var array $options
		 */
		extract($this->link_config+array(
			'dsn'        => '',
			'username'   => NULL,
			'password'   => NULL,
			'persistent' => FALSE,
		));
		if (!isset($options)) $options=[];
		// Force PDO to use exceptions for all errors
		$options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
		if ( ! empty($persistent))
		{
			// Make the connection persistent
			$options[\PDO::ATTR_PERSISTENT] = TRUE;
		}
		try
		{
			$connent = new \PDO($dsn, $username, $password, $options);
		}
		catch (\PDOException $e)
		{
			throw new PDOException($e->getMessage(),$e->getCode(),$e);
		}
		return $connent;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectMaster::prepare()
	 */
	public function prepare(string $sql){
	    return new \LSYS\Database\Connect\PDO\Prepare($this, $sql,$this->event_manager);
	}
	/**
	 * {@inheritDoc}
	 * @return \PDO
	 */
	public function link()
	{
	    $this->connect();
	    return $this->connection;
	}
	public function quote($value,$value_type=null):string {
	    if(is_string($value)||is_numeric($value)){
	        if(is_int($value))return intval($value);
	        if (is_float ( $value )) return sprintf ( '%F', $value );
	        $this->connect();
	        return $this->connection->quote($value);
	    }
	    return parent::quote($value,$value_type);
	}
	public function beginTransaction($mode = NULL):bool
	{
	    while (true) {
	        $this->connect();
	        $connent=$this->connection;
	        $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionBegin($connent));
	        if (!$connent->beginTransaction()){
	            if ($this instanceof ConnectRetry
	                &&$this->isReConnect($connent)) {
	                    continue;
	                }
                $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionFail($connent));
                throw new PDOException ($connent->errorInfo(),$connent->errorCode());
	        }
	        break;
	    }
	    return true;
	}
	public function commit():bool
	{
	    $this->connect();
	    $connent=$this->connection;
	    $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionCommit($connent));
	    $status=@$connent->commit();
	    if (!$status)throw new PDOException ($connent->errorInfo(),$connent->errorCode());
	    return $status;
	}
	public function rollback():bool
	{
	    $this->connect();
	    $connent=$this->connection;
	    $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionRollback($connent));
	    $status=$connent->rollBack();
	    if (!$status)throw new PDOException ($connent->errorInfo(),$connent->errorCode());
	    return $status;
	}
	public function inTransaction():bool{
	    if (!is_object($this->connection))return false;
	    return (bool)$this->connection->inTransaction();
	}
}
