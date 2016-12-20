<?php 
namespace webservices;

/**
 * 
 * @package  webservices.db
 * @author  mrlin <714480119@qq.com>
 */
class Db
{
	/**
	 * 
	 * errors
	 * 
	 * @var array
	 */
	public $errors = array();

	/**
	 * 
	 * sql statement
	 * 
	 * @var string
	 */
	private  $sql = "";

	/**
	 * 
	 * name => value pairs
	 * 
	 * @var array
	 */
	private $values = array();

	/**
	 * 
	 * The pdo
	 * 
	 * @var null
	 */
	private $db = null;

// ------------------------------------------------------------------------
	/**
	 * @construct
	 *
	 * @param array $dsn
	 *
	 */
	public function __construct(array $dsn = array())
	{
		if (!empty($dsn))
		{
			$this->setDb($dsn);
		}
	}

// ------------------------------------------------------------------------
	/**
	 * init db config
	 *
	 * @param array $dsn
	 */
	public function setDb(array $dsn = array())
	{
		$db_type = @strtolower($dsn['db_type']);

		switch($db_type)
		{
			case 'pgsql':
			case 'postgresql':
			case 'mysql':
				$db_type  = isset($dsn['db_type'])  ? $dsn['db_type']: '';
				$hostname = isset($dsn['hostname']) ? $dsn['hostname']: '';
				$database = isset($dsn['database']) ? $dsn['database']: '';
				$username = isset($dsn['username']) ? $dsn['username']: '';
				$password = isset($dsn['password']) ? $dsn['password']: '';
				$db_port  = isset($dsn['db_port'])  ? $dsn['db_port']: '';
				$charset  = isset($dsn['charset'])  ? $dsn['charset']: 'utf8';
				try
				{
					$this->db = new \PDO("$db_type:host=$hostname;port=$db_port;dbname=$database;charset=$charset", $username, $password);
					$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				}
				catch(\PDOException $e)
				{
					print "database configuration:\n";
					die($e->getMessage());
				}
				break;
			case 'sqlite':
				try
				{
					$path = isset($dsn['sqlite_path']) ? $dsn['sqlite_path']: '';
					$this->db = new \PDO("sqlite:$path");
					$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				}
				catch(\PDOException $e)
				{
					print "sqlite path: $path\n";
					die($e->getMessage());
				}
				break;
			default:
				throw new \Exception("Database type not supported");
		}
	}

// ------------------------------------------------------------------------
	/**
	 * 
	 * singlon method
	 * 
	 * @return \Db
	 */
	public static function getDb()
	{
		static $instance = null;

		if (!$instance instanceof Db)
		{
			$instance = new self(array(
				'db_type'  => 'mysql',
				'hostname' => 'localhost',
				'database' => 'test',
				'username' => 'root',
				'password' => 'root',
				'db_port'  => '3306',
				'charset'  => 'utf8',
			));

		}
		return $instance;
	}
}
