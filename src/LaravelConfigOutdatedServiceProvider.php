<?php

namespace JackBayliss\LaravelConfigOutdated;

use Illuminate\Support\ServiceProvider;
use JackBayliss\LaravelConfigOutdated\Commands\LaravelConfigOutdatedCommand;

class LaravelConfigOutdatedServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LaravelConfigOutdatedCommand::class,
            ]);
        }
    }
}
