# Laravel Flysystem Service #

[![Software License](https://img.shields.io/packagist/l/danhunsaker/laravel-flysystem-service.svg?style=flat-square)](LICENSE)
[![Gitter](https://img.shields.io/gitter/room/danhunsaker/laravel-flysystem-service.svg?style=flat-square)](https://gitter.im/danhunsaker/laravel-flysystem-service)

[![Latest Stable Version](https://img.shields.io/packagist/v/danhunsaker/laravel-flysystem-service.svg?label=stable&style=flat-square)](https://github.com/danhunsaker/laravel-flysystem-service/releases)
[![Latest Unstable Version](https://img.shields.io/packagist/vpre/danhunsaker/laravel-flysystem-service.svg?label=unstable&style=flat-square)](https://github.com/danhunsaker/laravel-flysystem-service)
[![Build Status](https://img.shields.io/travis/danhunsaker/laravel-flysystem-service.svg?style=flat-square)](https://travis-ci.org/danhunsaker/laravel-flysystem-service)
[![Codecov](https://img.shields.io/codecov/c/github/danhunsaker/laravel-flysystem-service.svg?style=flat-square)](https://codecov.io/gh/danhunsaker/laravel-flysystem-service)
[![Total Downloads](https://img.shields.io/packagist/dt/danhunsaker/laravel-flysystem-service.svg?style=flat-square)](https://packagist.org/packages/danhunsaker/laravel-flysystem-service)

Registers recognized Flysystem adapters with Laravel automatically.

This lets you use other adapters without having to write your own service
providers to load them properly.  It automatically detects which adapters are
available, and registers only the ones actually installed.  It also detects
whether the [Eventable](https://github.com/thephpleague/flysystem-eventable-filesystem)
version of Flysystem is available, and if so, it switches to it, letting you
listen in on Flysystem [events](http://event.thephpleague.com/) and affect them
accordingly.

> Note: This package only recognizes the adapters officially supported by
> [The PHP League](https://github.com/thephpleague?query=flysystem)
> (except AWS-S3-v2) - for other adapters, install
> [danhunsaker/laravel-flysystem-others](https://github.com/danhunsaker/laravel-flysystem-others)
> instead (it will pull in this package as a dependency).

## Installation ##

The usual methods for using [Composer](https://getcomposer.org) apply here:

    composer require danhunsaker/laravel-flysystem-service

You do still have to register one service, but only one, and at least you don't
have to write it.  Be sure to **REPLACE** the
`Illuminate\Filesystem\FilesystemServiceProvider::class` line with the new one:

```php
// In config/app.php

    'providers' => [
        // ...
        Danhunsaker\Laravel\Flysystem\FlysystemServiceProvider::class,
        // ...
    ],
```

## Setup ##

You can get example definitions for all supported filesystem drivers by
publishing the replacement `filesystems` config - just run the following Artisan
command:

```
php artisan vendor:publish --provider=Danhunsaker\\Laravel\\Flysystem\\FlysystemServiceProvider --force
```

The `--force` flag is required to overwrite the existing `filesystems` config
that ships with Laravel.  You can also rename the existing file, then run the
command without the `--force` flag, if you'd like to preserve the existing
contents for transfer to the new file.

### Cache Decorator ###

Flysystem provides support for adding decorators to filesystem adapters,
complete with an abstract implementation that other implementations can extend,
reducing the number of methods they have to implement themselves if they don't
particularly care about all of them.  They also provide a complete decorator
that provides support for caching metadata, which can greatly speed up several
operations on slow filesystems, such as cloud storage.

Since this cache decorator is one of the official PHP League packages designed
to be used with Flysystem, this package supports it as well.  Ensure you have
`league/flysystem-cached-adapter`, then simply add a `cache` array to your drive
definition.  Multiple cache drivers are supported directly, and each has unique
options you can configure alongside it, so we'll break those down, below.

> A [library](https://github.com/madewithlove/illuminate-psr-cache-bridge) has
> been written for wrapping the Laravel cache implementation for use with PSR-6
> consumers (such as the aforementioned `Psr6Cache` driver), but it hasn't (as
> of this writing) been published to Packagist for use by third parties.  Once
> it becomes available, this package will be updated to use it to provide the
> use of Laravel's own caching system with the cache decorator.

#### Flysystem Adapter ####

Store the cached data in a file on one of the disks defined in your config.

```php
    'cache' => [
        'driver' => 'adapter',
        'disk'   => 'local',
        'file'   => 'flysystem.cache',
        'expire' => 300,
    ],
```

#### Memcached ####

Store the data on a Memcache server.

```php
    'cache' => [
        'driver' => 'memcached',
        'host'   => 'localhost',
        'port'   => 11211,
        'key'    => 'flysystem',
        'expire' => 300,
    ],
```

#### Memory ####

Just store the cached data in a class instance ('application memory').  When the
application shuts down, the cache will be lost.

```php
    'cache' => [
        'driver' => 'memory',
    ],
```

#### No Op ####

Don't store the cached data at all.  Essentially the same as not providing a
`cache` array at all.

> Note: This driver **does not actually cache any data**.

```php
    'cache' => [
        'driver' => 'noop',
    ],
```

#### Redis ####

Store the cached data on a Redis server.  Specify a Redis connection name from
your `database` config.

```php
    'cache' => [
        'driver'     => 'redis',
        'connection' => 'default',
        'key'        => 'flysystem',
        'expire'     => 300,
    ],
```

#### Stash ####

Store the cached data using the Stash caching framework.  This is easily the
most complex cache driver supported here.  Each `backend` is the full class name
of a Stash cache driver, and the `options` array varies between which one you
choose to use.  Alternately, you can set `backend` to a preconfigured instance
of the driver, which is useful in cases such as the `Composite` driver, which is
otherwise unsupported.  More information on these options is available on
[the Stash site](http://www.stashphp.com/Drivers.html).

```php
    'cache' => [
        'driver'  => 'stash',
        'backend' => 'Stash\Driver\Filesystem',
        'options' => [
            'dirSplit'        => 500,
            'path'            => storage_path('stash'),
            'filePermissions' => 0660,
            'dirPermissions'  => 0770,
        ],
        'key'     => 'flysystem',
        'expire'  => 300,
    ],
```

```php
        'backend' => 'Stash\Driver\Sqlite',
        'options' => [
            'extension'       => 'pdo',
            'version'         => 3,
            'nesting'         => 0,
            'path'            => storage_path('stash.db'),
            'filePermissions' => 0660,
            'dirPermissions'  => 0770,
        ],
```

```php
        'backend' => 'Stash\Driver\Apc',
        'options' => [
            'ttl'       => 3600,
            'namespace' => 'stash',
        ],
```

```php
        'backend' => 'Stash\Driver\Memcache',
        'options' => [
            'servers'   => ['localhost', '11211'],
            'extension' => 'memcached',
            // Plus any other options Memcache might want...
        ],
```

```php
        'backend' => 'Stash\Driver\Redis',
        'options' => [
            'servers' => ['localhost', '6379'],
        ],
```

```php
        'backend' => 'Stash\Driver\Ephemeral',
```

#### Use An Instance ####

You can also pass a preconfigured instance of your preferred cache driver
instead of a driver name, if you like.  This is useful for third-party adapters,
and for using external libraries through the `Psr6Cache` adapter.

## Contributions ##

Pull requests, bug reports, and so forth are all welcome on [GitHub][].

Security issues should be reported directly to [danhunsaker (plus) laraflyserv
(at) gmail (dot) com](mailto:danhunsaker+laraflyserv@gmail.com).

And head to [GitHub][] for everything else.

[GitHub]:https://github.com/danhunsaker/laravel-flysystem-service
