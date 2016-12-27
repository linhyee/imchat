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
        // var_dump($_REQUEST);
        // echo $name;
        echo 'what! 你个老伙计~';
    }

    public function action404()
    {
        header("HTTP/1.1 404 Not Found");exit;
    }
}