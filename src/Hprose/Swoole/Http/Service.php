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
 * LastModified: Aug 20, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Http;

use Hprose\Swoole\Timer;

class Service extends \Hprose\Http\Service {
    const ORIGIN = 'origin';
    const MAX_PACK_LEN = 0x200000;
    private $crossDomainXmlFile = null;
    private $crossDomainXmlContent = null;
    private $clientAccessPolicyXmlFile = null;
    private $clientAccessPolicyXmlContent = null;
    private $lastModified;
    private $etag;
    public function __construct() {
        parent::__construct();
        $this->lastModified = gmstrftime("%a, %d %b %Y %T %Z", time());
        $this->etag = '"' . dechex(mt_rand()) . ':' . dechex(mt_rand()) . '"';
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
    public function getCrossDomainXmlFile() {
        return $this->crossDomainXmlFile;
    }
    public function setCrossDomainXmlFile($value) {
        $this->crossDomainXmlFile = $value;
        $this->crossDomainXmlContent = file_get_contents($value);
    }
    public function getCrossDomainXmlContent() {
        return $this->crossDomainXmlContent;
    }
    public function setCrossDomainXmlContent($value) {
        $this->crossDomainXmlFile = null;
        $this->crossDomainXmlContent = $value;
    }
    public function getClientAccessPolicyXmlFile() {
        return $this->clientAccessPolicyXmlFile;
    }
    public function setClientAccessPolicyXmlFile($value) {
        $this->clientAccessPolicyXmlFile = $value;
        $this->clientAccessPolicyXmlContent = file_get_contents($value);
    }
    public function getClientAccessPolicyXmlContent() {
        return $this->clientAccessPolicyXmlContent;
    }
    public function setClientAccessPolicyXmlContent($value) {
        $this->clientAccessPolicyXmlFile = null;
        $this->clientAccessPolicyXmlContent = $value;
    }
    private function crossDomainXmlHandler($request, $response) {
        if ($request->server['path_info'] === '/crossdomain.xml') {
            if ($request->header['if-modified-since'] === $this->lastModified &&
                $request->headers['if-none-match'] === $this->etag) {
                $response->status(304);
            }
            else {
                $response->header('Last-Modified', $this->lastModified);
                $response->header('Etag', $this->etag);
                $response->header('Content-Type', 'text/xml');
                $response->header('Content-Length', strlen($this->crossDomainXmlContent));
                $response->write($this->crossDomainXmlContent);
            }
            $response->end();
            return true;
        }
        return false;
    }
    private function clientAccessPolicyXmlHandler($request, $response) {
        if ($request->server['path_info'] === '/clientaccesspolicy.xml') {
            if ($request->header['if-modified-since'] === $this->lastModified &&
                $request->headers['if-none-match'] === $this->etag) {
                $response->status(304);
            }
            else {
                $response->header('Last-Modified', $this->lastModified);
                $response->header('Etag', $this->etag);
                $response->header('Content-Type', 'text/xml');
                $response->header('Content-Length', strlen($this->clientAccessPolicyXmlContent));
                $response->write($this->clientAccessPolicyXmlContent);
            }
            $response->end();
            return true;
        }
        return false;
    }
    public function handle($request = null, $response = null) {
        if ($this->clientAccessPolicyXmlContent !== null &&
            $this->clientAccessPolicyXmlHandler($request, $response)) {
            return $response;
        }
        if ($this->crossDomainXmlContent !== null &&
            $this->crossDomainXmlHandler($request, $response)) {
            return $response;
        }
        return parent::handle($request, $response);
    }
    public function httpHandle($server) {
        $server->on('request', array($this, 'handle'));
    }
}
