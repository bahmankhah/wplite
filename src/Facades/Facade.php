<?php
namespace WPLite\Facades;

use WPLite\Container;
use RuntimeException;

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
