<?php

namespace WPLite\Contracts;

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
