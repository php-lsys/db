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
use LSYS\Database\Connect;
use function LSYS\Database\__;
use LSYS\Database\Exception;
use LSYS\Database\RWCache;
use LSYS\Database\ConnectParam;
/**
 *
 * @throws \LSYS\Database\Exception
 * @author lonely
 *        
 */
abstract class Database implements \Serializable{
	/**
	 * @var integer
	 */
	const FETCH_ASSOC=1;
	/**
	 * @var integer
	 */
	const FETCH_CLASS=2;
	/**
	 * @var integer
	 */
	const FETCH_INTO=3;
	/**
	 * @var integer
	 */
	const FETCH_OBJ=4;
	/**
	 * set MQL query to auto select database
	 * @var integer
	 */
	const QUERY_AUTO=0;
	/**
	 * set MQL query to master database
	 * @var integer
	 */
	const QUERY_MASTER=1;
	/**
	 * 获得EXPR
	 * 
	 * @param string $value        	
	 * @return \LSYS\Database\Expr
	 */
	public static function expr($value) {
		return new Database\Expr ( $value );
	}
	/**
	 * create database object
	 * @param \LSYS\Config $config
	 * @throws \LSYS\Database\Exception
	 * @return Database
	 */
	public static function factory(\LSYS\Config $config,RWCache $cache=null){
	    $name=$config->name();
	    $driver=$config->get("type",NULL);
	    if (!$driver||!class_exists($driver,true)||!in_array(Database::class,class_parents($driver))){
	        throw new \LSYS\Database\Exception( __('Database type not defined in [:name on :driver] configuration',array(":name"=>$name,":driver"=>$driver)));
	    }
	    return new $driver ( $config ,$cache);
	}
	/**
	 * you need on __construct init it
	 * @var Connect
	 */
	protected $_connection = NULL;
	/**
	 * @var string
	 */
	protected $_instance;
	/**
	 * @var Config
	 */
	protected $_config;
	/**
	 * query model
	 * @var int
	 */
	protected $_query_mode=Database::QUERY_AUTO;
	/**
	 * @var string the last query executed
	 */
	protected $last_query;
	/**
	 *  Character that is used to quote identifiers
	 */
	protected $_identifier = '"';
	/**
	 * @var \LSYS\Profiler
	 */
	protected $_profiler;
	/**
	 * @var \LSYS\EventManager
	 */
	protected $_event_manager;
	protected $_master_cache;
	/**
	 * @return void
	 */
	public function __construct(\LSYS\Config $config,RWCache $cache=null) {
		$this->_instance = $config->name();
		$this->_config = $config;
		$this->_master_cache=$cache;
	}
	/**
	 * create connect param
	 * @return \LSYS\Database\ConnectParam
	 */
	protected function _connectParamCreate(){
	    return new ConnectParam($this->_query_mode);
	}
	public function setEventManager(\LSYS\EventManager $event_manager){
	    $this->_event_manager=$event_manager;
	    return $this;
	}
	public function serialize (){
	    if (!$this->_config instanceof \Serializable){
	        throw new Exception(__("your database config can't be serializable"));
	    }
	    return serialize($this->_config);
	}
	public function unserialize ($serialized){
	    $this->__construct(unserialize($serialized));
	}
	/**
	 * set profiler
	 * @param \LSYS\Profiler $profiler
	 */
	public function setProfiler(\LSYS\Profiler $profiler){
	    $this->_profiler=$profiler;
	}
	/**
	 * Connect to the database.
	 * auto model link read database
	 * @throws \LSYS\Database\Exception
	 * @return void
	 */
	public function connect(){
	    $queryparse=$this->_connectParamCreate();
	    $this->_connection->getConnect($queryparse->isSlave());
		return true;
	}
	/**
	 * return last query sql
	 * @return string
	 */
	public function lastQuery(){
		return $this->last_query;
	}
	/**
	 * set query model
	 * @param int
	 */
	public function setQuery($query_mode){
		$this->_query_mode=$query_mode;
		return $this;
	}
	/**
	 * Quote a value for an SQL query.
	 *
	 * @param mixed $value
	 *        	any value to quote
	 * @return string
	 * @uses Database::escape
	 */
	public function quote($value) {
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
			return $this->escape ( $value );
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
	 * @uses Database::quote_identifier
	 * @uses Database::table_prefix
	 */
	public function quoteColumn($column) {
		if(empty($column)) return '';
		// Identifiers are escaped by repeating them
		$escaped_identifier = $this->_identifier . $this->_identifier;
		
		if (is_array ( $column )) {
			list ( $column, $alias ) = $column;
			$alias = str_replace ( $this->_identifier, $escaped_identifier, $alias );
		}
		if ($column instanceof Database\Expr) {
			// Compile the expression
			$column = $column->compile ( $this );
		} else {
			// Convert to a string
			$column = ( string ) $column;
			
			$column = str_replace ( $this->_identifier, $escaped_identifier, $column );
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
						$part = $this->_identifier . $part . $this->_identifier;
					}
				}
				
				$column = implode ( '.', $parts );
			} else {
				$column = $this->_identifier . $column . $this->_identifier;
			}
		}
		if (isset ( $alias )) {
			$column .= ' AS ' . $this->_identifier . $alias . $this->_identifier;
		}
		return $column;
	}
	/**
	 * Disconnect from the database.
	 * @return boolean
	 */
	public function disConnect() {
		$this->_connection&&$this->_connection->disconnect();
		$this->_connection=null;
		return TRUE;
	}
	/**
	 * destory connent res
	 */
	public function __destruct(){
		$this->disconnect();
	}
	/**
	 * Returns the database instance name.
	 * echo (string) $db;
	 * @return  string
	 */
	public function __toString()
	{
		return $this->_instance;
	}
	/**
	 * Return the table prefix defined in the current configuration.
	 * @return  string
	 */
	public function tablePrefix() {
		return $this->_config->get("table_prefix");
	}
	/**
	 * @param string $table        	
	 */
	public function quoteTable($table) {
		// Identifiers are escaped by repeating them
		$escaped_identifier = $this->_identifier . $this->_identifier;
		
		if (is_array ( $table )) {
			list ( $table, $alias ) = $table;
			$alias = str_replace ( $this->_identifier, $escaped_identifier, $alias );
		}
		
		if ($table instanceof Expr) {
			// Compile the expression
			$table = $table->compile ( $this );
		} else {
			// Convert to a string
			$table = ( string ) $table;
			
			$table = str_replace ( $this->_identifier, $escaped_identifier, $table );
			
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
					$part = $this->_identifier . $part . $this->_identifier;
				}
				
				$table = implode ( '.', $parts );
			} else {
				// Add the table prefix
				$table = $this->_identifier . $this->tablePrefix() . $table . $this->_identifier;
			}
		}
		
		if (isset ( $alias )) {
			// Attach table prefix to alias
			$table .= ' AS ' . $this->_identifier.$this->tablePrefix(). $alias . $this->_identifier;
		}
		return $table;
	}
	/**
	 * escape value
	 * @param string $value        	
	 * @throws \LSYS\Database\Exception
	 */
	abstract public function escape($value);
	/**
	 * Set the connection character set.
	 * This is called automatically by [Database::connect].
	 *
	 * @throws \LSYS\Database\Exception
	 * @param string $charset
	 *        	character set name
	 * @return void
	 */
	abstract public function setCharset($charset);
	/**
	 * Perform an SQL query of the given type.
	 *
	 * @param   integer  $type     
	 * @param   string   $sql        SQL query
	 * @return  Result|bool Database::DQL|Database::**L
	 */
	abstract public function query($sql,array $data=[]);
	/**
	 * Perform an SQL query of the given type.
	 *
	 * @param   integer  $type
	 * @param   string   $sql        SQL query
	 * @return  Result|bool Database::DQL|Database::**L
	 */
	abstract public function exec($sql,array $data=[]);
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
	/**
	 * List all of the tables in the database.
	 * @param string $like
	 *        	table to search for
	 * @return array
	 */
	abstract public function listTables($like = NULL);
	/**
	 * Lists all of the columns in a table.
	 *
	 * @param string $table
	 *        	table to get columns from
	 * @param string $like
	 *        	column to search for
	 * @param boolean $column_info
	 *        	column more data
	 * @return array
	 */
	abstract public function listColumns($table, $like = NULL);
	/**
	 * dump object
	 */
	public function __debugInfo(){
		$out=get_object_vars($this);
		$name=$this->_config->name();
		if (isset($out['_config']))$out['_config']="Config[{$name}] object is hidden";
		return $out;
	}
}