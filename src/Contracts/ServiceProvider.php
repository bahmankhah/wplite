<?php

namespace WPLite\Contracts;

/**
 * ServiceProvider contract — defines the lifecycle hooks for providers.
 *
 * Role: The interface that all service providers must implement. Each method
 *       corresponds to a specific point in the WordPress plugin lifecycle.
 *
 * @see \WPLite\Provider         Abstract base that implements this with empty defaults.
 * @see \WPLite\ProviderManager   Wires these hooks to WordPress actions.
 */
interface ServiceProvider
{
    public function register();      // bind services, configs
    public function bootEarly();     // before WP init hook
    public function onInit();        // inside init hook
    public function boot();          // after full WP loaded

    public function admin();         // wp-admin only
    public function ajax();          // wp-ajax only
    public function rest();          // wp rest routes

    public function activate();      // plugin activation
    public function deactivate();    // plugin deactivation
    public function uninstall();     // plugin uninstall
}
