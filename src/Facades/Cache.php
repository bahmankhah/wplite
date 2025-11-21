<?php

namespace WPLite\Facades;

use WPLite\Cache\CacheManager;


/**
 * @method static \WPLite\Application make($class, array $params = [])
 * @see \WPLite\Application
**/
class Cache extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CacheManager::class;
    }
}