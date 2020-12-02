<?php

namespace Danhunsaker\Laravel\Flysystem;

use Danhunsaker\Laravel\Flysystem\FlysystemManager;
use Illuminate\Filesystem\FilesystemServiceProvider;

class FlysystemServiceProvider extends FilesystemServiceProvider
{
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
     * Register the expanded configuration.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([realpath(__DIR__ . '/../config/filesystems.php') => config_path('filesystems.php')]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();

        parent::register();
    }
}
