<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
interface ReConnect{
	/**
	 * return is retry connect
	 * @param mixed $error_object error object
	 */
	public function is_reconnect($error_object);
}