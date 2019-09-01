<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\Database;
use LSYS\EventManager;
abstract class Prepare{
    protected $db;
    protected $sql;
    protected $slave_check;
    protected $event_manager;
    protected $value=[];
    protected $value_type=[];
    public function __construct(Database $db,$sql,EventManager $event_manager=null,SlaveQueryCheck $slave_check=null) {
        $this->db=$db;
        $this->sql=$sql;
        $this->slave_check=$slave_check;
        $this->event_manager=$event_manager;
    }
    public function db() {
        return $this->db;
    }
    public function lastQuery(){
        return $this->sql.(count($this->value)?" -- ".json_encode($this->value,JSON_UNESCAPED_UNICODE):"");
    }
    public function bindParam(array $value=[],array $value_type=[]){
        $this->value=$value;
        $this->value_type=$value_type;
        return $this;
    }
	/**
	 * exec Prepare sql
	 * @param array $parameters
	 * @return bool
	 */
    abstract public function exec();
	/**
	 * query Prepare sql
	 * @param array $parameters
	 * @return Result
	 */
    abstract public function query();
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
