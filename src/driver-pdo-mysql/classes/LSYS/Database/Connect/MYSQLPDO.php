<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\Connect;
use LSYS\Database\ConnectCharset;
use LSYS\Database\ConnectRetry;
use LSYS\Database\ConnectSchema;
use LSYS\EventManager;
use LSYS\Database\PDOException;
class MYSQLPDO extends \LSYS\Database\Connect\PDO implements ConnectCharset,ConnectRetry,ConnectSchema {
    /**
     * 默认数据库
     * @var string
     */
    protected $schema;
    protected $identifier = '`';
    /**
     * 当前使用字符编码
     * @var string
     */
    protected $charset;
    /**
     * 重试次数
     * @var integer
     */
    protected $try_num=0;
    
    public function __construct(\LSYS\Database $db,\LSYS\Config $config,array $link_config,EventManager $event_manager=null) {
        parent::__construct($db, $config, $link_config,$event_manager);
        $this->charset=$this->config->get("charset");
        $match=[];
        if(isset($link_config['dsn'])&&preg_match("/dbname\s*=\s*([^;]+).*$/", $link_config['dsn'],$match)){
            $this->schema=$match[1];
        }
    }
    protected function connectCreate(){
        while(true){
            $connect=parent::connectCreate();
            $charset=$connect->quote($this->charset);
            $sql='SET NAMES '.$charset;
            if($connect->exec($sql)===false){
                if($this->isReConnect($connect)){
                    continue;
                }else{
                    $errno=$connect->errorCode();
                    $msg=is_array($connect->errorInfo())?array_pop($connect->errorInfo()):'';
                    throw (new PDOException($msg,$errno))->setErrorSql($sql);
                }
            }
            break;
        }
        return $connect;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\ConnectSlave::setCharset()
     */
    public function setCharset(string $charset):bool{
        while(true){
            $this->connect();
            $connect=$this->connection;
            $sql='SET NAMES '.$connect->quote($charset);
            if($connect->exec($sql)===false){
                if($this->isReConnect($connect)){
                    $this->disConnect();
                    continue;
                }else{
                    $errno=$connect->errorCode();
                    $msg=is_array($connect->errorInfo())?array_pop($connect->errorInfo()):'';
                    throw (new PDOException($msg,$errno))->setErrorSql($sql);
                }
            }
            $this->charset=$charset;
            break;
        }
        return true;
    }
    public function charset(): ?string
    {
        return $this->charset;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\ConnectSchema::schema()
     */
    public function schema():?string{
        return $this->schema;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\ConnectSchema::useSchema()
     */
    public function useSchema(string $dbname): bool
    {
        while(true){
            $this->connect();
            $connect=$this->connection;
            $sql="use {$dbname}";
            if($connect->exec($sql)===false){
                if($this->isReConnect($connect)){
                    $this->disConnect();
                    continue;
                }else{
                    $errno=$connect->errorCode();
                    $msg=is_array($connect->errorInfo())?array_pop($connect->errorInfo()):'';
                    throw (new PDOException($msg,$errno))->setErrorSql($sql);
                }
            }
            $this->link_config['dsn']=preg_replace("/dbname\s*=\s*([^;]+)(.*)$/", "/dbname={$dbname};$2/", $this->link_config['dsn']??'');
            $this->schema=$dbname;
            break;
        }
        return true;
    }
    public function isReConnect($error_info):bool
    {
        if($error_info instanceof PDOException){
            $errno=$error_info->getPdoErrorCode();
            $msg=$error_info->getMessage();
        }else if($error_info instanceof \PDO){
            $errno=$error_info->errorCode();
            $msg=is_array($error_info->errorInfo())?array_pop($error_info->errorInfo()):null;
        }else return false;
        switch ($errno){
            case 'HY000':
                if(strpos($msg, '2006')||strpos($msg, '2013')){
                    $try_re_num=$this->config->get("try_re_num",0);
                    if($try_re_num==0)return false;
                    if($this->try_num<$try_re_num){
                        $this->try_num++;
                        return true;
                    }
                    $try_re_sleep=$this->config->get("try_re_sleep",0);
                    if($try_re_sleep<=0)return false;
                    sleep($try_re_sleep);
                    $this->try_num=0;
                    return true;
                }
        }
        return false;
    }
}
