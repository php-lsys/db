<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */ 
namespace LSYS\Database\Connect\MYSQLi;
class Result extends \LSYS\Database\Result{
	protected $next_result;
	/**
	 * @var \mysqli_result
	 */
	protected $result;
	protected $connect;
	protected $index=0;
	public function __construct($result,$next_result=null)
	{
	    $this->result=$result;
	    $this->next_result=$next_result;
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
        $this->index++;
    }
    public function valid()
    {
        if(!$this->result)return false;
        return $this->result->data_seek($this->index);
    }
    public function nextRowset():bool
    {
        if(is_callable($this->next_result)){
            $this->result=call_user_func($this->next_result);
            if(!is_object($this->result))$this->result=null;
        }else{
            $this->result=null;
        }
        return !is_null($this->result);
    }
    public function rewind()
    {
        $this->index=0;
        return true;
    }
    public function count():int
    {
        return $this->result?$this->result->num_rows:0;
    }
    public function key()
    {
        return $this->index;
    }
    public function __destruct()
    {
        if (is_resource($this->result))
        {
            $this->result->free();
        }
    }
}