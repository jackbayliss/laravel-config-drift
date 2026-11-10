<?php

namespace JackBayliss\LaravelConfigOutdated\Tests;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use JackBayliss\LaravelConfigOutdated\LaravelConfigOutdatedServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'config-outdated-'.Str::random(8);

        (new Filesystem)->ensureDirectoryExists($this->root.DIRECTORY_SEPARATOR.'config');

        $this->app->useConfigPath($this->root.DIRECTORY_SEPARATOR.'config');

        $this->flushPublishables();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->root);

        $this->flushPublishables();

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelConfigOutdatedServiceProvider::class,
        ];
    }

    protected function publishes(array $paths, $group = 'config')
    {
        $provider = new class($this->app) extends ServiceProvider
        {
            public function register($paths = [], $group = null)
            {
                $this->publishes($paths, $group);
            }
        };

        $provider->register($paths, $group);
    }

    protected function writeConfig($path, array $config)
    {
        $path = $this->root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

        (new Filesystem)->ensureDirectoryExists(dirname($path));

        file_put_contents($path, '<?php return '.var_export($config, true).';');

        return $path;
    }

    protected function flushPublishables()
    {
        Closure::bind(function () {
            static::$publishes = [];
            static::$publishGroups = [];
        }, null, ServiceProvider::class)();
    }
}
