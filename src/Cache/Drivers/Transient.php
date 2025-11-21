<?php

namespace WPLite\Cache\Drivers;

use WPLite\Adapters\Adapter;
use WPLite\Contracts\Cache\CacheDriver;

class Transient extends Adapter implements CacheDriver
{
    public function get(string $key)
    {
        return get_transient($key);
    }

    public function set(string $key, $value, int $seconds = 0)
    {
        return set_transient($key, $value, $seconds);
    }

    public function delete(string $key)
    {
        return delete_transient($key);
    }

    public function clear()
    {
        global $wpdb;
        return $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
    }
}
