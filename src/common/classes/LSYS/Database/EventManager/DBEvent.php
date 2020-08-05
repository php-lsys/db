<?php
/**
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\EventManager;
use LSYS\EventManager\Event;
use LSYS\Database\PrepareSlave;
class DBEvent extends Event
{
    const SQL_START="db.sql.start";
    const SQL_OK="db.sql.ok";
    const SQL_BAD="db.sql.bad";
    const SQL_END="db.sql.end";
    const TRANSACTION_BEGIN="db.transaction.begin";
    const TRANSACTION_COMMIT="db.transaction.commit";
    const TRANSACTION_ROLLBACK="db.transaction.rollback";
    const TRANSACTION_FAIL="db.transaction.fail";
    public static function sqlStart(PrepareSlave $prepare,bool $exec) {
        return new self(self::SQL_START,func_get_args());
    }
    public static function sqlOk(PrepareSlave $prepare,bool $exec) {
        return new self(self::SQL_OK,func_get_args());
    }
    public static function sqlBad(PrepareSlave $prepare,bool $exec) {
        return new self(self::SQL_BAD,func_get_args());
    }
    public static function sqlEnd(PrepareSlave $prepare,bool $exec) {
        return new self(self::SQL_END,func_get_args());
    }
    public static function transactionBegin($connent) {
        return new self(self::TRANSACTION_BEGIN,func_get_args());
    }
    public static function transactionCommit($connent) {
        return new self(self::TRANSACTION_COMMIT,func_get_args());
    }
    public static function transactionRollback($connent) {
        return new self(self::TRANSACTION_ROLLBACK,func_get_args());
    }
    public static function transactionFail($connent) {
        return new self(self::TRANSACTION_FAIL,func_get_args());
    }
}