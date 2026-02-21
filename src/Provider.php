<?php

namespace WPLite;

use WPLite\Contracts\ServiceProvider;

/**
 * Provider — abstract base class for service providers ("modules").
 *
 * Role: The primary organizational unit in WPLite. Each provider represents
 *       a feature module with lifecycle hooks mapped to WordPress actions.
 *
 * Responsibilities:
 *   - Provide empty default implementations for all ServiceProvider hooks.
 *   - Serve as the base class for all custom providers.
 *
 * How to use:
 *   - Extend this class and override only the hooks you need.
 *   - Register the provider in configs/app.php 'providers' array,
 *     or place it under the Src\Provider\ namespace for auto-discovery.
 *
 * Lifecycle hooks (in execution order):
 *   1. register()    — Bind services to the container (runs immediately).
 *   2. bootEarly()   — Pre-init setup (runs immediately after register).
 *   3. onInit()      — Runs on WordPress 'init' action.
 *   4. boot()        — Runs on WordPress 'wp_loaded' action.
 *   5. admin()       — Called always; add is_admin() checks if needed.
 *   6. ajax()        — Called only when wp_doing_ajax() is true.
 *   7. rest()        — Runs on 'rest_api_init' action.
 *   8. activate()    — Runs on plugin activation hook.
 *   9. deactivate()  — Runs on plugin deactivation hook.
 *  10. uninstall()   — Runs on plugin uninstall hook.
 *
 * Avoid:
 *   - Do not put heavy logic in register(); only bind services.
 *   - Do not call WordPress functions that require 'init' in register().
 *
 * @see \WPLite\Contracts\ServiceProvider  The interface this implements.
 * @see \WPLite\ProviderManager             Loads and wires providers.
 */
abstract class Provider implements ServiceProvider
{
    public function register() {}
    public function bootEarly() {}
    public function onInit() {}
    public function boot() {}
    public function admin() {}
    public function ajax() {}
    public function rest() {}
    public function activate() {}
    public function deactivate() {}
    public function uninstall() {}
}
