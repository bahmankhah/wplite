<?php

namespace WPLite\Providers;
use WPLite\Facades\Route;
use WPLite\Provider;

class RouteServiceProvider extends Provider
{
    public function boot()
    {
        // load frontend routes
        Route::loadRoutesFile(WPLITE_PATH . 'routes/web.php');

        // load admin routes
        if (is_admin()) {
            Route::loadRoutesFile(WPLITE_PATH . 'routes/admin.php');
        }

        // load ajax routes
        if (wp_doing_ajax()) {
            Route::loadRoutesFile(WPLITE_PATH . 'routes/ajax.php');
        }

        // load REST routes
        add_action('rest_api_init', function () {
            Route::loadRoutesFile(WPLITE_PATH . 'routes/rest.php');
        });
    }
}
