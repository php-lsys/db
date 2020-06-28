<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\EventManager;
abstract class PrepareSlave{
    protected $connect;
    protected $sql;
    protected $event_manager;
    protected $value=[];
    protected $value_type=[];
    /**
     * 从库预执行
     * @param ConnectSlave $connect
     * @param string $sql
     * @param EventManager $event_manager
     */
    public function __construct(ConnectSlave $connect,string $sql,EventManager $event_manager=null) {
        $this->connect=$connect;
        $this->sql=$sql;
        $this->event_manager=$event_manager;
    }
    /**
     * return database object
     * @return \LSYS\Database\ConnectSlave
     */
    public function connect(){
        return $this->connect;
    }
    /**
     * return last query database sql
     * @return string
     */
    abstract public function lastQuery():?string;
    /**
     * bind data to sql
     * @param array $value
     * @param array $value_type
     * @return $this
     */
    public function bindParam(array $value=[],array $value_type=[]){
        $this->value=$value;
        $this->value_type=$value_type;
        return $this;
    }
	/**
	 * query Prepare sql
	 * @param array $parameters
	 * @return Result
	 */
    abstract public function query();
}
