<?php
namespace server;

use lib\Http;

/**
 *
 * @package server.chat
 * @author mrlin <714480119@qq.com>
 */
class Chat {
  public $ws = null;
  public $apiHost = 'http://192.168.66.10';
  public $protocol = 'ws';
  public $connections = array();
    
  public function __construct ($ws, $proto = 'ws') {
    $this->ws       = $ws;
    $this->protocol = $proto;
  }

// ------------------------------------------------------------------------
  /**
   *
   * 返回的出席报文
   *
   * {
   *    'code' : 0,  // err code
   *    'msg'  : '', // wrong message
   *    'data' : {
   *      'id'       : 'present',
   *      'uqid'     : '2', // The received fd
   *      'username' : 'kate',
   *      'email'    : 'abc@qq.com',
   *    },
   * }
   * 
   * handle user logination
   * 
   * @noreturn
   * 
   */
  public function doLogin($fd, $data) {
    $email = isset($data['e']) ? $data['e'] : '';
    $uname = isset($data['u']) ? $data['u'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($username)) {
          $wxs = array(
        'code' => -2,
        'msg'  => 'invalid request data',
        'data' => '',
          );
            goto end;
        }


    //check if logined user exists
    $ret = Http::getHttp()->get($this->apiHost . '/api?a=user&u='.$uname.'&e='.$email);

    if ($ret['body'] == -1) {
      $wxs = array(
        'code' => -1,
        'msg'  => 'username or email already exists',
        'data' => '',
      );
      goto end;
    }

    $user = array(
      'fd'        => $fd,
      'logintime' => time(),
      'type'      => 2, //web end
      'username'  => $uname,
      'email'     => $email,
      'remoteip'  => $data['remoteip'],
    );


    // add user
    Http::getHttp()->post($user)->submit($this->apiHost . '/api.php?a=adduser');

    //登录成功, 返回出席信息
    $wxs = array(
      'code' => 0,
      'msg'  => 'login success',
      
      'data' => array(
        'id'       => 'present',
        'uqid'     => $data['fd'],
        'username' => $data['username'],
        'email'    => $data['email'],
      ),
    );

    end:
    $this->send($fd, $wxs);
  }

// ------------------------------------------------------------------------
  /**
   * 
   * 返回消息报文
   *
   * {
   *    'code' : 0,
   *    'msg'  : '',
   *    'data' : {
   *      'id'   : 'message',
   *      'uqid' : '2',
   *      'chat' : 'a', //a=>all, u=>someone
   *      'data' : 'hello world!',
   *    }
   * }
   * 
   * @param int $fd
   * 
   * @param  array $data
   * 
   * @noreturn
   * 
   */
  public function doMessage($fd, $data) {
    $wxs = array(
      'code' => 0,
      'msg'  => 'received success',
      'data' => array(
      ),
    );

    $this->send($fd, $wxs);
  }

// ------------------------------------------------------------------------
  /**
   *
   * 返回退出报文
   * {
   *    'code' : 0,
   *    'msg'  : '',
   *    'data' : {
   *      'id' : 'quit',
   *      'uqid' : '2',
   *      'data' : 'someone logout!',
   *    }
   * }
   * 
   * @param  array $data
   * 
   * @noreturn
   */
  public function doLogout($fd, $data) {
    $wxs = array(
      'code' => 0,
      'msg'  => 'logout',
      'data' => array(
      ),
    );

    print_r($this->connections);

    echo "doing logout\r\n";

    $this->send($fd, $wxs);
  }

  protected function send($fd, $data) {
    $func = $this->protocol == 'ws' ? 'push' : 'send';

    if ($data['code'] !== 0) {
      $this->ws->serv->$func($fd, json_encode($data));

      //close the exception fd
      print_r($this->ws->connections);
      $this->ws->serv->close($fd);
    }

    // get all connections
    $ret   = Http::getHttp()->get($this->apiHost . '/api.php?a=userlist');
    $conns = json_decode($ret['body']);

    if ($conns) {
      foreach ($conns as $conn) {
        //标记是否是自己的报文(eg: present, logout), 用于客户端会话
        $data['data']['mine'] = $conn->fd === $fd ? 1 : 0;

        $this->ws->serv->$func($conn->fd, json_encode($data));
      }
    }
  }
}
