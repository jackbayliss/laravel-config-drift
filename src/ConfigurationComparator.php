<?php

namespace JackBayliss\LaravelConfigOutdated;

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

        foreach (array_keys($original) as $key) {
            if (is_string($key) && ! array_key_exists($key, $published)) {
                $missing[] = $this->key($prefix, $key);
            }
        }

        foreach (array_keys($published) as $key) {
            if (is_string($key) && ! array_key_exists($key, $original)) {
                $unknown[] = $this->key($prefix, $key);
            }
        }

        return compact('missing', 'unknown');
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
