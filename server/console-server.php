<?php

namespace server;

/**
 *
 * @package server.server
 * @author mrlin <714480119@qq.com>
 */

class Server {
	protected $serv = null;
	protected $addr = '0.0.0.0';
	protected $port = 9512;
	protected $connections = array();
	protected $config = array(
		'worker_num' => 4,
		'daemonize' => false,
		'task_worker_num' => 4
	);

	public function __construct(array $config = array()) {
		if (count($config) > 0) {
			$this->initialize($config);
		}

		$this->serv = new \Swoole\Server($this->addr, $this->port);
		$this->serv->set($this->config);

		$this->serv->on('connect', array($this, 'onConnect'));
		$this->serv->on('receive', array($this, 'onReceive'));
		$this->serv->on('close', array($this, 'onClose'));
		$this->serv->on('task', array($this, 'onTask'));
		$this->serv->on('finish', array($this, 'onFinish'));
		$this->serv->start();
	}

	public static function run() {
		$serv = new self();
	}

	public function initialize(array $config = array()) {
		foreach ($config as $key => $value) {
			if (isset($this->$key)) {
				$this->$key = $value;
			} else {
				$this->config[$key] = $value;
			}
		}
	}

	public function onConnect($serv, $fd) {
		$this->serv->task(json_encode(array(
			'task' => 'connect',
			'fd' => $fd,
		)));
		echo "client $fd connected\n";
	}

	public function onReceive($serv, $fd, $fromId, $data) {
		$data = json_decode($data, true);
		$type = 'message';

		switch (ord($data['chat'])) {
			case 'u':
				$data = Chat::msgpack($fd, 'u', $data);
				break;
			case 'a':
				$data = Chat::msgpack($fd, 'a', $data);
				break;
			default:
				break;
		}

		$this->serv->task(json_encode(array(
			'task'  => $type,
			'data'  => $data,
			'param' => '',
		)));
		echo "receive data $data\n";
	}

	public function onClose($serv, $fd) {
		$this->serv->task();
		echo "client $fd disconnected\n";
	}

	public function onFinish($serv, $taskId, $data) {
		echo 'task ' .$taskId. ' finish\n';
	}

	public function onTask($serv, $taskId, $fromId, $data) {
		$data = json_decode($data, true);
		switch ($data['task']) {
			case 'login':
				Chat::doLogin($serv, $data['package']);
				break;
			case 'message':
				Chat::send($serv, $data['package']);
				break;
			default:
				break;
		}
	}
}
