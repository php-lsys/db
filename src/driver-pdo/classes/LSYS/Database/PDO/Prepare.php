<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\PDO;
use LSYS\Database\Exception;
use LSYS\Database\ConnectRetry;
/**
 * @property \LSYS\Database\PDO $db
 */
class Prepare extends \LSYS\Database\Prepare{
    protected $query_sql;
    protected $prepare;
    protected $connect;
    protected $insert_id;
    protected function prepareCreate($allow_slave){
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
                    if($p===false)continue;
                    if($v instanceof \LSYS\Database\Expr){
                        $v=$v->compile($this->db);
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
                $v=$v->compile($this->db);
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
        $connect_mgr=$this->db->getConnectManager();
        $this->connect=$connect_mgr->getConnect($allow_slave?ConnectManager::CONNECT_SLAVE:ConnectManager::CONNECT_MASTER);
        try{
            $result = $this->connect->prepare($this->query_sql);
        }catch (\Exception $e)
        {
            throw (new Exception($e->getMessage(),$e->getCode(),$e))->setErrorSql($this->query_sql);
        }
        if($result===false){
            throw (new Exception($this->connect->errorInfo(),$this->connect->errorCode()))->setErrorSql($this->query_sql);
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
            throw new Exception($this->prepare->errorInfo(),$this->prepare->errorCode());
        }
    }
    protected function disConnect(){
        if(!$this->connect)return ;
        $connect_mgr=$this->db->getConnectManager();
        $connect_mgr->disconnect($this->connect);
        $this->connect=null;
    }
    protected function reConnect(){
        if(!$this->connect)return ;
        $connect_mgr=$this->db->getConnectManager();
        return $connect_mgr instanceof ConnectRetry
        &&!$this->db->inTransaction()
        &&$connect_mgr->isReconnect($this->connect);
    }
    protected function bindValues(){
        foreach ($this->value as $k=>$v){
            $this->bindValue($k,$v,$this->value_type[$v]??null);
        }
    }
    public function exec(){
        while(true){
            $this->prepareCreate(false);
            $this->bindValues();
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
        $this->insert_id=$this->connect->lastInsertId();
        return true;
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
        return new Result($this->prepare);
    }
    public function lastQuery(){
        if ($this->prepare)return $this->prepare->queryString;
        return parent::lastQuery();
    }
    public function affectedRows(){
        return $this->prepare?$this->prepare->rowCount():0;
    }
    public function insertId(){
        return $this->prepare?$this->prepare->rowCount():0;
    }
    public function __destruct(){
        $this->_result&&$this->_result->closeCursor();
    }
}