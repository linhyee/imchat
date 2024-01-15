<?php

namespace server;

/**
 *
 * @package server.server
 * @author mrlin <714480119@qq.com>
 */

class Server {
  private $addr = '0.0.0.0';
  private $port = 8888;
  private $sockets = array();
  private $conns = array();
  
  function __construct($address = '127.0.0.1', $port = 8000) {
    $this->addr = $address;
    $this->port = $port;
  }

  function run() {
    $this->listen();
    while (true) {
      $this->loop();
    }
  }

  private function listen() {
    $socket = stream_socket_server(sprintf("tcp://%s:%d", $this->addr, $this->port),
      $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);

    if (!$socket) {
      trigger_error("stream_socket_server error: $errstr ($errno)", 256);
    }
    log_msg("server listened on %d", $this->port);
    stream_set_blocking($socket, 0);
    array_push($this->sockets, $socket);
  }

  private function loop() {
    $read = $this->sockets;
    $write = null;
    $except = null;
    if (stream_select($read, $write, $except, 5) > 0) {
      foreach ($read as $sock) {
        $this->handle_read($sock);
      }
    }
    $read = null;
    $write = $this->sockets;
    $except = null;
    if (stream_select($read, $write, $except, 5) > 0) {
      foreach ($write as $sock) {
        if ($sock !== $this->sockets[0]) { //不用处理服务端的write
          $this->handle_write($sock);
        }
      }
    }
  }

  private function server_accept($server_sock) {
    if ($sock = @stream_socket_accept($server_sock, empty($this->conns) ? -1 : 0, $peer)) {
      log_msg($peer. " connected". PHP_EOL);
      stream_set_blocking($sock, 0);
      array_push($this->sockets, $sock);
      array_push($this->conns, new conn(
        $sock,
      ));
    }
  }

  private function handle_read($sock) {
    if ($sock == $this->sockets[0]) {
      return $this->server_accept($sock);
    }
    $con = $this->get_active_conn($sock);
    if (!$con) {
      trigger_error("can not find active connection object : ".
        stream_socket_get_name($sock, true), 256);
    }
    $total_buf = '';
    while ($buf = stream_socket_recvfrom($sock, 1024)) {
      if ($buf !== false) {
        $total_buf .= $buf;
      }
    }
    if ($total_buf === '') { // client closed, 主动关闭
      log_msg("recv client %s EOL", stream_socket_get_name($con->sock, true));
      $this->do_quit($con, array(
        'type' => conn::Quit,
      ));
      return;
    }
    //数据解包
    $msgs = $this->split_packet($con, $total_buf);
    if ($msgs === false) { //无效帧头
      // 强制退出, 不用消息广播
      log_msg("recv client %s invalid data package", stream_socket_get_name($con->sock, true));
      $this->close($sock);
    } else {
      log_msg("recev msgs : %s", json_encode($msgs));
      foreach ($msgs as $msg) {
        array_push($con->write_msgs, $msg);
      }
    }
  }

  private function close($sock) {
    $remote = stream_socket_get_name($sock, true);
    fclose($sock);
    $this->remove_sock($sock);
    $this->remove_conn($sock);
    log_msg("client %s disconnected", $remote);
  }

  private function split_packet($conn, $buf) {
    if ($conn->read_buf) {
      $buf = $conn->read_buf . $buf;
    }
    $msg = array();
    $buf_sz = strlen($buf) ;
    $unpack_sz = $buf_sz;
    $offset = 0;
    while ($unpack_sz >= 3) { // 封包格式: |1byte|2byte|------data-------|, 所以有至少3个字节长度buf
      $byte = substr($buf, $offset, 1);
      $offset += 1;
      $id = ord($byte);
      if ($id != 0xeb) { //检查帧头
        return false;
      }
      // 载荷长度
      $sz = unpack('n',substr($buf, $offset, 2))[1];
      $offset += 2;
      if ($sz > 0 ) {
        if ($buf_sz - $offset >= $sz) {
          $payload = substr($buf, $offset, $sz -1); // tail with \0
          array_push($msg, $payload);
          $offset += $sz;
        } else {
          // 没有收到足够的数据
          $offset -= 3;
          break;
        }
      } else { //非法数据
        return false;
      }
      $unpack_sz -= $offset;
    }
    $conn->read_buf = substr($buf, $offset);
    log_msg("partial buf : buf_sz=%d, offset=%d, read_buf=%s", $buf_sz, $offset, $conn->read_buf);
    return $msg;
  }

