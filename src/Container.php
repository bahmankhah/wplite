<?php
namespace WPLite;

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
