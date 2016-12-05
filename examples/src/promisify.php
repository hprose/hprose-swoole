<?php
require_once "../vendor/autoload.php";

use \Hprose\Promise;

Promise\co(function() {
    list($host, $ip) = yield Promise\promisify('swoole_async_dns_lookup')("www.baidu.com");
    $client = new swoole_http_client($ip, 80);
    $cli = yield Promise\promisify([$client, 'get'])('/');
    var_dump($cli->body);
});