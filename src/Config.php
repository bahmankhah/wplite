<?php

namespace WPLite;

use WPLite\Facades\App;

/**
 * Config — loads and provides dot-notation access to configuration files.
 *
 * Role: Read all PHP files from the plugin's configs/ directory and store
 *       them in a global array keyed by filename (without extension).
 *
 * Responsibilities:
 *   - Scan configs/*.php and require each file.
 *   - Provide static get() for retrieving config values.
 *   - Store configs in $GLOBALS scoped by plugin path hash to avoid collisions.
 *
 * How to use:
 *   - Prefer the appConfig() helper for dot-notation access:
 *     appConfig('app.api.namespace', 'default')
 *   - Or use Config::get('app') to get an entire config file array.
 *
 * Avoid:
 *   - Do not call Config::load() manually; Application::boot() handles it.
 *
 * @see \WPLite\Helpers\main.php  appConfig() helper function.
 */
class Config{

    public static function load(){
        $files = glob(App::pluginPath() . 'configs/*.php');
        $configs = [];
        foreach ($files as $file) {
            $configs[basename($file, '.php')] = require_once($file);
        }
        $GLOBALS[md5(App::pluginPath()).'_wplite_configs'] = $configs;
    }
    
    public static function get($configName, $default = null){
        return $GLOBALS['wplite_configs'][$configName] ?? $default;
    }
    
}