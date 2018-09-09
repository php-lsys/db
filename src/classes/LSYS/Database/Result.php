<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */ 
namespace LSYS\Database;
abstract class Result implements \Countable, \Iterator, \SeekableIterator, \ArrayAccess {
	// Executed SQL for this result
	protected $_query;
	// Raw result resource
	protected $_result;

	// Total number of rows and current row
	protected $_total_rows  = 0;
	protected $_current_row = 0;

	// Return rows as an object or associative array
	protected $_as_object;

	// Parameters for __construct when using object results
	protected $_object_params = NULL;

	/**
	 * Sets the total number of rows and stores the result locally.
	 *
	 * @param   mixed   $result     query result
	 * @param   string  $sql        SQL query
	 * @param   mixed   $as_object
	 * @param   array   $params
	 * @return  void
	 */
	public function __construct($result, $sql)
	{
		// Store the result locally
		$this->_result = $result;

		// Store the SQL locally
		$this->_query = $sql;

	}
	/**
	 * set fetch data mode
	 * @param int $mode
	 * @param string $classname
	 * @param array $ctorargs
	 */
	public function set_fetch_mode ( $mode,$classname=NULL, array $ctorargs=NULL){
		switch ($mode){
			case \LSYS\Database::FETCH_ASSOC:
				$this->_as_object=false;
			break;
			case \LSYS\Database::FETCH_CLASS:
				if (is_object($classname))
				{
					$classname = get_class($classname);
				}
				$this->_as_object = $classname;
				$this->_object_params = $ctorargs;
			break;
			case \LSYS\Database::FETCH_INTO:
				if (!is_object($classname))
				{
					throw new Exception("classname param is obj,but you give not.");
				}
				$this->_as_object = $classname;
			break;
			case \LSYS\Database::FETCH_OBJ:
				$this->_as_object = true;
			break;
		}
		return $this;
	}
	/**
	 * return query sql
	 * @return string
	 */
	public function __toString(){
	    return $this->_query;
	}
	/**
	 * Result destruction cleans up all open result sets.
	 *
	 * @return  void
	 */
	abstract public function __destruct();
	/**
	 * Return all of the rows in the result as an array.
	 *
	 *     // Indexed array of all rows
	 *     $rows = $result->as_array();
	 *
	 *     // Associative array of rows by "id"
	 *     $rows = $result->as_array('id');
	 *
	 *     // Associative array of rows, "id" => "name"
	 *     $rows = $result->as_array('id', 'name');
	 *
	 * @param   string  $key    column for associative keys
	 * @param   string  $value  column for values
	 * @return  array
	 */
	public function as_array($key = NULL, $value = NULL)
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

			if ($this->_as_object)
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

			if ($this->_as_object)
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

			if ($this->_as_object)
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

		if ($this->_as_object)
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
	 * Implements [Countable::count], returns the total number of rows.
	 *
	 *     echo count($result);
	 *
	 * @return  integer
	 */
	public function count()
	{
		return $this->_total_rows;
	}

	/**
	 * Implements [ArrayAccess::offsetExists], determines if row exists.
	 *
	 *     if (isset($result[10]))
	 *     {
	 *         // Row 10 exists
	 *     }
	 *
	 * @param   int     $offset
	 * @return  boolean
	 */
	public function offsetExists($offset)
	{
		return ($offset >= 0 AND $offset < $this->_total_rows);
	}

	/**
	 * Implements [ArrayAccess::offsetGet], gets a given row.
	 *
	 *     $row = $result[10];
	 *
	 * @param   int     $offset
	 * @return  mixed
	 */
	public function offsetGet($offset)
	{
		if ( ! $this->seek($offset))
			return NULL;

		return $this->current();
	}

	/**
	 * Implements [ArrayAccess::offsetSet], throws an error.
	 *
	 * [!!] You cannot modify a database result.
	 *
	 * @param   int     $offset
	 * @param   mixed   $value
	 * @return  void
	 * @throws  Exception
	 */
	final public function offsetSet($offset, $value)
	{
		throw new Exception('Database results are read-only');
	}

	/**
	 * Implements [ArrayAccess::offsetUnset], throws an error.
	 *
	 * [!!] You cannot modify a database result.
	 *
	 * @param   int     $offset
	 * @return  void
	 * @throws  Exception
	 */
	final public function offsetUnset($offset)
	{
		throw new Exception('Database results are read-only');
	}

	/**
	 * Implements [Iterator::key], returns the current row number.
	 *
	 *     echo key($result);
	 *
	 * @return  integer
	 */
	public function key()
	{
		return $this->_current_row;
	}

	/**
	 * Implements [Iterator::next], moves to the next row.
	 *
	 *     next($result);
	 *
	 * @return  $this
	 */
	public function next()
	{
		++$this->_current_row;
		return $this;
	}

	/**
	 * Implements [Iterator::prev], moves to the previous row.
	 *
	 *     prev($result);
	 *
	 * @return  $this
	 */
	public function prev()
	{
		--$this->_current_row;
		return $this;
	}

	/**
	 * Implements [Iterator::rewind], sets the current row to zero.
	 *
	 *     rewind($result);
	 *
	 * @return  $this
	 */
	public function rewind()
	{
		$this->_current_row = 0;
		return $this;
	}

	/**
	 * Implements [Iterator::valid], checks if the current row exists.
	 *
	 * [!!] This method is only used internally.
	 *
	 * @return  boolean
	 */
	public function valid()
	{
		return $this->offsetExists($this->_current_row);
	}

}
