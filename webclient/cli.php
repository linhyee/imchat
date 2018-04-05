<?php
// echo ENOTSOCK;
// exit;
$host ="127.0.0.1";
$port = 9527;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

if ($socket < 0) {
	die("Unable to create socket\n");
}

$ret = socket_connect($socket, $host, $port);
if (!$ret) {
	die("Unable to connect socket\n");
}
for ($i = 0; $i<5; $i++) {
	$str = str_repeat($i, 10). "\0";
	if ( ($ret = socket_send($socket, $str, strlen($str), 0)) < 0) {
		echo socket_last_error($ret), "\n";
		var_dump('abc');
	}
	var_dump($ret);
}

for ($i =0; $i < 5; $i++) {
	$buf = '';
	$m = socket_recv($socket, $buf, 20000, 0);

	printf("%s\n", $buf);
	printf("%d\n", $m);
}

sleep(5);

socket_close($socket);
