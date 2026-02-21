<?php

namespace WPLite\Facades;

use WPLite\Application;

/**
 * App facade — static access to the Application container.
 *
 * @method static object make(string $class, array $params = []) Resolve a class with DI
 * @method static void bind(string $name, callable $resolver) Bind a service factory
 * @method static mixed resolve(string $name) Resolve a bound service
 * @method static bool has(string $name) Check if a service is bound
 * @method static void setPluginFile(string $file) Set the main plugin file path
 * @method static void setPluginPath(string $path) Set the plugin directory path
 * @method static string pluginPath() Get the plugin directory path
 * @method static string pluginFile() Get the main plugin file path
 * @method static void boot() Boot the framework
 * @method static void setRequest(mixed $request) Store the current request
 * @method static mixed request() Get the current request
 *
 * @see \WPLite\Application
 */
class App extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Application::class;
    }
}