<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
class PDOException extends \LSYS\Database\Exception{
    /**
     * @var string
     */
    private $pdo_errcode;
	/**
	 * Creates a new translated exception.
	 * @param   string          $message    error message
	 * @param   string  $code       the exception code
	 * @param   \Exception       $previous   Previous exception
	 * @return  void
	 */
	public function __construct(string $message, string $code = null, \Exception $previous = NULL)
	{
		$this->pdo_errcode=$code;
		parent::__construct($message,null,$previous);
	}
	/**
	 * get pdo error code
	 * @return string
	 */
	public function getPdoErrorCode():string{
	    return $this->pdo_errcode;
	}
}