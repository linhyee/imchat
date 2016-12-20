<?php 
namespace webservices;

/**
 * 
 * @package  webservices.controller
 * @author  mrlin <714480119@qq.com>
 */
class Controller
{
	public function actionUser($name)
	{
		echo $name;
	}

	public function action404()
	{
		header("HTTP/1.1 404 Not Found");exit;
	}
}