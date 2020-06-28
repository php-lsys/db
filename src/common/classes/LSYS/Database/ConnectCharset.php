<?php
/**
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
interface ConnectCharset{
	/**
	 * 返回连接字符编码
	 * @return string
	 */
    public function charset():?string;
    /**
	 * 设置默认字符编码
	 * @param string $charset
	 * @return bool
	 */
	public function setCharset(string $charset):bool;
}
