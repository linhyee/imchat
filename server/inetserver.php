<?php
namespace server;

/**
 * 
 * @package server.inetserv
 * @author mrlin <714480119@qq.com>
 */
interface INetserver
{
	/**
	 * 
	 * connect callback
	 * 
	 * @param  object $serv
	 * 
	 * @param  object $req
	 * 
	 * @noreturn
	 */
	public function onConnect($serv, $req);

// ------------------------------------------------------------------------
	/**
	 * 
	 * connect callback
	 * 
	 * @param  object $serv
	 * 
	 * @param  object $req
	 * 
	 * @noreturn
	 */
	public function onReceive($serv, $req);

// ------------------------------------------------------------------------
	/**
	 * 
	 * task callback
	 * 
	 * @param  object $serv
	 * 
	 * @param  object $req
	 * 
	 * @noreturn
	 */
	public function onTask($serv, $taskId, $fromId, $data);

// ------------------------------------------------------------------------
	/**
	 * 
	 * finish callback
	 * 
	 * @param  object $serv
	 * 
	 * @param  object $req
	 * 
	 * @noreturn
	 */
	public function onFinish($serv, $taskId, $data);
}