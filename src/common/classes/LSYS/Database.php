<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS;
use LSYS\Database\Expr;
use LSYS\Database\Prepare;
use LSYS\Database\Result;
use LSYS\Database\ConnectManager;
use function LSYS\Database\__;
use LSYS\Database\SlaveQueryCheck;
/**
 * @throws \LSYS\Database\Exception
 * @author lonely
 */
abstract class Database{
	/**
	 * create database object
	 * @param \LSYS\Config $config
	 * @throws \LSYS\Database\Exception
	 * @return static
	 */
	public static function factory(\LSYS\Config $config){
	    $name=$config->name();
	    $driver=$config->get("type",NULL);
	    if (!$driver||!class_exists($driver,true)||!in_array(Database::class,class_parents($driver))){
	        throw new \LSYS\Database\Exception( __('Database type not defined in [:name on :driver] configuration',array(":name"=>$name,":driver"=>$driver)));
	    }
	    return new $driver ($config);
	}
	/**
	 * create expr object
	 * @param string $value
	 * @return \LSYS\Database\Expr
	 */
	public static function expr($value) {
	    return new Database\Expr ( $value );
	}
	/**
	 * @var Config
	 */
	protected $config;
	/**
	 * @var Prepare
	 */
	protected $last_query;
	/**
	 * @var Prepare
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
	 * @var SlaveQueryCheck
	 */
	protected $slave_check;
	/**
	 * @return void
	 */
	public function __construct(\LSYS\Config $config) {
	    //$config->dumpHide();
		$this->config = $config;
	}
	/**
	 * 设置事件管理器
	 * @param \LSYS\EventManager $event_manager
	 * @return static
	 */
	public function setEventManager(\LSYS\EventManager $event_manager){
	    $this->event_manager=$event_manager;
	    return $this;
	}
	/**
	 * 设置从库查询检测
	 * @param SlaveQueryCheck $clave_check
	 * @return static
	 */
	public function setSlaveQueryCheck(SlaveQueryCheck $clave_check){
	    $this->slave_check=$clave_check;
	    return $this;
	}
	/**
	 * return last query sql
	 * @return string
	 */
	public function lastQuery(){
	    return $this->last_query;
	}
	/**
	 * Quote a value for an SQL query.
	 *
	 * @param mixed $value
	 *        	any value to quote
	 * @return string
	 */
	public function quote($value,$value_type=null) {
		if ($value === NULL) {
			return 'NULL';
		} elseif ($value === TRUE) {
			return "'1'";
		} elseif ($value === FALSE) {
			return "'0'";
		} elseif (is_object ( $value )) {
			if ($value instanceof Database\Expr) {
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
	 * Quote a database column name and add the table prefix if needed.
	 *
	 * @param mixed $column
	 *        	column name or array(column, alias)
	 * @return string
	 */
	public function quoteColumn($column) {
		if(empty($column)) return '';
		// Identifiers are escaped by repeating them
		$escaped_identifier = $this->identifier . $this->identifier;
		
		if (is_array ( $column )) {
			list ( $column, $alias ) = $column;
			$alias = str_replace ( $this->identifier, $escaped_identifier, $alias );
		}
		if ($column instanceof \LSYS\Database\Expr) {
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
				
				if ($prefix = $this->tablePrefix()) {
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
	 * Return the table prefix defined in the current configuration.
	 * @return  string
	 */
	public function tablePrefix() {
		return $this->config->get("table_prefix");
	}
	/**
	 * @param string $table        	
	 */
	public function quoteTable($table) {
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
				
				if ($prefix = $this->tablePrefix()) {
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
				$table = $this->identifier . $this->tablePrefix() . $table . $this->identifier;
			}
		}
		
		if (isset ( $alias )) {
			// Attach table prefix to alias
			$table .= ' AS ' . $this->identifier.$this->tablePrefix(). $alias . $this->identifier;
		}
		return $table;
	}
	/**
	 * Perform an SQL query of the given type.
	 *
	 * @param   integer  $type
	 * @param   string   $sql        SQL query
	 * @return  Result
	 */
	public function query($sql,array $value=[],array $value_type=[]){
	    $this->last_prepare=$this->prepare($sql);
	    $res=$this->last_prepare->bindParam($value,$value_type)->query();
	    $this->last_query=$this->last_prepare->lastQuery();
	    return $res;
	}
	/**
	 * Perform an SQL query of the given type.
	 *
	 * @param   integer  $type
	 * @param   string   $sql        SQL query
	 * @return  bool
	 */
	public function exec($sql,array $value=[],array $value_type=[]){
	    $this->last_prepare=$this->prepare($sql);
	    $res=$this->last_prepare->bindParam($value,$value_type)->exec();
	    $this->last_query=$this->last_prepare->lastQuery();
	    return $res;
	}
	/**
	 * return last query affected rows
	 * @return int
	 */
	public function affectedRows(){
	    return $this->last_prepare?$this->last_prepare->affectedRows():0;
	}
	/**
	 * return last insert auto id
	 * @return int
	 */
	public function insertId(){
	    return $this->last_prepare?$this->last_prepare->insertId():null;
	}
	/**
	 * get ConnectManager
	 * @return ConnectManager
	 */
	abstract public function getConnectManager();
	/**
	 * escape value
	 * @param string $value        	
	 * @throws \LSYS\Database\Exception
	 */
	abstract public function escape($value);
	/**
	 * @param string $sql
	 * @return Prepare
	 */
	abstract public function prepare($sql);
	/**
	 * in transaction
	 */
	abstract public function inTransaction();
	/**
	 * Start a SQL transaction
	 * @param string $mode
	 *        	transaction mode
	 * @return boolean
	 */
	abstract public function beginTransaction($mode = NULL);
	
	/**
	 * Commit the current transaction
	 * @return boolean
	 */
	abstract public function commit();
	
	/**
	 * Abort the current transaction
	 * @return boolean
	 */
	abstract public function rollback();
}