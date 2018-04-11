<?php

namespace server;

/**
 *
 * @package server.server
 * @author mrlin <714480119@qq.com>
 */

class Server {
  private $serv;
  private $addr = '0.0.0.0';
  private $port = 8888;
  private $conns = array();
  
  private $config = array(
    'worker_num' => 4,
    'daemonize' => false,
    'task_worker_num' => 4
  );

  function __construct(array $config = array()) {
    if (count($config) > 0) {
      $this->init($config);
    }

    $this->serv = new \Swoole\Server($this->addr, $this->port);
    $this->serv->set($this->config);

    $this->serv->on('connect', array($this, 'connect'));
    $this->serv->on('receive', array($this, 'receive'));
    $this->serv->on('close', array($this, 'close'));
    $this->serv->on('task', array($this, 'task'));
    $this->serv->on('finish', array($this, 'finish'));
  }

  function run() {
    $this->serv->start();
  }

  function init(array $config) {
    foreach ($config as $key => $value) {
      if (isset($this->$key)) {
        $this->$key = $value;
      } else {
        $this->config[$key] = $value;
      }
    }
  }

  function connect($serv, $fd) {
    $conn = new conn($fd);
    $this->conns[$fd] = $conn;
    log_msg("client $fd connected");
  }

  function receive($serv, $fd, $fromId, $data) {
    $data = json_decode($data, true);
    switch ($data['type']) {
      case 'login': // login action
        $task = array(
          'task' => 'login',
          'username' => $data['username'],
          'fd' => $fd,
        );
        break;
      case 'msg': // new message
        $task = array(
          'task' => 'msg',
          'msg' => $data['msg'],
          'from' => $data['from'],
          'to' => isset($data['to']) ? $data['to'] : '',
          'fd' => $fd,
        );
        break;
    }
    $this->serv->task(json_encode($task));
  }

  function close($serv, $fd) {
    $data = array(
      'task' => 'quit',
      'fd' => $fd,
    );
    $this->serv->task(json_encode($data));
    log_msg("client $fd disconnected!");
  }

  function finish($serv, $taskId, $data) {
    log_msg("task $taskId finished!!1");
  }

  function task($serv, $taskId, $fromId, $data) {
    $data = json_decode($data, true);
    switch ($data['task']) {
      case 'login':
        $this->dologin($data);
        break;
      case 'msg':
        $this->domsg($data);
        break;
      case 'quit':
        $this->doquit($data);
        break;
    }
  }

  function dologin($data) {
    if (empty($data['username'])) {
      $this->sendmsg(array(
        'type' => 'login',
        'msg' => 'username empty!',
        'islogin' => false,
      ));
      $this->serv->close($fd);
      return;
    }
    $already_taken = fasle;
    foreach ($this->conns as $key => $val) {
      if ($val->username == $data['username']) {
        $already_taken = true;
        break;
      }
    }
  }

  function doquit($data) {
    $data = json_decode($data, true);
    $this->conns[$data['fd']]->islogin = false;
    unset($this->conns)
  }
}

class conn {
  public $id;
  public $fd;
  public $username;
  public $islogin = fasle;

  function __construct($fd) {
    $this->id = uniqid('u');
    $this->fd = $fd;
  }
}

function log_msg() {
  $args = func_get_args();

  if (count($args) > 0) {
    fwrite(STDOUT, date('Y/m/d H:i:s '). call_user_func_array("sprintf", $args));
    fwrite(STDOUT, "\n");
  }
}
