<?php

namespace WPLite\Facades;

use WPLite\Cache\CacheManager;

/**
 * Cache facade — static access to the cache manager.
 *
 * @method static mixed get(string $key) Retrieve a cached value
 * @method static bool set(string $key, mixed $value, int $seconds = 0) Store a value
 * @method static bool delete(string $key) Remove a cached value
 * @method static void clear() Clear all cache
 * @method static mixed use(string $driver) Switch to a named cache driver
 *
 * @see \WPLite\Cache\CacheManager
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CacheManager::class;
    }
}