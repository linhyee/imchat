<?php
include 'inetserver.php';
include 'baseserver.php';
include 'wsserver.php';
include 'server.php';
include 'chat.php';
include 'log.php';
include 'config.php';

use server\Server;
use server\Wsserver;

Wsserver::run();
