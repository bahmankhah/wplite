<?php

namespace WPLite\Facades;

use WPLite\Container;
use RuntimeException;

/**
 * Facade — abstract base class for all facades.
 *
 * Role: Provides static-like access to services resolved from the container.
 *       Each concrete facade returns a class name from getFacadeAccessor(),
 *       which is resolved (or instantiated) and cached.
 *
 * Responsibilities:
 *   - Define the getFacadeAccessor() contract for subclasses.
 *   - Resolve and cache the underlying service instance.
 *   - Proxy static method calls to the resolved instance.
 *
 * How to use:
 *   - Extend Facade and implement getFacadeAccessor() returning a class FQCN.
 *   - Call static methods on your facade: MyFacade::someMethod().
 *
 * Resolution order:
 *   1. Check resolved instance cache.
 *   2. Check Container::has() and resolve from container.
 *   3. Instantiate the class directly (new $name()).
 *
 * Avoid:
 *   - Do not store mutable state on facades.
 *   - Do not call getFacadeRoot() directly; use the static proxy.
 *
 * @see \WPLite\Container  Service storage backend.
 */
abstract class Facade
{

    abstract protected static function getFacadeAccessor();

    protected static $resolvedInstance;

    protected static $container;


    protected static function resolveFacadeInstance($name)
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }
        if(Container::has($name)){
            return static::$resolvedInstance[$name] = Container::resolve($name);
        }else{
            // $class = static::class;
            return static::$resolvedInstance[$name] = new $name();
        }
        
    }
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }
    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();
        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}
