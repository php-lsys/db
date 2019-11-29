<?php
/**
 * 检测是否可以从 slave库查询
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\Database\SlaveQueryCheck\Parse;
use LSYS\Database\SlaveQueryCheck\Cache;
use LSYS\Database\SlaveQueryCheck\Parse\Simple;
class SlaveQueryCheck{
    protected $cache;
    protected $parse;
    public function __construct(Cache $cache,Parse $parse=null){
        $this->cache=$cache;
        $this->parse=$parse?$parse:new Simple();
    }
    /**
     * 检测当前SQL是否可以通过从库查询
     * @param string $sql
     */
    public function allowSlave($sql){
        if($this->cache->delayed()<=0)return false;
        $table=$this->parse->queryParseTable($sql);
        if(empty($table))return false;
        if($this->cache->time($table)){
            return false;
        }
        return true;
    }
    /**
     * SQL更改告知
     * @param string $table_schema 默认数据库名
     * @param string $sql
     */
    public function execNotify(Prepare $prepare,$connect){
        $sql=$prepare->lastQuery();
        if($this->cache->delayed()<=0
            ||$prepare->affectedRows()<=0
            ||empty($sql))return;
        $table=$this->parse->execParseTable($sql);
        if(empty($table))return;
        $table_schema=$prepare->db()->getConnectManager()->schema($connect);
        if(!empty($table_schema)){
            $add=[];
            foreach ($table as $v){
                if(strpos($v, '.')===false)$add[]=$table_schema.'.'.$v;
            }
            $table=array_merge($table,$add);
        }
        $this->cache->save($table);
    }
}
