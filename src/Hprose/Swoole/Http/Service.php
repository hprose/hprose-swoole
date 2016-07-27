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
 * Hprose/Swoole/Http/Service.php                         *
 *                                                        *
 * hprose swoole http service library for php 5.3+        *
 *                                                        *
 * LastModified: Jul 27, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Http;

use Hprose\Swoole\Timer;

class Service extends \Hprose\Http\Service {
    const ORIGIN = 'origin';
    const MAX_PACK_LEN = 0x200000;
    public function __construct() {
        parent::__construct();
        $this->timer = new Timer();
    }
    public function header($name, $value, $context) {
        $context->response->header($name, $value);
    }
    public function getAttribute($name, $context) {
        return $context->request->header[$name];
    }
    public function hasAttribute($name, $context) {
        return array_key_exists($name, $context->request->header);
    }
    protected function readRequest($context) {
        return $context->request->rawContent();
    }
    public function writeResponse($data, $context) {
        $response = $context->response;
        $len = strlen($data);
        if ($len <= self::MAX_PACK_LEN) {
            $response->end($data);
        }
        else {
            for ($i = 0; $i < $len; $i += self::MAX_PACK_LEN) {
                if (!$response->write(substr($data, $i, min($len - $i, self::MAX_PACK_LEN)))) {
                    return false;
                }
            }
            $response->end();
        }
        return true;
    }
    public function isGet($context) {
        return $context->request->server['request_method'] == 'GET';
    }
    public function isPost($context) {
        return $context->request->server['request_method'] == 'POST';
    }
    public function httpHandle($server) {
        $server->on('request', array($this, 'handle'));
    }
}
