<?php
require_once "../vendor/autoload.php";

use Hprose\Swoole\Server;

function hello($name) {
    return "Hello $name!";
}
$server = new Server("ws://0.0.0.0:8088");
$server->setErrorTypes(E_ALL);
$server->setDebugEnabled();
$server->addFunction('hello');
$server->start();
