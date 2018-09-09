<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\Database;
class ConnectParam {
    protected $_model;
    protected $_query_model;
    protected $_parse_cache;
    protected $_sql;
    protected $_type;
    public function __construct($query_mode){
        $this->_query_model=$query_mode;
        $this->_model=$query_mode==Database::QUERY_AUTO?Connect::READ_MODEL:Connect::MASTER_MODEL;
    }
    /**
     * 设置当前查询
     * @param int $type
     * @param string $sql
     * @param RWCache $parse_cache
     */
    public function set_query($type,$sql,RWCache $parse_cache=null){
        $this->_parse_cache=$parse_cache;
        $this->_type=$type;
        $this->_sql=$sql;
        if (!($type==Database::DQL&&$this->_query_model==Database::QUERY_AUTO)){
            $this->_model=Connect::MASTER_MODEL;
        }
        if($this->_model==Connect::MASTER_MODEL)return ;
        if(!$this->_parse_cache||$this->_parse_cache->cache()->delayed()<=0)return;
        if($this->_parse_cache->cache()->time($this->_parse_cache->parse()->query_parse($sql))){
            $this->_model=Connect::MASTER_MODEL;
        }
    }
    /**
     * 是否可以得到从库
     * @return boolean
     */
    public function is_slave(){
        return $this->_model==Connect::READ_MODEL;
    }
    public function save(){
        if(!$this->_parse_cache||$this->_parse_cache->cache()->delayed()<=0||!$this->_sql)return;
        $this->_parse_cache->cache()->save($this->_parse_cache->parse()->exec_parse($this->_type,$this->_sql));
    }
}
