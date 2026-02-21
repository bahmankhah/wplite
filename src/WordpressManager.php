<?php

namespace WPLite;

/**
 * WordpressManager — thin wrapper for common WordPress hook functions.
 *
 * Role: Provides a clean OOP interface for registering WordPress
 *       actions, filters, and shortcodes via the Wordpress facade.
 *
 * Responsibilities:
 *   - Register actions via add_action().
 *   - Register filters via add_filter().
 *   - Register shortcodes via add_shortcode().
 *
 * How to use:
 *   - Wordpress::action('wp_enqueue_scripts', [$this, 'enqueue']);
 *   - Wordpress::filter('the_content', [$this, 'filter']);
 *   - Wordpress::shortcode('my_tag', [$this, 'render']);
 *
 * @see \WPLite\Facades\Wordpress  Facade for this class.
 */
class WordpressManager{
    public static function shortcode($name, $callback){
        add_shortcode($name, $callback);
    }
    public static function action($name, $callback, int $priority = 10, int $accepted_args = 1){
        add_action($name, $callback, $priority, $accepted_args);
    }
    public static function filter($name, $callback, int $priority = 10, int $accepted_args = 1){
        add_filter($name, $callback, $priority, $accepted_args);
    }
}