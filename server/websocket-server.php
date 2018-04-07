<?php 
namespace server;

use Exception;

class server {
  private $sock;
  private $status = false; // not running 
  private $address;
  private $port;
  private $protocol;
  public $looper;

  function __construct($address = '127.0.0.1', $port = 9527) {
    $this->address = $address;
    $this->port = $port;
    $this->looper = new looper();
  }

  function register(iprotocol $protocol) {
    $this->protocol = $protocol;
  }

  function listen() {
    if (($sock = socket_create(AF_INET, SOCK_STREAM, 0)) < 0) {
      trigger_error("failed to create socket:".socket_strerror($sock), 256);
    }
    if (($ret = socket_bind($sock, $this->address, $this->port)) < 0) {
      trigger_error("failed to bind socket:".socket_strerror($ret), 256);
    }
    if (($ret = socket_listen($sock, 5)) < 0) {
      trigger_error("failed to listen to socket:".socket_strerror($ret), 256);
    }

    socket_set_nonblock($sock);
    $this->sock = $sock;

    log_msg("server listenning on port[%d]\n", $this->port);
  }

  function accept($sock, $event_type, $data) {
    $nsock = @socket_accept($this->sock);
    if ($nsock === false) {
      usleep(100);
    } else if ($nsock > 0) {
      socket_set_nonblock($nsock);
      $this->looper->add_event(new event($nsock, array($this->protocol, 'receiv'), event::READ));

      if (is_callable(array($this->protocol, 'connect'))) {
        $this->protocol->connect($nsock, null);
      }
    } else {
      trigger_error("error:".socket_strerror($nsock), 256);
    }
  }

  function start() {
    $this->listen();
    $this->looper->add_event(new event($this->sock, array($this, 'accept'), event::READ));
    $this->status = true;
    while ($this->status) {
      $this->looper->loop();
    }
    socket_close($this->sock);
  }
}

date_default_timezone_set('PRC');

function log_msg() {
  $args = func_get_args();

  if (count($args) > 0) {
    fwrite(STDOUT, date('Y/m/d H:i:s '). call_user_func_array("sprintf", $args));
    fwrite(STDOUT, "\n");
  }
}

function socketstr($socket) {
  return str_replace(" ", "", strval($socket));
}

// read buf with \0
function read_eof($sock, $len = 1024) {
  $buf = '';
  $read = '';
  while (($flag=@socket_recv($sock, $buf, $len, 0)) > 0) {
    $asc = ord(substr($buf,-1));
    if ($asc == 0) {
      $read .= substr($buf,0,-1);
      break;
    } else {
      $read .= $buf;
    }
  }
  if ($flag > 0) {
    return $read;
  } else {
    return false;
  }
}

function read($sock, $len = 1024) {
  $buf = "";
  $n = @socket_recv($sock, $buf, $len, 0);
  if ($n > 0) {
    return $buf;
  }
  return false;
}

function write($sock, $buf) {
  $len = strlen($buf);
  $write = 0;
  while (($flag=@socket_send($sock, $buf, $len, 0)) > 0) {
    if ($flag < $len) {
      $len = $len - $flag;
      $buf = substr($buf, $flag - 1);
    } else {
      $write = $len;
      break;
    }
  }
  if ($flag > 0) {
    return $write;
  } else {
    return false;
  }
}

interface iprotocol {
  public function receiv($sock, $event_type, $data);
  public function send($sock, $event_type, $data);
  public function connect($sock, $data);
}

class looper {
  public $selector;
  public $event_list = array();

  function __construct() {
    $this->selector = new select();
  }

  function add_event(event $event) {
    $id = socketstr($event->sock);
    if (!isset($this->event_list[$id][$event->event_type])) {
      $this->event_list[$id][$event->event_type] = $event;
    } else {
      $this->event_list[$id][$event->event_type]->data .= $event->data;
    }

    if (!$this->selector->exists($event->sock)) {
      $this->selector->add($event->sock);
    }
  }

