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
    protected $redis;
    protected $key;
    protected $delayed;
    public function __construct($delayed=10,\LSYS\Redis $redis=null,$key='db_master'){
        $this->delayed=$delayed;
        $this->redis=$redis?$redis:\LSYS\Redis\DI::get()->redis();
        $this->key=$key;
    }
    public function time(array $table){
        $this->redis->configConnect();
        $val=$this->redis->hMGet($this->key,$table);
        if (is_array($val)){
            foreach ($val as $v){
                if (intval($v)>time())return true;
            }
        }
    }
    public function save(array $table){
        $this->redis->configConnect();
        foreach ($table as $v){
            $this->redis->hSet($this->key,$v,time()+$this->delayed());
        }
    }
    public function delayed(){
        return $this->delayed;
    }
}