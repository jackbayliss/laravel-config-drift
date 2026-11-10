<?php

namespace JackBayliss\LaravelConfigOutdated\Tests;

use JackBayliss\LaravelConfigOutdated\ConfigurationComparator;
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

    protected function compare(array $original, array $published)
    {
        return (new ConfigurationComparator)->compare($original, $published);
    }
}