  function remove_event(event $event) {
    $id = socketstr($event->sock);
    unset($this->event_list[$id][$event->event_type]);

    if (empty($this->event_list[$id])) {
      unset($this->event_list[$id]);

      if ($this->selector->exists($event->sock)) {
        $this->selector->remove($event->sock);
      }
    }
  }

  function get_active_event($sock, $event_type) {
    $id = socketstr($sock);
    if (isset($this->event_list[$id][$event_type])) {
      return $this->event_list[$id][$event_type];
    }
    return null;
  }

  function emit(event $event) {
    if (is_callable($event->func)) {
      call_user_func_array($event->func, array($event->sock, $event->event_type, $event->data));
    }
  }

  function loop() {
    $read = $this->selector->can_read(5);
    foreach ($read as $sock) {
      $event = $this->get_active_event($sock, event::READ);
      if ($event) {
        $this->emit($event);
      }
    }

    $write = $this->selector->can_write(5);
    foreach ($write as $sock) {
      $event = $this->get_active_event($sock, event::WRITE);
      if ($event) {
        $this->emit($event);
      }
    }
  }
}

class event {
  const READ = 0x01;
  const WRITE = 0x02;

  public $sock;
  public $func;
  public $event_type;
  public $data;

  function __construct($sock, $func, $event_type) {
    $this->sock = $sock;
    $this->func = $func;
    $this->event_type = $event_type;
  }
}

class select {
  public $sockets;

  function __construct($sockets = array()) {
    $this->sockets = array();

    foreach ($sockets as $socket) {
      $this->add($socket);
    }
  }

  function exists($socket) {
    return in_array($socket, $this->sockets);
  }

  function add($add_socket) {
    array_push($this->sockets,$add_socket);
  }

  function remove($remove_socket) {
    $sockets = array();

    foreach ($this->sockets as $socket) {
      if($remove_socket != $socket)
        $sockets[] = $socket;
    }

    $this->sockets = $sockets;
  }

  function can_read($timeout) {
    $read = $this->sockets;
    @socket_select($read,$write = NULL,$except = NULL,$timeout);
    return $read;
  }

  function can_write($timeout) {
    $write = $this->sockets;
    @socket_select($read = NULL,$write,$except = NULL,$timeout);
    return $write;
  }
}

class echonic implements iprotocol {
  public $server;

  function __construct($server) {
    $this->server = $server;
  }

  function connect($sock, $data) {
    printf("client %s connected\n", $sock);
  }

  function receiv($sock, $event, $data) {
    $buf = read_eof($sock);
    $this->server->looper->remove_event(new event($sock,null,event::READ));
    if (!$buf) {
      $this->close($sock);
      return;
    } else {
      $event = new event($sock, array($this, 'send'), event::WRITE);
      $event->data = $buf;
      $this->server->looper->add_event($event);
    }
    printf("receiv: %s\n", $buf);
  }

  function send($sock, $event, $data) {
    $n = write($sock, $data);
    $this->server->looper->remove_event(new event($sock, null, event::WRITE));
    if ($n > 0) {
      $event = new event($sock, array($this, 'receiv'), event::READ);
      $event->data = '';
      $this->server->looper->add_event($event);
    } else {
      $this->close($sock);
      return;
    }
    printf("send: %s\n", $data);
  }

  function close($sock) {
    socket_close($sock);
    printf("client %s closed\n", $sock);
  }
}


class wscon {
  public $id;
  public $sock;
  public $request = null;
  public $is_connected = false;

  public $handling_partial_packet = false;
  public $partial_buff = '';

  public $huge_payload = '';

  public $has_sent_close = false;

  public $path = "";

  function __construct($id, $sock) {
    $this->id = $id;
    $this->sock = $sock;
  }
}

class BadOpcodeException extends Exception {}
class BadUriException extends Exception {}
class ConnectionException extends Exception {}

