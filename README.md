## Laravel Flysystem Service

[![Total Downloads](https://poser.pugx.org/danhunsaker/laravel-flysystem-service/d/total.svg)](https://packagist.org/packages/danhunsaker/laravel-flysystem-service)
[![Latest Stable Version](https://poser.pugx.org/danhunsaker/laravel-flysystem-service/v/stable.svg)](https://packagist.org/packages/danhunsaker/laravel-flysystem-service)
[![Latest Unstable Version](https://poser.pugx.org/danhunsaker/laravel-flysystem-service/v/unstable.svg)](https://packagist.org/packages/danhunsaker/laravel-flysystem-service)
[![License](https://poser.pugx.org/danhunsaker/laravel-flysystem-service/license.svg)](https://packagist.org/packages/danhunsaker/laravel-flysystem-service)

Registers recognized Flysystem adapters with Laravel automatically.

This lets you use other adapters without having to write your own service providers to load them properly.  It automatically detects which adapters are available, and registers only the ones actually installed.  It also detects whether the [Eventable](https://github.com/thephpleague/flysystem-eventable-filesystem) version of Flysystem is available, and if so, it switches to it, letting you listen in on Flysystem [events](http://event.thephpleague.com/) and affect them accordingly.

> Note: This package only recognizes the adapters officially supported by [The PHP League](https://github.com/thephpleague?query=flysystem) (except AWS-S3-v2) - for other adapters, install [danhunsaker/laravel-flysystem-others](https://github.com/danhunsaker/laravel-flysystem-others) as well (or instead, since it actually depends on this one).

### Installation

The usual methods for using [Composer](https://getcomposer.org) apply here:

    composer require danhunsaker/laravel-flysystem-service

You do still have to register one service, but at least you don't have to write it:

```php
// In config/app.php

    'providers' => [
        // ...
        Danhunsaker\Laravel\Flysystem\FlysystemServiceProvider::class,
        // ...
    ],
```

### Issues, Contributions, Etc

GitHub is the best place to interact about this project.

Enjoy!