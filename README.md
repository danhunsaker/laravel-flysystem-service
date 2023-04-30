# Laravel Flysystem Service #

[![Software License](https://img.shields.io/packagist/l/danhunsaker/laravel-flysystem-service.svg?style=flat-square)](LICENSE)
[![Liberapay receiving](https://img.shields.io/liberapay/receives/danhunsaker.svg?style=flat-square)](https://liberapay.com/danhunsaker/)

[![Latest Stable Version](https://img.shields.io/packagist/v/danhunsaker/laravel-flysystem-service.svg?label=stable&style=flat-square)](https://github.com/danhunsaker/laravel-flysystem-service/releases)
[![Latest Unstable Version](https://img.shields.io/packagist/vpre/danhunsaker/laravel-flysystem-service.svg?label=unstable&style=flat-square)](https://github.com/danhunsaker/laravel-flysystem-service)
[![Build Status](https://img.shields.io/travis/danhunsaker/laravel-flysystem-service.svg?style=flat-square)](https://travis-ci.org/danhunsaker/laravel-flysystem-service)
[![Codecov](https://img.shields.io/codecov/c/github/danhunsaker/laravel-flysystem-service.svg?style=flat-square)](https://codecov.io/gh/danhunsaker/laravel-flysystem-service)
[![Total Downloads](https://img.shields.io/packagist/dt/danhunsaker/laravel-flysystem-service.svg?style=flat-square)](https://packagist.org/packages/danhunsaker/laravel-flysystem-service)

Registers recognized Flysystem adapters with Laravel automatically.

This lets you use other adapters without having to write your own service
providers to load them properly.  It automatically detects which adapters are
available, and registers only the ones actually installed.

> Note: This package only recognizes the adapters officially supported by
> [The PHP League][] - for other adapters, install
> [danhunsaker/laravel-flysystem-others][] instead (it will pull in this package
> as a dependency).

## Installation ##

The usual methods for using [Composer][] apply here:

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

    php artisan vendor:publish --provider=Danhunsaker\\Laravel\\Flysystem\\FlysystemServiceProvider --force

The `--force` flag is required to overwrite the existing `filesystems` config
that ships with Laravel.  You can also rename the existing file, then run the
command without the `--force` flag, if you'd like to preserve the existing
contents for transfer to the new file.

## Contributions ##

Pull requests, bug reports, and so forth are all welcome on [GitHub][].

Security issues should be reported directly to [hennikhunsaker (plus) laraflyserv
(at) gmail (dot) com](mailto:hennikhunsaker+laraflyserv@gmail.com).

And head to [GitHub][] for everything else.

[composer]: https://getcomposer.org
[danhunsaker/laravel-flysystem-others]: https://github.com/danhunsaker/laravel-flysystem-others
[github]: https://github.com/danhunsaker/laravel-flysystem-service
[the php league]: https://github.com/thephpleague?query=flysystem
