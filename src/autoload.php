<?php

use WPLite\Facades\Config;
use WPLite\ProviderManager;

spl_autoload_register(function ($class) {
    $prefix = 'WPLite\\';
    $base_dir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

foreach (glob(__DIR__ . '/Helpers/*.php') as $file) {
    require_once $file;
}
load_env_file( __DIR__ . '/../.env' );

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});


Config::load();

ProviderManager::loadProviders();