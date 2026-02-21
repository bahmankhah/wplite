<?php

namespace WPLite\Providers;

use WPLite\Facades\App;
use WPLite\Facades\Route;
use WPLite\Provider;

/**
 * RouteServiceProvider — built-in provider that loads route definition files.
 *
 * Role: Loads routes/{admin,ajax,web,rest}.php during the onInit lifecycle
 *       hook so that all routes are registered with WordPress.
 *
 * This is always included as the first provider by ProviderManager.
 *
 * @see \WPLite\RouteManager   Router that processes the loaded route files.
 * @see \WPLite\Provider        Base provider class.
 */
class RouteServiceProvider extends Provider
{
    public function onInit()
    {
        if (is_admin()) {
            Route::loadRoutesFile(App::pluginPath() . 'routes/admin.php');
        }
        
        if (wp_doing_ajax()) {
            Route::loadRoutesFile(App::pluginPath() . 'routes/ajax.php');
        }
        Route::loadRoutesFile(App::pluginPath() . 'routes/web.php');

        Route::loadRoutesFile(App::pluginPath() . 'routes/rest.php');
    }
}
