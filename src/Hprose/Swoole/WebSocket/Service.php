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
 * Hprose/Swoole/WebSocket/Service.php                    *
 *                                                        *
 * hprose swoole websocket service library for php 5.3+   *
 *                                                        *
 * LastModified: Aug 20, 2016                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

namespace Hprose\Swoole\WebSocket;

use stdClass;
use Exception;
use Throwable;
use Hprose\Swoole\Timer;

class Service extends \Hprose\Swoole\Http\Service {
    public $onAccept = null;
    public $onClose = null;
    public function __construct() {
        parent::__construct();
        $this->timer = new Timer();
    }
    /*private*/ function wsPush($server, $fd, $data) {
        $dataLength = strlen($data);
        if ($dataLength <= self::MAX_PACK_LEN) {
            return $server->exist($fd) &&
                $server->push($fd, $data, WEBSOCKET_OPCODE_BINARY, true);
        }
        else {

            for ($i = 0; $i < $dataLength; $i += self::MAX_PACK_LEN) {
                $chunkLength = min($dataLength - $i, self::MAX_PACK_LEN);
                $chunk = substr($data, $i, $chunkLength);
                $finish = ($dataLength - $i === $chunkLength);
                if (!($server->exist($fd) &&
                    $server->push($fd, $chunk, WEBSOCKET_OPCODE_BINARY, $finish))) {
                    return false;
                }
            }
            return true;
        }
    }
    public function onMessage($server, $fd, $data) {
        $id = substr($data, 0, 4);
        $request = substr($data, 4);

        $context = new stdClass();
        $context->server = $server;
        $context->fd = $fd;
        $context->id = $id;
        $context->userdata = new stdClass();
        $self = $this;

        $this->userFatalErrorHandler = function($error)
                use ($self, $server, $fd, $id, $context) {
            $self->wsPush($server, $fd, $id . $self->endError($error, $context));
        };

        $response = $this->defaultHandle($request, $context);

        $response->then(function($response) use ($self, $server, $fd, $id) {
            $self->wsPush($server, $fd, $id . $response);
        });
    }
    public function wsHandle($server) {
        $self = $this;
        $buffers = array();
        $server->on('open', function ($server, $request) use ($self, &$buffers) {
            $fd = $request->fd;
            if (isset($buffers[$fd])) {
                unset($buffers[$fd]);
            }
            $context = new stdClass();
            $context->server = $server;
            $context->request = $request;
            $context->fd = $fd;
            $context->userdata = new stdClass();
            try {
                $onAccept = $self->onAccept;
                if (is_callable($onAccept)) {
                    call_user_func($onAccept, $context);
                }
            }
            catch (Exception $e) { $server->close($fd); }
            catch (Throwable $e) { $server->close($fd); }
        });
        $server->on('close', function ($server, $fd) use ($self, &$buffers) {
            if (isset($buffers[$fd])) {
                unset($buffers[$fd]);
            }
            $context = new stdClass();
            $context->server = $server;
            $context->fd = $fd;
            $context->userdata = new stdClass();
            try {
                $onClose = $self->onClose;
                if (is_callable($onClose)) {
                    call_user_func($onClose, $context);
                }
            }
            catch (Exception $e) {}
            catch (Throwable $e) {}
        });
        $server->on('message', function($server, $frame) use ($self, &$buffers) {
            if (isset($buffers[$frame->fd])) {
                if ($frame->finish) {
                    $data = $buffers[$frame->fd] . $frame->data;
                    unset($buffers[$frame->fd]);
                    $self->onMessage($server, $frame->fd, $data);
                }
                else {
                    $buffers[$frame->fd] .= $frame->data;
                }
            }
            else {
                if ($frame->finish) {
                    $self->onMessage($server, $frame->fd, $frame->data);
                }
                else {
                    $buffers[$frame->fd] = $frame->data;
                }
            }
        });
    }
}
