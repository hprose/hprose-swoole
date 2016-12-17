<p align="center"><img src="http://hprose.com/banner.@2x.png" alt="Hprose" title="Hprose" width="650" height="200" /></p>

# Hprose for Swoole

[![Build Status](https://travis-ci.org/hprose/hprose-swoole.svg?branch=master)](https://travis-ci.org/hprose/hprose-swoole)
![Supported PHP versions: 5.3 .. 7.1](https://img.shields.io/badge/php-5.3~7.1-blue.svg)
[![Packagist](https://img.shields.io/packagist/v/hprose/hprose-swoole.svg)](https://packagist.org/packages/hprose/hprose-swoole)
[![Packagist Download](https://img.shields.io/packagist/dm/hprose/hprose-swoole.svg)](https://packagist.org/packages/hprose/hprose-swoole)
[![License](https://img.shields.io/packagist/l/hprose/hprose-swoole.svg)](https://packagist.org/packages/hprose/hprose-swoole)

## Introduction

*Hprose* is a High Performance Remote Object Service Engine.

It is a modern, lightweight, cross-language, cross-platform, object-oriented, high performance, remote dynamic communication middleware. It is not only easy to use, but powerful. You just need a little time to learn, then you can use it to easily construct cross language cross platform distributed application system.

*Hprose* supports many programming languages, for example:

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

Through *Hprose*, You can conveniently and efficiently intercommunicate between those programming languages.

This project is the implementation of Hprose for PHP based on swoole.

More Documents for Hprose 2.0: https://github.com/hprose/hprose-php/wiki

## Installation

### Download Source Code
[Download Link](https://github.com/hprose/hprose-swoole/archive/master.zip)

### install by `composer`
```javascript
{
    "require": {
        "hprose/hprose-swoole": "dev-master"
    }
}
```

## Usage

You need to install [swoole](http://www.swoole.com/) first. The minimum version of [swoole](https://github.com/swoole/swoole-src) been supported is 1.8.8.

You also need to install [hprose-pecl](https://pecl.php.net/package/hprose) 1.6.5+.

### Server

Hprose for PHP is very easy to use.

You can create a standalone hprose http server like this:

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

The websocket server is also a http server.

### Client

Then you can create a hprose client to invoke it like this:

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

The result of invoking is a promise object, you can also specify the callback function after the arguments, the callback function supports 0 - 3 parameters:

|params   |comments                                                           |
|--------:|:------------------------------------------------------------------|
|result   |The result is the server returned, if no result, its value is null.|
|arguments|It is an array of arguments. if no argument, it is an empty array. |
|error    |It is an object of Exception, if no error, its value is null.      |
