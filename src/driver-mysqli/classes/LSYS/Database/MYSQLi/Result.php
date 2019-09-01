<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */ 
namespace LSYS\Database\MYSQLi;
class Result extends \LSYS\Database\Result{
	protected $next_result;
	protected $result;
	public function __construct($result,$next_result=null)
	{
	    $this->next_result=$next_result;
	    $this->result=$result;
	}
	public function current()
	{
	    if(!$this->result)return null;
		if ($this->as_object === TRUE)
		{
			return $this->result->fetch_object();
		}
		elseif (is_string($this->as_object))
		{
			return $this->result->fetch_object($this->as_object, (array) $this->object_params);
		}
		elseif (is_object($this->as_object))
		{
			$res=$this->result->fetch_assoc();
			if ($res===null) return $res;
			foreach ($res as $k=>$v){
				$this->as_object->{$k}=$v;
			}
			return $this->as_object;
		}
		else
		{
			return $this->result->fetch_assoc();
		}
	}
    public function next()
    {
        return $this->result&&$this->result->next();
    }
    public function valid()
    {
        return $this->result&&$this->result->valid();
    }
    public function nextRowset()
    {
        if(is_callable($this->next_result)){
            $this->result=call_user_func($this->next_result);
            if(!is_object($this->result))$this->result=null;
        }else{
            $this->result=null;
        }
    }
    public function rewind()
    {
        return $this->result&&$this->result->rewind();
    }
    public function count()
    {
        return $this->result&&$this->result->num_rows;
    }
    public function key()
    {
        return $this->result&&$this->result->key();
    }
    public function __destruct()
    {
        if (is_resource($this->result))
        {
            $this->result->free();
        }
    }
}