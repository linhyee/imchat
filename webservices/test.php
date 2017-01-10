<?php 
namespace webservices;

include 'db.php';
include 'user.php';
include 'config.php';
include 'controller.php';
include 'jwt.php';
include 'http.php';
include 'email.php';
include 'thread.php';

/**
 * 
 * @package webservices.test
 * @author mrline <714480119@qq.com>
 */
class Test
{

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

        $rs = User::_existsUser('dave');
        var_dump($rs);
    }

    /**
     * @test
     * 
     * @noreturn
     */
    public static function httptest()
    {
        $rs = Http::getHttp()->get('http://www.baidu.com');
        // echo $rs['body'];

        $rs = Http::getHttp()->post(array('fd'=>1, 'time'=>time()))->submit("http://www.baidu.com");

        print_r($rs);
    }

    /**
     * @test
     * 
     * @noreturn
     */
    public static function emailtest($to, $title, $content)
    {
        $email = new \Email();

        $email->initialize(array(
            'protocol'  => 'smtp',
            'mailtype'  => 'html',
            'smtp_host' => 'smtp.zmail300.cn',
            'smtp_user' => 'sales@adisbodyjewelry.com',
            'smtp_pass' => '***',
            'smtp_port' => '25',
            'charset'   => 'utf-8',
            'wordwrap'  => TRUE,
        ));

        $r = $email->clear()
            ->to($to)
            ->from('sales@adisbodyjewelry.com', '***')
            ->subject($title)
            ->message($content)
            ->send();

        echo $email->print_debugger();
    }
}

// Test::emailtest('714480119@qq.com', 'test', 'hello world!');
Test::httptest();
