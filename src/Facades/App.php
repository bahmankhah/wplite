<?php

namespace WPLite\Facades;

use WPLite\Application;

/**
 * @method static \WPLite\Application make($class, array $params = [])
 * @see \WPLite\Application
**/
class App extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Application::class;
    }
}