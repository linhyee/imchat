<?php
error_reporting(1);

$table = new Swoole\Table(1024);
$serv  = new Swoole\Server("192.168.66.10", 8080);

$table->column('fd', Swoole\Table::TYPE_INT);
$table->column('login_time', Swoole\Table::TYPE_STRING, 24);
$table->create();

$serv->set(array(
	'worker_num' => 8,
	'daemonize' => false,
));

$serv->table = $table;

$serv->on('connect', function ($serv, $fd) use ($conns)
{
	$usr = array(
		'fd'         => $fd,
		'name'       => 'u'. $fd . substr(md5($fd), 0, 4),
		'login_time' => date('Y-m-d H:i:s', time()),
	);

	$serv->table->set($fd, $usr);

	foreach ($serv->connections as $cfd) {
		if ($cfd != $fd) {
			$serv->send($cfd, $usr['name'] . '加入了房间!'. '<'. $usr['login_time'] .'>');
		}
	}
	echo "client $fd connected.\n";

});

$serv->on('receive', function ($serv, $fd, $fromId, $data)
{
	$serv->send($fd, "from serv:" . $data);
});

$serv->on('close', function ($serv, $fd)
{
	echo "client $fd close.", "\n";
});

$serv->start();
