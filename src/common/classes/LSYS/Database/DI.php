<?php
namespace LSYS\Database;
/**
 * @method \LSYS\Database db($config=null) 
 */
class DI extends \LSYS\DI{
    /**
     *
     * @var string default config
     */
    public static $config = 'database.mysqli';
    /**
     * @return static
     */
    public static function get(){
        $di=parent::get();
        !isset($di->db)&&$di->db(new \LSYS\DI\ShareCallback(function($config=null){
            return $config?$config:self::$config;
        },function($config=null){
            $config=\LSYS\Config\DI::get()->config($config?$config:self::$config);
            return \LSYS\Database::factory($config);
        }));
        return $di;
    }
}


