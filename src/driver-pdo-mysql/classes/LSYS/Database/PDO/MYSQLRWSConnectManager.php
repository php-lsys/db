<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\PDO;
use LSYS\Database\ConnectRetry;
class MYSQLRWSConnectManager extends RWSConnectManager implements ConnectRetry{
	protected $_try_num=0;
	public function isReconnect($connect,$error_info){
	    $errno=$error_info->errorCode();
	    $msg=$error_info->errorInfo();
	    switch ($errno){
	        case 'HY000':
	            if(strpos($msg, '2006')||strpos($msg, '2013')){
	                $try_re_num=$this->config->get("try_re_num",0);
	                if($try_re_num==0)return false;
	                if($this->try_num<$try_re_num){
	                    $this->try_num++;
	                    return true;
	                }
	                $try_re_sleep=$this->config->get("try_re_sleep",0);
	                if($try_re_sleep<=0)return false;
	                sleep($try_re_sleep);
	                $this->try_num=0;
	                return true;
	            }
	    }
	    return false;
	}
	protected function connectCreate(array $link_config){
	    $connect=parent::connectCreate($link_config);
	    $variables=$link_config['variables']??[];
        $_variables = array ();
        foreach ( $variables as $var => $val ) {
            $_variables [] = 'SESSION ' . $var . ' = ' . $this->quote ( $val );
        }
        $connect->exec('SET ' . implode ( ', ', $_variables ));
	    return $connect;
	}
}
