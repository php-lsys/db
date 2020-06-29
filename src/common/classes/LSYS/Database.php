<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS;
use LSYS\Database\ConnectMaster;
use LSYS\Database\ConnectSlave;
use function LSYS\Database\__ as __;
abstract class Database{
	/**
	 * 创建一个SQL表达式
	 * @param string $value
	 * @return \LSYS\Database\Expr
	 */
	public static function expr(string $value, array $parameters = array()) {
	    return new Database\Expr ( $value,$parameters );
	}
	/**
	 * @var Config
	 */
	protected $config;
	/**
	 * @var \LSYS\EventManager
	 */
	protected $event_manager;
	/**
	 * 得到一个数据库管理对象
	 * @param \LSYS\Config $config
	 * @throws \LSYS\Database\Exception
	 * @return static
	 */
	public static function factory(\LSYS\Config $config){
	    $name=$config->name();
	    $driver=$config->get("type",NULL);
	    if (!$driver||!class_exists($driver,true)||!in_array(Database::class,class_parents($driver))){
	        throw new \LSYS\Database\Exception( __('Database type not defined in [:name on :driver] configuration',array(":name"=>$name,":driver"=>$driver)));
	    }
	    return new $driver ($config);
	}
	/**
	 * 数据库对象
	 * @param Config $config
	 * @throws Exception
	 * @return void|boolean
	 */
	public function __construct(Config $config){
	    $this->config=$config;
	}
	/**
	 *  返回公共表前缀
	 * @return string
	 */
	public function tablePrefix():string{
	    return (string)$this->config->get('table_prefix','');
	}
	/**
	 * 设置事件管理器
	 * @param \LSYS\EventManager $event_manager
	 * @return static
	 */
	public function setEventManager(\LSYS\EventManager $event_manager){
	    $this->event_manager=$event_manager;
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
	 * 是否存在连接
	 * @return bool
	 */
	public function isConnected():bool{
	    return $this->isMasterConnected() or $this->isSlaveConnected();
	}
	/**
	 * 返回一个连接
	 * 不管主从,未连接时调用getSlaveConnect创建连接并返回
	 * @return ConnectSlave|ConnectMaster
	 */
	abstract public function getConnect();
	/**
	 * 返回一个主库连接
	 * @return ConnectMaster
	 */
	abstract public function getMasterConnect():ConnectMaster;
	/**
	 * 返回一个从库连接,当从库不可用时返回主库连接
	 * @return ConnectSlave
	 */
	abstract public function getSlaveConnect():ConnectSlave;
	/**
	 * 是否已连接主库
	 * @return bool
	 */
	abstract public function isMasterConnected():bool;
	/**
	 * 是否已连接从库
	 * @return bool
	 */
	abstract public function isSlaveConnected():bool;
	/**
	 * 关闭所有连接
	 * @param mixed $connect
	 */
	abstract public function disConnect():bool;
}