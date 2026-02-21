<?php

namespace WPLite\Facades;

/**
 * Wordpress facade — static access to the WordpressManager.
 *
 * @method static void shortcode(string $name, callable $callback) Register a shortcode
 * @method static void action(string $name, callable $callback, int $priority = 10, int $accepted_args = 1) Register an action
 * @method static void filter(string $name, callable $callback, int $priority = 10, int $accepted_args = 1) Register a filter
 *
 * @see \WPLite\WordpressManager
 */
class Wordpress extends Facade{

    protected static function getFacadeAccessor() {
        return \WPLite\WordpressManager::class;
    }
}