abstract class wsserver implements iprotocol {
  public $server;
  public $conns;
  public $bufsize;

  public $held_packets = array();

  public $WsConnClass = 'wscon';

  public static $opcodes = array(
    'continuation' => 0,
    'text' => 1,
    'binary' => 2,
    'close' => 8,
    'ping' => 9,
    'pong' => 10,
  );

  abstract function connecting($sock);
  abstract function message($sock, $data);
  abstract function closing($sock);

  function __construct($server, $connclass = 'wscon', $bufsize = 2048) {
    $this->server = $server;
    $this->bufsize = $bufsize;
    $this->WsConnClass = $connclass;

    if (method_exists($this, 'init')) {
      $this->init();
    }
  }

  function connect($sock, $data) {
    $ws = new $this->WsConnClass(uniqid('u'), $sock);
    $this->conns[socketstr($sock)] = $ws;
    // Can do something, after the instance of the
    // connection is created, before the handshake has completed
  }

  function receiv($sock, $event, $data) {
    $conn = $this->get_active_conn($sock);
    try {
      if (!$conn->is_connected) {
        $this->dohandshake($conn);
        $this->connecting($conn->sock); // Overide it !
        return;
      }
      $buf = read($sock, $this->bufsize);
      if ($buf) {
        $this->split_packet(strlen($buf), $buf, $conn);
      }
    } catch (Exception $e) {
      $this->close($sock);
    }
  }

  function send($sock, $event, $data) {
  }

  function close(&$conn) {
    $this->closing($conn->sock); // Overide it !
    $this->enforce_close($conn);
  }

  function enforce_close(&$conn) {
    $this->wrap_packet('', $conn, 'close', false);
    $conn->is_connected = false;
    unset($this->conns[socketstr($conn->sock)]);
    socket_close($conn->sock);

    log_msg("client %s disconnected.\n", $conn->sock);
  }

  function get_active_conn($sock) {
    $id = socketstr($sock);
    if (isset($this->conns[$id])) {
      return $this->conns[$id];
    }
    return null;
  }

  function dohandshake(&$conn) {
    $request = read($conn->sock, $this->bufsize);
    if (!$request) {
      throw new ConnectionException("Socket error:". socket_strerror(socket_last_error($conn->sock)));
    }

    if (!preg_match('/GET (.*) HTTP\//mUi', $request, $matches)) {
      throw new BadUriException("No GET in request:\n".$request);
    }
    $get_uri = trim($matches[1]);
    $uri_parts = parse_url($get_uri);

    $conn->request = explode("\n", $request);
    $conn->path = $uri_parts['path'];
    /// @todo Get query and fragment as well

    if (!preg_match('#Sec-WebSocket-Key:\s(.*)$#mUi', $request, $matches)) {
      throw new ConnectionException("Client had no Key in upgrade request:\n".$request);
    }

    $key = trim($matches[1]);

    // @todo Validate key length and base 64
    $response_key = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

    $header = "HTTP/1.1 101 Switching Protocols\r\n"
      . "Upgrade: websocket\r\n"
      . "Connection: Upgrade\r\n"
      . "Sec-WebSocket-Accept: $response_key\r\n"
      . "\r\n";

      write($conn->sock, $header);
      $conn->is_connected = true;

      log_msg("client %s connected\n", $conn->sock);
  }

  function split_packet($len, $packet, &$conn) {
    if ($conn->handling_partial_packet) {
      $packet = $conn->partial_buff . $packet;
      $conn->handling_partial_packet = false;
      $len = strlen($packet);
    }
    $frame_pos = 0;
    $frame_id = 1;

    while ($frame_pos < $len) {
      if (($message = $this->receive_fragment($packet, $conn, $frame_size)) !== FALSE) {
        if ($conn->has_sent_close) {
          $this->close($conn);
        } else {
          $this->message($conn->sock, $message); // Overide it !
        }
      }
      $frame_pos += $frame_size;
      $packet = substr($packet, $frame_pos);
      $frame_id++;
    }
  }

