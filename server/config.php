<?php

namespace server;

/**
 * 
 * @package server.config
 * @author mrline <714480119@qq.com>
 */
class Config
{
	/**
	 * 
	 * get cofig
	 * 
	 * @return array
	 * 
	 */
	public static function getConfig()
	{
		return array(
			'addr'            => '0.0.0.0',
			'port'            => 9512,
			'worker_num'      => 4,
			'daemonize'       => false,
			'task_worker_num' => 4,
		);
	}
}