<?php
require_once "../vendor/autoload.php";

use Hprose\Swoole\Server;

$server = new Server("tcp://0.0.0.0:2016");
$server->publish('time');
$server->on('workerStart', function($serv) use ($server) {
    $serv->tick(1000, function() use ($server) {
        $server->push('time', microtime(true));
    });
});
$server->start();
