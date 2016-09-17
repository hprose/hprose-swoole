<?php
require_once "../vendor/autoload.php";

use Hprose\Swoole\Server;

function hello($name) {
    return "Hello $name!";
}
$server = new Server("http://0.0.0.0:8086");
$server->setErrorTypes(E_ALL);
$server->setDebugEnabled();
$server->setCrossDomainEnabled();
$server->addFunction('hello');
$server->start();
