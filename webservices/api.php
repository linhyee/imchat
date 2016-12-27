<?php
namespace webservices;

include 'db.php';
include 'user.php';
include 'config.php';
include 'controller.php';
include 'jwt.php';


use webservices\Controller;

/**
 * 
 * @package  webservices.api
 * @author  mrlin <714480119@qq.com>
 */
class Api
{
    /**
     * 
     * run api
     *          
     * @noreturn
     * 
     */
    public static function run()
    {
        $c = new Controller;

        $a = filter_input(INPUT_GET, 'a');
        $m = 'action' . ucfirst($a);
        $m = method_exists($c, $m) ? $m : "action404";

        unset($_REQUEST['a']);

        call_user_func_array(array($c, $m), $_REQUEST);
    }

    /**
     * 
     * @test
     * 
     * @noreturn
     * 
     */
    public static function dbtest()
    {

        echo Db::getDb()->select('user')
            ->where('id', '1')
            ->andClause('name', 'mary')
            ->orClause('sex', 'male')
            ->limit(1, 2)
            ->orderBy('id', 'ASC')
            ->buildSql(), "\n";

        echo Db::getDb()->select('group')
            ->where('id', 2, '>')
            ->andClause('name', '%kate%', 'LIKE')
            ->orClause('date', '(2012, 2013)', 'BETWEEN')
            ->andClause('sex', '1', '=')
            ->limit(1, 10)
            ->orderBy('date', 'DESC')
            ->buildSql(), "\n";

        echo Db::getDb()->select('menu')
            ->join('rule', ' id=menu_id')
            ->where('id', 6)
            ->andClause('condition', 'abc')
            ->orClause('title', 'action')
            ->having('cnt', '>30')
            ->orderBy('create_time', 'ASC')
            ->limit(1,3)
            ->buildSql(), "\n";

        $rs = Db::getDb()->select('parts')->query();

        $rows = $rs->fetchColumn(1);
    }

    /**
     * @test
     * 
     * @noreturn
     * 
     */
    public static function jwttest()
    {
        $jwt = Jwt::encode('user:abc;pass:cde', '#2s@1');
        echo $jwt, "\n";

        $jwt = Jwt::decode($jwt, '#2s@1');
        echo $jwt, "\n";
    }

    /**
     * @test
     * 
     * @noreturn
     */
    public static function usertest()
    {
        $rs = User::_foobar(array('abc', 2), "what, 你个老秋伙计!");

        // $lastId = User::_addUser(array('fd' => 2, 'logintime' => 123423823423, 'type' => 1, 'username'=> 'kate', 'loginip' => '127.0.0.1'));
        // echo $lastId, "\n";

        $rs = User::_getUserByName('kate');
        var_dump($rs);
    }
}

Api::run();
