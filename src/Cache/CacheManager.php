<?php

namespace WPLite\Cache;

use WPLite\Adapters\AdapterManager;

/**
 * CacheManager — adapter manager for cache drivers.
 *
 * Role: Resolves the active cache driver from configs/adapters.php
 *       and proxies cache operations to it.
 *
 * How to use:
 *   - Via the Cache facade: Cache::get('key'), Cache::set('key', $val, 3600).
 *   - Switch drivers: Cache::use('redis')->get('key').
 *
 * @see \WPLite\Adapters\AdapterManager     Parent class.
 * @see \WPLite\Contracts\Cache\CacheDriver  Driver interface.
 * @see \WPLite\Facades\Cache                Facade for this class.
 */
class CacheManager extends AdapterManager
{

    public function getKey(): string{
        return 'cache';
    }

}
