<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
abstract class Prepare{
	protected $_connection;
	protected $_result;
	protected $_type;
	protected $_param;
	/**
	 * @param \LSYS\Database $db
	 * @param int $type
	 * @param string $sql
	 */
	public function __construct(&$connection,$type,&$result,$param=null){
		$this->_connection=&$connection;
		$this->_type=$type;
		$this->_result=$result;
		$this->_param=$param;
	}
	/**
	 * bind value
	 * @param mixed $parameters
	 * @param string $value
	 * @return bool
	 */
	abstract public function bindValue($parameters,$value=null);
	/**
	 * exec sql
	 * @param array $parameters
	 * @return Result
	 */
	abstract public function execute(array $parameters=null);
	/**
	 * return last query affected rows
	 * @return int
	 */
	abstract public function affectedRows();
	/**
	 * return last insert auto id
	 * @return int
	 */
	abstract public function insertId();
}
