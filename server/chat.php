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
	 *   		'uqid'     : '2', // fd
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
		print_r($data);

		$user = array(
			'fd'        => $data['fd'],
			'logintime' => $data['logintime'],
			'type'      => $data['type'],
			'username'  => $data['username'],
			'remoteip'  => $data['remoteip'],
		);

		// Http::getHttp()->post($user)->submit('http://192.168.66.10/api.php?a=adduser');

		self::broadcast();

		//登录成功, 返回出席信息

		return array(
		);
	}

// ------------------------------------------------------------------------
	/**
	 * 
	 * 返回消息报文
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