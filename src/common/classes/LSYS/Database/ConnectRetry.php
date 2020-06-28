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
	 * 返回是否可重新连接
	 * @param mixed $error_object error object
	 * @return bool
	 */
    public function isReConnect($error_info):bool;
}