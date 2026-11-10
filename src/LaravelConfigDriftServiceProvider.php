<?php

namespace JackBayliss\LaravelConfigDrift;

use Illuminate\Support\ServiceProvider;
use JackBayliss\LaravelConfigDrift\Commands\LaravelConfigDriftCommand;

class LaravelConfigDriftServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LaravelConfigDriftCommand::class,
            ]);
        }
    }
}
