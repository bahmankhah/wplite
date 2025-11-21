<?php

namespace WPLite;

use WPLite\Facades\App;

class Config{

    public static function load(){
        $files = glob(App::pluginPath() . 'configs/*.php');
        $configs = [];
        foreach ($files as $file) {
            $configs[basename($file, '.php')] = require_once($file);
        }
        $GLOBALS['wplite_configs'] = $configs;
    }
    
    public static function get($configName, $default = null){
        return $GLOBALS['wplite_configs'][$configName] ?? $default;
    }
    
}