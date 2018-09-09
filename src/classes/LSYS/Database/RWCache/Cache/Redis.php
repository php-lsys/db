<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\RWCache\Cache;
use LSYS\Database\RWCache\Cache;
class Redis implements Cache{
    protected $_redis;
    protected $_key;
    protected $_delayed;
    public function __construct($delayed=10,\LSYS\Redis $redis=null,$key='db_master'){
        $this->_delayed=$delayed;
        $this->_redis=$redis?$redis:\LSYS\Redis\DI::get()->redis();
        $this->_key=$key;
    }
    public function time(array $table){
        $this->_redis->configConnect();
        $val=$this->_redis->hMGet($this->_key,$table);
        if (is_array($val)){
            foreach ($val as $v){
                if (intval($v)>time())return true;
            }
        }
    }
    public function save(array $table){
        $this->_redis->configConnect();
        foreach ($table as $v){
            $this->_redis->hSet($this->_key,$v,time()+$this->delayed());
        }
    }
    public function delayed(){
        return $this->_delayed;
    }
}