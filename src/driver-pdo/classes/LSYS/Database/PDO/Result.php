<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */ 
namespace LSYS\Database\PDO;
class Result extends \LSYS\Database\Result{
    /**
     * @var \PDOStatement
     */
    protected $result;
    protected $total_rows=0;
    protected $init=false;
    protected $index=0;
    //not all
    protected $row;
    //all
	protected $cache_rows=array();
	/**
	 * @param mixed $result
	 * @param string $sql
	 */
	public function __construct($result)
	{
	    $this->result=$result;
	    $this->total_rows = $this->result->rowCount();
	}
	public function setFetchMode($mode,$classname=NULL, array $ctorargs=NULL){
	    parent::setFetchMode($mode,$classname,$ctorargs);
	    if ($this->as_object === TRUE)
	    {
	        $this->result->setFetchMode(\PDO::FETCH_OBJ);
	    }
	    elseif (is_object($this->as_object))
	    {
	        $this->result->setFetchMode(\PDO::FETCH_INTO,$this->as_object);
	    }
	    elseif (is_string($this->as_object))
	    {
	        //|\PDO::FETCH_PROPS_LATE
	        $this->result->setFetchMode(\PDO::FETCH_CLASS,$this->as_object,$this->object_params);
	    }
	    else
	    {
	        $this->result->setFetchMode(\PDO::FETCH_ASSOC);
	    }
	    return $this;
	}
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Database\Result::__destruct()
	 */
	public function __destruct()
	{
	    if ($this->result instanceof \PDOStatement){
            empty($this->cache_rows)&&@$this->result->fetchAll();
	        $this->result->closeCursor();
	    }
	    $this->result=null;
	    unset($this->cache_rows);
	    unset($this->row);
	}
	protected function init(){
	    if($this->init)return;
	    $this->init=true;
	    if(!$this->fetch_free){
	        $this->cache_rows=$this->result->fetchAll();
	        $this->result->closeCursor();
	        $this->total_rows=count($this->cache_rows);
	    }else{
	        $this->fetch();
	        if(is_null($this->row))$this->total_rows=0;//SQLLITE PDO 存在BUG
	    }
	}
	/**
	 * @param int $offset
	 * @return boolean
	 */
	private function fetch(){
	    $this->row=$this->result->fetch();
	    if(is_bool($this->row))$this->row=null;
	    if($this->total_rows==0&&!is_null($this->row))$this->total_rows++;
	}
	/**
	 * {@inheritDoc}
	 * @see \Iterator::current()
	 */
	public function current()
	{
	    $this->init();
	    if($this->fetch_free){
	        return $this->row;
	    }else{
	        return $this->cache_rows[$this->index]??null;
	    }
	}
    public function next()
    {
        $this->init();
        $this->index++;
        if ($this->fetch_free) {
            $this->fetch();
        }
    }
    public function valid()
    {
        $this->init();
        if ($this->fetch_free) {
            return !is_null($this->row);
        }else{
            return array_key_exists($this->index, $this->cache_rows);
        }
    }
    public function rewind()
    {
        $this->index=0;
    }
    public function key()
    {
        return $this->index;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Database\Result::count()
     */
    public function count()
    {
        $this->init();
        return $this->total_rows;
    }
    public function nextRowset()
    {
        return $this->result&&$this->result->nextRowset();
    }
}
