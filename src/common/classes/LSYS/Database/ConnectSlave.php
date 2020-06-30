<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use function LSYS\Database\__;
use LSYS\Config;
use LSYS\EventManager;
abstract class ConnectSlave{
	/**
	 * @var Config
	 */
	protected $config;
	/**
	 * link config
	 * @var array
	 */
	protected $link_config=[];
	/**
	 * @var PrepareSlave
	 */
	protected $last_prepare;
	/**
	 *  Character that is used to quote identifiers
	 */
	protected $identifier = '"';
	/**
	 * @var \LSYS\EventManager
	 */
	protected $event_manager;
	/**
	 * @var \LSYS\Database
	 */
	protected $db;
	/**
	 * @return void
	 */
	public function __construct(\LSYS\Database $db,\LSYS\Config $config,array $link_config,EventManager $event_manager=null) {
		$this->db = $db;
		$this->config = $config;
		$this->link_config=$link_config;
		$this->event_manager=$event_manager;
	}
	/**
	 * 返回数据库对象
	 * @return \LSYS\Database
	 */
	public function db() {
	    return $this->db;
	}
	/**
	 * 返回最后请求SQL
	 * @return string
	 */
	public function lastQuery():?string {
	    return $this->last_prepare?$this->last_prepare->querySQL():null;
	}
	/**
	 * 包裹值
	 * @param mixed $value
	 *        	any value to quote
	 * @return string
	 */
	public function quote($value,$value_type=null):string {
		if ($value === NULL) {
			return 'NULL';
		} elseif ($value === TRUE) {
			return "'1'";
		} elseif ($value === FALSE) {
			return "'0'";
		} elseif (is_object ( $value )) {
			if ($value instanceof Expr) {
				// Compile the expression
				return $value->compile ( $this );
			} else {
				// Convert the object to a string
				return $this->quote ( ( string ) $value );
			}
		} elseif (is_array ( $value )) {
			return '(' . implode ( ', ', array_map ( array (
					$this,
					__FUNCTION__ 
			), $value ) ) . ')';
		} elseif (is_int ( $value )) {
			return ( int ) $value;
		} elseif (is_float ( $value )) {
			// Convert to non-locale aware float to prevent possible commas
			return sprintf ( '%F', $value );
		}
		try{
			return "'".$this->escape ( $value )."'";
		}catch (\LSYS\Database\Exception $e){//callback can't throw exception...
			return "'".addslashes($value)."'";
		}
	}
	/**
	 * 包裹字段名
	 * 
	 * @param mixed $column
	 *        	column name or array(column, alias)
	 * @return string
	 */
	public function quoteColumn($column):string {
		if(empty($column)) return '';
		// Identifiers are escaped by repeating them
		$escaped_identifier = $this->identifier . $this->identifier;
		
		if (is_array ( $column )) {
			list ( $column, $alias ) = $column;
			$alias = str_replace ( $this->identifier, $escaped_identifier, $alias );
		}
		if ($column instanceof Expr) {
			// Compile the expression
			$column = $column->compile ( $this );
		} else {
			// Convert to a string
			$column = ( string ) $column;
			
			$column = str_replace ( $this->identifier, $escaped_identifier, $column );
			if ($column === '*') {
				return $column;
			} elseif (strpos ( $column, '.' ) !== FALSE) {
				$parts = explode ( '.', $column );
				
				if ($prefix = $this->db->tablePrefix()) {
					// Get the offset of the table name, 2nd-to-last part
					$offset = count ( $parts ) - 2;
					
					// Add the table prefix to the table name
					$parts [$offset] = $prefix . $parts [$offset];
				}
				
				foreach ( $parts as & $part ) {
					if ($part !== '*') {
						// Quote each of the parts
						$part = $this->identifier . $part . $this->identifier;
					}
				}
				
				$column = implode ( '.', $parts );
			} else {
				$column = $this->identifier . $column . $this->identifier;
			}
		}
		if (isset ( $alias )) {
			$column .= ' AS ' . $this->identifier . $alias . $this->identifier;
		}
		return $column;
	}
	/**
	 * 包裹表名
	 * @param string $table        	
	 */
	public function quoteTable($table):string {
		// Identifiers are escaped by repeating them
		$escaped_identifier = $this->identifier . $this->identifier;
		
		if (is_array ( $table )) {
			list ( $table, $alias ) = $table;
			$alias = str_replace ( $this->identifier, $escaped_identifier, $alias );
		}
		
		if ($table instanceof Expr) {
			// Compile the expression
			$table = $table->compile ( $this );
		} else {
			// Convert to a string
			$table = ( string ) $table;
			
			$table = str_replace ( $this->identifier, $escaped_identifier, $table );
			
			if (strpos ( $table, '.' ) !== FALSE) {
				$parts = explode ( '.', $table );
				
				if ($prefix = $this->db->tablePrefix()) {
					// Get the offset of the table name, last part
					$offset = count ( $parts ) - 1;
					
					// Add the table prefix to the table name
					$parts [$offset] = $prefix . $parts [$offset];
				}
				
				foreach ( $parts as & $part ) {
					// Quote each of the parts
					$part = $this->identifier . $part . $this->identifier;
				}
				
				$table = implode ( '.', $parts );
			} else {
				// Add the table prefix
			    $table = $this->identifier . $this->db->tablePrefix() . $table . $this->identifier;
			}
		}
		
		if (isset ( $alias )) {
			// Attach table prefix to alias
		    $table .= ' AS ' . $this->identifier.$this->db->tablePrefix(). $alias . $this->identifier;
		}
		return $table;
	}
	/**
	 * 发送SQL查下请求
	 * @param   string     $sql           SQL语句
	 * @param   array      $value         绑定值
	 * @param   array      $value_type    绑定值类型
	 * @return  Result
	 */
	public function query(string $sql,array $value=[],array $value_type=[]){
	    $this->last_prepare=$this->prepare($sql);
	    $res=$this->last_prepare->bindParam($value,$value_type)->query();
	    return $res;
	}
	/**
	 * 返回连接对象 
	 * @return mixed
	 */
	abstract public function link();
	/**
	 * 转移SQL值
	 * @param string $value        	
	 * @throws \LSYS\Database\Exception
	 */
	abstract public function escape($value):?string;
	/**
	 * 生成预执行对象
	 * @param string $sql
	 * @return PrepareSlave
	 */
	abstract public function prepare(string $sql);
	/**
	 * 断开连接
	 * @return bool
	 */
	abstract public function disConnect():bool;
	/**
	 * 连接数据库
	 * @return bool
	 */
	abstract public function connect():bool;
	/**
	 * 是否已经连接数据库
	 * @return bool
	 */
	abstract public function isConnected():bool;
}