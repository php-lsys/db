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
    protected $_memcache;
    protected $_prefix;
    protected $_delayed;
    public function __construct($delayed=10,\LSYS\Memcache $memcache=null,$prefix='db_master'){
        $this->_memcache=$memcache?$memcache:\LSYS\Memcache\DI::get()->memcache();
        $this->_prefix=$prefix;
        $this->_delayed=$delayed;
    }
    public function time(array $table){
        $this->_memcache->configServers();
        foreach ($table as $v){
            if(intval($this->_memcache->get($this->_prefix.$v))>time())return true;
        }
    }
    public function save(array $table){
        $this->_memcache->configServers();
        $delayed=$this->delayed();
        foreach ($table as $v){
            $this->_memcache->set($this->_prefix.$v,time()+$delayed,0,$delayed);
        }
    }
    public function delayed(){
        return $this->_delayed;
    }
}