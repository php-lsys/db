<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use LSYS\Core;

class Exception extends \LSYS\Exception{
	/**
	 * Creates a new translated exception.
	 *
	 *     throw new Exception('Something went terrible wrong');
	 *
	 * @param   string          $message    error message
	 * @param   integer|string  $code       the exception code
	 * @param   Exception       $previous   Previous exception
	 * @return  void
	 */
	public function __construct($message = "", $code = 0, \Exception $previous = NULL)
	{
	    if (DIRECTORY_SEPARATOR === '\\'&&$this->_is_gb2312($message)){
	        if(PHP_SAPI!=='cli'||PHP_SAPI==='cli'&&version_compare(PHP_VERSION,'7.0.0',">=")){
				$message=iconv("gb2312", "utf-8",$message);//windows in china : cover string
			}
		}
		parent::__construct($message,$code,$previous);
	}
	private function _is_gb2312($str)
	{
		for($i=0; $i<strlen($str); $i++) {
			$v = ord( $str[$i] );
			if( $v > 127) {
				if( ($v >= 228) && ($v <= 233) )
				{
					if(($i+2) >= (strlen($str)- 1)) return true;  // not enough characters
					$v1 = ord( $str[$i+1] );
					$v2 = ord( $str[$i+2] );
					if( ($v1 >= 128) && ($v1 <=191) && ($v2 >=128) && ($v2 <= 191) ) // utf编码
						return false;
						else
							return true;
				}
			}
		}
		return true;
	}
	/**
	 * @var string
	 */
	private $_error_sql;
	/**
	 * set error sql
	 * @param string $sql
	 * @return \LSYS\Database\Exception
	 */
	public function set_error_sql($sql){
		if (Core::$environment!==Core::PRODUCT){
			$this->message.=" [{$sql}]";
		}
		$this->_error_sql=$sql;
		return $this;
	}
	/**
	 * get error sql
	 * @return string
	 */
	public function get_error_sql(){
		return $this->_error_sql;
	}
}