<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\PDO;
class RWSConnectManager extends ConnectManager {
	protected $read_connection;
	protected $slave_schema;
	public function disConnect($connection=null){
		if($connection===null){
			unset($this->read_connection);
			$this->read_connection=null;
			parent::disConnect();
		}else{
			switch ($connection){
				case $this->read_connection:
					unset($this->read_connection);
					$this->read_connection=null;
				break;
				case $this->connection:
				    parent::disConnect();
				break;
			}
		}
	}
	public function getConnect($connect_type=self::CONNECT_AUTO){
	    if ($connect_type===self::CONNECT_AUTO) {
	        if ($this->read_connection)return $this->read_connection;
	        if ($this->connection)return $this->connection;
	    }
	    if ($this->query_mode==self::QUERY_AUTO&&$connect_type===self::CONNECT_SLAVE){//读数据库
	        if ($this->read_connection) return $this->read_connection;
	        $config=$this->config->get("slave_connection",array());
	        $config=$this->weightGetConfig($config);
	        if(!empty($config)){
	            $this->read_connection=$this->connectCreate($config);
	            $this->slave_schema=$this->getSchema($config);
	            return $this->read_connection;
	        }
	    }
	    return parent::getConnect($connect_type);
	}
	public function isConnected($connect_type=self::CONNECT_AUTO) {
	    switch ($connect_type) {
	        case self::CONNECT_AUTO:
	            if(is_object($this->connection))return true;
	            if(is_object($this->read_connection))return true;
	            break;
	        case self::CONNECT_MASTER:
	            return parent::isConnected($connect_type);
	            break;
	        case self::CONNECT_SLAVE:
	            if(is_object($this->read_connection))return true;
	    }
	    return false;
	}
	public function schema($connection){
	    if ($this->connection==$connection) {
	        return parent::schema($connection);
	    }else if ($this->read_connection==$connection){
	        return $this->slave_schema;
	    }
	}
}
