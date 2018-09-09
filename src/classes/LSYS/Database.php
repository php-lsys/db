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
	//query type
	/**
	 * 数据查询语言
	 * @var integer
	 */
	const DQL=1;
	/**
	 * 数据操纵语言
	 * @var integer
	 */
	const DML=2;
	/**
	 * 数据定义语言
	 * @var integer
	 */
	const DDL=3;
	/**
	 * 数据控制语言
	 * @var integer
	 */
	const DCL=4;
	
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
	        throw new \LSYS\Database\Exception( __('Database type not defined in [:name on :driver] configuration',array("name"=>$name,"driver"=>$driver)));
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
	protected function _connect_param_create(){
	    return new ConnectParam($this->_query_mode);
	}
	public function set_event_manager(\LSYS\EventManager $event_manager){
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
	public function set_profiler(\LSYS\Profiler $profiler){
	    $this->_profiler=$profiler;
	}
	/**
	 * Connect to the database.
	 * auto model link read database
	 * @throws \LSYS\Database\Exception
	 * @return void
	 */
	public function connect(){
	    $queryparse=$this->_connect_param_create();
	    $this->_connection->get_connect($queryparse->is_slave());
		return true;
	}
	/**
	 * return last query sql
	 * @return string
	 */
	public function last_query(){
		return $this->last_query;
	}
	/**
	 * set query model
	 * @param int
	 */
	public function set_query($query_mode){
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
	public function quote_column($column) {
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
				
				if ($prefix = $this->table_prefix ()) {
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
	public function disconnect() {
		$this->_connection&&$this->_connection->disconnect();
		$this->_connection=null;
		return TRUE;
	}
	/**
	 * destory connent res
	 */
	public function __destruct(){
		$this->disconnect();
		//$name=$this->_config->name();
		//clear instance cache
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
	public function table_prefix() {
		return $this->_config->get("table_prefix");
	}
	/**
	 * @param string $table        	
	 */
	public function quote_table($table) {
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
				
				if ($prefix = $this->table_prefix ()) {
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
				$table = $this->_identifier . $this->table_prefix () . $table . $this->_identifier;
			}
		}
		
		if (isset ( $alias )) {
			// Attach table prefix to alias
			$table .= ' AS ' . $this->_identifier.$this->table_prefix(). $alias . $this->_identifier;
		}
		return $table;
	}
	/**
	 * Extracts the text between parentheses, if any.
	 *
	 * // Returns: array('CHAR', '6')
	 * list($type, $length) = $db->_parse_type('CHAR(6)');
	 *
	 * @param string $type        	
	 * @return array list containing the type and length, if any
	 */
	protected function _parse_type($type) {
		if (($open = strpos ( $type, '(' )) === FALSE) {
			// No length specified
			return array (
					$type,
					NULL 
			);
		}
		// Closing parenthesis
		$close = strrpos ( $type, ')', $open );
		
		// Length without parentheses
		$length = substr ( $type, $open + 1, $close - 1 - $open );
		
		// Type without the length
		$type = substr ( $type, 0, $open ) . substr ( $type, $close + 1 );
		
		return array (
				$type,
				$length 
		);
	}
	/**
	 * 类型映射
	 * 
	 * @param string $type        	
	 * @return array
	 */
	public function datatype($type) {
		$types = array (
				'blob' => array (
					'type' => 'string',
					'binary' => TRUE,
					'character_maximum_length' => '65535' 
				),
				'bool' => array (
						'type' => 'bool' 
				),
				'bigint unsigned' => array (
						'type' => 'int',
						'min' => '0',
						'max' => '18446744073709551615' 
				),
				'datetime' => array (
						'type' => 'string' 
				),
				'decimal unsigned' => array (
						'type' => 'float',
						'exact' => TRUE,
						'min' => '0' 
				),
				'double' => array (
						'type' => 'float' 
				),
				'double precision unsigned' => array (
						'type' => 'float',
						'min' => '0' 
				),
				'double unsigned' => array (
						'type' => 'float',
						'min' => '0' 
				),
				'enum' => array (
						'type' => 'string' 
				),
				'fixed' => array (
						'type' => 'float',
						'exact' => TRUE 
				),
				'fixed unsigned' => array (
						'type' => 'float',
						'exact' => TRUE,
						'min' => '0' 
				),
				'float unsigned' => array (
						'type' => 'float',
						'min' => '0' 
				),
				'int unsigned' => array (
						'type' => 'int',
						'min' => '0',
						'max' => '4294967295' 
				),
				'integer unsigned' => array (
						'type' => 'int',
						'min' => '0',
						'max' => '4294967295' 
				),
				'longblob' => array (
						'type' => 'string',
						'binary' => TRUE,
						'character_maximum_length' => '4294967295' 
				),
				'longtext' => array (
						'type' => 'string',
						'character_maximum_length' => '4294967295' 
				),
				'mediumblob' => array (
						'type' => 'string',
						'binary' => TRUE,
						'character_maximum_length' => '16777215' 
				),
				'mediumint' => array (
						'type' => 'int',
						'min' => '-8388608',
						'max' => '8388607' 
				),
				'mediumint unsigned' => array (
						'type' => 'int',
						'min' => '0',
						'max' => '16777215' 
				),
				'mediumtext' => array (
						'type' => 'string',
						'character_maximum_length' => '16777215' 
				),
				'national varchar' => array (
						'type' => 'string' 
				),
				'numeric unsigned' => array (
						'type' => 'float',
						'exact' => TRUE,
						'min' => '0' 
				),
				'nvarchar' => array (
						'type' => 'string' 
				),
				'point' => array (
						'type' => 'string',
						'binary' => TRUE 
				),
				'real unsigned' => array (
						'type' => 'float',
						'min' => '0' 
				),
				'set' => array (
						'type' => 'string' 
				),
				'smallint unsigned' => array (
						'type' => 'int',
						'min' => '0',
						'max' => '65535' 
				),
				'text' => array (
						'type' => 'string',
						'character_maximum_length' => '65535' 
				),
				'tinyblob' => array (
						'type' => 'string',
						'binary' => TRUE,
						'character_maximum_length' => '255' 
				),
				'tinyint' => array (
						'type' => 'int',
						'min' => '-128',
						'max' => '127' 
				),
				'tinyint unsigned' => array (
						'type' => 'int',
						'min' => '0',
						'max' => '255' 
				),
				'tinytext' => array (
						'type' => 'string',
						'character_maximum_length' => '255' 
				),
				'year' => array (
						'type' => 'string' 
				) 
		);
		
		$type = str_replace ( ' zerofill', '', $type );
		if (isset ( $types [$type] ))
			return $types [$type];
		$types = array (
				// SQL-92
				'bit' => array (
						'type' => 'string',
						'exact' => TRUE 
				),
				'bit varying' => array (
						'type' => 'string' 
				),
				'char' => array (
						'type' => 'string',
						'exact' => TRUE 
				),
				'char varying' => array (
						'type' => 'string' 
				),
				'character' => array (
						'type' => 'string',
						'exact' => TRUE 
				),
				'character varying' => array (
						'type' => 'string' 
				),
				'date' => array (
						'type' => 'string' 
				),
				'dec' => array (
						'type' => 'float',
						'exact' => TRUE 
				),
				'decimal' => array (
						'type' => 'float',
						'exact' => TRUE 
				),
				'double precision' => array (
						'type' => 'float' 
				),
				'float' => array (
						'type' => 'float' 
				),
				'int' => array (
						'type' => 'int',
						'min' => '-2147483648',
						'max' => '2147483647' 
				),
				'integer' => array (
						'type' => 'int',
						'min' => '-2147483648',
						'max' => '2147483647' 
				),
				'interval' => array (
						'type' => 'string' 
				),
				'national char' => array (
						'type' => 'string',
						'exact' => TRUE 
				),
				'national char varying' => array (
						'type' => 'string' 
				),
				'national character' => array (
						'type' => 'string',
						'exact' => TRUE 
				),
				'national character varying' => array (
						'type' => 'string' 
				),
				'nchar' => array (
						'type' => 'string',
						'exact' => TRUE 
				),
				'nchar varying' => array (
						'type' => 'string' 
				),
				'numeric' => array (
						'type' => 'float',
						'exact' => TRUE 
				),
				'real' => array (
						'type' => 'float' 
				),
				'smallint' => array (
						'type' => 'int',
						'min' => '-32768',
						'max' => '32767' 
				),
				'time' => array (
						'type' => 'string' 
				),
				'time with time zone' => array (
						'type' => 'string' 
				),
				'timestamp' => array (
						'type' => 'string' 
				),
				'timestamp with time zone' => array (
						'type' => 'string' 
				),
				'varchar' => array (
						'type' => 'string' 
				),
				
				// SQL:1999
				'binary large object' => array (
						'type' => 'string',
						'binary' => TRUE 
				),
				'blob' => array (
						'type' => 'string',
						'binary' => TRUE 
				),
				'boolean' => array (
						'type' => 'bool' 
				),
				'char large object' => array (
						'type' => 'string' 
				),
				'character large object' => array (
						'type' => 'string' 
				),
				'clob' => array (
						'type' => 'string' 
				),
				'national character large object' => array (
						'type' => 'string' 
				),
				'nchar large object' => array (
						'type' => 'string' 
				),
				'nclob' => array (
						'type' => 'string' 
				),
				'time without time zone' => array (
						'type' => 'string' 
				),
				'timestamp without time zone' => array (
						'type' => 'string' 
				),
				
				// SQL:2003
				'bigint' => array (
						'type' => 'int',
						'min' => '-9223372036854775808',
						'max' => '9223372036854775807' 
				),
				
				// SQL:2008
				'binary' => array (
						'type' => 'string',
						'binary' => TRUE,
						'exact' => TRUE 
				),
				'binary varying' => array (
						'type' => 'string',
						'binary' => TRUE 
				),
				'varbinary' => array (
						'type' => 'string',
						'binary' => TRUE 
				) 
		);
		if (isset ( $types [$type] ))
			return $types [$type];
		return array ();
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
	abstract public function set_charset($charset);
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
	abstract public function list_columns($table, $like = NULL, $column_info = TRUE);
	/**
	 * Perform an SQL query of the given type.
	 *
	 * @param   integer  $type     
	 * @param   string   $sql        SQL query
	 * @return  Result|bool Database::DQL|Database::**L
	 */
	abstract public function query($type, $sql);
	/**
	 * return reepare obj
	 * @param int $type
	 * @param string $sql
	 * @return Prepare
	 */
	abstract public function prepare($type,$sql);
	/**
	 * return last query affected rows
	 * @return int
	 */
	abstract public function affected_rows();
	/**
	 * return last insert auto id
	 * @return int
	 */
	abstract public function insert_id();
	/**
	 * in transaction
	 */
	abstract public function in_transaction ();
	/**
	 * Start a SQL transaction
	 * @param string $mode
	 *        	transaction mode
	 * @return boolean
	 */
	abstract public function begin_transaction ($mode = NULL);
	
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
	abstract public function list_tables($like = NULL);
	/**
	 * set attribute
	 * @param string $attr
	 * @param mixed $value
	 * @return bool
	 */
	abstract public function set_attr($attr, $value);
	/**
	 * get attribute
	 * @param string $attr
	 * @return null
	 */
	abstract public function get_attr($attr);
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