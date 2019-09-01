<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */ 
namespace LSYS\Database;
abstract class Result implements \Iterator,\Countable{
    //fetch row is array
    const FETCH_ASSOC=1;
    // fetch row is class object
    const FETCH_CLASS=2;
    // fetch row to object
    const FETCH_INTO=3;
    // fetch row is object
    const FETCH_OBJ=4;
	// is once fetch data
	protected $fetch_free=false;
	// Return rows as an object or associative array
	protected $as_object;
	// Parameters for __construct when using object results
	protected $object_params = NULL;
	/**
	 * once fetch data
	 * @return static
	 */
	public function setFetchFree(){
	    $this->fetch_free=true;
	    return $this;
	}
	/**
	 * set fetch data mode
	 * @param int $mode
	 * @param string $classname
	 * @param array $ctorargs
	 */
	public function setFetchMode( $mode,$classname=NULL, array $ctorargs=NULL){
		switch ($mode){
			case self::FETCH_ASSOC:
				$this->as_object=false;
			break;
			case self::FETCH_CLASS:
				if (is_object($classname))
				{
					$classname = get_class($classname);
				}
				$this->as_object = $classname;
				$this->object_params = $ctorargs;
			break;
			case self::FETCH_INTO:
				if (!is_object($classname))
				{
					throw new Exception("classname param is obj,but you give not.");
				}
				$this->as_object = $classname;
			break;
			case self::FETCH_OBJ:
				$this->as_object = true;
			break;
		}
		return $this;
	}
	/**
	 * Return all of the rows in the result as an array.
	 *
	 *     // Indexed array of all rows
	 *     $rows = $result->asArray();
	 *
	 *     // Associative array of rows by "id"
	 *     $rows = $result->asArray('id');
	 *
	 *     // Associative array of rows, "id" => "name"
	 *     $rows = $result->asArray('id', 'name');
	 *
	 * @param   string  $key    column for associative keys
	 * @param   string  $value  column for values
	 * @return  array
	 */
	public function asArray($key = NULL, $value = NULL)
	{
		$results = array();

		if ($key === NULL AND $value === NULL)
		{
			// Indexed rows

			foreach ($this as $row)
			{
				$results[] = $row;
			}
		}
		elseif ($key === NULL)
		{
			// Indexed columns

			if ($this->as_object)
			{
				foreach ($this as $row)
				{
					$results[] = $row->$value;
				}
			}
			else
			{
				foreach ($this as $row)
				{
					$results[] = $row[$value];
				}
			}
		}
		elseif ($value === NULL)
		{
			// Associative rows

			if ($this->as_object)
			{
				foreach ($this as $row)
				{
					$results[$row->$key] = $row;
				}
			}
			else
			{
				foreach ($this as $row)
				{
					$results[$row[$key]] = $row;
				}
			}
		}
		else
		{
			// Associative columns

			if ($this->as_object)
			{
				foreach ($this as $row)
				{
					$results[$row->$key] = $row->$value;
				}
			}
			else
			{
				foreach ($this as $row)
				{
					$results[$row[$key]] = $row[$value];
				}
			}
		}

		$this->rewind();

		return $results;
	}

	/**
	 * Return the named column from the current row.
	 *
	 *     // Get the "id" value
	 *     $id = $result->get('id');
	 *
	 * @param   string  $name     column to get
	 * @param   mixed   $default  default value if the column does not exist
	 * @return  mixed
	 */
	public function get($name, $default = NULL)
	{
		$row = $this->current();

		if ($this->as_object)
		{
			if (isset($row->$name))
				return $row->$name;
		}
		else
		{
			if (isset($row[$name]))
				return $row[$name];
		}

		return $default;
	}
	/**
	 * next query result
	 */
	abstract public function nextRowset();
	/**
	 * fetch num
	 */
	abstract public function count ();
}
