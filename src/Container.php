<?php

namespace WPLite;

/**
 * Service Container — the foundation of the WPLite framework.
 *
 * Role: Stores and retrieves service instances (factories) by name.
 *
 * Responsibilities:
 *   - Bind named services as factory closures.
 *   - Resolve services by invoking their factory.
 *   - Check whether a service has been registered.
 *
 * How to use:
 *   - Call Container::bind('name', fn() => new Service()) to register.
 *   - Call Container::resolve('name') to retrieve.
 *   - Prefer using the App facade (App::make()) for auto-resolving DI.
 *
 * Avoid:
 *   - Do not use Container directly when a Facade exists for the service.
 *   - Do not store state other than service factories here.
 *
 * @see \WPLite\Application  Extended container with auto-resolving DI.
 * @see \WPLite\Facades\App   Facade for convenient access.
 */
class Container {
    private static $instances = [];

    public static function bind($name, $resolver) {
        self::$instances[$name] = $resolver;
    }

    public static function resolve($name) {
        if (isset(self::$instances[$name])) {
            return call_user_func(self::$instances[$name]);
        }
        throw new \Exception("Service {$name} not found in container");
    }

    public static function has($name) {
        return isset(self::$instances[$name]);
    }
}
