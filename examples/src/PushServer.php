<?php
require_once "../vendor/autoload.php";

use Hprose\Swoole\Server;

function hello($name, $context) {
    $context->clients->push("news", "this is a pushed message: $name");
    $context->clients->broadcast("news", array('x' => 1, 'y' => 2));
    $fdinfo = $context->server->connection_info($context->socket);
    return "Hello $name! -- {$fdinfo['remote_ip']}";
}

$server = new Server("tcp://0.0.0.0:1980");
$server->publish('news');
$server->onSubscribe = function($topic, $id, $service) {
    error_log("client $id subscribe $topic on " . microtime(true));
};
$server->onUnsubscribe = function($topic, $id, $service) {
    error_log("client $id unsubscribe $topic on " . microtime(true));
};
$server->addFunction('hello', array('passContext' => true));
$server->start();
