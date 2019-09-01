<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\MYSQLi;
use LSYS\Database\MYSQLi;
use LSYS\Database\Exception;
use LSYS\Database\ConnectRetry;
class ConnectManager  extends \LSYS\Database\ConnectManager implements ConnectRetry{
	protected $read_connection;
	protected $master_connection;
	protected $schema=[];
	protected $try_num=0;
	public function getConnect($connect_type=self::CONNECT_AUTO){
	    if ($connect_type===self::CONNECT_AUTO) {
	        if ($this->read_connection)return $this->read_connection;
	        if ($this->master_connection)return $this->master_connection;
	    }
	    if ($this->query_mode==self::QUERY_AUTO&&$connect_type===self::CONNECT_SLAVE){//读数据库
	        if ($this->read_connection) return $this->read_connection;
	        $config=$this->config->get("slave_connection",array());
	        $config=$this->weightGetConfig($config);
	        if(!empty($config)){
	            $this->read_connection=$this->connectCreate($config);
	            $this->schema['slave']=$config['database']??null;
	            return $this->read_connection;
	        }
	    }
	    if ($this->master_connection) return $this->master_connection;
	    $config=$this->config->get("connection",array());
	    $this->master_connection=$this->connectCreate($config);
	    $this->schema['master']=$config['database']??null;
	    return $this->master_connection;
	}
	public function isConnected($connect_type=self::CONNECT_AUTO) {
	    switch ($connect_type) {
	        case self::CONNECT_AUTO:
	            if(is_object($this->master_connection)){
	                if($this->master_connection->ping())return true;
	                @$this->master_connection->close();
	                $this->master_connection=null;
	            }
	            if(is_object($this->read_connection)){
	                if($this->read_connection->ping())return true;
	                @$this->read_connection->close();
	                $this->read_connection=null;
	            }
	        break;
	        case self::CONNECT_MASTER:
	            if(is_object($this->master_connection)){
	                if($this->master_connection->ping())return true;
	                @$this->master_connection->close();
	                $this->master_connection=null;
	            }
	            break;
	        case self::CONNECT_SLAVE:
	            if(is_object($this->read_connection)){
	                if($this->read_connection->ping())return true;
	                @$this->read_connection->close();
	                $this->read_connection=null;
	            }
	            break;
	    }
	    return false;
	}
	/**
	 * 是否需要进行重新连接
	 * @param \mysqli $connection
	 */
	public function isReConnect($error_object,$error_info){
		if($error_object->errno == 2006||$error_object->errno == 2013){
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
	public function disConnect($connection=null){
	    $status = TRUE;
	    if ($connection===null){
	        if (is_object( $this->master_connection )) {
	            $status = $this->master_connection->close();
	            unset($this->master_connection);
	        }
	        if (is_object ( $this->read_connection )) {
	            $status = $this->read_connection->close();
	            unset($this->read_connection);
	        }
	        $this->master_connection=$this->read_connection=null;
	    }else{
	        switch ($connection){
	            case $this->master_connection:
	                if (is_object ( $this->master_connection )) {
	                    $status = $this->master_connection->close();
	                    unset($this->master_connection);
	                }
	                $this->master_connection=null;
	                break;
	            case $this->read_connection:
	                if (is_object ( $this->read_connection )) {
	                    $status = $this->read_connection->close();
	                    unset($this->read_connection);
	                }
	                $this->read_connection=null;
	                break;
	             default:
	                 $status=($connection instanceof \mysqli)&&$connection->close();
	                 unset($connection);
	                break;
	        }
	    }
	    return $status;
	}
	public function setCharset($connection,$charset){
	    $status = $connection->set_charset($charset);
	    if ($status === FALSE) {
	        throw new Exception (  $connection->error, $connection->errno );
	    }
	}
    public function isMaster($connection){
        return $this->master_connection==$connection;
    }
	public function schema($connection){
	    if($this->isMaster($connection))return $this->schema['master']??null;
	    else return $this->schema['slave']??null;
	}
	public function createConnect($connect_type=self::CONNECT_SLAVE){
	    if ($this->query_mode==self::QUERY_AUTO&&$connect_type===self::CONNECT_SLAVE){//读数据库
	        $config=$this->config->get("slave_connection",array());
	        $config=$this->weightGetConfig($config);
	        if(!empty($config))return $this->connectCreate($config);
	    }
	    $config=$this->config->get("connection",array());
	    return $this->connectCreate($config);
	}
	/**
	 * @param array $link_config
	 * @throws Exception
	 * @return \mysqli
	 */
	protected function connectCreate(array $link_config){
	    /**
	     * @var bool $persistent
	     * @var string $hostname
	     * @var string $username
	     * @var string $password
	     * @var string $database
	     * @var string $port
	     * @var array $variables
	     */
	    extract($link_config+array(
	        'hostname' 		=> NULL,
	        'username' 		=> NULL,
	        'password' 		=> NULL,
	        'database' 		=> NULL,
	        'port' 			=> 3306,
	        'persistent' 	=> FALSE,
	        'variables'  	=> array()
	    ));
	    unset($link_config);
	    if ($persistent) {
	        // To open a persistent connection you must prepend p: to the hostname when connecting.
	        $hostname = 'p:' . $hostname;
	    }
	    try {
	        $connection = @new \mysqli($hostname, $username, $password,$database,$port);
	    } catch ( \Exception $e ) {
	        throw new Exception ( $e->getMessage(), $e->getCode(),$e );
	    }
	    if ($connection->connect_errno) {
	        throw new Exception($connection->connect_error, $connection->connect_errno);
	    }
	    $charset=$this->config->get("charset");
	    if (! empty ($charset))  $this->setCharset($connection, $charset);
	    if (is_array($variables)&&count($variables)>0) {
	        // Set session variables
	        $_variables = array ();
	        foreach ( $variables as $var => $val ) {
	            $_variables [] = 'SESSION ' . $var . ' = ' . $this->quote ( $val );
	        }
	        $connection->query('SET ' . implode ( ', ', $_variables ));
	    }
	    return $connection;
	}
}
