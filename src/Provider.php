<?php

namespace WPLite;

use WPLite\Contracts\ServiceProvider;

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
