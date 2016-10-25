<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * Hprose/Swoole/Server.php                               *
 *                                                        *
 * hprose swoole server library for php 5.3+              *
 *                                                        *
 * LastModified: Oct 25, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole;

class Server {
    private $server = null;
    public function __construct($uri, $mode = SWOOLE_BASE) {
        $p = parse_url($uri);
        if ($p) {
            switch (strtolower($p['scheme'])) {
                case 'ws':
                case 'wss':
                    $this->server = new \Hprose\Swoole\WebSocket\Server($uri, $mode);
                    break;
                case 'http':
                case 'https':
                    $this->server = new \Hprose\Swoole\Http\Server($uri, $mode);
                    break;
                case 'tcp':
                case 'tcp4':
                case 'tcp6':
                case 'ssl':
                case 'sslv2':
                case 'sslv3':
                case 'tls':
                case 'unix':
                    $this->server = new \Hprose\Swoole\Socket\Server($uri, $mode);
                    break;
                default:
                    throw new Exception("Can't support this scheme: {$p['scheme']}");
            }
        }
        else {
            throw new \Exception("Can't parse this url: " . $uri);
        }
    }
    public function __call($name, $args) {
        return call_user_func_array(array($this->server, $name), $args);
    }
    public function __set($name, $value) {
        $this->server->$name = $value;
    }
    public function __get($name) {
        return $this->server->$name;
    }
    public function __isset($name) {
        return isset($this->server->$name);
    }
    public function __unset($name) {
        unset($this->server->$name);
    }
}
