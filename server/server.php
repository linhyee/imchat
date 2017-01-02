<?php

namespace server;

/**
 *
 * @package server.server
 * @author mrlin <714480119@qq.com>
 */

class Server
{
	/**
	 * 
	 * server
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
	protected $port = 9512;

	/**
	 * 
	 * server properties
	 * 
	 * @var array
	 */
	protected $config = array('worker_num' => 4, 'daemonize' => false, 'task_worker_num' => 4);

// ------------------------------------------------------------------------
	/**
	 * 
	 * @__construct
	 * 
	 * @param array $config
	 * 
	 */
	public function __construct(array $config = array())
	{
		if (count($config) > 0)
		{
			$this->initialize($config);
		}

		$this->serv = new \Swoole\Server($this->addr, $port);
		$this->serv->set($this->config);

		$this->serv->on('connect', array($this, 'onConnect'));
		$this->serv->on('receive', array($this, 'onReceive'));
		$this->serv->on('close', array($this, 'onClose'));
		$this->serv->on('task', array($this, 'onTask'));
		$this->serv->on('finish', array($this, 'onFinish'));

		// start a server
		$this->serv->start();
	}

// ------------------------------------------------------------------------
	/**
	 * 
	 * start server
	 * 
	 * @noreturn
	 * 
	 */
	public static function start()
	{
		$serv = new self(Config::getConfig());
	}

// ------------------------------------------------------------------------
	/**
	 * 
	 * initialize serv properties
	 * 
	 * @param  array  $config
	 * 
	 * @noreturn
	 * 
	 */
	public function initialize(array $config = array())
	{
		foreach ($config as $key => $value)
		{
			if (isset($this->$key))
			{
				$this->$key = $value;
			}
			else
			{
				$this->config[$key] = $value;
			}
		}
	}

// ------------------------------------------------------------------------
	/**
	 * 
	 * connect callback
	 * 
	 * @param  object $serv
	 * 
	 * @param  int $fd
	 * 
	 * @noreturn
	 * 
	 */
	public function onConnect($serv, $fd)
	{
		$this->serv->task(json_encode(array(
			'task' => 'connect',
			'fd' => $fd,
		)));

		echo "client $fd connected\n";
	}

// ------------------------------------------------------------------------
	/**
	 *
	 * 
	 * receive callback
	 * 
	 * @param  object $serv
	 * 
	 * @param  int $fd
	 * 
	 * @param  int $fromId
	 * 
	 * @param  string $data
	 * 
	 * @noreturn
	 * 
	 */
	public function onReceive($serv, $fd, $fromId, $data)
	{
		$data = json_decode($data, true);
		switch (ord($data['chat'])) {
			case 'u':
				# code...
				break;
			case 'a':
				break;
			default:
				# code...
				break;
		}
	}

// ------------------------------------------------------------------------
	/**
	 *
	 * 
	 * close callback
	 * 
	 * @param  object $serv
	 * 
	 * @param  int $fd
	 * 
	 * @noreturn
	 */
	public function onClose($serv, $fd)
	{

	}

// ------------------------------------------------------------------------
	/**
	 * 
	 * finish callback
	 * 
	 * @param  object $serv
	 * 
	 * @param  int $taskId
	 * 
	 * @param  string $data
	 * 
	 * @noreturn
	 */
	public function onFinish($serv, $taskId, $data)
	{

	}

// ------------------------------------------------------------------------
	/**
	 *
	 * 
	 * task callback
	 * 
	 * @param  object $serv
	 * 
	 * @param  int $taskId
	 * 
	 * @param  int $fromId
	 * 
	 * @param  string $data
	 * 
	 * @noreturn
	 */
	public function onTask($serv, $taskId, $fromId, $data)
	{

	}
}
