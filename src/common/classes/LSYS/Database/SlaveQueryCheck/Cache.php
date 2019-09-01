<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\SlaveQueryCheck;
interface Cache{
    /**
     * @param array $table
     * @return bool
     */
    public function time(array $table);
    public function save(array $table);
    public function delayed();
}