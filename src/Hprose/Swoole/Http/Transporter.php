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
 * Hprose/Swoole/Http/Transporter.php                     *
 *                                                        *
 * hprose http Transporter class for php 5.3+             *
 *                                                        *
 * LastModified: Sep 17, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\Http;

use stdClass;
use Exception;
use Hprose\TimeoutException;
use swoole_http_client;

class Transporter {
    public $client;
    public $uri;
    public $size = 0;
    public $pool = array();
    public $requests = array();
    public $cookies = array();
    public function __construct(Client $client) {
        $this->client = $client;
        $this->uri = $client->uri;
    }
    public function create() {
        $client = $this->client;
        $conn = new swoole_http_client($client->host, $client->port, $client->ssl);
        $this->size++;
        return $conn;
    }
    public function fetch() {
        while (!empty($this->pool)) {
            $conn = array_pop($this->pool);
            if ($conn->isConnected()) {
                swoole_timer_clear($conn->timer);
                $conn->timer = null;
                return $conn;
            }
        }
        return null;
    }
    public function recycle($conn) {
        if ($this->client->keepAlive) {
            if (array_search($conn, $this->pool, true) === false) {
                $conn->timer = swoole_timer_after($this->client->poolTimeout,
                function() use ($conn) {
                    swoole_timer_clear($conn->timer);
                    if ($conn->isConnected()) {
                        $conn->close();
                    }
                });
                $this->pool[] = $conn;
            }
        }
        else {
            if ($conn->isConnected()) {
                $conn->close();
            }
        }
    }
    public function clean($conn) {
        if (isset($conn->timeoutId)) {
            swoole_timer_clear($conn->timeoutId);
            unset($conn->timeoutId);
        }
    }
    public function sendNext($conn) {
        if (!empty($this->requests)) {
            $request = array_pop($this->requests);
            $request[] = $conn;
            call_user_func_array(array($this, "send"), $request);
        }
        else {
            $this->recycle($conn);
        }
    }
    public function send($request, $future, $context, $conn) {
        $self = $this;
        $timeout = $context->timeout;
        if ($timeout > 0) {
            $conn->timeoutId = swoole_timer_after($timeout,
            function() use ($self, $future, $conn) {
                $future->reject(new TimeoutException('timeout'));
                if ($conn->isConnected()) {
                    $conn->close();
                }
            });
        }
        $conn->setCookies($this->cookies);
        $conn->post($this->client->path, $request,
        function($conn) use ($self, $future) {
            $self->cookies = $conn->cookies;
            if ($conn->errCode === 0) {
                if ($conn->statusCode == 200) {
                    $future->resolve($conn->body);
                }
                else {
                    $future->reject(new Exception($conn->body));
                }
            }
            else {
                $future->reject(new Exception(socket_strerror($conn->errCode)));
            }
            $self->sendNext($conn);
        });
    }
    public function sendAndReceive($request, $future, stdClass $context) {
        $conn = $this->fetch();
        if ($conn !== null) {
            $this->send($request, $future, $context, $conn);
        }
        else if ($this->size < $this->client->maxPoolSize) {
            $self = $this;
            $conn = $this->create();
            $conn->on('error', function($conn) use ($future) {
                $future->reject(new Exception(socket_strerror($conn->errCode)));
            });
            $conn->on('close', function($conn) use ($self, $future) {
                $self->clean($conn);
                if ($conn->errCode !== 0) {
                    $future->reject(new Exception(socket_strerror($conn->errCode)));
                }
                else {
                    $future->reject(new Exception('The server is closed.'));
                }
                $self->size--;
            });
            $conn->set(array('keep_alive' => $this->client->keepAlive));
            $conn->setHeaders($this->client->header);
            $self->send($request, $future, $context, $conn);
        }
        else {
            $this->requests[] = array($request, $future, $context);
        }
    }

}