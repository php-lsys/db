<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\EventManager;
/**
 * @method ConnectMaster connect()
 */
abstract class PrepareMaster extends PrepareSlave{
    /**
     * 主库预执行
     * @param ConnectMaster $connect
     * @param string $sql
     * @param EventManager $event_manager
     */
    public function __construct(ConnectMaster $connect,string $sql,EventManager $event_manager=null) {
        parent::__construct($connect, $sql,$event_manager);
    }
	/**
	 * exec Prepare sql
	 * @param array $parameters
	 * @return bool
	 */
    abstract public function exec():bool;
    /**
     * return last query affected rows
     * @return int
     */
    abstract public function affectedRows():int;
    /**
     * return last insert auto id
     * @return int
     */
    abstract public function insertId():?int;
}
