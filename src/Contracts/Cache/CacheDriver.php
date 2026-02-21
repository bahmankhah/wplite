<?php

namespace WPLite\Contracts\Cache;

/**
 * CacheDriver contract — defines the interface for cache driver implementations.
 *
 * Role: Any cache driver (Transients, Redis, file-based, etc.) must implement
 *       these four methods.
 *
 * How to use:
 *   - Implement this interface + extend Adapter for new cache drivers.
 *   - Register in configs/adapters.php under the 'cache' key.
 *
 * @see \WPLite\Cache\Drivers\Transient  Built-in WP Transients driver.
 * @see \WPLite\Cache\CacheManager        Resolves cache drivers.
 */
interface CacheDriver
{
    public function get(string $key);
    public function set(string $key, $value, int $seconds = 0);
    public function delete(string $key);
    public function clear();
}