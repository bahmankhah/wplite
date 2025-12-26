<?php

use WPLite\Facades\App;
use WPLite\Facades\Route;
use WPLite\Facades\View;

if (!function_exists('appConfig')) {
    function appConfig($key = null, $default = null)
    {
        $configsName = (md5(App::pluginPath())) . '_wplite_configs';
        global $$configsName;
        if ($key === null) {
            return $$configsName;
        }

        $keys = explode('.', $key);
        $value = $$configsName;

        foreach ($keys as $keyPart) {
            if (is_array($value) && array_key_exists($keyPart, $value)) {
                $value = $value[$keyPart];
            } else {
                return $default; 
            }
        }
        return $value;
    }
}

if (!function_exists('appLogger')) {
    function appLogger($message)
    {
        $message = (string) $message;
        // $plugin_dir = WP_PLUGIN_DIR . '/' . appConfig('app.name');
        $plugin_dir = App::pluginPath();
        $log_file = $plugin_dir . 'logs/wplite-errors.log';
        // Ensure the directory exists
        $directory = dirname($log_file);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true); // Create the directory with permissions
        }

        $time = date('Y-m-d H:i:s');
        $formatted_message = "[{$time}] {$message}". PHP_EOL;

        // Write to the log file.
        file_put_contents($log_file, $formatted_message, FILE_APPEND);
    }
}

if (!function_exists('load_env_file')) {
    // Load .env file manually
    function load_env_file($file_path)
    {
        if (file_exists($file_path)) {
            $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                // Ignore comments (lines starting with #)
                if (strpos($line, '#') === 0) {
                    continue;
                }

                // Split the line into key and value
                $parts = explode('=', $line, 2);

                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);

                    // Set the environment variable
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                }
            }
        } else {
            appLogger("No .env file found at {$file_path}");
        }
    }
}

if (!function_exists('replacePlaceholders')) {
    function replacePlaceholders(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Replace placeholders in the format {key} with their corresponding values
            $template = str_replace("{" . $key . "}", $value, $template);
        }
        return $template;
    }
}
if(!function_exists('view')){
    function view($path, $data = []){
        return View::render($path, $data);
    }
}
if(!function_exists(('reverse'))){
    function reverse($routeName, $params = [], $prefix = null){
        /**
         * @var WPLite\RouteDefinition $routeDef
         */
        $routeDef = Route::getName($routeName);
        if(!$routeDef){
            throw new \Exception("Route with name {$routeName} not found");
        }
        $prefix = appConfig('app.api.namespace', 'dnp/v1');
        $url = trim($routeDef->buildRoute($params), '/');
        if($prefix === null){
            return rest_url(trim($prefix . '/' . $url, '/'));
        }
        return $prefix. trim($prefix . '/' . $url, '/');
    }
}

