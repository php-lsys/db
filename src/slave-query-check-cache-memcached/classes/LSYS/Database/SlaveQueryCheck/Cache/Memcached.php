<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\SlaveQueryCheck\Cache;
use LSYS\Database\SlaveQueryCheck\Cache;
class Memcached implements Cache{
    protected $memcache;
    protected $prefix;
    protected $delayed;
    public function __construct($delayed=10,\LSYS\Memcached $memcache=null,$prefix='db_master'){
        $this->memcache=$memcache?$memcache:\LSYS\Memcached\DI::get()->memcached();
        $this->prefix=$prefix;
        $this->delayed=$delayed;
    }
    public function time(array $table){
        $this->memcache->configServers();
        foreach ($table as $v){
            if(intval($this->memcache->get($this->prefix.$v))>time())return true;
        }
    }
    public function save(array $table){
        $this->memcache->configServers();
        $delayed=$this->delayed();
        foreach ($table as $v){
            $this->memcache->set($this->prefix.$v,time()+$delayed,$delayed);
        }
    }
    public function delayed(){
        return $this->delayed;
    }
}