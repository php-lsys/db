<?php
/**
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\EventManager;
use LSYS\EventManager\Event;
use LSYS\Database;
class DBEvent extends Event
{
    //SWOOLE 事件列表 $swoole_event 变量得取值
    const QUERY_START=1;
    const QUERY_OK=2;
    const QUERY_ERROR=3;
    const QUERY_END=4;
    const EXEC_START=5;
    const EXEC_OK=6;
    const EXEC_ERROR=7;
    const EXEC_END=8;
    const TRANSACTION_BEGIN=9;
    const TRANSACTION_COMMIT=10;
    const TRANSACTION_ROLLBACK=11;
    protected $db;
    protected $event;
    protected $args;
    public function __construct(Database $db,$event_name,$args) {
        $this->db=$db;
        $this->event=$event_name;
        $this->args=$args;
    }
    public function name(){
        return $this->event;
    }
    public function db(){
        return $this->db;
    }
    /**
     * @return array
     */
    public function eventArgs(){
        return is_array($this->args)?$this->args:[];
    }
}