<?php
namespace webservices;

include 'db.php';
include 'user.php';
include 'config.php';
include 'controller.php';

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
}

Api::run();
