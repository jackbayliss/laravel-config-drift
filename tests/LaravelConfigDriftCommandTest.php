<?php

namespace JackBayliss\LaravelConfigDrift\Tests;

use Illuminate\Console\Command;
use Illuminate\Foundation\Console\ConfigPublishCommand;
use ReflectionClass;

class LaravelConfigDriftCommandTest extends TestCase
{
    public function test_it_displays_when_no_configuration_files_are_published()
    {
        $this->packageConfig('acme', ['driver' => 'redis']);

        $this->artisan('config:outdated')
            ->expectsOutputToContain('No published configuration files were found.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_displays_when_everything_is_up_to_date()
    {
        $this->packageConfig('acme', ['driver' => 'redis']);
        $this->publishedConfig('acme', ['driver' => 'redis']);

        $this->artisan('config:outdated')
            ->expectsOutputToContain('All published configuration files are up to date.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_displays_missing_and_unknown_options()
    {
        $this->packageConfig('acme', ['driver' => 'redis', 'new_option' => true]);
        $this->publishedConfig('acme', ['driver' => 'redis', 'old_option' => true]);

        $this->artisan('config:outdated')
            ->expectsOutputToContain('1 missing, 1 unknown')
            ->expectsOutputToContain('acme.new_option')
            ->expectsOutputToContain('acme.old_option')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_can_fail_when_configuration_is_outdated()
    {
        $this->packageConfig('acme', ['driver' => 'redis', 'new_option' => true]);
        $this->publishedConfig('acme', ['driver' => 'redis']);

        $this->artisan('config:outdated')->assertExitCode(Command::SUCCESS);
        $this->artisan('config:outdated', ['--fail' => true])->assertExitCode(Command::FAILURE);
    }

    public function test_it_does_not_fail_when_configuration_is_up_to_date()
    {
        $this->packageConfig('acme', ['driver' => 'redis']);
        $this->publishedConfig('acme', ['driver' => 'redis']);

        $this->artisan('config:outdated', ['--fail' => true])->assertExitCode(Command::SUCCESS);
    }

    public function test_it_outputs_json()
    {
        $this->packageConfig('outdated', ['driver' => 'redis', 'new_option' => true]);
        $this->publishedConfig('outdated', ['driver' => 'redis', 'old_option' => true]);

        $this->packageConfig('current', ['driver' => 'redis']);
        $this->publishedConfig('current', ['driver' => 'redis']);

        $root = str_replace('\\', '/', $this->root);

        $this->artisan('config:outdated', ['--json' => true])
            ->expectsOutput(json_encode([[
                'file' => $root.'/config/outdated.php',
                'source' => $root.'/vendor/outdated/config/outdated.php',
                'missing' => ['outdated.new_option'],
                'unknown' => ['outdated.old_option'],
            ]]))
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_can_check_a_single_file()
    {
        $this->packageConfig('acme', ['driver' => 'redis', 'new_option' => true]);
        $this->publishedConfig('acme', ['driver' => 'redis']);

        $this->packageConfig('other', ['driver' => 'redis', 'new_option' => true]);
        $this->publishedConfig('other', ['driver' => 'redis']);

        $this->artisan('config:outdated', ['file' => 'other'])
            ->expectsOutputToContain('other.new_option')
            ->doesntExpectOutputToContain('acme.new_option')
            ->assertExitCode(Command::SUCCESS);

        $this->artisan('config:outdated', ['file' => 'acme.php'])
            ->expectsOutputToContain('acme.new_option')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_errors_when_checking_an_unpublished_file()
    {
        $this->packageConfig('acme', ['driver' => 'redis']);
        $this->publishedConfig('acme', ['driver' => 'redis']);

        $this->artisan('config:outdated', ['file' => 'horizon'])
            ->expectsOutputToContain('Unable to find a published [horizon] configuration file.')
            ->assertExitCode(Command::FAILURE);
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

    public function test_it_checks_published_configuration_directories()
    {
        $this->writeConfig('vendor/acme/config/acme/queue.php', ['driver' => 'redis', 'new_option' => true]);
        $this->writeConfig('config/acme/queue.php', ['driver' => 'redis']);

        $this->publishes([
            $this->root.'/vendor/acme/config' => $this->app->configPath(),
        ]);

        $this->artisan('config:outdated')
            ->expectsOutputToContain('acme.queue.new_option')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_checks_published_framework_configuration_files()
    {
        $cache = $this->frameworkConfig('cache');

        unset($cache['stores']['database']['lock_table']);
        $cache['old_option'] = true;

        $this->publishedConfig('cache', $cache);

        $this->artisan('config:outdated', ['--json' => true])
            ->expectsOutputToContain('"missing":["cache.stores.database.lock_table"],"unknown":["cache.old_option"]')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_ignores_missing_options_the_framework_merges_at_runtime()
    {
        $cache = $this->frameworkConfig('cache');

        unset($cache['prefix']);

        $this->publishedConfig('cache', $cache);

        $this->artisan('config:outdated')
            ->expectsOutputToContain('All published configuration files are up to date.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_reports_missing_framework_options_when_the_framework_configuration_is_not_merged()
    {
        $this->app->dontMergeFrameworkConfiguration();

        $cache = $this->frameworkConfig('cache');

        unset($cache['prefix']);

        $this->publishedConfig('cache', $cache);

        $this->artisan('config:outdated', ['--json' => true])
            ->expectsOutputToContain('"missing":["cache.prefix"]')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_reports_missing_package_options_at_the_top_level()
    {
        $this->packageConfig('acme', ['driver' => 'redis', 'prefix' => 'acme']);
        $this->publishedConfig('acme', ['driver' => 'redis']);

        $this->artisan('config:outdated', ['--json' => true])
            ->expectsOutputToContain('"missing":["acme.prefix"]')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_it_does_not_check_the_framework_services_file()
    {
        $this->publishedConfig('services', ['stripe' => ['key' => 'pk_test']]);

        $this->artisan('config:outdated')
            ->expectsOutputToContain('No published configuration files were found.')
            ->assertExitCode(Command::SUCCESS);
    }

    protected function packageConfig($name, array $config)
    {
        $this->publishes([
            $this->writeConfig("vendor/{$name}/config/{$name}.php", $config) => $this->app->configPath("{$name}.php"),
        ]);
    }

    protected function publishedConfig($name, array $config)
    {
        $this->writeConfig("config/{$name}.php", $config);
    }

    protected function frameworkConfig($name)
    {
        return require dirname((new ReflectionClass(ConfigPublishCommand::class))->getFileName(), 5)."/config/{$name}.php";
    }
}
