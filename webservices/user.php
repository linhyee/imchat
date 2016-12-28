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
     * The user info:
     * 
     * user {
     *     'id' : 132, // auto_increment
     *     'fd' : 2, //file describtion
     *     'logintime' : 1423123421,
     *     'type' : 1 // 1 => console, 2 => web, 3 => app
     *     'username' : 'kate',
     *     'remoteip' : '48.123.10.47'
     * }
     *
     * 
     */

    /**
     * 
     * table name
     * 
     * @var string
     */
    protected $table = "tbl_user";

// ------------------------------------------------------------------------
    /**
     * __construct 
     */
    public function __construct()
    {

    }

// ------------------------------------------------------------------------
    /**
     * 
     * find user
     * 
     * @param  string $name
     * 
     * @return array|bool
     * 
     */
    public function getUserByName($name)
    {
        $users = Db::getDb()->select($this->table)->where('username', $name)->limit(1)->query();

        return $users->fetch(\PDO::FETCH_ASSOC);
    }

// ------------------------------------------------------------------------
    /**
     * 
     * find user by Fd
     *
     * @param  int $fd
     * 
     * @return array|bool
     * 
     */
    public function getUserByFd($fd)
    {
        $users = Db::getDb()->select($this->table)->where('fd', $fd)->limit(1)->query();

        return $users->fetch(\PDO::FETCH_ASSOC);
    }

// ------------------------------------------------------------------------
    /**
     * add user
     * 
     * @param array $user
     * 
     * @return  int
     * 
     */
    public function addUser(array $user)
    {
        return Db::getDb()->insert($this->table, $user);
    }

// ------------------------------------------------------------------------
    /**
     * 
     * delete an user
     * 
     * @param  int $id
     * 
     */
    public function delUser($id)
    {
        Db::getDb()->delete($this->table, $id);
    }

// ------------------------------------------------------------------------
    public function existsUser($search)
    {
        $rs = Db::getDb()->select($this->table)->where('name', $search)->orClause('fd', $search)->limit(1)->query();

        return ($rs != NULL);
    }

// ------------------------------------------------------------------------
    /**
     * 
     * foo
     *     
     * @param  array  $array
     * 
     * @param  string $str
     * 
     * @return array
     * 
     */
    public function foobar(array $array, $str = "test")
    {
        echo $str, "\n"; //do something

        return $array;
    }

// ------------------------------------------------------------------------

    /**
     * 
     * singelon method, call class method by static way(with '_')
     *  
     * @param string $func
     * 
     * @return mixed
     * 
     */
    public static function __callStatic($func, $arguments)
    {
        static $usr = null;

        if (! $usr instanceof User) {
            $usr  = new self();
        }

        $func = substr($func, 1);

        if (!method_exists($usr, $func)) {
            throw new \Exception("Error calling static method: $func", 1);
        }

        return call_user_func_array(array($usr, $func), $arguments);
    }
}
