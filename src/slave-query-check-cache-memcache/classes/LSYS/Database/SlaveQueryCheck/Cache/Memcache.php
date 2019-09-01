<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\RWCache\Cache;
use LSYS\Database\RWCache\Cache;
class Memcache implements Cache{
    protected $memcache;
    protected $prefix;
    protected $delayed;
    public function __construct($delayed=10,\LSYS\Memcache $memcache=null,$prefix='db_master'){
        $this->memcache=$memcache?$memcache:\LSYS\Memcache\DI::get()->memcache();
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
            $this->memcache->set($this->prefix.$v,time()+$delayed,0,$delayed);
        }
    }
    public function delayed(){
        return $this->delayed;
    }
}