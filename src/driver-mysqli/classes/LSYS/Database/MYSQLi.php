<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\Database\EventManager\DBEvent;

class MYSQLi extends \LSYS\Database implements AsyncQuery {
    protected $identifier = '`';
	// 已连接的数据库
	protected $in_transaction;
	protected $connection;
	protected $async=[];
	/**
	 * {@inheritDoc}
	 * @return \LSYS\Database\MYSQLi\ConnectManager
	 */
	public function getConnectManager()
	{
	    if(!$this->connection) $this->connection= new \LSYS\Database\MYSQLi\ConnectManager($this->config);
	    return $this->connection;
	}
	/**
	 * 转义值
	 * 
	 * @param string $value        	
	 * @throws Exception
	 */
	public function escape($value) {
	    $connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_AUTO);
		if (($value = $connent->real_escape_string(strval($value))) === FALSE) {
			throw new Exception ( $connent->error, $connent->errno );
		}
		return $value;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database::prepare()
	 */
	public function prepare($sql){
	    return new \LSYS\Database\MYSQLi\Prepare($this, $sql,$this->event_manager,$this->slave_check);
	}
    /**
     * Start a SQL transaction
     *
     * @link http://dev.mysql.com/doc/refman/5.0/en/set-transaction.html
     *
     * @param string $mode  Isolation level
     * @return boolean
     */
    public function beginTransaction($mode = NULL)
    {
        $connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER_MUST);
        // Make sure the database is connected
        if ($mode AND ! $connent->query( "SET TRANSACTION ISOLATION LEVEL $mode"))
        {
            throw new Exception ($connent->error, $connent->errno  );
        }
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionBegin($connent));
        $status = (bool) $connent->query('START TRANSACTION');
        $this->in_transaction=true;
        return $status;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::inTransaction()
     */
    public function inTransaction(){
    	return $this->in_transaction;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::commit()
     */
    public function commit()
    {
        $connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER_MUST);
        // Make sure the database is connected
		
		$this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionCommit($connent));
		$status = (bool) $connent->query('COMMIT');
		$this->in_transaction=false;
		return $status;
    }
   /**
    * {@inheritDoc}
    * @see \LSYS\Database::rollback()
    */
    public function rollback()
    {
        $connent=$this->getConnectManager()->getConnect(ConnectManager::CONNECT_MASTER_MUST);
        // Make sure the database is connected
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionRollback($connent));
        $status = (bool) $connent->query('ROLLBACK');
        $this->in_transaction=false;
        return $status;
    }
    protected function asyncAdd($is_exec,$sql, array $value = [], array $value_type = []){
        $this->last_query = $sql;
        $param=[];
        foreach ($value as $k=>$v){
            $param[$k]=$this->quote($v,$value_type[$k]??null);
        }
        $sql=strtr($sql,$param);
        $connect_mgr=$this->getConnectManager();
        while(true){
            if($is_exec)$conn_type=ConnectManager::CONNECT_MASTER_MUST;
            else{
                if($this->slave_check&&$this->slave_check->allowSlave($sql))$conn_type=ConnectManager::CONNECT_SLAVE;
                else $conn_type=ConnectManager::CONNECT_MASTER_SUGGEST;
            }
            if(count($this->async)){
                $conn=$connect_mgr->createConnect($conn_type);
            }else $conn=$connect_mgr->getConnect($conn_type);
            $res=$conn->query($sql, MYSQLI_ASYNC);
            if($res===false){
                if($connect_mgr instanceof ConnectRetry
                    &&!$this->inTransaction()
                    &&$connect_mgr->isReconnect($conn)){
                        $connect_mgr->disConnect($conn);
                }else{
                    throw new Exception ($conn->error, $conn->errno);
                }
            }
            break;
        }
        $this->async[]=array(
            $is_exec,$conn
        );
        return count($this->async);
    }
    public function asyncAddExec($sql, array $value = [], array $value_type = [])
    {
        return $this->asyncAdd(true, $sql,$value,$value_type);
    }
    public function asyncAddQuery($sql, array $value = [], array $value_type = [])
    {
        return $this->asyncAdd(false, $sql,$value,$value_type);
    }
    public function asyncExecute()
    {
        $result=$insert=$aff_row=[];
        $async=$this->async;
        $this->async=[];
        foreach ($async as $k=>$v){
            list($is_exec,$conn)=$v;
            $sql_result = $conn->reap_async_query();
            if($sql_result===false)throw new Exception ($conn->error, $conn->errno);
            if($is_exec){
                $_aff_row=$conn->affected_rows;
                $_ins=$conn->insert_id;
                $_result=$sql_result;
            }else{
                $_result=new \LSYS\Database\MYSQLi\Result($sql_result);
                $_aff_row=0;
                $_ins=0;
            }
            $result[$k]=$_result;
            $aff_row[$k]=$_aff_row;
            $insert[$k]=$_ins;
            if($k!=0)$this->getConnectManager()->disConnect($conn);
        }
        return new AsyncResult($result,$aff_row,$insert);
    }
    
    /**
     * Perform an SQL query of the given type.
     *
     * @param   integer  $type
     * @param   string   $sql        SQL query
     * @return  Result
     */
    public function query($sql,array $value=[],array $value_type=[]){
        try{
            return parent::query($sql,$value,$value_type);
        }catch (Exception $e){//unlink connect reset transaction status
            if ($this->getConnectManager()->isUnConnect($e->getCode())) {
                $this->in_transaction=false;
            }
            throw $e;
        }
    }
    /**
     * Perform an SQL query of the given type.
     *
     * @param   integer  $type
     * @param   string   $sql        SQL query
     * @return  bool
     */
    public function exec($sql,array $value=[],array $value_type=[]){
        try{
            return parent::query($sql,$value,$value_type);
        }catch (Exception $e){//unlink connect reset transaction status
            if ($this->getConnectManager()->isUnConnect($e->getCode())) {
                $this->in_transaction=false;
            }
            throw $e;
        }
    }
    
    
}
