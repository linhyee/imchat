<?php 
require "Email.php";

//__construct
function sendMail($to, $title, $content, $sign = '')
{
	$email = new Email();

	$email->initialize(array(
		'protocol'  => 'smtp',
		'mailtype'  => 'html',
		'smtp_host' => 'smtp.zmail300.cn',
		'smtp_user' => 'sales@adisbodyjewelry.com',
		'smtp_pass' => '123456789adis*',
		'smtp_port' => '25',
		'charset'   => 'utf-8',
		'wordwrap'  => TRUE,
	));

	$r = $email->clear()
		->to($to)
		->from('sales@adisbodyjewelry.com', 'adisbodyjewelry')
		->subject($title)
		->message($content)
		->send();

	echo $email->print_debugger();

	return $r;
}

$r = sendMail('714480119@qq.com', 'test', 'test', 'what');
var_dump($r);