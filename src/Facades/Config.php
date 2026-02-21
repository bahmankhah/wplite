<?php

namespace WPLite\Facades;

/**
 * Config facade — static access to the Config manager.
 *
 * @method static void load() Load all config files from configs/
 * @method static mixed get(string $key, mixed $default = null) Get a config value
 *
 * Note: Prefer the appConfig() helper for dot-notation access.
 *
 * @see \WPLite\Config
 */
class Config extends Facade{

    protected static function getFacadeAccessor() {
        return \WPLite\Config::class;
    }
}