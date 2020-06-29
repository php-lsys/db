<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\Connect;
use LSYS\Database\ConnectCharset;
use LSYS\EventManager;
use LSYS\Database\PDOException;
class PostgrePDO extends \LSYS\Database\Connect\PDO implements ConnectCharset {
    protected $identifier = '';
    /**
     * 当前使用字符编码
     * @var string
     */
    protected $charset;
    public function __construct(\LSYS\Database $db,\LSYS\Config $config,array $link_config,EventManager $event_manager=null) {
        parent::__construct($db, $config, $link_config,$event_manager);
        $this->charset=$this->config->get("charset");
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\ConnectSlave::setCharset()
     */
    public function setCharset(string $charset):bool{
        $this->connect();
        $connect=$this->connection;
        $charset=$connect->quote($charset);
        $sql='set client_encoding to '.$charset;
        if($connect->exec($sql)===false){
            $errno=$connect->errorCode();
            $msg=is_array($connect->errorInfo())?array_pop($connect->errorInfo()):'';
            throw (new PDOException($msg,$errno))->setErrorSql($sql);
        }
        $this->charset=$charset;
        return true;
    }
    public function charset(): ?string
    {
        return $this->charset;
    }
}
