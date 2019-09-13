<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\PDO;
use LSYS\Database\Exception;
class ConnectManager extends \LSYS\Database\ConnectManager {
	protected $connection;
	protected $schema;
	protected function getSchema(array $link_config){
	    $match=null;
	    if(isset($link_config['dsn'])&&preg_match("/dbname\s*=\s*([^;]+).*$/", $link_config['dsn'],$match)){
	        return $match[1];
	    }
	    return null;
	}
	protected function connectCreate(array $link_config){
		/**
		 * @var bool $persistent
		 * @var string $password
		 * @var string $username
		 * @var string $dsn
		 * @var array $options
		 */
		extract($link_config+array(
			'dsn'        => '',
			'username'   => NULL,
			'password'   => NULL,
			'persistent' => FALSE,
		));
		unset($link_config);
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
			// Create a new PDO connection
			$connent = new \PDO($dsn, $username, $password, $options);
		}
		catch (\PDOException $e)
		{
			throw new Exception($e->getMessage(),$e->getCode(),$e);
		}
		$charset=$this->config->get("charset");
		if (! empty ($charset))  $this->setCharset($connent, $charset);
		return $connent;
	}
	/**
	 * @return  \PDO
	 */
	public function getConnect($connect_type=self::CONNECT_AUTO){
	    if(!$this->connection){
	        $link_config=(array)$this->config->get("connection",array());
	        $this->connection=$this->connectCreate($link_config);
	        $this->schema=$this->getSchema($link_config);
	    }
	    return $this->connection;
	}
	public function isConnected($connect_type=self::CONNECT_AUTO) {
	    return is_object($this->connection);
	}
	public function disConnect($connect=null){
	    unset($this->connection);
	    $this->connection=null;
	}
	public function schema($connection){
	    return $this->schema;
	}
	public function setCharset($connection,$charset){
	    $charset=$connection->quote($charset);
	    $connection->exec('SET NAMES '.$charset);
	}
    public function isMaster($connect)
    {
        return true;
    }
}
