<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
interface AsyncQuery{
    /**
     * add sql to query queue
     * @param string $sql
     * @param array $value
     * @param array $value_type
     */
    public function asyncAddQuery($sql,array $value=[],array $value_type=[]);
    /**
     * add sql to exec queue
     * @param string $sql
     * @param array $value
     * @param array $value_type
     */
    public function asyncAddExec($sql,array $value=[],array $value_type=[]);
	/**
	 * exec add queue
	 * @return AsyncResult
	 */
    public function asyncExecute();
}