<?php
/**
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\EventManager;
use LSYS\EventManager\Event;
class DBEvent extends Event
{
    const QUERY_START="db.query.start";
    const QUERY_OK="db.query.ok";
    const QUERY_ERROR="db.query.error";
    const QUERY_END="db.query.end";
    const EXEC_START="db.exec.start";
    const EXEC_OK="db.exec.ok";
    const EXEC_ERROR="db.exec.error";
    const EXEC_END="db.exec.end";
    const TRANSACTION_BEGIN="db.transaction.begin";
    const TRANSACTION_COMMIT="db.transaction.commit";
    const TRANSACTION_ROLLBACK="db.transaction.rollback";
    public static function queryStart($sql) {
        return new self(self::QUERY_START,['sql'=>$sql]);
    }
}