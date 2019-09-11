<?php
/**
 * lsys service
 * 示例配置 未引入
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
return array(
	'default'=>array(
		'instant_death'      => TRUE,               // Take server offline immediately on first fail (no retry)
		'servers'            => array(
				array(
						'host'             => '127.0.0.1',
						'port'             => 11211,
						'persistent'       => FALSE,
						'weight'           => 1,
						'timeout'          => 1,
						'retry_interval'   => 15,
						'status'           => TRUE,
				)
		),
	)
);