  function wrap_packet($packet, &$conn, $opcode = 'text', $masked = false) {
    if ($conn->is_connected) {
      if (!array_key_exists($opcode, self::$opcodes)) {
        throw new BadOpcodeException("Bad opcode '$opcode'. Try 'text' or 'binary'. ");
      }

      $strlen = extension_loaded('mbstring') ? 'mb_strlen' : 'strlen';
      // record the length of the payload
      $payload_length = $strlen($packet);

      $fragment_cursor = 0; 
      // while we have daa to send
      while ($payload_length > $fragment_cursor) {
        // get a fragment of the payload
        $sub_payload = substr($packet, $fragment_cursor, $this->bufsize);

        // advance the cursor
        $fragment_cursor += $this->bufsize;

        // is this the final fragment to send?
        $final = $payload_length <= $fragment_cursor;

        // send the fragment
        $frame = $this->pack_fragment($final, $sub_payload, $opcode, $masked);
        write($conn->sock, $frame);

        // all fragments after the first will be marked a continuation
        $opcode = 'continuation';
      }
    } else {
      $holding_packet = array (
        'conn' => $conn,
        'packet' => $packet,
      );
      $this->held_packets[] = $holding_packet;
    }
  }

  function pack_fragment($final, $payload, $opcode, $masked) {
    // Binary string for header.
    $frame_head_binstr = '';

    // Write FIN, final fragment bit.
    $frame_head_binstr .= (bool) $final ? '1' : '0';

    // RSV 1, 2, & 3 false and unused.
    $frame_head_binstr .= '000';

    // Opcode rest of the byte.
    $frame_head_binstr .= sprintf('%04b', self::$opcodes[$opcode]);

    // Use masking?
    $frame_head_binstr .= $masked ? '1' : '0';

    $strlen = extension_loaded('mbstring') ? 'mb_strlen' : 'strlen';

    // 7 bits of payload length...
    $payload_length = $strlen($payload);
    if ($payload_length > 65535) {
      $frame_head_binstr .= decbin(127);
      $frame_head_binstr .= sprintf('%064b', $payload_length);
    } else if ($payload_length > 125) {
      $frame_head_binstr .= decbin(126);
      $frame_head_binstr .= sprintf('%016b', $payload_length);
    } else {
      $frame_head_binstr .= sprintf('%07b', $payload_length);
    }

    $frame = '';
    // Write frame head to frame.
    foreach (str_split($frame_head_binstr, 8) as $binstr) {
      $frame .= chr(bindec($binstr));
    }

    // Hanle masking
    if ($masked) {
      // generate a random mask:
      $mask = '';
      for ($i = 0; $i < 4; $i++) {
        $mask .= chr(rand(0, 255));
      }
      $frame .= $mask;
    }

    // Append payload to frame:
    for ($i = 0; $i< $payload_length; $i++) {
      $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
    }

    return $frame;
  }

