<?php
namespace server;

/**
 *
 * @package server.chat
 * @author mrlin <714480119@qq.com>
 */
class Chat
{
	/**
	 * The server
	 * 
	 * @var null
	 */
	public $serv = null;

	/**
	 * 
	 * The authencation host
	 * 
	 * @var string
	 */
	public $apiHost = 'http://192.168.66.10';

// ------------------------------------------------------------------------
	/**
	 * 
	 * @__construct
	 * 
	 * @param object $serv
	 * 
	 */
	public function __construct ($serv)
	{
		$this->serv = $serv;		
	}

// ------------------------------------------------------------------------
	/**
	 *
	 * 返回的出席报文
	 *
	 * {
	 * 		'code' : 0,  // err code
	 * 		'msg'  : '', // wrong message
	 * 		'data' : {
	 *   		'id'       : 'present',
	 *   		'uqid'     : '2', // The received fd
	 *   		'username' : 'kate',
	 *   		'email'    : 'abc@qq.com',
	 * 		},
	 * }
	 * 
	 * handle user logination
	 * 
	 * @noreturn
	 * 
	 */
	public function doLogin($data)
	{
		$user = array(
			'fd'        => $data['fd'],
			'logintime' => $data['logintime'],
			'type'      => $data['type'],
			'username'  => $data['username'],
			'email'     => $data['email'],
			'remoteip'  => $data['remoteip'],
		);

		//check if logined user exists
		$ret = Http::getHttp()->get($this->apiHost . '/api?a=user&u='.$data['username'].'&e='.$data['email']);

		if ($ret['body'] == -1)
		{
			$wxs = array(
				'code' => -1,
				'msg'  => 'username or email already exists',
				'data' => '',
			);
			goto end;
		}

		// add user
		Http::getHttp()->post($user)->submit($this->apiHost . '/api.php?a=adduser');
		self::broadcast();

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
		return $wxs;
	}

// ------------------------------------------------------------------------
	/**
	 * 
	 * 返回消息报文
	 *
	 * {
	 *		'code' : 0,
	 *		'msg'  : '',
	 *		'data' : {
	 *			'id'   : 'message',
	 *			'uqid' : '2',
	 *			'chat' : 'a', //a=>all, u=>someone
	 *			'data' : 'hello world!',
	 *		}
	 * }
	 * 
	 * @param  array $data
	 * 
	 * @return array
	 * 
	 */
	public function doMessage($data)
	{

	}

// ------------------------------------------------------------------------
	/**
	 *
	 * 返回退出报文
	 * {
	 * 		'code' : 0,
	 * 		'msg'  : '',
	 * 		'data' : {
	 * 			'id' : 'quit',
	 * 			'uqid' : '2',
	 * 			'data' : 'someone logout!',
	 * 		}
	 * }
	 * 
	 * @param  array $data
	 * 
	 * @return array
	 */
	public function doLogout($data)
	{

	}

// ------------------------------------------------------------------------
	/**
	 *
	 * send mssage to client
	 * 
	 * @param  int $fd
	 * 
	 * @param  array $message
	 *
	 * @noreturn
	 */
	public function send($fd, $message)
	{
		$jsonstr = Http::getHttp()->get('http://192.168.66.10/api.php?a=userlist');

		$conns = json_encode($jsonstr);

		foreach ($conns as $conn)
		{
			$mssage['data']['mine'] = $conn->fd === $fd? 1 : 0;

			$this->serv->send($conn->fd, $json_encode($mssage));
		}
	}

// ------------------------------------------------------------------------
	/**
	 * 
	 * pack message data
	 * 
	 * @return array
	 * 
	 */
	public function msgpack()
	{
		return array();
	}

// ------------------------------------------------------------------------
	/**
	 * 
	 * broadcast user login event
	 * 
	 * @noreturn
	 */
	public function broadcast()
	{

	}
}