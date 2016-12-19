<?php 
namespace webservices;

/**
 * 
 * @package  webservices.User
 * @author  mrlin <714480119@qq.com>
 */
class User
{
	/**
	 * __construct 
	 */
	public function __construct()
	{

	}

	/**
	 * find user
	 * 
	 * @param  string $name
	 * @return string
	 */
	public function getUser($name)
	{
		return "usr1";	
	}

	/**
	 * check user exists
	 * 	
	 * @param string $name
	 * @return boolean
	 */
	public static function checkUser($name)
	{
		$usr  = new self();

		if ($usr->getUser($name) == '') {
			return false;
		}

		return true;
	}
}

$name = filter_input(INPUT_GET, 'u');

if ($name) {
	echo User::checkUser($name) == false ? '0' : '1';
} else {
	echo "-1";
}