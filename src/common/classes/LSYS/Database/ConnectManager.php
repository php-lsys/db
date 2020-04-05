<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\Config;
abstract class ConnectManager {
    /**
     * set MQL query to auto select database
     * @var integer
     */
    const QUERY_AUTO=0;
    /**
     * set MQL query to master database
     * @var integer
     */
    const QUERY_MASTER=1;
    /**
     * set MQL query to slave database [CONNECT_MASTER_SUGGEST]
     * @var integer
     */
    const QUERY_SLAVE=2;
    
    /**
     * 返回一个可用连接,不管主从
     * @var integer
     */
    const CONNECT_AUTO=0;
    /**
     * 建议选择主库连接
     * @var integer
     */
    const CONNECT_MASTER_SUGGEST=1;
    /**
     * 必须选择主库连接
     * @var integer
     */
    const CONNECT_MASTER_MUST=1;
    /**
     * 如果有从库,得到从库连接
     * @var integer
     */
    const CONNECT_SLAVE=2;
	/**
	 * @var Config
	 */
	protected $config;
	/**
	 * query model
	 * @var int
	 */
	protected $query_mode=self::QUERY_AUTO;
	/**
	 * @param Config $config
	 * @param 
	 * @throws Exception
	 * @return void|boolean
	 */
	public function __construct(Config $config){
		$this->config=$config;
	}
	/**
	 * set query model
	 * @param int
	 */
	public function setQuery($query_mode){
	    $this->query_mode=$query_mode;
	    return $this;
	}
	/**
	 * 根据权重取配置
	 * @param array $config_array
	 * @return number[]
	 */
	protected function weightGetConfig(array $config_array){
	    if(empty($config_array))return $config_array;
	    $weight=0;
	    $wa=array();
	    $config_array=array_values($config_array);
	    foreach ($config_array as $k=>$item){
	        if(!is_array($item))continue;
	        $wa[$k]=intval(isset($item['weight'])?$item['weight']:1);
	        $wa[$k]=$wa[$k]<=0?1:$wa[$k];
	        $weight+=$wa[$k];
	    }
	    if($weight===0)return NULL;
	    $_k=0;
	    $_c=0;
	    $r=rand(1,$weight);
	    foreach ($wa as $k=>$v){
	        $_c+=$v;
	        if ($_c>=$r){
	            $_k=$k;break;
	        }
	    }
	    return $config_array[$_k];
	}
	/**
	 * open connect link
	 * $allow_slave is null return connect
	 * $allow_slave is true return salve connect
	 * $allow_slave is false return master connect
	 * @param int $allow_slave 
	 */
	abstract public function getConnect($connect_type=self::CONNECT_AUTO);
	/**
	 * @param bool $master
	 * @return bool
	 */
	abstract public function isConnected($connect_type=self::CONNECT_AUTO);
	/**
	 * dis connect
	 */
	abstract public function disConnect($connect=null);
	/**
	 * get connect database name
	 * @param mixed $connect
	 */
	abstract public function schema($connect);
    /**
     * check connect is master
     * @param mixed $connect
     */
	abstract public function isMaster($connect);
	/**
	 * Set the connection character set.
	 * This is called automatically by [Database::connect].
	 *
	 * @throws \LSYS\Database\Exception
	 * @param string $charset
	 *        	character set name
	 * @return void
	 */
	abstract public function setCharset($connect,$charset);
}
