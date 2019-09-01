<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
interface ConnectRetry{
	/**
	 * return is retry connect
	 * @param mixed $connect connect object
	 * @param mixed $error_object error object
	 * @return bool
	 */
    public function isReConnect($connect,$error_info);
}