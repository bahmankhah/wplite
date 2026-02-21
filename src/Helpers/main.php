<?php
/**
 * Global Helper Functions — convenience functions available everywhere.
 *
 * Role: Provides framework-wide helper functions that are autoloaded via
 *       Composer's "files" autoload or included by the autoloader.
 *
 * Key functions:
 *   - appConfig($key, $default) — Dot-notation access to configs/*.php values.
 *   - appLogger($message) — Write to the plugin's log file.
 *   - load_env_file($path) — Parse .env files into environment variables.
 *   - view($path, $data) — Shortcut for View::render().
 *   - reverse($routeName, $params) — Generate URL for a named route.
 *   - replacePlaceholders($template, $vars) — String template substitution.
 *   - getClientIp() — Get client IP with CDN/proxy support.
 *
 * Avoid:
 *   - Do not add application-specific helpers here; this is framework code.
 *   - In consumer plugins, create your own helpers file instead.
 */

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
if(!function_exists('reverse')){
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


if (!function_exists('getClientIp')) {
    /**
     * Get client IP address with proxy/CDN support
     * Supports Cloudflare, Arvan, load balancers, and standard proxies
     */
    function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'HTTP_X_REAL_IP',            // Nginx reverse proxy
            'HTTP_AR_REAL_IP',           // Arvan CDN
            'X_REAL_IP',                 // Nginx reverse proxy
            'AR_REAL_IP',                // Arvan CDN
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
