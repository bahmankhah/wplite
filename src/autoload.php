<?php
/**
 * Standalone Autoloader — PSR-4 autoloading without Composer.
 *
 * Role: Provides autoloading for the WPLite\ namespace and consumer
 *       App\ namespace when Composer is not available. Also loads
 *       helper files and bootstraps Config + ProviderManager.
 *
 * Note: When using Composer (recommended), this file is NOT needed.
 *       Composer's autoloader handles class loading, and the helper
 *       files are loaded via composer.json "files" autoload.
 *       Application::boot() handles Config and ProviderManager loading.
 *
 * @see composer.json  Preferred autoloading configuration.
 */

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