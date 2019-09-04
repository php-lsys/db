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
}