  //https://github.com/Textalk/websocket-php/blob/master/lib/Base.php
  //return int(frame size) or false(error)
  function receive_fragment($packet, &$conn, &$frame_size) {
    //Current packet offset
    $offset = 0;

    // Just read the main fragment imformation first
    $data = self::read($packet, $offset, 2);
    // Is this the final fragment? // Bit 0 in byte 0
    $final = (boolean) (ord($data[0]) & 1 << 7);

    // Should be unused, and must be false... // Bits 1, 2, & 3
    $rsv1 = (boolean) (ord($data[0]) & 1 << 6);
    $rsv2 = (boolean) (ord($data[0]) & 1 << 5);
    $rsv3 = (boolean) (ord($data[0]) & 1 << 4);

    // Parse opcode
    $opcode_int = ord($data[0]) & 31; // Bits 4-7
    $opcode_ints = array_flip(self::$opcodes);
    if (!array_key_exists($opcode_int,  $opcode_ints)) {
      throw new BadOpcodeException("bad opcode in websocket frame:".$opcode_int);
    }

    $opcode = $opcode_ints[$opcode_int];

    //record the opcode if we are not receiving a continuation frament
    if ($opcode !== 'continuation') {
      // Go ahead, Here do nothing!
    }

    // Masking ?
    $mask = (boolean) (ord($data[1]) >> 7); // Bit 0 in byte 1
    $payload = '';

    // Payload length
    $payload_length = (int) ord($data[1]) & 127; // Bits 1-7 in byte 1
    if ($payload_length > 125) {
      if ($payload_length == 126) {
        //126: Payload is a 16-bit unsigned int
        $data = self::read($packet, $offset, 2);
      } else {
        // 127: Payload is a 64-bit unsigned int
        $data = self::read($packet, $offset, 8);
      }
      $payload_length = bindec(self::sprint_b($data));
    }

    // Get masking key
    if ($mask) {
      $masking_key = self::read($packet, $offset, 4);
    }

    // Get real frame size we expected
    $frame_size = $offset + $payload_length;

    if ($payload_length > 0) {
      // Get raw payload
      $data = self::read($packet, $offset, $payload_length);
      $strlen = extension_loaded('mbstring') ? 'mb_strlen' : 'strlen';

      if ($payload_length > $strlen($conn->huge_payload.$data)) {
        $conn->handling_partial_packet = true;
        // Just the current frame size buf
        $conn->partial_buff = substr($packet, 0, $frame_size);
        return false;
      }

      if ($mask) {
        // Unmask payload.
        for ($i = 0; $i < $payload_length; $i++) {
          $payload .= ($data[$i] ^ $masking_key[$i % 4]);
        }
      } else {
        $payload = $data;
      }
    }

    if ($opcode === 'close') {
      $conn->has_sent_close = true;

      //Get the close status
      if ($payload_length >=2) {
        $status_bin = $payload[0].$payload[1];
        $status = bindec(sprintf("%08b%08b", ord($payload[0]), ord($payload[1])));
        $payload = substr($payload, 2);

        $buf = $status_bin . 'Close acknowledged: ' . $status;
      }
      return '';
    }

    if (!$final) {
      $conn->huge_payload .= $payload;
      return false;
    } else if ($conn->huge_payload) { // this is the last fragment, and we are processing a huge_payload
      // sp we need to retreive the whole payload
      $payload = $conn->huge_payload.= $payload;
      $conn->huge_payload = '';
    }

    return $payload;
  }

  static function read($packet, &$offset, $len) {
    $buf = '';
    $n = strlen($packet)-1;
    for ($i = 0; $i < $len; $i++) {
      if ($n < $i + $offset) {
        break;
      }
      $buf .= $packet[$i + $offset];
    }
    $offset += $i;
    return $buf;
  }

  static function sprint_b($str) {
    $s = '';
    for ($i = 0; $i < strlen($str); $i++) {
      $s .= sprintf("%08b", ord($str[$i]));
    }
    return $s;
  }

  static function escape($str) {
    return htmlentities($str, ENT_NOQUOTES);
  }
}

class wsechonic extends wsserver {
  function connecting($sock) {}

  function message($sock, $data) {
    log_msg("receiv: ".$data);
    $conn = $this->get_active_conn($sock);
    $this->wrap_packet($data, $conn);
  }

  function closing($sock) {}
}

class usrconn extends wscon {
  public $username;
  public $email;
  public $islogin = false;
  public $icon;
}

class chatroom extends wsserver {
  public $db = null;

  function init() {
    require '../lib/db.php';
    $db = new \lib\Db(array('db_type' => 'sqlite','sqlite_path' => dirname(getcwd()).'/data/ichat.db' ));
    $this->db = $db;
  }

  function connecting($sock) {}

