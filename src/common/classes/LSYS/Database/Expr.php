<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
class Expr implements \JsonSerializable{

	// Unquoted parameters
	protected $parameters;
	
	// Raw expression string
	protected $value;
	
	/**
	 * Sets the expression string.
	 *
	 *     $expression = new Database\Expr('COUNT(users.id)');
	 *
	 * @param   string  $value      raw SQL expression string
	 * @param   array   $parameters unquoted parameter values
	 * @return  void
	 */
	public function __construct($value, $parameters = array())
	{
		// Set the expression string
		$this->value = $value;
		$this->parameters = $parameters;
	}
	/**
	 * Add multiple parameter values.
	 *
	 * @param   array   $params list of parameter values
	 * @return  $this
	 */
	public function bindParam(array $params)
	{
		$this->parameters = $params + $this->parameters;
	
		return $this;
	}
	
	/**
	 * Get the expression value as a string.
	 *
	 *     $sql = $expression->value();
	 *
	 * @return  string
	 */
	public function value()
	{
		return (string) $this->value;
	}
	
	/**
	 * Return the value of the expression as a string.
	 *
	 *     echo $expression;
	 *
	 * @return  string
	 * @uses    Database_Expression::value
	 */
	public function __toString()
	{
		return $this->value();
	}
	
	/**
	 * Compile the SQL expression and return it. Replaces any parameters with
	 * their given values.
	 *
	 * @param   mixed    Database instance or name of instance
	 * @return  string
	 */
	public function compile($db=NULL)
	{
		if ( ! is_object($db))
		{
			// Get the database instance
		    $db = \LSYS\Database\DI::get()->db();
		}
	
		$value = $this->value();
	
		if ( ! empty($this->parameters))
		{
			// Quote all of the parameter values
			$params = array_map(array($db, 'quote'), $this->parameters);
	
			// Replace the values in the expression
			$value = strtr($value, $params);
		}
	
		return $value;
	}
    public function jsonSerialize()
    {
        $db = \LSYS\Database\DI::get()->db();
        $value = $this->value();
        if ( ! empty($this->parameters))
        {
            // Quote all of the parameter values
            $params = array_map(array($db, 'quote'), $this->parameters);
            
            // Replace the values in the expression
            $value = strtr($value, $params);
        }
        return $value;
    }
}