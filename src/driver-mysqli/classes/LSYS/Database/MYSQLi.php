<?php
/**
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use \LSYS\Database\Connect\MYSQLi as MysqliConnect;
class MYSQLi extends \LSYS\Database implements AsyncMaster{
    /**
     * @var MysqliConnect
     */
    protected $read_connection;
    /**
     * @var MysqliConnect
     */
    protected $master_connection;
    /**
     * async list
     * @var array
     */
    protected $async=[];
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::getConnect()
     */
    public function getConnect(){
        if ($this->read_connection)return $this->read_connection;
        if ($this->master_connection)return $this->master_connection;
        return $this->getSlaveConnect();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::getMasterConnect()
     */
    public function getMasterConnect():ConnectMaster{
        if ($this->master_connection) return $this->master_connection;
        $config=$this->config->get("connection",array());
        $this->master_connection=$this->connectCreate($config);
        return $this->master_connection;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::getSlaveConnect()
     */
    public function getSlaveConnect():ConnectSlave{
        if ($this->read_connection) return $this->read_connection;
        $config=$this->config->get("slave_connection",array());
        $config=$this->weightGetConfig($config);
        if(!empty($config)){
            $this->read_connection=$this->connectCreate($config);
            return $this->read_connection;
        }
        return $this->getMasterConnect();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::disConnect()
     */
    public function disConnect():bool{
        $status = TRUE;
        if (is_object( $this->master_connection )) {
            $status = $this->master_connection->disConnect();
            unset($this->master_connection);
        }
        if (is_object ( $this->read_connection )) {
            $status = $status&&$this->read_connection->disConnect();
            unset($this->read_connection);
        }
        $this->master_connection=$this->read_connection=null;
        return $status;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::isMasterConnected()
     */
    public function isMasterConnected(): bool
    {
        return is_object($this->master_connection);
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database::isSlaveConnected()
     */
    public function isSlaveConnected(): bool
    {
        return is_object($this->read_connection);
    }
    /**
     * 创建连接
     * @param array $link_config
     * @throws Exception
     * @return MysqliConnect
     */
    protected function connectCreate(array $link_config){
        return new MysqliConnect($this,$this->config,$link_config,$this->event_manager);
    }
    /**
     * 添加异步执行
     * @param MysqliConnect $connect
     * @param bool $is_exec
     * @param string $sql
     * @param array $value
     * @param array $value_type
     * @throws Exception
     * @return number
     */
    protected function asyncAdd(MysqliConnect $connect,bool $is_exec,string $sql, array $value = [], array $value_type = []):int{
        $param=[];
        foreach ($value as $k=>$v){
            $param[$k]=$this->getConnect()->quote($v,$value_type[$k]??null);
        }
        $sql=strtr($sql,$param);
        while(true){
            $mysqlconnect=$connect->connectCreate();
            $res=$mysqlconnect->query($sql, MYSQLI_ASYNC);
            if($res===false){
                if(!$connect->inTransaction()
                    &&$connect->isReconnect(null)){
                        $connect->disConnect();
                }else{
                    throw new Exception ($mysqlconnect->error, $mysqlconnect->errno);
                }
            }
            break;
        }
        $this->async[]=array(
            $is_exec,$mysqlconnect
        );
        return count($this->async);
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\AsyncMaster::asyncExec()
     */
    public function asyncExec(ConnectMaster $connect,string $sql, array $value = [], array $value_type = []):int
    {
        return $this->asyncAdd($connect,true, $sql,$value,$value_type);
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\AsyncSlave::asyncQuery()
     */
    public function asyncQuery(ConnectSlave $connect,string $sql, array $value = [], array $value_type = []):int
    {
        return $this->asyncAdd($connect,false, $sql,$value,$value_type);
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\AsyncMaster::asyncExec()
     */
    public function asyncExecute():AsyncResult
    {
        $result=$insert=$aff_row=[];
        $async=$this->async;
        $this->async=[];
        foreach ($async as $k=>$v){
            list($is_exec,$mysqlconnect)=$v;
            assert($mysqlconnect instanceof \mysqli);
            $sql_result = $mysqlconnect->reap_async_query();
            if($sql_result===false){
                $_result=new Exception ($mysqlconnect->error, $mysqlconnect->errno);
                $_aff_row=0;
                $_ins=0;
                goto end;
            }
            if($is_exec){
                $_aff_row=$mysqlconnect->affected_rows;
                $_ins=$mysqlconnect->insert_id;
                $_result=$sql_result;
            }else{
                $more_result=null;
                if($mysqlconnect->more_results()){
                    $more_result=function()use($mysqlconnect){
                        if(!$mysqlconnect->next_result()){
                            $mysqlconnect->close();
                            return ;
                        }
                        return $mysqlconnect->store_result();
                    };
                }
                $_result=new \LSYS\Database\Connect\MYSQLi\Result($sql_result,$more_result);
                $_aff_row=0;
                $_ins=0;
            }
            end:
            $result[$k]=$_result;
            $aff_row[$k]=$_aff_row;
            $insert[$k]=$_ins;
            if (!$mysqlconnect->more_results()) {
                @$mysqlconnect->close();
            }
        }
        return new AsyncResult($result,$aff_row,$insert);
    }
}
