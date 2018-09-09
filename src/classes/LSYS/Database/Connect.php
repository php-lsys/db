<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\Config;

abstract class Connect {
	/**
	 * read database connent
	 * @var integer
	 */
	const READ_MODEL=0;
	/**
	 * master database connent
	 * @var integer
	 */
	const MASTER_MODEL=1;
	
	/**
	 * @var Config
	 */
	protected $_config;
	/**
	 * @param Config $config
	 * @param 
	 * @throws Exception
	 * @return void|boolean
	 */
	public function __construct(Config $config){
		$this->_config=$config;
	}
	/**
	 * get connect link
	 * @param int $model
	 */
	abstract public function get_connect($allow_read);
	abstract public function set_charset($charset);
	/**
	 * disconnect link
	 */
	abstract public function disconnect();
	/**
	 * 根据权重取配置
	 * @param array $config_array
	 * @return number[]
	 */
	protected function _array_weight_get(array $config_array){
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
		$_w=0;
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
	 * dump object
	 */
	public function __debugInfo(){
		$out=get_object_vars($this);
		$name=$this->_config->name();
		if (isset($out['_config']))$out['_config']="Config[{$name}] object is hidden";
		return $out;
	}
}
