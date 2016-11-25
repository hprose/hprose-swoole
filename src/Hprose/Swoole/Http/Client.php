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
 * Hprose/Swoole/Http/Client.php                          *
 *                                                        *
 * hprose swoole http client library for php 5.3+         *
 *                                                        *
 * LastModified: Nov 25, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Http;

use stdClass;
use Exception;
use Hprose\Future;
use swoole_http_client;

class Client extends \Hprose\Client {
    public $type;
    public $host = '';
    public $ip = '';
    public $port = 80;
    public $ssl = false;
    public $keepAlive = true;
    public $keepAliveTimeout = 300;
    public $poolTimeout = 30000;
    public $maxPoolSize = 10;
    public $header = array();
    private $trans;
    public function __construct($uris = null) {
        parent::__construct($uris);
        $this->trans = new Transporter($this);
    }
    public function setHeader($name, $value) {
        $lname = strtolower($name);
        if ($lname != 'content-type' &&
            $lname != 'content-length' &&
            $lname != 'host') {
            if ($value) {
                $this->header[$name] = $value;
            }
            else {
                unset($this->header[$name]);
            }
        }
    }
    public function setKeepAlive($keepAlive = true) {
        $this->keepAlive = $keepAlive;
        $this->header['Connection'] = $keepAlive ? 'keep-alive' : 'close';
        if ($keepAlive) {
            $this->header['Keep-Ailve'] = $this->keepAliveTimeout;
        }
        else {
            unset($this->header['Keep-Ailve']);
        }
    }
    public function isKeepAlive() {
        return $this->keepAlive;
    }
    public function setKeepAliveTimeout($timeout) {
        $this->keepAliveTimeout = $timeout;
        if ($this->keepAlive) {
            $this->header['Keep-Ailve'] = $timeout;
        }
    }
    public function getKeepAliveTimeout() {
        return $this->keepAliveTimeout;
    }
    public function setMaxPoolSize($value) {
        $this->maxPoolSize = $value;
    }
    public function getMaxPoolSize() {
        return $this->maxPoolSize;
    }
    public function setPoolTimeout($value) {
        $this->poolTimeout = $value;
    }
    public function getPoolTimeout() {
        return $this->poolTimeout;
    }
    public function getHost() {
        return $this->host;
    }
    public function getPort() {
        return $this->port;
    }
    public function isSSL() {
        return $this->ssl;
    }
    protected function setUri($uri) {
        parent::setUri($uri);
        $p = parse_url($uri);
        if ($p) {
            switch (strtolower($p['scheme'])) {
                case 'http':
                    $this->host = $p['host'];
                    $this->port = isset($p['port']) ? $p['port'] : 80;
                    $this->path = isset($p['path']) ? $p['path'] : '/';
                    $this->ssl = false;
                    break;
                case 'https':
                    $this->host = $p['host'];
                    $this->port = isset($p['port']) ? $p['port'] : 443;
                    $this->path = isset($p['path']) ? $p['path'] : '/';
                    $this->ssl = true;
                    break;
                default:
                    throw new Exception("Only support http and https scheme");
            }
        }
        else {
            throw new Exception("Can't parse this uri: " . $uri);
        }
        $this->header['Host'] = $this->host;
        $this->header['Connection'] = $this->keepAlive ? 'keep-alive' : 'close';
        if ($this->keepAlive) {
            $this->header['Keep-Ailve'] = $this->keepAliveTimeout;
        }
        if (filter_var($this->host, FILTER_VALIDATE_IP) === false) {
            $ip = gethostbyname($this->host);
            if ($ip === $this->host) {
                throw new Exception('DNS lookup failed');
            }
            else {
                $this->ip = $ip;
            }
        }
        else {
            $this->ip = $this->host;
        }
    }
    protected function wait($interval, $callback) {
        $future = new Future();
        swoole_timer_after($interval * 1000, function() use ($future, $callback) {
            Future\sync($callback)->fill($future);
        });
        return $future;
    }
    protected function sendAndReceive($request, stdClass $context) {
        $future = new Future();
        $this->trans->sendAndReceive($request, $future, $context);
        if ($context->oneway) {
            $future->resolve(null);
        }
        return $future;
    }
}
