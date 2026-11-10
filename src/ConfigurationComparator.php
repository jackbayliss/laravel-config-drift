<?php

namespace JackBayliss\LaravelConfigDrift;

use Illuminate\Support\Collection;

class ConfigurationComparator
{
    /**
     * Compare the keys of the published configuration against the original.
     *
     * @param  string|null  $prefix
     * @return array
     */
    public function compare(array $original, array $published, $prefix = null)
    {
        $missing = $unknown = [];

        $isCollection = ! is_null($prefix) && $this->isCollection($original);

        foreach ($original as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (! array_key_exists($key, $published)) {
                if (! $isCollection) {
                    $missing[] = $this->key($prefix, $key);
                }

                continue;
            }

            if ($this->shouldCompare($value, $published[$key])) {
                $result = $this->compare($value, $published[$key], $this->key($prefix, $key));

                array_push($missing, ...$result['missing']);
                array_push($unknown, ...$result['unknown']);
            }
        }

        if (! $isCollection) {
            foreach (array_keys($published) as $key) {
                if (is_string($key) && ! array_key_exists($key, $original)) {
                    $unknown[] = $this->key($prefix, $key);
                }
            }
        }

        return compact('missing', 'unknown');
    }

    /**
     * Determine if the given values should be compared.
     *
     * @param  mixed  $original
     * @param  mixed  $published
     * @return bool
     */
    protected function shouldCompare($original, $published)
    {
        return is_array($original)
            && is_array($published)
            && $original !== []
            && ! array_is_list($original);
    }

    /**
     * Determine if the given configuration is a collection of named entries.
     *
     * @return bool
     */
    protected function isCollection(array $config)
    {
        return (new Collection($config))->every(fn ($value) => is_array($value));
    }

    /**
     * Get the dot notation key for the given prefix and key.
     *
     * @param  string|null  $prefix
     * @param  string  $key
     * @return string
     */
    protected function key($prefix, $key)
    {
        return is_null($prefix) ? $key : $prefix.'.'.$key;
    }
}
