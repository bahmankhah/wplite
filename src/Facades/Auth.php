<?php

namespace WPLite\Facades;

use WPLite\Application;
use WPLite\Auth\AuthManager;

/**
 * Auth facade — static access to the authentication manager.
 *
 * @method static bool check() Check if user is authenticated
 * @method static mixed user() Get the authenticated user
 * @method static void login(mixed $user) Log a user in
 * @method static void logout() Log the current user out
 * @method static mixed attempt(array $credentials) Attempt authentication
 *
 * @see \WPLite\Auth\AuthManager (resolved via adapter pattern)
 */
class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AuthManager::class;
    }
}