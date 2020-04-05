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
    protected $log;
    /**
     * @param number $delayed
     * @param \LSYS\Redis $redis
     * @param string $key
     * @param callable $log error report callback (\LSYS\Exception $e)
     */
    public function __construct($delayed=10,\LSYS\Redis $redis=null,$key='db_master',callable $log=null){
        $this->delayed=$delayed;
        $this->key=$key;
        $this->redis=$redis;
        $this->log=$log;
    }
    protected function redis(){
        if (!is_object($this->redis))$this->redis=\LSYS\Redis\DI::get()->redis();
        try{
            $this->redis->configConnect();
        }catch (\LSYS\Exception $e){
            is_callable($this->log)&&call_user_func($this->log,$e);
            return;
        }
        return $this->redis;
    }
    public function time(array $table){
        $redis=$this->redis();
        if(!is_object($redis))return true;
        $val=$redis->hMGet($this->key,$table);
        if (is_array($val)){
            foreach ($val as $v){
                if (intval($v)>time())return true;
            }
        }
    }
    public function save(array $table){
        $redis=$this->redis();
        if(!is_object($redis))return ;
        $data=array_combine($table, array_fill(0, count($table), time()+$this->delayed()));
        return $redis->hmSet($this->key,$data);
    }
    public function delayed(){
        return $this->delayed;
    }
}