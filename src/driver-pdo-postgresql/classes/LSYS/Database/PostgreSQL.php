<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database;
use \LSYS\Database\Connect\PostgrePDO as PostgrePDOConnect;
/**
 * @property PostgrePDOConnect $master_connection
 */
class PostgrePDO extends \LSYS\Database\PDO{
    use PDORWS;
    /**
     * 创建连接
     * @param array $link_config
     * @return PostgrePDOConnect
     */
    protected function connectCreate(array $link_config){
        return new PostgrePDOConnect($this,$this->config,$link_config,$this->event_manager);
    }
}
