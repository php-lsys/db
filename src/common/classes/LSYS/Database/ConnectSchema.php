<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
interface ConnectSchema{
	/**
	 * 返回操作数据库名
	 * @return string
	 */
	public function schema():?string;
    /**
     * 修改默认操作数据库
     * @param string $dbname
     * @return bool
     */
	public function useSchema(string $dbname):bool;
}
