<?php
/**
 * 读写分离延迟查询缓存
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\Database\RWCache\Cache;
use LSYS\Database\RWCache\Parse;
class RWCache{
    protected $_cache;
    protected $_parse;
    public function __construct(Cache $cache,Parse $parse){
        $this->_cache=$cache;
        $this->_parse=$parse;
    }
    public function cache(){
        return $this->_cache;
    }
    public function parse(){
        return $this->_parse;
    }
}