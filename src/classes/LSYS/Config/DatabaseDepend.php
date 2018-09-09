<?php
namespace LSYS\Config;
use LSYS\DI;
use LSYS\DI\MethodCallback;
/**
 * @method \LSYS\Database database_config_db() 
 * @method string database_config_table() 
 */
class DatabaseDepend extends DI{
    public static function get(){
        if(!self::has())self::set(function(){
            return (new self)
            ->database_config_db(new MethodCallback(function(){
                return \LSYS\Database\DI::get()->db();
            }))
            ->database_config_table(new MethodCallback(function(){
                return 'config';
            }));
        });
        return parent::get();
    }
}