  function message($sock, $data) {
    $conn = $this->get_active_conn($sock);
    $data = json_decode($data, true);

    switch ($data['type']) {
      case 'login':
        if (!$conn->islogin) {
          $this->dologin($conn, $data);
        } else {
          $this->close($conn);
        }
        break;
      case 'msg':
        $this->domessage($conn, $data);
        break;
      case 'quit':
        // Invoke parent method.
        $this->close($conn); 
        break;
      default:
        // Enforce close
        $this->enforce_close($conn);
    }
  }

  function closing($sock) {
    $conn = $this->get_active_conn($sock);
    $msg = array(
      'type' => 'presence',
    );

    foreach ($this->conns as &$client) {
      if ($client->islogin) {
        if ($client->sock == $sock) {
          $client->username = null;
          $client->email = null;
          $client->islogin = false;
        } else {
          $msg['roster'][] = array(
            'name' => $client->username,
            'email' => $client->email,
            'icon' => $client->icon,
          );
        }
      }
    }

    $this->broadcast($conn, $msg);
  }

  function dologin(&$conn, $data) {
    // Verify user infomartion
    $username = $data['name'];
    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    $msg = array(
      'type' => 'presence',
      'roster' => array(),
      'session' => array(
        'islogin' => false,
        'name' => $username,
        'email' => $email,
        'history_msg' => array(),
      ),
    );

    $strlen = extension_loaded('mbstring') ? 'mb_strlen' : 'strlen';
    if ($strlen($username) < 2 || !$email) {
      $this->wrap_packet(json_encode($msg), $conn);
      return;
    } else {
      $icon = rand(1, 12);
      // Make a session 
      $conn->username = $username;
      $conn->email = $email;
      $conn->islogin = true;
      $conn->icon = $icon;
    }

    // Update roster
    foreach ($this->conns as $client) {
      if ($client->islogin) {
        $msg['roster'][] = array(
          'name' => $client->username,
          'email' => $client->email,
          'icon' => $client->icon,
        );
      }
    }

    // Get history msg
    $msg['session']['history_msg'] = $this->getmsg();
    $msg['session']['islogin'] = true;
    $msg['session']['icon'] = $conn->icon;

    // Boradcast other user
    $this->broadcast($conn, $msg);
  }

  function domessage($conn, $data) {
    $msg = array(
      'type' => 'msg',
      'data' => nl2br(self::escape($data['data'])),
    );
    $this->broadcast($conn, $msg);
    $this->savemsg(array(
      'from' => $conn->username,
      'msg' => $data['data'],
      'image' => NULL,
    ));
  }

  function broadcast($conn, array $msg) {
    foreach ($this->conns as $client) {
      $data = $msg; // Storage a local var because every connection could change it
      if ($client->islogin) {
        if ($client->sock != $conn->sock) {
          if ($data['type'] == 'presence') {
            // Others no need its session
            if (isset($data['session'])) {
              unset($data['session']);
              unset($data['history_msg']);
            }
          }
        }
        if ($data['type'] == 'msg') {
          $data['from'] = $conn->username;
          $data['to'] = $client->username;
        }
        $this->wrap_packet(json_encode($data), $client);
      }
    }
  }

  function savemsg(array $msg) {
    return $this->db->insert('msg', array(
      'id' => NULL,
      'from' => $msg['from'],
      'msg' => $msg['msg'],
      'image' => $msg['image'],
      'create_time' => time(),
    ));
  }

  function getmsg($limit = 20) {
    $pdostmt = $this->db->select('msg')->orderBy('create_time', 'asc')->limit(20)->query();
    $rs = $pdostmt->fetchAll(\PDO::FETCH_ASSOC);
    $msg = array();
    foreach ($rs as $val) {
      $msg[] = array(
        'from' => $val['from'],
        'msg' => nl2br(self::escape($val['msg'])),
        'image' => $val['image'],
      );
    }
    return $msg;
  }
}

$srv = new server();
$srv->register(new chatroom($srv, '\\server\\usrconn')); //wtf, namespace!!!
$srv->start();
