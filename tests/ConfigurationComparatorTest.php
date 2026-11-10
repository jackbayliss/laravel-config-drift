<?php

namespace JackBayliss\LaravelConfigDrift\Tests;

use JackBayliss\LaravelConfigDrift\ConfigurationComparator;
use PHPUnit\Framework\TestCase as BaseTestCase;

class ConfigurationComparatorTest extends BaseTestCase
{
    public function test_identical_configuration_has_no_differences()
    {
        $config = ['driver' => 'redis', 'options' => ['timeout' => 5]];

        $this->assertSame(['missing' => [], 'unknown' => []], $this->compare($config, $config));
    }

    public function test_values_are_never_compared()
    {
        $result = $this->compare(
            ['driver' => 'redis', 'options' => ['timeout' => 5]],
            ['driver' => 'sqs', 'options' => ['timeout' => 60]],
        );

        $this->assertSame(['missing' => [], 'unknown' => []], $result);
    }

    public function test_missing_keys_are_reported()
    {
        $result = $this->compare(
            ['driver' => 'redis', 'prefix' => 'app'],
            ['driver' => 'redis'],
        );

        $this->assertSame(['prefix'], $result['missing']);
    }

    public function test_unknown_keys_are_reported()
    {
        $result = $this->compare(
            ['driver' => 'redis'],
            ['driver' => 'redis', 'legacy' => true],
        );

        $this->assertSame(['legacy'], $result['unknown']);
    }

    public function test_nested_keys_are_reported_using_dot_notation()
    {
        $result = $this->compare(
            ['retry' => ['times' => 3, 'backoff' => [1, 5, 10]]],
            ['retry' => ['times' => 3, 'sleep' => 100]],
        );

        $this->assertSame(['retry.backoff'], $result['missing']);
        $this->assertSame(['retry.sleep'], $result['unknown']);
    }

    public function test_a_missing_group_is_reported_once()
    {
        $result = $this->compare(
            ['driver' => 'redis', 'retry' => ['times' => 3, 'sleep' => 100]],
            ['driver' => 'redis'],
        );

        $this->assertSame(['retry'], $result['missing']);
    }

    public function test_lists_are_treated_as_values()
    {
        $result = $this->compare(
            ['except' => ['telescope', 'horizon']],
            ['except' => ['pulse']],
        );

        $this->assertSame(['missing' => [], 'unknown' => []], $result);
    }

    public function test_empty_arrays_are_treated_as_values()
    {
        $result = $this->compare(
            ['headers' => []],
            ['headers' => ['X-Foo' => 'bar']],
        );

        $this->assertSame(['missing' => [], 'unknown' => []], $result);
    }

    public function test_only_entries_kept_by_the_user_are_compared_within_collections()
    {
        $result = $this->compare(
            ['connections' => [
                'mysql' => ['host' => 'localhost', 'port' => 3306, 'timezone' => null],
                'sqlite' => ['database' => 'database.sqlite'],
            ]],
            ['connections' => [
                'mysql' => ['host' => 'localhost', 'port' => 3306],
                'reporting' => ['host' => 'reporting.test'],
            ]],
        );

        $this->assertSame(['connections.mysql.timezone'], $result['missing']);
        $this->assertSame([], $result['unknown']);
    }

    public function test_the_root_of_a_file_is_never_treated_as_a_collection()
    {
        $result = $this->compare(
            ['routes' => ['prefix' => 'api'], 'cache' => ['ttl' => 60]],
            ['routes' => ['prefix' => 'api']],
        );

        $this->assertSame(['cache'], $result['missing']);
    }

    public function test_integer_keys_are_ignored()
    {
        $result = $this->compare(
            ['middleware' => ['web', 'throttle' => 'api']],
            ['middleware' => ['web', 'auth', 'throttle' => 'api']],
        );

        $this->assertSame(['missing' => [], 'unknown' => []], $result);
    }

    public function test_arrays_replaced_with_scalars_are_not_compared()
    {
        $result = $this->compare(
            ['retry' => ['times' => 3]],
            ['retry' => false],
        );

        $this->assertSame(['missing' => [], 'unknown' => []], $result);
    }

    protected function compare(array $original, array $published)
    {
        return (new ConfigurationComparator)->compare($original, $published);
    }
}
