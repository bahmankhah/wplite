<?php

namespace WPLite\Cache\Drivers;

use WPLite\Adapters\Adapter;
use WPLite\Contracts\Cache\CacheDriver;

/**
 * Transient — cache driver using WordPress Transients API.
 *
 * Role: Stores cache values using set_transient/get_transient.
 *       This is the default cache driver shipped with WPLite.
 *
 * @see \WPLite\Contracts\Cache\CacheDriver  Interface this implements.
 * @see \WPLite\Cache\CacheManager            Manager that resolves this driver.
 */
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
