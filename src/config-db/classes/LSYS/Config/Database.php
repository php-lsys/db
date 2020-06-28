<?php
/**
 * lsys config storage to database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Config;
use LSYS\Config;
class Database implements Config{
	protected $_name;
	protected $_load=false;
	/**
	 * @var \LSYS\Database
	 */
	protected $_db;
	protected $_table;
	protected $_node=array();
	/**
	 * php file config
	 * @param string $name
	 */
	public function __construct (string $name,DatabaseDepend $depend=null){
	    $depend=$depend?$depend:DatabaseDepend::get();
	    $db=$depend->databaseConfigDb();
	    $table=$depend->databaseConfigTable();
		$name=trim($name);
		$this->_name=$name;
		$this->_db=$db;
		$db=$db->getSlaveConnect();
		$this->_table=$table;
		// id name value
		$len=strlen($name);
		$name=$db->quote($name.'%');
		$table=$db->quoteTable($table);
		$sql="select name,value from {$table} where name like {$name}";
		$row=$db->query($sql);
		$this->_load=count($row)>0;
		foreach ($row as $v){
			$_name=substr($v['name'],$len+1);
			if (empty($_name))continue;
			$note=&$this->_node;
			foreach (explode(".",$_name) as $vv){
				if (!isset($note[$vv])||!is_array($note[$vv]))$note[$vv]=[];
				$note=&$note[$vv];
			}
			$note=$v['value'];
		}
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::loaded()
	 */
	public function loaded():bool{
		return $this->_load;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::name()
	 */
	public function name():string{
		return $this->_name;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::get()
	 */
	public function get(string $key,$default=NULL){
		$group= explode('.', $key);
		$t=$this->_node;
		while (count($group)){
			$node=array_shift($group);
			if(isset($t[$node])){
				$t=&$t[$node];
			}else return $default;
		}
		return $t;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::get()
	 */
	public function exist($key):bool{
		$group= explode('.', $key);
		$t=$this->_node;
		while (count($group)){
			$node=array_shift($group);
			if(isset($t[$node])){
				$t=&$t[$node];
			}else return false;
		}
		return true;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::asArray()
	 */
	public function asArray():array{
	    return is_array($this->_node)?$this->_node:[];
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::set()
	 */
	public function set (string $key,$value = NULL):bool{
		$keys=explode(".",$key);
		$config=&$this->_node;
		foreach ($keys as $v){
			if(!isset($config[$v]))$config[$v]=array();
			$config=&$config[$v];
		}
		if ($config!=$value){
			$config=$value;
		}
		$this->_save($key, $value);
		$this->_load=true;
		return true;
	}
	protected function _save(string $key,$value){
	    $db=$this->_db->getMasterConnect();
		$table=$db->quoteTable($this->_table);
		if ($value===null){
			$_key=$db->quote($this->_name.".".$key.'%');
			$sql="DELETE FROM {$table} WHERE name like {$_key}";
			$db->exec($sql);
		}else{
			if (is_bool($value))$value=$value?1:0;
			if (is_numeric($value))$value.='';
			if (is_string($value)){
				$section=count(explode(".", $this->_name.".".$key));
				$_key=$db->quote($this->_name.".".$key);
				$sql="select id from {$table} where name={$_key}";
				$row=$db->query($sql);
				if($row&&count($row)>0){
					$value=$db->quote($value);
					$sql="UPDATE {$table} SET value={$value},section={$section} WHERE name={$_key}";
					$db->exec($sql);
				}else{
					$value=$db->quote($value);
					$sql="INSERT INTO {$table} (name, value,section) VALUES({$_key},{$value},{$section})";
					$db->exec($sql);
				}
			}else if (is_array($value)){
				foreach ($value as $k=>$v){
					$this->_save($key.".".$k, $v);
				}
			}
		}
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Config::readonly()
	 */
	public function readonly ():bool{
		return false;
	}
}
