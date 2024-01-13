<?php 
namespace lib;

include 'db.php';
include 'user.php';
include 'config.php';
include 'controller.php';
include 'jwt.php';
include 'http.php';
include 'email.php';
include 'thread.php';
include 'array2xml.php';

/**
 * 
 * @package lib.test
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

        //download test
        for ($i = 1; $i <= 33; $i++) {
            $url  = 'http://tb2.bdstatic.com/tb/editor/images/face/i_f'.str_pad($i, 2, '0', STR_PAD_LEFT).'.png';
            $path = 'D:/vm/www/image/'. str_pad($i, 2, '0', STR_PAD_LEFT).'.png';

            Http::getHttp()->download($url)->save($path);
        }

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

    public static function arr2xmltest()
    {
        $input = array(
            'product' => array(
                '@id'  => 7,
                'name' => 'some string',
                'seo'  => 'some-string',
                'ean'  => '',
                'producer' => array(
                    'name' => null,
                    'photo' => '1.png'
                ),

                'stock' => 123,
                'trackstock' => 0,
                'new' => 0,
                'pricewithoutvat' => 1111,
                'price' => 1366.53,
                'discountpricenetto' => null,
                'discountprice' => null,
                'vatvalue' => 23,
                'currencysymbol' => 'PLN',
                '#description' => '',
                '#longdescription' => '',
                '#shortdescription' => '',

                'category' => array(
                    'photo' => '1.png',
                    'name' => 'test3',
                ),

                'staticattributes' => array(
                    'attributegroup' => array(
                        1 => array(
                            '@name' => 'attributes group',
                            'attribute' => array(
                                0 => array(
                                    'name' => 'second',
                                    'description' => '<p>desc2</p>',
                                    'file' => '',
                                ),
                                1 => array(
                                    'name' => 'third',
                                    'description' => '<p>desc3</p>',
                                    'file' => '',
                                ),
                            ),
                        ),
                    ),
                ),

                'attributes' => array(),

                'photos' => array(
                   'photo' => array(
                        0 => array(
                            '@mainphoto' => '1',
                            '%' => '1.png',
                        ),
                        1 => array(
                            '@mainphoto' => '0',
                            '%' => '2.png',
                        ),
                        2 => array(
                            '@mainphoto' => '0',
                            '%' => '3.png',
                        ),
                    ),
                ),
            ),
        );

        // $arr2xml = new Array2XML();
        // echo $arr2xml->buildXml($input, 'data'), "\r\n";

        echo Array2XML::createXML($input, 'data'), "\r\n";

        $foo = array(
          'Lighting_ProductType_LightsAndFixtures_BaseDiameter_@unitOfMeasure' => 'MM',
          'Lighting_ProductType_LightsAndFixtures_BaseDiameter_a_0_@unitOfMeasure' => 'MM',
          'Lighting_ProductType_LightsAndFixtures_BaseDiameter_a_0_%' => '12.2',
          'Lighting_ProductType_LightsAndFixtures_BaseDiameter_b_0_@unitOfMeasure' => '12.2',
          'Lighting_ProductType_LightsAndFixtures_BaseDiameter_b_0_%' => '12.2',
          'A_B_C_D_E_F' => 'hee!',
        );

        print_r(Array2XML::mapArrayData($foo));
    }
}

// Test::emailtest('714480119@qq.com', 'test', 'hello world!');
Test::httptest();
// Test::arr2xmltest();
