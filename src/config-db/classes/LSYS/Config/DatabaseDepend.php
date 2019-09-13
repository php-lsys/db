<?php
namespace LSYS\Config;
use LSYS\DI;
use LSYS\DI\MethodCallback;
/**
 * @method \LSYS\Database databaseConfigDb() 
 * @method string databaseConfigTable() 
 */
class DatabaseDepend extends DI{
    public static function get(){
        if(!self::has())self::set(function(){
            return (new self)
            ->databaseConfigDb(new MethodCallback(function(){
                return \LSYS\Database\DI::get()->db();
            }))
            ->databaseConfigTable(new MethodCallback(function(){
                return 'config';
            }));
        });
        return parent::get();
    }
}