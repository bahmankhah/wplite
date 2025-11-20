<?php

namespace WPLite;

class Config{

    public static function load(){
        $files = glob(__DIR__ . '/../src/configs/*.php');
        $configs = [];
        foreach ($files as $file) {
            $configs[basename($file, '.php')] = require_once($file);
        }
        $GLOBALS['donapp_configs'] = $configs;
    }
    
    public static function get($configName, $default = null){
        return $GLOBALS['donapp_configs'][$configName] ?? $default;
    }
    
}