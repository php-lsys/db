<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\Connect\PDO;
use LSYS\Database\ConnectRetry;
use LSYS\Database\EventManager\DBEvent;
use LSYS\Database\PDOException;
/**
 * @property \LSYS\Database\Connect\PDO $connect
 */
class Prepare extends \LSYS\Database\PrepareMaster{
    protected $query_sql;
    protected $prepare;
    protected $insert_id;
    protected function prepareCreate(){
        $sql=$this->sql;
        $arrv=$irm=$earrv=[];
        foreach ($this->value??[] as $k=>$v){
            if (is_int($k)) {
                if ($v instanceof \LSYS\Database\Expr
                   ||is_array($v)) {
                    $p=false;$f=1;
                    while (true) {
                        $p=strpos($sql, '?',$p?($p+1):0);
                        if($p===false)break;
                        if($k>=$f)break;
                    }
                    if($p===false){
                        $v=serialize($v);
                        trigger_error("? not match [{$v}]",E_USER_NOTICE);
                        continue;
                    }
                    if($v instanceof \LSYS\Database\Expr){
                        $v=$v->compile($this->connect->db());
                        $sql=substr_replace($sql, $v,$p,1);
                        $irm[]=$k;
                    }
                    if(is_array($v)){
                        $rval=array_fill(0, count($v), '?');
                        $rval='('.implode(",", $rval).')';
                        $sql=substr_replace($sql,$rval,$p,1);
                        $earrv[$k]=$v;
                    }
                    unset($this->value[$k]);
                    continue;
                }
                continue;
            }
            if ($v instanceof \LSYS\Database\Expr) {
                //解析表达式对象
                $v=$v->compile($this->connect->db());
                $sql=strtr($sql,[$k=>$v]);
                unset($this->value[$k]);
            }
            if (is_array($v)) {
                $rkey=[];
                foreach ($v as $kk=>$vv){
                    $key=uniqid($k).$kk;
                    $arrv[$key]=$vv;
                    $rkey[]=$key;
                }
                $sql=strtr($sql,[$k=>'('.implode(",", $rkey).')']);
                unset($this->value[$k]);
            }
        }
        $this->value=array_merge($this->value,$arrv);
        if(count($irm)||count($earrv)){
            $value=[];
            $i=1;
            $j=1;
            $rp=0;
            while (true) {
                if (in_array($i, $irm)) {
                    $rp-=1;
                    $i++;
                    $j++;
                    continue;
                }
                if (isset($this->value[$i])) {
                    $value[$j+$rp]=$this->value[$i];
                    $j++;
                }else if(isset($earrv[$i])){
                    foreach (array_values($earrv[$i]) as $v){
                        $value[$j+$rp]=$v;
                        $j++;
                    }
                }else break;
                $i++;
            }
            $this->value=$value;
        }
        if ($this->prepare) {//存在预编译
            if($sql===$this->query_sql)return true;//解析SQL相同
        }
        $this->query_sql=$sql;//解析完SQL
        while (true) {
            $connect=$this->connect->link();
            try{
                $result = $connect->prepare($this->query_sql);
            }catch (\Exception $e)
            {
                if ($this->reConnect($e)) {
                    $this->connect->disConnect();
                    continue;
                }
                throw (new PDOException($e->getMessage(),$e->getCode(),$e))->setErrorSql($this->query_sql);
            }
            if($result===false){
                if ($this->reConnect($connect)) {
                    $this->connect->disConnect();
                    continue;
                }
                throw (new PDOException($connect->errorInfo(),$connect->errorCode()))->setErrorSql($this->query_sql);
            }
            break;
        }
        $this->prepare=$result;
    }
    protected function bindValue($key,$value,$value_type=null){
        if(is_int($value)){
            $attr=\PDO::PARAM_INT;
        }else if (is_bool($value)){
            $attr=\PDO::PARAM_BOOL;
        }else if (is_null($value)){
            $attr=\PDO::PARAM_NULL;
        }else if (is_resource($value)){
            $attr=\PDO::PARAM_LOB;
        }else{
            $attr=\PDO::PARAM_STR;
            $value=strval($value);
        }
        if(!$this->prepare->bindValue($key,$value,$attr)){
            throw new PDOException($this->prepare->errorInfo(),$this->prepare->errorCode());
        }
    }
    protected function bindValues(){
        foreach ($this->value as $k=>$v){
            assert(!is_array($v));
            $this->bindValue($k,$v,$this->value_type[$k]??null);
        }
    }
    protected function reConnect($error_info):bool{
        $connect=$this->connect;
        if(!($connect instanceof ConnectRetry))return false;
        return !$connect->inTransaction()
        &&$connect->isReConnect($error_info);
    }
    public function exec():bool{
        $this->querySql();
        $this->insert_id=$this->connect->link()->lastInsertId();
        return true;
    }
    public function query(){
        $this->querySql();
        return new Result($this->prepare);
    }
    protected function querySql(){
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlStart($this,true));
        while(true){
            $this->prepareCreate();
            try{
                $this->bindValues();
                $exec=$this->prepare->execute();
            }catch (\Exception $e){
                if ($this->reConnect($e)) {
                    $this->prepare=null;
                    $this->connect->disConnect();
                    continue;
                }
                $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlBad($this,true));
                throw (new PDOException($e->getMessage(), $e->getCode()))->setErrorSql($this->query_sql);
            }
            if(!$exec){
                $connect=$this->connect->link();
                if ($this->reConnect($connect)) {
                    $this->prepare=null;
                    $this->connect->disConnect();
                    continue;
                }
                $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlBad($this,true));
                throw (new PDOException(json_encode($connect->errorInfo(),JSON_UNESCAPED_UNICODE), $connect->errorCode()))->setErrorSql($this->query_sql);
            }
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlOk($this,true));
            break;
        }
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($this,true));
    }
    public function lastQuery():?string{
        if (!$this->prepare) return null;
        return strval($this->prepare->queryString);
    }
    public function affectedRows():int{
        return $this->prepare?$this->prepare->rowCount():0;
    }
    public function insertId():?int{
        return $this->insert_id;
    }
    public function __destruct(){
        $this->prepare&&$this->prepare->closeCursor();
    }
}