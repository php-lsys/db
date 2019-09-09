<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\MYSQLi;
use \LSYS\Database\Exception;
use LSYS\Database\ConnectRetry;
class Prepare extends \LSYS\Database\Prepare{
    protected $prepare;
    protected $connect;
    protected $affected_rows=0;
    protected $query_sql;
    protected $query_map=[];
    protected function prepareCreate($allow_slave){
        $sql=$this->sql;
        $index=$val=$fill=$index_expr=[];
        foreach ($this->value as $k => $v){
            if (is_int($k)){
                $p=end($index);
                $p=strpos($sql,'?',$p?($p+1):0);
                if($p===false)continue;
                $index[]=$p;
                $val[$p]=$k;
                if ($v instanceof \LSYS\Database\Expr) {
                    $v=$v->compile($this->db);
                    $index_expr[$p]=$v;
                }
                continue;
            }
            $p=0;
            while (true) {
                $p=strpos($sql,$k,$p);
                if($p===false)break;
                $val[$p]=$k;
                $p+=1;
            }
            if (is_array($v)) {
                $fill[$k]="(".implode(",", array_fill(0, count($v), "?")).")";
            }else{
                if ($v instanceof \LSYS\Database\Expr) {
                    $v=$v->compile($this->db);
                    $fill[$k]=$v;
                }else{
                    $fill[$k]="?";
                }
            }
        }
        //实际请求预编译SQL
        ksort($val);
        $this->query_map=array_values($val);
        $op=0;
        foreach ($index_expr as $k=>$v){
            $sql=substr_replace($sql, $v, $k+$op,1);
            $op+=strlen($v)-1;
        }
        if(count($fill)){
            $sql=strtr($sql,$fill);
        }
        if ($this->prepare) {//存在预编译
            if($sql===$this->query_sql)return;//请求SQL相同
        }
        $this->query_sql=$sql;
        $connect_mgr=$this->db->getConnectManager();
        while (true){
            $this->connect=$connect_mgr->getConnect($allow_slave?ConnectManager::CONNECT_SLAVE:ConnectManager::CONNECT_MASTER);
            $result = @$this->connect->prepare($this->query_sql);
            if($result===false){
                if($this->reConnect()){
                    $this->disConnect();
                    continue;
                }else {
                    throw (new Exception($this->connect->error, $this->connect->errno))->setErrorSql($this->query_sql);
                }
            }else break;
        }
        $this->prepare=$result;
    }
    protected function quote($value,$value_type=null){
        if ($value === TRUE) {
            return ["1",'i'];
        } elseif ($value === FALSE) {
            return ["0",'i'];
        } elseif (is_array ( $value )) {
            $type=[];
            foreach ($value as &$v) {
                list($v,$type[])=$this->quote($v,$value_type);
            }
            return [$value,empty($type)?"s":$type];
        } elseif (is_int ( $value )) {
            return [$value,"i"];
        } elseif (is_float ( $value )) {
            return [$value,"d"];
        }
        return [strval($value),"s"];
    }
    protected function bindValue(){
        $st=null;
        $param=[&$st];
        foreach ($this->query_map as $v){
            $val=$this->value[$v]??null;
            list($val,$type)=$this->quote($val,$this->value_type[$v]??null);
            $st.=$type;
            if(is_array($val))$param=array_merge($param,$val);
            else $param[]=$val;
        }
        if(!is_null($st)&&!call_user_func_array(array($this->prepare,'bind_param'), $param)){
            throw new Exception ($this->connect->error, $this->connect->errno);
        }
        return $param;
    }
    protected function reConnect(){
        if(!$this->connect)return ;
        $connect_mgr=$this->db->getConnectManager();
        return $connect_mgr instanceof ConnectRetry
            &&!$this->db->inTransaction()
            &&$connect_mgr->isReconnect($this->connect,$this->prepare);
    }
    protected function disConnect(){
        $connect_mgr=$this->db->getConnectManager();
        $connect_mgr->disconnect($this->connect);
        $this->connect=null;
    }
    public function query(){
        while(true){
            $this->prepareCreate($this->slave_check&&$this->slave_check->allowSlave($this->sql));
            $this->bindValue();
            if(!@$this->prepare->execute()){
                if($this->reConnect()){
                    $this->prepare=null;
                    $this->disConnect();
                    continue;
                }else{
                    throw (new Exception($this->connect->error, $this->connect->errno))->setErrorSql($this->query_sql);
                }
            }
            break;
        }
        return new Result($this->prepare->get_result(),function(){
            $this->prepare->next_result();
            return $this->prepare->get_result();
        });
    }
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\Prepare::execute()
	 */
    public function exec(){
	    $connect_mgr=$this->db->getConnectManager();
	    if(!$connect_mgr->isMaster($this->connect)){
	        $this->prepare=null;
	    }
	    while(true){
            $this->prepareCreate(false);
	        $this->bindValue();
	        if(!@$this->prepare->execute()){
	            if($this->reConnect()){
	                $this->prepare=null;
	                $this->disConnect();
	                continue;
	            }else {
	                throw (new Exception($this->connect->error, $this->connect->errno))->setErrorSql($this->query_sql);
	            }
	        }
	        break;
	    }
	    $this->slave_check&&$this->slave_check->execNotify($connect_mgr->schema($this->connect),$this->sql);
	    $this->affected_rows=$this->connect->affected_rows;
	    $this->insert_id=$this->connect->insert_id;
	    return true;
	}
	/**
	 * return last query affected rows
	 * @return int
	 */
	public function affectedRows(){
	    return $this->affected_rows;
	}
	/**
	 * return last insert auto id
	 * @return int
	 */
	public function insertId(){
	    $connent=$this->connect->getConnect(ConnectManager::CONNECT_MASTER);
	    return $connent->insert_id;
	}
	/**
	 * free result
	 */
	public function __destruct(){
	    if ($this->prepare&&$this->prepare->get_result()!==false) $this->prepare->free_result();
	}
}
