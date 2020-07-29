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
use LSYS\Database\ConnectSchema;
use LSYS\Database\Exception;
use LSYS\EventManager;
use LSYS\Database\ConnectCharset;
class MYSQLi extends \LSYS\Database\ConnectMaster implements ConnectRetry,ConnectSchema,ConnectCharset {
    protected $identifier = '`';
	// 已连接的数据库
	protected $in_transaction;
	/**
	 * @var \mysqli|null
	 */
	protected $connection;
	/**
	 * 当前使用字符编码
	 * @var string
	 */
	protected $charset;
	/**
	 * 尝试次数
	 * @var integer
	 */
	protected $try_num=0;
	public function __construct(\LSYS\Database $db,\LSYS\Config $config,array $link_config,EventManager $event_manager=null) {
	   parent::__construct($db, $config, $link_config,$event_manager);
	   $this->charset=$this->config->get("charset");
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectSlave::disConnect()
	 */
	public function disConnect():bool{
	    $status=true;
	    if (is_object($this->connection)) {
	        $status=(bool)@$this->connection->close();
	    }
	    $this->in_transaction=false;
	    $this->connection=null;
	    return $status;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectSlave::isConnected()
	 */
	public function isConnected():bool{
	    if (!is_object($this->connection))return false;
	    $status=(bool)$this->connection->ping();
	    if(!$status)$this->disConnect();
	    return $status;
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
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectSlave::setCharset()
	 */
	public function setCharset(string $charset):bool{
	    while(true){
	        $this->connect();
	        if($this->connection->set_charset($charset)===false){
	            if($this->isReConnect(null)){
                    $this->disConnect();
	                continue;
	            }else{
	                throw (new Exception($this->connection->error, $this->connection->errno));
	            }
	        }
	        $this->charset=$charset;
	        break;
	    }
	    return true;
	}
	/**
	 * {@inheritDoc}
	 * @return string|NULL
	 */
	public function charset():?string
	{
	    return $this->charset;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectSlave::escape()
	 */
	public function escape($value):?string {
	    while(true){
	        $this->connect();
	        if(($value = $this->connection->real_escape_string(strval($value))) === FALSE){
	            if($this->isReConnect(null)){
	                $this->disConnect();
	                continue;
	            }else{
	                return false;
	            }
	        }
	        break;
	    }
		return $value;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectSchema::schema()
	 */
	public function schema():?string{
	    return $this->link_config['database']??null;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectSchema::useSchema()
	 */
	public function useSchema(string $dbname): bool
	{
	    while(true){
	        $this->connect();
	        if($this->connection->select_db($dbname)===false){
	            if($this->isReConnect(null)){
	                $this->disConnect();
	                continue;
	            }else{
	                return false;
	            }
	        }
	        break;
	    }
	    $this->link_config['database']=$dbname;
	    return true;
	}
	/**
	 * 创建一个连接
	 * @param array $link_config
	 * @throws Exception
	 * @return \mysqli
	 */
	public function connectCreate(){
	    /**
	     * @var bool $persistent
	     * @var string $hostname
	     * @var string $username
	     * @var string $password
	     * @var string $database
	     * @var string $port
	     * @var array $variables
	     */
	    extract($this->link_config+array(
	        'hostname' 		=> NULL,
	        'username' 		=> NULL,
	        'password' 		=> NULL,
	        'database' 		=> NULL,
	        'port' 			=> 3306,
	        'persistent' 	=> FALSE,
	        'variables'  	=> array()
	    ));
	    if ($persistent) {
	        // To open a persistent connection you must prepend p: to the hostname when connecting.
	        $hostname = 'p:' . $hostname;
	    }
	    while (true) {
	        try {
	            $connection = @new \mysqli($hostname, $username, $password,$database,$port);
	        } catch ( \Exception $e ) {
	            throw new Exception( $e->getMessage(), $e->getCode(),$e );
	        }
	        if ($connection->connect_errno) {
	            throw new Exception($connection->connect_error, $connection->connect_errno);
	        }
	        if (! empty ($this->charset)){
	            if($connection->set_charset($this->charset)===false){
	                if($this->isUnConnect($connection))continue;
	                throw new Exception($connection->error,$connection->errno);
	            }
	        }
	        if (is_array($variables)&&count($variables)>0) {
	            // Set session variables
	            $_variables = array ();
	            foreach ( $variables as $var => $val ) {
	                $_variables [] = 'SESSION ' . $var . ' = ' . $this->quote ( $val );
	            }
	            if($connection->query('SET ' . implode ( ', ', $_variables ))===false){
	                if($this->isUnConnect($connection))continue;
	                throw new Exception($connection->error,$connection->errno);
	            }
	        }
	        break;
	    }
	    return $connection;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\ConnectMaster::prepare()
	 */
	public function prepare(string $sql){
	    return new \LSYS\Database\Connect\MYSQLi\Prepare($this, $sql,$this->event_manager);
	}
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\ConnectMaster::beginTransaction()
     */
	public function beginTransaction($mode = NULL):bool
    {
        while(true){
            $this->connect();
            $connent=$this->connection;
            if ($mode)
            {
                if(!$connent->query( "SET TRANSACTION ISOLATION LEVEL $mode")){
                    if($this->isReConnect(null)){
                        $this->disConnect();
                        continue;
                    }else{
                        throw new Exception ($connent->error, $connent->errno  );
                    }
                }
            }
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionBegin($connent));
            if(!@$connent->query('START TRANSACTION')){
                if($this->isReConnect(null)){
                    $this->disConnect();
                    continue;
                }else{
                    $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionFail($connent));
                    throw new Exception ($connent->error, $connent->errno  );
                }
            }
            break;
        }
        $this->in_transaction=true;
        return true;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\ConnectMaster::inTransaction()
     */
    public function inTransaction():bool{
        return boolval($this->in_transaction);
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\ConnectMaster::commit()
     */
    public function commit():bool
    {
        $this->connect();
        $connent=$this->connection;
		$this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionCommit($connent));
		$status = (bool) $connent->query('COMMIT');
		if (!$status) throw new Exception ($connent->error, $connent->errno  );
		$this->in_transaction=false;
		return $status;
    }
   /**
    * {@inheritDoc}
    * @see \LSYS\Database\ConnectMaster::rollback()
    */
    public function rollback():bool
    {
        $this->connect();
        $connent=$this->connection;
        // Make sure the database is connected
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionRollback($connent));
        $status = (bool) $connent->query('ROLLBACK');
        if (!$status){
            if(in_array(intval($connent->errno), [2006,2013])&&strpos($connent->error, 'has gone away')!==false){
                $this->in_transaction=false;
                return true;
            }
            throw new Exception ($connent->error,$connent->errno);
        }
        $this->in_transaction=false;
        return $status;
    }
   /**
    * {@inheritDoc}
    * @see \LSYS\Database\ConnectSlave::query()
    */
    public function query(string $sql,array $value=[],array $value_type=[]){
        $this->connect();
        try{
            return parent::query($sql,$value,$value_type);
        }catch (Exception $e){//unlink connect reset transaction status
            if ($this->isUnConnectException($e)) {
                $this->in_transaction=false;
            }
            throw $e;
        }
    }
   /**
    * {@inheritDoc}
    * @see \LSYS\Database\ConnectMaster::exec()
    */
    public function exec(string $sql,array $value=[],array $value_type=[]):bool{
        $this->connect();
        try{
            return parent::exec($sql,$value,$value_type);
        }catch (Exception $e){//unlink connect reset transaction status
            if ($this->isUnConnectException($e)) {
                $this->in_transaction=false;
            }
            throw $e;
        }
    }
   /**
    * {@inheritDoc}
    * @see \LSYS\Database\ConnectRetry::isReConnect()
    */
    public function isReConnect($error_info):bool{
        if (!is_object($this->connection))return false;
        if($this->isUnConnect($this->connection)){
            $try_re_num=$this->config->get("try_re_num",0);
            if($try_re_num==0)return false;
            if($this->try_num<$try_re_num){
                $this->try_num++;
                return true;
            }
            $try_re_sleep=$this->config->get("try_re_sleep",0);
            if($try_re_sleep<=0)return false;
            sleep($try_re_sleep);
            $this->try_num=0;
            return true;
        }
        return false;
    }
    /**
     * 是否是可重连错误
     * @param object $error_object
     * @return boolean
     */
    protected function isUnConnectException(\Exception $e):bool{
        $code=strval($e->getCode());
        if ($code=='2006'||$code=='2013') {
            if(strpos($e->getMessage(), 'has gone away')!==false)return true;
        }
        return false;
    }
    protected function isUnConnect(\mysqli $conn):bool{
        if (strval($conn->errno)=='2006'||strval($conn->errno)=='2013')return true;
        if (strval($conn->connect_errno)=='2006' or strval($conn->connect_errno)=='2013' ) {
            if (strpos($conn->connect_error, 'has gone away')!==false)return true;
        }
        return false;
    }
    /**
     * {@inheritDoc}
     * @return \mysqli
     */
    public function link()
    {
        $this->connect();
        return $this->connection;
    }
}
