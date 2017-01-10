<?php
namespace server;

/**
 *
 * @package server.baseserver
 * @author mrlin <714480119@qq.com>
 */
class Baseserver
{
	/**
	 * The server
	 * 
	 * @var object
	 */
	protected $serv = null;

	/**
	 * 
	 * ip address to bind ?
	 * 
	 * @var string
	 */
	protected $addr = '0.0.0.0';

	/**
	 * 
	 * port
	 * 
	 * @var integer
	 */
	protected $port = 9513;

	/**
	 * 
	 * server properties
	 * 
	 * @var array
	 */
	protected $config = array('worker_num' => 4, 'daemonize' => false, 'task_worker_num' => 4);

	/**
	 * 
	 * @__construct
	 */
	public function __construct()
	{

	}
}