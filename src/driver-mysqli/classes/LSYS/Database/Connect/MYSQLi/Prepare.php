<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\Connect\MYSQLi;
use \LSYS\Database\Exception;
use LSYS\Database\EventManager\DBEvent;
/**
 * @property \LSYS\Database\Connect\MYSQLi $connect 
 */
class Prepare extends \LSYS\Database\PrepareMaster{
    /**
     * @var \mysqli_stmt|NULL
     */
    protected $prepare;
    protected $query_sql;
    protected $query_map=[];
    protected function prepareCreate(){
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
                    $v=$v->compile($this->connect);
                    $index_expr[$p]=$v;
                    array_pop($val);
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
                    $v=$v->compile($this->connect);
                    $fill[$k]=$v;
                    array_pop($val);
                }else{
                    $fill[$k]="?";
                }
            }
        }
        //实际请求预编译SQL
        $op=0;
        foreach ($index_expr as $k=>$v){
            $sql=substr_replace($sql, $v, $k+$op,1);
            $op+=strlen($v)-1;
        }
        if(count($fill)){
            $sql=strtr($sql,$fill);
        }
        ksort($val);
        $this->query_map=array_values($val);
        if ($this->prepare) {//存在预编译
            if($sql===$this->query_sql)return;//请求SQL相同
        }
        $this->query_sql=$sql;
        $native_connect=$this->connect->link();
        while (true){
            $result = @$native_connect->prepare($this->query_sql);
            if($result===false){
                if($this->reConnect()){
                    $this->connect->disConnect();
                    continue;
                }else {
                    throw (new Exception($native_connect->error, $native_connect->errno))->setErrorSql($this->query_sql);
                }
            }else break;
        }
        $this->prepare=$result;
    }
    protected function quote($value,$value_type=null):array{
        if ($value === TRUE) {
            return ["1",'i'];
        } elseif ($value === FALSE) {
            return ["0",'i'];
        } elseif (is_array ( $value )) {
            $type=[];
            foreach ($value as &$v) {
                list($v,$type[])=$this->quote($v,$value_type);
            }
            return [$value,empty($type)?"s":implode("", $type)];
        } elseif (is_int ( $value )) {
            return [$value,"i"];
        } elseif (is_float ( $value )) {
            return [$value,"d"];
        }
        return [strval($value),"s"];
    }
    /**
     * 执行绑定值
     * @throws Exception
     * @return array
     */
    protected function bindValue():array{
        if(empty($this->query_map))return [];
        $st=null;
        $param=[];
        foreach ($this->query_map as $v){
            $val=$this->value[$v]??null;
            list($val,$type)=$this->quote($val,$this->value_type[$v]??null);
            $st.=$type;
            if(is_array($val)){
                $param=array_merge($param,$val);
            }else{
                $param[]=$val;
            }
        }
        $refs = array();
        foreach($param as $key => $_){
            $_;$refs[$key] = &$param[$key];
        }
        array_unshift($refs, $st);
        if(!is_null($st)&&!call_user_func_array(array($this->prepare,'bind_param'), $refs)){
            $native_connect=$this->connect->link();
            throw new Exception ($native_connect->error, $native_connect->errno);
        }
        return $param;
    }
    protected function reConnect(){
        return !$this->connect->inTransaction()
            &&$this->connect->isReConnect($this->prepare);
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\PrepareSlave::query()
     */
    public function query(){
        $this->execSql(false);
        return new Result($this->prepare->get_result(),function(){
            $this->prepare->next_result();
            return $this->prepare->get_result();
        });
    }
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\PrepareMaster::exec()
	 */
    public function exec():bool{
        $this->execSql(true);
	    return true;
	}
	protected function execSql($exec) {
	    while(true){
	        $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlStart($this,$exec));
	        $this->prepareCreate();
	        $this->bindValue();
	        if(!@$this->prepare->execute()){
	            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlBad($this,$exec));
	            if($this->reConnect()){
	                $this->prepare=null;
	                $this->connect->disConnect();
	                continue;
	            }else{
	                $native_connect=$this->connect->link();
	                throw (new Exception($native_connect->error, $native_connect->errno))->setErrorSql($this->query_sql);
	            }
	        }
	        $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlOk($this,$exec));
	        break;
	    }
	    $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($this,$exec));
	}
	/**
	 * return last query affected rows
	 * @return int
	 */
	public function affectedRows():int{
	    return $this->prepare?$this->prepare->affected_rows:0;
	}
	/**
	 * return last insert auto id
	 * @return int
	 */
	public function insertId():?int{
	    return $this->prepare?$this->prepare->insert_id:null;
	}
	/**
	 * free result
	 */
	public function __destruct(){
	    if ($this->prepare&&$this->prepare->get_result()!==false) $this->prepare->free_result();
	}
}
