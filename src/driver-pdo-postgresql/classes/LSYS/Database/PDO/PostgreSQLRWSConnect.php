<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\PDO;
class PostgreSQLRWSConnectManager extends RWSConnectManager {
    protected function getSchema(array $link_config){
        return $link_config['schema']??null;
    }
    public function setCharset($connection,$charset){
        $charset=$connection->quote($charset);
        $connection->exec('set client_encoding to '.$charset);
    }
}
