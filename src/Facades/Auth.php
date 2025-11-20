<?php

namespace WPLite\Facades;

use WPLite\Application;
use WPLite\Auth\AuthManager;

/**
 * @method static \WPLite\Application make($class, array $params = [])
 * @see \WPLite\Application
**/
class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AuthManager::class;
    }
}