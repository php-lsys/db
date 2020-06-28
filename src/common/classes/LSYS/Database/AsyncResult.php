<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */ 
namespace LSYS\Database;
class AsyncResult{
    protected $result;
    protected $affected_rows;
    protected $insert_id;
    public function __construct(array $result,array $affected_rows,array $insert_id) {
        $this->result=$result;
        $this->affected_rows=$affected_rows;
        $this->insert_id=$insert_id;
    }
    /**
     * 得到异步结构
     * @param array|int $aysnc_index
     * @throws \LSYS\Database\Exception
     * @return Result|bool|NULL
     */
    public function result($aysnc_index){
        if(is_array($aysnc_index)){
            $out=array();
            foreach ($aysnc_index as $index){
                $res=$this->result($index);
                if ($res instanceof \Exception)throw $res;
                $out[]=$res;
            }
            return $out;
        }
        $res=$this->result[$aysnc_index-1]??null;
        if ($res instanceof \Exception)throw $res;
        return $res;
    }
    /**
     * return last query affected rows
     * @return int
     */
    public function affectedRows($aysnc_index):int{
        if(is_array($aysnc_index)){
            $out=array();
            foreach ($aysnc_index as $index){
                $out[$index]=$this->affectedRows($index);
            }
            return $out;
        }
        return $this->affected_rows[$aysnc_index-1]??0;
    }
    /**
     * return last insert auto id
     * @return int
     */
    public function insertId($aysnc_index):?int{
        if(is_array($aysnc_index)){
            $out=array();
            foreach ($aysnc_index as $index){
                $out[$index]=$this->insertId($index);
            }
            return $out;
        }
        return $this->insert_id[$aysnc_index-1]??null;
    }
}