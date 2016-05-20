<?php

namespace Danhunsaker\Laravel\Flysystem;

use Danhunsaker\Laravel\Flysystem\FlysystemManager;
use Illuminate\Filesystem\FilesystemServiceProvider;

class FlysystemServiceProvider extends FilesystemServiceProvider
{
    protected static $fsClass;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the filesystem manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('filesystem', function () {
            return new FlysystemManager($this->app);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $source = realpath(__DIR__ . '../config/filesystems.php');

        $this->publishes([$source => config_path('filesystems.php')]);

        parent::register();
    }
}
