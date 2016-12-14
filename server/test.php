<?php 
require "Email.php";

//__construct
function sendMail($to, $title, $content, $sign = '')
{
	$email = new Email();

	$email->initialize(array(
		'protocol'  => 'smtp',
		'mailtype'  => 'html',
		'smtp_host' => '',
		'smtp_user' => '',
		'smtp_pass' => '',
		'smtp_port' => '',
		'charset'   => 'utf-8',
		'wordwrap'  => TRUE,
	));

	$r = $email->clear()
		->to($to)
		->from('', '标题')
		->subject($title)
		->message($content)
		->send();

	echo $email->print_debugger();

	return $r;
}

$r = sendMail('714480119@qq.com', 'test', 'test', 'what');
var_dump($r);