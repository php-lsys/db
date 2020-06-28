<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use function LSYS\Database\__;
/**
 * @method PrepareMaster prepare(string $sql);
 */
abstract class ConnectMaster extends ConnectSlave{
    /**
     * @var PrepareMaster
     */
    protected $last_prepare_exec;
	/**
	 * 执行指定SQL语句
	 * @param   string   $sql        SQL query
	 * @return  bool
	 */
	public function exec(string $sql,array $value=[],array $value_type=[]):bool{
	    $this->last_prepare_exec=$this->prepare($sql);
	    $res=$this->last_prepare_exec->bindParam($value,$value_type)->exec();
	    $this->last_prepare=$this->last_prepare_exec;
	    return $res;
	}
	/**
	 * 返回影响行数
	 * @return int
	 */
	public function affectedRows():int{
	    return $this->last_prepare_exec?$this->last_prepare_exec->affectedRows():0;
	}
	/**
	 * 最后请求插入ID
	 * @return int|null
	 */
	public function insertId():?int{
	    return $this->last_prepare_exec?$this->last_prepare_exec->insertId():null;
	}
	/**
	 * in transaction
	 */
	abstract public function inTransaction():bool;
	/**
	 * Start a SQL transaction
	 * @param string $mode
	 *        	transaction mode
	 * @return boolean
	 */
	abstract public function beginTransaction($mode = NULL):bool;
	
	/**
	 * Commit the current transaction
	 * @return boolean
	 */
	abstract public function commit():bool;
	
	/**
	 * Abort the current transaction
	 * @return boolean
	 */
	abstract public function rollback():bool;
}