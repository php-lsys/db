<?php
/**
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\EventManager;
use LSYS\EventManager\Event;
use LSYS\Database\Connect\MYSQLi\Prepare;
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
    public static function sqlStart(Prepare $prepare,string $sql,bool $exec) {
        return new self(self::SQL_START,compact(func_get_argsname()));
    }
    public static function sqlOk(Prepare $prepare,string $sql,bool $exec) {
        return new self(self::SQL_OK,compact(func_get_argsname()));
    }
    public static function sqlBad(Prepare $prepare,string $sql,bool $exec) {
        return new self(self::SQL_BAD,compact(func_get_argsname()));
    }
    public static function sqlEnd(Prepare $prepare,string $sql,bool $exec) {
        return new self(self::SQL_END,compact(func_get_argsname()));
    }
    public static function transactionBegin($connent) {
        return new self(self::TRANSACTION_BEGIN,compact(func_get_argsname()));
    }
    public static function transactionCommit($connent) {
        return new self(self::TRANSACTION_COMMIT,compact(func_get_argsname()));
    }
    public static function transactionRollback($connent) {
        return new self(self::TRANSACTION_ROLLBACK,compact(func_get_argsname()));
    }
    public static function transactionFail($connent) {
        return new self(self::TRANSACTION_FAIL,compact(func_get_argsname()));
    }
}