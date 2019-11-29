<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\SlaveQueryCheck\Cache;
use LSYS\Database\SlaveQueryCheck\Cache;
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
        $data=array_combine($table, array_fill(0, count($table), time()+$this->delayed()));
        return $this->redis->hmSet($this->key,$data);
    }
    public function delayed(){
        return $this->delayed;
    }
}