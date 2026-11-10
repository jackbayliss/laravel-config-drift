<?php

namespace JackBayliss\LaravelConfigOutdated\Tests;

use Illuminate\Console\Command;

class LaravelConfigOutdatedCommandTest extends TestCase
{
    public function test_it_displays_when_no_configuration_files_are_published()
    {
        $this->publishes([
            $this->writeConfig('vendor/acme/config/acme.php', ['driver' => 'redis']) => $this->app->configPath('acme.php'),
        ]);

        $this->artisan('config:outdated')
            ->expectsOutputToContain('No published configuration files were found.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_displays_when_everything_is_up_to_date()
    {
        $this->publishPackageConfig(['driver' => 'redis'], ['driver' => 'sqs']);

        $this->artisan('config:outdated')
            ->expectsOutputToContain('All published configuration files are up to date.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_displays_missing_and_unknown_options()
    {
        $this->publishPackageConfig(
            ['driver' => 'redis', 'retry' => ['times' => 3, 'backoff' => 5]],
            ['driver' => 'redis', 'retry' => ['times' => 3], 'legacy' => true],
        );

        $this->artisan('config:outdated')
            ->expectsOutputToContain('1 missing, 1 unknown')
            ->expectsOutputToContain('acme.retry.backoff')
            ->expectsOutputToContain('acme.legacy')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_can_fail_when_configuration_is_outdated()
    {
        $this->publishPackageConfig(['driver' => 'redis', 'prefix' => 'acme'], ['driver' => 'redis']);

        $this->artisan('config:outdated')->assertExitCode(Command::SUCCESS);
        $this->artisan('config:outdated', ['--fail' => true])->assertExitCode(Command::FAILURE);
    }

    public function test_it_does_not_fail_when_configuration_is_up_to_date()
    {
        $this->publishPackageConfig(['driver' => 'redis'], ['driver' => 'redis']);

        $this->artisan('config:outdated', ['--fail' => true])->assertExitCode(Command::SUCCESS);
    }

    public function test_it_ignores_published_files_outside_of_the_config_directory()
    {
        $this->publishes([
            $this->writeConfig('vendor/acme/database/migration.php', ['up' => true]) => $this->writeConfig('database/migration.php', []),
        ], 'migrations');

        $this->artisan('config:outdated')
            ->expectsOutputToContain('No published configuration files were found.')
            ->assertExitCode(Command::SUCCESS);
    }

    protected function publishPackageConfig(array $original, array $published, $name = 'acme')
    {
        $this->publishes([
            $this->writeConfig("vendor/{$name}/config/{$name}.php", $original) => $this->writeConfig("config/{$name}.php", $published),
        ]);
    }
}
