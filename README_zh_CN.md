<p align="center"><img src="http://hprose.com/banner.@2x.png" alt="Hprose" title="Hprose" width="650" height="200" /></p>

# Hprose for Swoole

[![Build Status](https://travis-ci.org/hprose/hprose-swoole.svg?branch=master)](https://travis-ci.org/hprose/hprose-swoole)
![Supported PHP versions: 5.3 .. 7.1](https://img.shields.io/badge/php-5.3~7.1-blue.svg)
[![Packagist](https://img.shields.io/packagist/v/hprose/hprose-swoole.svg)](https://packagist.org/packages/hprose/hprose-swoole)
[![Packagist Download](https://img.shields.io/packagist/dm/hprose/hprose-swoole.svg)](https://packagist.org/packages/hprose/hprose-swoole)
[![License](https://img.shields.io/packagist/l/hprose/hprose-swoole.svg)](https://packagist.org/packages/hprose/hprose-swoole)

## 简介

*Hprose* 是高性能远程对象服务引擎（High Performance Remote Object Service Engine）的缩写。

它是一个先进的轻量级的跨语言跨平台面向对象的高性能远程动态通讯中间件。它不仅简单易用，而且功能强大。你只需要稍许的时间去学习，就能用它轻松构建跨语言跨平台的分布式应用系统了。

*Hprose* 支持众多编程语言，例如：

* AAuto Quicker
* ActionScript
* ASP
* C++
* Dart
* Delphi/Free Pascal
* dotNET(C#, Visual Basic...)
* Golang
* Java
* JavaScript
* Node.js
* Objective-C
* Perl
* PHP
* Python
* Ruby
* ...

通过 *Hprose*，你就可以在这些语言之间方便高效的实现互通了。

本项目是基于 swoole 扩展的 Hprose 的 PHP 语言版本实现。

Hprose 2.0 更多文档: https://github.com/hprose/hprose-php/wiki 

## 安装

### 通过下载源码
[下载地址](https://github.com/hprose/hprose-swoole/archive/master.zip)

### 通过 composer
```javascript
{
    "require": {
        "hprose/hprose-swoole": "dev-master"
    }
}
```

## 使用

你首先需要安装 [swoole](http://www.swoole.com/)。[swoole](https://github.com/swoole/swoole-src) 被支持的最低版本为 1.8.8.

### 服务器

Hprose for PHP 使用起来很简单，例如：

`http_server.php`
```php
<?php
    require_once "vendor/autoload.php";

    use Hprose\Swoole\Server;

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new Server('http://0.0.0.0:80/');
    $server->addFunction('hello');
    $server->start();
```

`tcp_server.php`
```php
<?php
    require_once "vendor/autoload.php";

    use Hprose\Swoole\Server;

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new Server('tcp://0.0.0.0:2016');
    $server->addFunction('hello');
    $server->start();
```

`unix_server.php`
```php
<?php
    require_once "vendor/autoload.php";

    use Hprose\Swoole\Server;

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new Server('unix:/tmp/my.sock');
    $server->addFunction('hello');
    $server->start();
```

`websocket_server.php`
```php
<?php
    require_once "vendor/autoload.php";

    use Hprose\Swoole\Server;

    function hello($name) {
        return 'Hello ' . $name;
    }

    $server = new Server('ws://0.0.0.0:8000/');
    $server->addFunction('hello');
    $server->start();
```

WebSocket 服务器同时也是 HTTP 服务器，所以既可以用 WebSocket 客户端访问，也可以用 HTTP 客户端访问。

### Client

然后你可以创建一个 Hprose 的客户端来调用它了，就像这样：

`http_client.php`
```php
<?php
    require_once "vendor/autoload.php";

    use Hprose\Swoole\Client;

    $client = new Client('http://127.0.0.1/');
    $client->hello('World')->then(function($result) {
        echo $result;
    }, function($e) {
        echo $e;
    });
    $client->hello('World 0', function() {
        echo "ok\r\n";
    });
    $client->hello('World 1', function($result) {
        echo $result . "\r\n";
    });
    $client->hello('World 2', function($result, $args) {
        echo $result . "\r\n";
    });
    $client->hello('World 3', function($result, $args, $error) {
        echo $result . "\r\n";
    });
```

`tcp_client.php`
```php
<?php
    require_once "vendor/autoload.php";

    use Hprose\Swoole\Client;

    $client = new Client('tcp://127.0.0.1:2016');
    $client->hello('World')->then(function($result) {
        echo $result;
    }, function($e) {
        echo $e;
    });
    $client->hello('World 0', function() {
        echo "ok\r\n";
    });
    $client->hello('World 1', function($result) {
        echo $result . "\r\n";
    });
    $client->hello('World 2', function($result, $args) {
        echo $result . "\r\n";
    });
    $client->hello('World 3', function($result, $args, $error) {
        echo $result . "\r\n";
    });
```

直接调用的结果是一个 Promise 对象，也可以在调用时直接指定回调函数，回调函数支持 0 - 3 个参数。它们分别表示：

|参数    |解释                                                       |
|-------:|:---------------------------------------------------------|
|结果    |就是服务器端的返回结果，如果没有结果则为 null。                  |
|调用参数|是一个包含了调用参数的数组，如果调用没有参数，则为 0 个元素的数组。 |
|错误    |一个 Exception 对象，如果没有错误则为 null。                   |
