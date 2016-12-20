<?php 
namespace webservices;

/**
 * 
 * @package  webservices.User
 * @author  mrlin <714480119@qq.com>
 */
class User
{

// ------------------------------------------------------------------------
	/**
	 * __construct 
	 */
	public function __construct()
	{

	}

// ------------------------------------------------------------------------
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

// ------------------------------------------------------------------------

	/**
	 * check user exists
	 * 	
	 * @param string $name
	 * @return boolean
	 */
	public static function checkUser($name)
	{
		static $usr = null;

		if (! $usr instanceof User) {
			$usr  = new self();
		}

		if ($usr->getUser($name) == '') {
			return false;
		}

		return true;
	}
}