  private function handle_write($sock) {
    $con = $this->get_active_conn($sock);
    if (!$con) {
      trigger_error("can not get active connection object: ".
        stream_socket_get_name($sock, true), 256);
    }
    while ($msg_buf = array_shift($con->write_msgs)) {
      $msg = json_decode($msg_buf, true);
      switch ($msg['type'] ?? -1) { //判断消息类型
        case conn::Login:
          $this->do_handshake($con, $msg);
          break;
        case conn::Message:
          $this->do_message($con, $msg);
          break;
        case conn::Quit:
          $this->do_quit($con, $msg);
          break;
      }
    }
  }

  private function do_handshake($conn, $msg ) {
    $send_msg = array(
      'data' => 'ok',
      'type' => conn::Login,
    );
    log_msg("recv handshaked msg: %s", json_encode($msg));
    if ($msg['data'] != 'syn') { //握手失败
      $this->close($conn->sock);
      return;
    }
    $conn->islogin = true;
    $conn->username = $msg['from'] ?? 'someone';
    if ( $res = stream_socket_sendto($conn->sock, $this->wrap_packet($send_msg))) {
      log_msg("send handshaked msg: %s", json_encode($send_msg));
      //广播消息
      foreach ($this->conns as $client_conn) {
        if ($client_conn->sock!== $conn->sock) {
          stream_socket_sendto($client_conn->sock, $this->wrap_packet(array(
            'data' => sprintf("welcome, %s !",$msg['from'] ?? 'someone'),
            'from' => 'system message',
            'type' => conn::Present,
          )));
        }
      }
    } else {
      log_msg("stream_socket_sendto: ret=%d", $res);
    }
  }

  private function do_quit($conn, $msg) {
    $conn->islogin = false;
    $this->close($conn->sock);
    //brocast
    foreach ($this->conns as $client_conn) {
      if ($client_conn->sock !== $conn->sock) {
        stream_socket_sendto($client_conn->sock, $this->wrap_packet(array(
          'data' => sprintf("%s leaved !", $conn->username),
          'from' => 'system message',
          'type' => conn::Present,
        )));
      }
    }
  }

  private function do_message($conn, $msg) {
    foreach ($this->conns as $client_conn) {
      if ($client_conn->sock !== $conn->sock) {
        stream_socket_sendto($client_conn->sock, $this->wrap_packet(array(
          'data' => $msg['data'],
          'from' => $msg['from'],
          'type' => conn::Message,
        )));
      }
    }
  }

  private function wrap_packet(array $msg) {
    $payload = json_encode($msg)  . "\0"; // c string end with '\0';
    $len = strlen($payload);
    $pack_msg =  pack('Cn', 0xeb, $len);
    return $pack_msg.$payload;
  }

  private function get_active_conn($sock) {
    foreach ($this->conns as $con) {
      if ($con->sock === $sock) {
        return $con;
      }
    }
    return false;
  }

  private function remove_conn($sock) {
    $conns = array();
    foreach ($this->conns as $con) {
      if ($con->sock !== $sock) {
        array_push($conns, $con);
      }
    }
    $this->conns = $conns;
  }

  private function remove_sock ($sock) {
    $sockets = array();
    foreach ($this->sockets as $so) {
      if ($so !== $sock) {
        array_push($sockets, $so);
      }
    }
    $this->sockets = $sockets;
  }
}

class conn {
  const Login = 0;
  const Message = 1;
  const Present = 2;
  const Quit = 3;

  public $id;
  public $sock;
  public $username;
  public $islogin = false;
  public $read_buf = '';
  public $write_msgs = array(); 

  function __construct($sock) {
    $this->id = uniqid('u');
    $this->sock = $sock;
  }
}

function log_msg() {
  $args = func_get_args();

  if (count($args) > 0) {
    fwrite(STDOUT, date('Y/m/d H:i:s '). call_user_func_array("sprintf", $args));
    fwrite(STDOUT, "\n");
  }
}

$srv = new Server();
$srv->run();
